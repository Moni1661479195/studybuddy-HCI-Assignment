<?php
header('Content-Type: application/json');
require_once '../config.php'; //
require_once '../lib/db.php'; // For potential future use, not strictly needed for this API call

// --- Configuration ---
// Check if the Gemini API key is defined
if (!defined('GEMINI_API_KEY')) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key is not configured on the server.']);
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
$duration = $input['duration'] ?? null;

if (empty($subject) || empty($weaknesses) || empty($duration)) {

    echo json_encode(['success' => false, 'message' => 'Missing required fields: subject, weaknesses, or duration.']);

    exit();

}



// --- Date Calculation ---

$startDate = new DateTime();

$endDate = clone $startDate; // Clone start date to modify it
$durationParts = explode(' ', $duration);
$value = isset($durationParts[0]) ? (int)$durationParts[0] : 0;
$unit = isset($durationParts[1]) ? strtolower(rtrim($durationParts[1], '(s)')) : '';



// Add duration to end date based on parsed unit

if ($value > 0) {
    try {
        if ($unit === 'day') {
            $endDate->modify("+$value days");
        } elseif ($unit === 'week') {
            $endDate->modify("+$value weeks");
        } elseif ($unit === 'month') {
            $endDate->modify("+$value months");
        }
    } catch (Exception $e) {
        // Log error and potentially fall back to a default
        error_log("Error parsing duration: {$e->getMessage()}");
    }

}

$startDateFormatted = $startDate->format('Y-m-d');
$endDateFormatted = $endDate->format('Y-m-d');





// --- Prompt Engineering ---

$prompt = "
You are an expert study plan generator. A user needs a study plan.

**Context for date generation:**
- Today's date (plan start date): {$startDateFormatted}
- The plan's final end date: {$endDateFormatted}

**User's Goal:**
- **Subject:** {$subject}
- **Weaknesses/Focus Areas:** {$weaknesses}
- **Desired Plan Duration:** {$duration}



**Your Task:**
Generate a structured study plan based on the user's input. The plan should have a clear name, a brief description, and a list of specific, actionable tasks.
For each task, assign a realistic `due_date`.
**Crucially, every task's `due_date` MUST be on or between the start date ({$startDateFormatted}) and the end date ({$endDateFormatted}). Do not assign dates outside this range.**



**Output Format:**
Return ONLY a valid JSON object with the following structure. Do NOT include any text, explanations, or markdown formatting like 
```json ... 
```
 outside of the JSON object itself.

{

  \"planName\": \"[A concise and descriptive name for the study plan]\",
  \"description\": \"[A short, motivating description of the plan's objectives]\",
  \"tasks\": [
    {
      \"name\": \"[Name of the first task]\",
      \"description\": \"[A brief description of what to do for this task]\",
      \"due_date\": \"[Due date for this task in YYYY-MM-DD format. Ensure this date is within the overall plan duration.]\"
    },

    {
      \"name\": \"[Name of the second task]\",
      \"description\": \"[A brief description of what to do for this task]\",
      \"due_date\": \"[Due date for this task in YYYY-MM-DD format. Ensure this date is within the overall plan duration.]\"
    }
  ]

}



**Example Tasks:**

- If the subject is 'Calculus' and weakness is 'Integration', tasks could be 'Review Fundamentals of Integration', 'Practice 10 Problems on Integration by Parts', 'Watch a video on U-Substitution'.
- Break down the user's request into 5-10 logical and manageable tasks covering the specified duration.

- Ensure `due_date` for each task is realistic and follows the YYYY-MM-DD format.

";





// --- cURL API Call ---
$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'topK' => 1,
        'topP' => 1,
        'maxOutputTokens' => 2048,
        'stopSequences' => []
    ],
     'safetySettings' => [
        [ 'category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
        [ 'category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
        [ 'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
        [ 'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE' ],
    ]
];

$ch = curl_init($apiEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60-second timeout for AI generation

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
    echo json_encode(['success' => false, 'message' => 'No content received from AI. The response might have been blocked for safety reasons.']);
    exit();
}

// Clean the response: remove markdown backticks if present
$json_string = preg_replace('/^```json\s*|\s*```$/', '', $text_content);

$plan_data = json_decode($json_string, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Failed to decode AI JSON response. Error: " . json_last_error_msg() . ". Raw content: " . $text_content);
    echo json_encode(['success' => false, 'message' => 'AI returned an invalid plan format. Please try again.']);
    exit();
}

// Standardize the response to handle inconsistencies from the AI
$standardized_plan = [
    'planName' => $plan_data['planName'] ?? $plan_data['plan_name'] ?? 'AI Generated Plan',
    'description' => $plan_data['description'] ?? '',
    'due_date' => $endDateFormatted,
    'tasks' => []
];

if (isset($plan_data['tasks']) && is_array($plan_data['tasks'])) {
    foreach ($plan_data['tasks'] as $task) {
        $std_task = [
            'name' => $task['name'] ?? $task['task_name'] ?? 'Untitled Task',
            'description' => $task['description'] ?? $task['task_description'] ?? '',
            'due_date' => $task['due_date'] ?? $task['dueDate'] ?? null
        ];
        $standardized_plan['tasks'][] = $std_task;
    }
}


// Log the successful data structure for debugging
error_log("Successfully decoded and standardized AI plan data: " . print_r($standardized_plan, true));

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

echo json_encode(['success' => true, 'plan' => $standardized_plan]);
?>

