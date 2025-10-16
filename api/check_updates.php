<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

try {
    $db = get_db();

    // Count pending study partner requests
    $stmt_partner = $db->prepare("SELECT COUNT(*) FROM study_requests WHERE receiver_id = ? AND status = 'pending'");
    $stmt_partner->execute([$current_user_id]);
    $partner_request_count = $stmt_partner->fetchColumn();

    // Count pending group invitations
    $stmt_group = $db->prepare("SELECT COUNT(*) FROM study_group_invitations WHERE receiver_id = ? AND status = 'pending'");
    $stmt_group->execute([$current_user_id]);
    $group_invite_count = $stmt_group->fetchColumn();

    // Check for unread messages
    $stmt_unread = $db->prepare("
        SELECT 1
        FROM chat_rooms cr
        LEFT JOIN (SELECT room_id, MAX(id) as last_msg_id FROM chat_messages GROUP BY room_id) cm ON cr.id = cm.room_id
        LEFT JOIN user_chat_room_status ucrs ON cr.id = ucrs.room_id AND ucrs.user_id = :current_user_id_1
        WHERE
            (cr.room_type = 'direct' AND (cr.partner1_id = :current_user_id_2 OR cr.partner2_id = :current_user_id_3))
            OR
            (cr.room_type = 'group' AND cr.group_id IN (SELECT group_id FROM study_group_members WHERE user_id = :current_user_id_4))
        HAVING last_msg_id IS NOT NULL AND (ucrs.last_read_message_id IS NULL OR cm.last_msg_id > ucrs.last_read_message_id)
        LIMIT 1
    ");
    $stmt_unread->execute([
        ':current_user_id_1' => $current_user_id,
        ':current_user_id_2' => $current_user_id,
        ':current_user_id_3' => $current_user_id,
        ':current_user_id_4' => $current_user_id
    ]);
    $has_unread_messages = ($stmt_unread->fetchColumn() !== false);

    echo json_encode([
        'success' => true,
        'new_partner_requests' => $partner_request_count,
        'new_group_invites' => $group_invite_count,
        'has_unread_messages' => $has_unread_messages
    ]);

} catch (Exception $e) {
    error_log("Check updates error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
?>
