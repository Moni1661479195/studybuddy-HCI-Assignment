<?php
// api/upload_file.php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$room_id = (int)($_POST['room_id'] ?? 0);

if (!$room_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Room ID is required.']);
    exit();
}

$db = get_db();

// Verify room access
$stmt = $db->prepare("
    SELECT 1 FROM chat_rooms 
    WHERE id = ? AND (partner1_id = ? OR partner2_id = ? OR group_id IN (
        SELECT group_id FROM study_group_members WHERE user_id = ?
    ))
");
$stmt->execute([$room_id, $current_user_id, $current_user_id, $current_user_id]);
if ($stmt->fetchColumn() === false) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied to this chat room.']);
    exit();
}

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // File validation
    $allowed_types = ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/svg+xml', 'audio/webm', 'audio/ogg', 'audio/mp3', 'audio/wav'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
        exit();
    }

    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File is too large.']);
        exit();
    }

    // Create a unique file name
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('file_', true) . '.' . $file_extension;
    $upload_path = __DIR__ . '/../uploads/' . $file_name;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Insert file message into the database
        $stmt = $db->prepare("INSERT INTO chat_messages (room_id, user_id, message, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$room_id, $current_user_id, '', 'uploads/' . $file_name]);

        echo json_encode(['success' => true, 'message' => 'File uploaded successfully.', 'file_path' => 'uploads/' . $file_name]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file was uploaded.']);
}
?>