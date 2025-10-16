<?php
require_once __DIR__ . '/../lib/db.php';

if (isset($_SESSION['user_id'])) {
    try {
        $db = get_db();
        $user_id = $_SESSION['user_id'];
        $stmt = $db->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        // Log error or handle it as needed
        error_log('Logout user activity failed: ' . $e->getMessage());
    }
}
?>