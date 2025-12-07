<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

// Fetch notifications from the notifications table
$stmt = $db->prepare("SELECT id, message, type, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$current_user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

// Fetch pending study requests
$stmt = $db->prepare("
    SELECT sr.request_id as id, u.username as sender_name
    FROM study_requests sr
    JOIN users u ON sr.sender_id = u.id
    WHERE sr.receiver_id = ? AND sr.status = 'pending'
");
$stmt->execute([$current_user_id]);
$study_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

foreach ($study_requests as $request) {
    $notifications[] = [
        'id' => 'sr_' . $request['id'],
        'message' => 'You have a new study partner request from ' . htmlspecialchars($request['sender_name']),
        'type' => 'study_request',
        'link' => 'dashboard.php#study-requests',
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s') // Placeholder, ideally requests table has a timestamp
    ];
}

// Fetch pending group invitations
$stmt = $db->prepare("
    SELECT sgi.id, u.username as inviter_name, sg.group_name as group_name
    FROM study_group_invitations sgi
    JOIN users u ON sgi.sender_id = u.id
    JOIN study_groups sg ON sgi.group_id = sg.group_id
    WHERE sgi.receiver_id = ? AND sgi.status = 'pending'
");
$stmt->execute([$current_user_id]);
$group_invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

foreach ($group_invitations as $invitation) {
    $notifications[] = [
        'id' => 'gi_' . $invitation['id'],
        'message' => 'You have been invited to join the group "' . htmlspecialchars($invitation['group_name']) . '" by ' . htmlspecialchars($invitation['inviter_name']),
        'type' => 'group_invitation',
        'link' => 'my-groups.php',
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s') // Placeholder, ideally invitations table has a timestamp
    ];
}

// Sort all notifications by date
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode($notifications);
?>