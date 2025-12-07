<?php
header('Content-Type: application/json');
require_once '../config.php';

// --- Configuration & Security ---
if (!defined('GEMINI_API_KEY')) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key is not configured.']);
    exit();
}
$apiKey = GEMINI_API_KEY;
$apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$subject = $input['subject'] ?? null;
$weaknesses = $input['weaknesses'] ?? null;
$numQuestions = isset($input['numQuestions']) ? (int)$input['numQuestions'] : 0;

if (empty($subject) || empty($weaknesses) || $numQuestions < 5 || $numQuestions > 20) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters. Requires subject, weaknesses, and a question count between 5 and 20.']);
    exit();
}

// --- Prompt Engineering ---
$prompt = "
You are an expert quiz generator for students. Your task is to create a multiple-choice quiz based on the user's specifications.

**User's Request:**
- **Subject:** {$subject}
- **Focus Areas/Weaknesses:** {$weaknesses}
- **Number of Questions to Generate:** {$numQuestions}

**Your Task:**
Generate a complete quiz in a valid JSON format. The quiz must include a title, a subject, a brief description, and an array of question objects.

**Rules for Generation:**
1.  **Question Type:** All questions must be multiple-choice.
2.  **Options:** Each question must have between 3 and 4 answer options.
3.  **Correct Answer:** For each question, exactly ONE option must be correct.
4.  **Content:** The questions should be relevant to the user's subject and focus areas.
5.  **Output Format:** You must return ONLY a single, valid JSON object. Do not include any text, explanations, or markdown formatting like ```json ... ``` outside of the JSON object itself.

**JSON Structure:**
{
  \"title\": \"[A fitting title for the quiz, e.g., 'JavaScript Async/Await Concepts']\",
  \"subject\": \"[The subject provided by the user, e.g., '{$subject}']\",
  \"description\": \"[A short, engaging description of what the quiz covers.]\",
  \"questions\": [
    {
      \"question_text\": \"[The text of the first question]\",
      \"options\": [
        { \"text\": \"[Text for option A]\", \"is_correct\": false },
        { \"text\": \"[Text for option B]\", \"is_correct\": true },
        { \"text\": \"[Text for option C]\", \"is_correct\": false },
        { \"text\": \"[Text for option D, optional]\", \"is_correct\": false }
      ]
    },
    {
      \"question_text\": \"[The text of the second question]\",
      \"options\": [
        { \"text\": \"[Text for option A]\", \"is_correct\": true },
        { \"text\": \"[Text for option B]\", \"is_correct\": false },
        { \"text\": \"[Text for option C]\", \"is_correct\": false }
      ]
    }
  ]
}
";

// --- API Call ---
$payload = [
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature' => 0.8,
        'topK' => 1,
        'topP' => 1,
        'maxOutputTokens' => 4096,
        'stopSequences' => [],
    ],
    'safetySettings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
    ]
];

$ch = curl_init($apiEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// --- Response Processing ---
if ($error) {
    echo json_encode(['success' => false, 'message' => 'API call failed: ' . $error]);
    exit();
}

if ($httpcode !== 200) {
    $apiError = json_decode($response, true);
    $errorMessage = $apiError['error']['message'] ?? 'Unknown API error';
    echo json_encode(['success' => false, 'message' => "API request failed with status {$httpcode}. Error: {$errorMessage}"]);
    exit();
}

$data = json_decode($response, true);
$text_content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$text_content) {
    echo json_encode(['success' => false, 'message' => 'No content received from AI. The response might have been blocked for safety reasons or be empty.']);
    exit();
}

// Clean the response: remove markdown backticks if present
$json_string = preg_replace('/^```json\s*|\s*```$/', '', $text_content);
$quiz_data = json_decode($json_string, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Failed to decode AI JSON for quiz. Error: " . json_last_error_msg() . ". Raw content: " . $text_content);
    echo json_encode(['success' => false, 'message' => 'AI returned an invalid format. Please try again. Raw=' . $text_content]);
    exit();
}

// --- Standardization Step ---
// Ensure the data structure is exactly what the frontend expects
$standardized_quiz = [
    'title' => $quiz_data['title'] ?? 'AI Generated Quiz',
    'subject' => $quiz_data['subject'] ?? $subject, // Fallback to user input
    'description' => $quiz_data['description'] ?? '',
    'questions' => [],
];

if (isset($quiz_data['questions']) && is_array($quiz_data['questions'])) {
    foreach ($quiz_data['questions'] as $q) {
        $std_question = [
            'question_text' => $q['question_text'] ?? 'Untitled Question',
            'options' => [],
        ];
        if (isset($q['options']) && is_array($q['options'])) {
            $has_correct = false;
            foreach ($q['options'] as $opt) {
                $is_correct = isset($opt['is_correct']) && $opt['is_correct'] === true;
                if ($is_correct) $has_correct = true;
                $std_question['options'][] = [
                    'text' => $opt['text'] ?? '',
                    'is_correct' => $is_correct,
                ];
            }
            // Ensure at least one option is correct if AI forgets
            if (!$has_correct && count($std_question['options']) > 0) {
                $std_question['options'][0]['is_correct'] = true;
            }
        }
        $standardized_quiz['questions'][] = $std_question;
    }
}

// Task: AI Exploration
// Moved before JSON output to avoid "Headers already sent" errors from session_start()
require_once '../session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    require_once '../lib/db.php';
    require_once '../includes/TaskLogic.php';
    $db = get_db();
    updateTaskProgress($db, $_SESSION['user_id'], 'daily_ai');
}

echo json_encode(['success' => true, 'quiz' => $standardized_quiz]);
?>
