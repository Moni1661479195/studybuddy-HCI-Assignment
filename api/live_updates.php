<?php
// api/live_updates.php

require_once '../session.php';
require_once '../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    $current_user_id = (int)$_SESSION['user_id'];
    $db = get_db();

    $stmt_received = $db->prepare("SELECT COUNT(*) FROM study_requests WHERE receiver_id = ? AND status = 'pending'");
    $stmt_received->execute([$current_user_id]);
    $received_count = $stmt_received->fetchColumn();

    $stmt_sent = $db->prepare("SELECT request_id FROM study_requests WHERE sender_id = ? AND status = 'pending'");
    $stmt_sent->execute([$current_user_id]);
    $sent_ids = $stmt_sent->fetchAll(PDO::FETCH_COLUMN);
    $sent_state_hash = md5(implode(',', $sent_ids));

    echo json_encode([
        'success' => true,
        'updates' => [
            'received_requests_count' => $received_count,
            'sent_requests_state' => $sent_state_hash
        ]
    ]);

} catch (Exception $e) {
    
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}