<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$mate_id = isset($input['mate_id']) ? (int)$input['mate_id'] : 0;

if ($mate_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid study mate ID.']);
    exit();
}

if ($current_user_id == $mate_id) {
    echo json_encode(['success' => false, 'message' => 'Cannot remove yourself as a study mate.']);
    exit();
}

try {
    $db = get_db();
    $db->beginTransaction();

    // 1. Delete the accepted study request entry
    $stmt_delete_request = $db->prepare("
        DELETE FROM study_requests 
        WHERE (
            (sender_id = ? AND receiver_id = ?) OR 
            (sender_id = ? AND receiver_id = ?)
        ) AND status = 'accepted'
    ");
    $stmt_delete_request->execute([$current_user_id, $mate_id, $mate_id, $current_user_id]);

    // 2. Delete the study session entry
    // This assumes a study session is created for each accepted request
    $stmt_delete_session = $db->prepare("
        DELETE FROM study_sessions 
        WHERE is_group = 1 
          AND JSON_CONTAINS(metadata, JSON_ARRAY(?, ?), '$.group')
    ");
    $stmt_delete_session->execute([$current_user_id, $mate_id]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Study mate removed successfully.']);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Error removing study mate: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while removing the study mate.']);
}
?>