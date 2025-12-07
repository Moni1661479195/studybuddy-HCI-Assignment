<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

// Load Gemini API Key securely
// Assuming config.php exists and defines GEMINI_API_KEY
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    // Fallback or error handling if config.php is missing
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error: API key not found.']);
    exit();
}

if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error: Gemini API Key is not set.']);
    exit();
}

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? (int)$_SESSION['user_id'] : null;
$db = get_db();

$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? '';
$history = $input['history'] ?? []; // Chat history from frontend

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Prompt cannot be empty.']);
    exit();
}

try {
    if (!$is_logged_in) {
        // Guest Mode Logic
        handle_guest_mode($prompt, $history);
    } else {
        // Assistant Mode Logic
        handle_assistant_mode($db, $current_user_id, $prompt, $history);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Gemini Chat API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
}

exit();

// --- Helper Functions ---

function handle_guest_mode(string $prompt, array $history): void {
    $response_text = '';
    // Define the buttons that should always be available in guest mode.
    $feature_buttons = [
        'Find a Study Partner',
        'Create a Study Plan',
        'Participate in Quizzes',
        'Join Study Groups'
    ];

    switch ($prompt) {
        case 'INIT_GREETING':
        case 'What can I do on this website?':
            $response_text = "Welcome to Study Buddy! I'm your AI assistant. I can help you navigate the site and find what you need. What would you like to explore?";
            break;
        case 'Find a Study Partner':
            $response_text = "Our 'Find a Study Partner' feature connects you with peers based on your interests and academic needs. You can set preferences for skill level, subjects, and availability to find your ideal study companion.\n\nFeel free to ask about another feature!";
            break;
        case 'Create a Study Plan':
            $response_text = "With 'Create a Study Plan', you can set academic goals, break them down into manageable tasks, and track your progress. It helps you stay organized and motivated towards achieving your learning objectives.\n\nFeel free to ask about another feature!";
            break;
        case 'Participate in Quizzes':
            $response_text = "Test your knowledge and reinforce your learning with our 'Participate in Quizzes' feature. We offer quizzes on various subjects and skill levels to help you identify strengths and weaknesses.\n\nFeel free to ask about another feature!";
            break;
        case 'Join Study Groups':
            $response_text = "Collaborate with others in 'Join Study Groups'. You can find groups focused on specific subjects, projects, or exam preparations. It's a great way to share knowledge and motivate each other.\n\nFeel free to ask about another feature!";
            break;
        default:
            // This case handles unrecognized input.
            $response_text = "I'm sorry, I can only provide information about the website's features. Please select one of the options below.";
            break;
    }

    // Always return the feature buttons so the user can continue exploring.
    echo json_encode(['success' => true, 'response' => $response_text, 'featureButtons' => $feature_buttons]);
}

function handle_assistant_mode(PDO $db, int $user_id, string $prompt, array $history): void {
    // Fetch current user's profile data
    $user_stmt = $db->prepare("SELECT first_name, last_name, major, skill_level FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_profile = $user_stmt->fetch(PDO::FETCH_ASSOC);

    $system_prompt_parts = [
        "You are a helpful and personalized study assistant for Study Buddy users. Your goal is to provide tailored advice and recommendations based on the user's profile and activity.",
        "The current user's name is " . htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']) . ".",
        "Their major is " . htmlspecialchars($user_profile['major'] ?? 'not specified') . " and their general skill level is " . htmlspecialchars($user_profile['skill_level'] ?? 'any') . "."
    ];

    // --- Intent Detection and Context Building ---
    $lower_prompt = strtolower($prompt);

    // 1. Academic Help / Study Plan
    if (str_contains($lower_prompt, 'improve') || str_contains($lower_prompt, 'weak') || str_contains($lower_prompt, 'exercises') || str_contains($lower_prompt, 'plan') || str_contains($lower_prompt, 'raise')) {
        $system_prompt_parts[] = "The user is asking for academic help or a study plan.";
        $quiz_stmt = $db->prepare("SELECT q.title, qa.score, qa.total_questions FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.user_id = ? ORDER BY qa.completed_at DESC LIMIT 3");
        $quiz_stmt->execute([$user_id]);
        $recent_quizzes = $quiz_stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($recent_quizzes)) {
            $system_prompt_parts[] = "Here are the user's 3 most recent quiz attempts:";
            foreach ($recent_quizzes as $quiz) {
                $system_prompt_parts[] = "- Quiz: " . htmlspecialchars($quiz['title']) . ", Score: " . htmlspecialchars($quiz['score']) . "/" . htmlspecialchars($quiz['total_questions']);
            }
            $system_prompt_parts[] = "Use this information to provide personalized advice on how they can improve, suggest exercises, or help them create a study plan.";
        } else {
            $system_prompt_parts[] = "The user has no recent quiz attempts. Ask them about their current study challenges or subjects they find difficult.";
        }
    }
    // 2. Partner Matching
    else if (str_contains($lower_prompt, 'partner') || str_contains($lower_prompt, 'match') || str_contains($lower_prompt, 'group')) {
        $system_prompt_parts[] = "The user is asking for help finding a study partner or group. You should recommend a few suitable users based on their profile and interests.";
        $interest_stmt = $db->prepare("SELECT i.name FROM user_interests ui JOIN interests i ON ui.interest_id = i.id WHERE ui.user_id = ?");
        $interest_stmt->execute([$user_id]);
        $user_interests = $interest_stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($user_interests)) {
            $system_prompt_parts[] = "The user's interests include: " . implode(', ', array_map('htmlspecialchars', $user_interests)) . ".";
        }
        $partner_stmt = $db->prepare("
            SELECT u.first_name, u.last_name, u.major, u.skill_level, GROUP_CONCAT(i.name) as interests
            FROM users u
            LEFT JOIN user_interests ui ON u.id = ui.user_id
            LEFT JOIN interests i ON ui.interest_id = i.id
            WHERE u.id != ? AND u.major = ?
            GROUP BY u.id
            LIMIT 3
        ");
        $partner_stmt->execute([$user_id, $user_profile['major']]);
        $potential_partners = $partner_stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($potential_partners)) {
            $system_prompt_parts[] = "Here are a few potential study partners:";
            foreach ($potential_partners as $partner) {
                $system_prompt_parts[] = "- " . htmlspecialchars($partner['first_name'] . ' ' . $partner['last_name']) . " (Major: " . htmlspecialchars($partner['major']) . ", Skill: " . htmlspecialchars($partner['skill_level']) . ", Interests: " . htmlspecialchars($partner['interests'] ?? 'None') . ")";
            }
            $system_prompt_parts[] = "Recommend a few of these users to the current user, explaining why they might be a good match based on their profiles. Keep the recommendations concise and text-only.";
        } else {
            $system_prompt_parts[] = "No immediate matching partners found based on simple criteria. Suggest broadening search or creating a study group.";
        }
    }
    // 3. General Query
    else {
        $system_prompt_parts[] = "The user has a general question. Provide a helpful and concise answer.";
    }

    $full_system_prompt = implode("\n", $system_prompt_parts);

    // --- Construct the final conversation payload for Gemini ---
    $contents_for_gemini = [];
    // 1. Add the system prompt as the first "user" turn
    $contents_for_gemini[] = ["role" => "user", "parts" => [["text" => $full_system_prompt]]];
    // 2. Add a priming "model" turn
    $contents_for_gemini[] = ["role" => "model", "parts" => [["text" => "OK, I understand. I am ready to assist."]]];
    // 3. Add the actual user chat history
    foreach ($history as $item) {
        $contents_for_gemini[] = [
            "role" => ($item['role'] === 'bot' ? 'model' : 'user'),
            "parts" => [["text" => $item['content']]]
        ];
    }
    // 4. Add the user's current prompt
    $contents_for_gemini[] = ["role" => "user", "parts" => [["text" => $prompt]]];

    // Call Gemini API with the well-formed contents
    $response_text = call_gemini_api($contents_for_gemini);
    echo json_encode(['success' => true, 'response' => $response_text]);
}
function call_gemini_api(array $contents): string {
    $api_key = GEMINI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
    $request_body = json_encode([
        "contents" => $contents
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch); 
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch); 
    curl_close($ch);

    if ($response === false) {
        error_log("cURL Error: " . $error);
        return "cURL Error: " . $error . " (请检查 XAMPP 防火墙或 PHP 的 cURL 扩展是否开启)";
    }

    $response_data = json_decode($response, true);

    if ($http_code !== 200) {
        error_log("Gemini API Error (HTTP " . $http_code . "): " . $response);
        return "Gemini API Error (HTTP " . $http_code . "). Full Response: " . $response;
    }

    if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        return $response_data['candidates'][0]['content']['parts'][0]['text'];
    } else {
        error_log("Gemini API Response Error: " . $response);
        if (isset($response_data['promptFeedback']['blockReason'])) {
             return "Error: Request blocked by safety settings (" . $response_data['promptFeedback']['blockReason'] . ")";
        }
        return "Error: Could not parse Gemini API response. Full Response: " . $response;
    }
}
