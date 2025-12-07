<?php
require_once __DIR__ . '/../lib/db.php';

function create_notification($user_id, $type, $message, $link) {
    try {
        $db = get_db();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        if ($stmt) {
            // PDO uses an array in execute() for parameter binding
            $stmt->execute([$user_id, $type, $message, $link]);
            // closeCursor() is the PDO equivalent for freeing up the connection
            $stmt->closeCursor();
        }
    } catch (Exception $e) {
        // Log error to a file instead of echoing, especially since this might be called from background processes.
        error_log("Failed to create notification: " . $e->getMessage());
    }
}
?>