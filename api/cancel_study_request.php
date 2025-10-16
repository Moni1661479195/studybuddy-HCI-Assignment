<?php
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

    // First, get the current status and sender_id, receiver_id of the request
    $stmt_check = $db->prepare("SELECT status, sender_id, receiver_id FROM study_requests WHERE request_id = ?");
    $stmt_check->execute([$request_id]);
    $request_info = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$request_info) {
        echo json_encode(['success' => false, 'message' => 'Request not found.']);
        exit();
    }

    // Determine if the current user is the sender or receiver of the request
    $is_sender = ($request_info['sender_id'] == $user_id);
    $is_receiver = ($request_info['receiver_id'] == $user_id);

    if (!$is_sender && !$is_receiver) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to cancel this request.']);
        exit();
    }

    $db->beginTransaction();

    if ($request_info['status'] == 'accepted') {
        // If accepted, treat as an 'unfriend' operation
        $mate_id = ($is_sender) ? $request_info['receiver_id'] : $request_info['sender_id'];

        // Delete the accepted study request entry
        $stmt_delete_request = $db->prepare("
            DELETE FROM study_requests 
            WHERE (
                (sender_id = ? AND receiver_id = ?) OR 
                (sender_id = ? AND receiver_id = ?)
            ) AND status = 'accepted'
        ");
        $stmt_delete_request->execute([$user_id, $mate_id, $mate_id, $user_id]);

        // Delete the study session entry
        $stmt_delete_session = $db->prepare("
            DELETE FROM study_sessions 
            WHERE is_group = 1 
              AND JSON_CONTAINS(metadata, JSON_ARRAY(?, ?), '$.group')
        ");
        $stmt_delete_session->execute([$user_id, $mate_id]);

        echo json_encode(['success' => true, 'message' => 'Study mate relationship terminated.']);

    } else if ($request_info['status'] == 'pending' && $is_sender) {
        // If pending and current user is sender, allow cancellation
        $stmt = $db->prepare("DELETE FROM study_requests WHERE request_id = ? AND sender_id = ? AND status = 'pending'");
        $stmt->execute([$request_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Study request cancelled successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel pending request.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel this request in its current state or you are not the sender.']);
    }

    $db->commit();

} catch (Exception $e) {
    $db->rollBack();
    error_log("Error cancelling study request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while cancelling the request.']);
}