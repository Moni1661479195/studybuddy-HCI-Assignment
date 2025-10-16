<?php
require_once 'session.php';

// Update user's online status and clear session ID
if (isset($_SESSION['user_id'])) {
    require_once 'lib/db.php';
    try {
        $db = get_db();
        $sql = "UPDATE users SET is_online = 0, last_seen = NOW(), session_id = NULL WHERE id = :user_id";
        if ($stmt = $db->prepare($sql)) {
            $stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Log the error, but don't block the user from logging out.
        error_log("Failed to update user online status on logout: " . $e->getMessage());
    }
}

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>
