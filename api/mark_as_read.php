<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$room_id = (int)($input['room_id'] ?? 0);
$last_message_id = (int)($input['last_message_id'] ?? 0);

if ($room_id <= 0 || $last_message_id < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID or message ID.']);
    exit();
}

try {
    $db = get_db();

    // Upsert: Try to update, if no rows affected, then insert
    $stmt_update = $db->prepare("
        UPDATE user_chat_room_status 
        SET last_read_message_id = ? 
        WHERE user_id = ? AND room_id = ?
    ");
    $stmt_update->execute([$last_message_id, $current_user_id, $room_id]);

    if ($stmt_update->rowCount() === 0) {
        // No row updated, so insert a new one
        $stmt_insert = $db->prepare("
            INSERT INTO user_chat_room_status (user_id, room_id, last_read_message_id)
            VALUES (?, ?, ?)
        ");
        $stmt_insert->execute([$current_user_id, $room_id, $last_message_id]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Mark as read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
