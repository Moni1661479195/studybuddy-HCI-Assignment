<?php
require_once '../session.php';
require_once '../lib/db.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$quiz_id = $data['quiz_id'] ?? null;
$recipient_type = $data['recipient_type'] ?? null; // 'user' or 'group'
$recipient_id = $data['recipient_id'] ?? null;

if (!$quiz_id || !$recipient_type || !$recipient_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // 1. Get Quiz Details
    $stmt = $db->prepare("SELECT title, subject, description, question_count FROM quizzes 
                          LEFT JOIN (SELECT quiz_id, COUNT(id) as question_count FROM quiz_questions GROUP BY quiz_id) qq ON quizzes.id = qq.quiz_id
                          WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        throw new Exception("Quiz not found.");
    }

    // 2. Resolve Room ID
    $room_id = null;

    if ($recipient_type === 'group') {
        $stmt = $db->prepare("SELECT id FROM chat_rooms WHERE group_id = ? AND room_type = 'group'");
        $stmt->execute([$recipient_id]);
        $room_id = $stmt->fetchColumn();

        if (!$room_id) {
            $stmt = $db->prepare("INSERT INTO chat_rooms (room_type, group_id) VALUES ('group', ?)");
            $stmt->execute([$recipient_id]);
            $room_id = $db->lastInsertId();
        }
    } elseif ($recipient_type === 'user') {
        $stmt = $db->prepare("
            SELECT id FROM chat_rooms 
            WHERE room_type = 'direct' 
            AND ((partner1_id = ? AND partner2_id = ?) OR (partner1_id = ? AND partner2_id = ?))
        ");
        $stmt->execute([$current_user_id, $recipient_id, $recipient_id, $current_user_id]);
        $room_id = $stmt->fetchColumn();

        if (!$room_id) {
            $stmt = $db->prepare("INSERT INTO chat_rooms (room_type, partner1_id, partner2_id) VALUES ('direct', ?, ?)");
            $stmt->execute([$current_user_id, $recipient_id]);
            $room_id = $db->lastInsertId();
        }
    } else {
        throw new Exception("Invalid recipient type.");
    }

    if (!$room_id) {
        throw new Exception("Could not determine chat room.");
    }

    // 3. Send Rich Message
    $quiz_url = "take_quiz.php?id=" . $quiz_id;
    
    $message = "
        <div style='border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background-color: #f3f4f6;'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #4b5563; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.05em;'>Challenge Request ⚔️</p>
            <p style='margin: 0 0 4px 0; font-size: 1.1em; color: #111827; font-weight: 700;'>{$quiz['title']}</p>
            <p style='margin: 0 0 12px 0; font-size: 0.9em; color: #6b7280;'>Subject: {$quiz['subject']} • {$quiz['question_count']} Questions</p>
            <a href='" . $quiz_url . "' style='display: inline-block; background-color: #7c3aed; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.95em; font-weight: 600; transition: background-color 0.2s;'>Take Quiz</a>
        </div>
    ";
    
    $stmt = $db->prepare("INSERT INTO chat_messages (room_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$room_id, $current_user_id, $message]);
    
    // 4. Trigger Notification
    if ($recipient_type === 'user') {
        $link = "chat.php?user_id=" . $current_user_id;
        create_notification($recipient_id, 'direct_message', "Challenged you to a quiz!", $link);
    } elseif ($recipient_type === 'group') {
        $stmt = $db->prepare("SELECT user_id FROM study_group_members WHERE group_id = ? AND user_id != ?");
        $stmt->execute([$recipient_id, $current_user_id]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $link = "group-chat.php?group_id=" . $recipient_id;
        foreach ($members as $member_id) {
            create_notification($member_id, 'group_message', "New quiz challenge in group", $link);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Quiz shared successfully!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>