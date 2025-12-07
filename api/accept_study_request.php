<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../lib/db.php';
require_once '../session.php';
require_once __DIR__ . '/../includes/notifications.php';

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
    $stmt_get_sender = $db->prepare("SELECT sender_id FROM study_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt_get_sender->execute([$request_id, $user_id]);
    $sender_data = $stmt_get_sender->fetch(PDO::FETCH_ASSOC);

    if (!$sender_data) {
        // Check if the request exists but is not pending
        $stmt_check_status = $db->prepare("SELECT status FROM study_requests WHERE id = ? AND receiver_id = ?");
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

    $db->beginTransaction();

    // Update study request status to accepted
    $stmt_update_request = $db->prepare("UPDATE study_requests SET status = 'accepted', responded_at = NOW() WHERE id = ?");
    $stmt_update_request->execute([$request_id]);

    $receiver_id = $user_id; // The current user is the receiver in this context

    // Clean up other pending requests between these two users to prevent duplicates
    $stmt_cleanup = $db->prepare("DELETE FROM study_requests WHERE status = 'pending' AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
    $stmt_cleanup->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);

    $user1 = min($sender_id, $receiver_id);
    $user2 = max($sender_id, $receiver_id);

    // Add to study_partners table
    $partner_stmt = $db->prepare("\n        INSERT INTO study_partners (user1_id, user2_id, is_active, last_activity) \n        VALUES (?, ?, 1, NOW())\n        ON DUPLICATE KEY UPDATE is_active = 1, last_activity = NOW()\n    ");
    $partner_stmt->execute([$user1, $user2]);

    // Create a notification for the sender
    $receiver_name = $_SESSION['username'] ?? 'Someone';
    $message = htmlspecialchars($receiver_name) . " accepted your study request.";
    $link = 'user_profile.php?id=' . $receiver_id;
    create_notification($sender_id, 'study_request_accepted', $message, $link);

    // Task: Social Butterfly
    require_once __DIR__ . '/../includes/TaskLogic.php';
    updateTaskProgress($db, $user_id, 'weekly_social');

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Study request accepted successfully!']);
} catch (Exception $e) {
    error_log("Error accepting study request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while accepting the request.']);
}