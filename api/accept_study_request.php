<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../lib/db.php';
require_once '../session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit();
}

try {
    $db = get_db();

    // Get sender_id from request_id and ensure current user is the receiver
    $stmt_get_sender = $db->prepare("SELECT sender_id FROM study_requests WHERE request_id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt_get_sender->execute([$request_id, $user_id]);
    $sender_data = $stmt_get_sender->fetch(PDO::FETCH_ASSOC);

    if (!$sender_data) {
        // Check if the request exists but is not pending
        $stmt_check_status = $db->prepare("SELECT status FROM study_requests WHERE request_id = ? AND receiver_id = ?");
        $stmt_check_status->execute([$request_id, $user_id]);
        $current_status = $stmt_check_status->fetchColumn();

        if ($current_status === 'accepted') {
            echo json_encode(['success' => false, 'message' => 'This request has already been accepted by you.']);
        } elseif ($current_status === 'declined') {
            echo json_encode(['success' => false, 'message' => 'This request was declined by you.']);
        } elseif ($current_status) {
            echo json_encode(['success' => false, 'message' => 'This request is no longer pending (it might have been cancelled by the sender).']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
        }
        exit();
    }
    $sender_id = (int)$sender_data['sender_id'];

    // Update study request status to accepted
    $stmt_update_request = $db->prepare("UPDATE study_requests SET status = 'accepted', responded_at = NOW() WHERE request_id = ?");
    $stmt_update_request->execute([$request_id]);

    // Create a study session for the accepted request
    $metadata = json_encode(['group' => [$user_id, $sender_id]]);
    $create_session = $db->prepare("INSERT INTO study_sessions (user_id, is_group, metadata, started_at) VALUES (?, 1, ?, NOW())");
    $create_session->execute([$user_id, $metadata]);

    // Create a notification for the sender
    $receiver_name_stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $receiver_name_stmt->execute([$user_id]);
    $receiver_name = $receiver_name_stmt->fetch(PDO::FETCH_ASSOC);
    $message = htmlspecialchars($receiver_name['first_name'] . ' ' . $receiver_name['last_name']) . " accepted your study request.";
    
    $stmt_notification = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
    $stmt_notification->execute([$sender_id, $message]);

    echo json_encode(['success' => true, 'message' => 'Study request accepted successfully!']);
} catch (Exception $e) {
    error_log("Error accepting study request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while accepting the request.']);
}