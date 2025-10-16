<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['received_requests_count' => 0, 'sent_pending_requests_count' => 0, 'study_mates_count' => 0]);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

try {
    $db = get_db();

    // Count pending received requests
    $stmt_received_pending = $db->prepare("SELECT COUNT(*) FROM study_requests WHERE receiver_id = ? AND status = 'pending'");
    $stmt_received_pending->execute([$current_user_id]);
    $received_requests_count = $stmt_received_pending->fetchColumn();

    // Count pending sent requests
    $stmt_sent_pending = $db->prepare("SELECT COUNT(*) FROM study_requests WHERE sender_id = ? AND status = 'pending'");
    $stmt_sent_pending->execute([$current_user_id]);
    $sent_pending_requests_count = $stmt_sent_pending->fetchColumn();

    // Count study mates
    $stmt_mates = $db->prepare("
        SELECT COUNT(DISTINCT u.id)
        FROM users u
        JOIN study_sessions s ON (s.user_id = u.id OR JSON_CONTAINS(s.metadata, u.id, '$.group'))
        WHERE s.is_group = 1
          AND (s.user_id = ? OR JSON_CONTAINS(s.metadata, ?, '$.group'))
          AND u.id != ?
    ");
    $stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
    $study_mates_count = $stmt_mates->fetchColumn();

    echo json_encode([
        'received_requests_count' => $received_requests_count,
        'sent_pending_requests_count' => $sent_pending_requests_count,
        'study_mates_count' => $study_mates_count
    ]);
} catch (Exception $e) {
    error_log("Error fetching user status updates: " . $e->getMessage());
    echo json_encode(['received_requests_count' => 0, 'sent_pending_requests_count' => 0, 'study_mates_count' => 0, 'error' => 'Failed to fetch status.']);
}
?>