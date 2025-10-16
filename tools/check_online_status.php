<?php
// This script is intended to be run as a cron job every minute or so.
// It checks for users who haven't been seen in the last 5 minutes and marks them as offline.

require_once __DIR__ . '/../lib/db.php';

try {
    $db = get_db();
    // Mark users as offline if they haven't been seen in the last 5 minutes
    $stmt = $db->prepare("UPDATE users SET is_online = 0 WHERE is_online = 1 AND last_seen < NOW() - INTERVAL 5 MINUTE");
    $stmt->execute();

    echo "Online status updated successfully for " . $stmt->rowCount() . " users.\n";

    close_db();
} catch (Exception $e) {
    error_log("Error in check_online_status.php: " . $e->getMessage());
    http_response_code(500);
    echo "An error occurred while updating online status.";
}
?>
