<?php
// This script acts as a gatekeeper for pages that require a completed profile.

// Ensure session is started, as this script relies on session data.
// Use @ to suppress warnings if session is already started.
@session_start();

// Only proceed if a user is logged in.
if (isset($_SESSION['user_id'])) {
    
    // Avoid running this check on the completion page itself to prevent a redirect loop.
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page === 'complete-profile.php') {
        return;
    }

    // We need a database connection.
    // Use require_once to prevent re-including db.php if it's already included.
    require_once __DIR__ . '/lib/db.php';

    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT profile_setup_complete FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_status = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the flag is not set to 1 (i.e., it's 0 or somehow null), redirect.
        if ($user_status && $user_status['profile_setup_complete'] != 1) {
            header("Location: complete-profile.php");
            exit();
        }

    } catch (PDOException $e) {
        // In case of a database error, log it and perhaps redirect to an error page.
        // For now, we'll just log and let the script continue to avoid breaking the site.
        error_log("Error in check_profile_status.php: " . $e->getMessage());
    }
}
?>