<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = (int)$data['id'];

$db = get_db();
$stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->execute([$notification_id, $current_user_id]);
?>