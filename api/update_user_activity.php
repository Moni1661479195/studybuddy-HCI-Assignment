<?php
require_once __DIR__ . '/../lib/db.php';

if (isset($_SESSION['user_id'])) {
    try {
        $db = get_db();
        $user_id = $_SESSION['user_id'];
        $stmt = $db->prepare("UPDATE users SET last_seen = NOW(), is_online = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        // Log error or handle it as needed
        error_log('Update user activity failed: ' . $e->getMessage());
    }
}
?>