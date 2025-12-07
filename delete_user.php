<?php
require_once 'session.php';

// Admin-only action
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: dashboard.php');
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: user_management.php?error=Invalid+user+ID');
    exit();
}

$user_id_to_delete = (int)$_GET['id'];

// Prevent admin from deleting themselves
if ($user_id_to_delete === $_SESSION['user_id']) {
    header('Location: user_management.php?error=Cannot+delete+yourself');
    exit();
}

require_once 'lib/db.php';

try {
    $db = get_db();
    
    // You might want to add more cleanup here, 
    // e.g., deleting user's posts, comments, etc., from other tables.

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id_to_delete, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        header('Location: user_management.php?success=User+deleted+successfully');
    } else {
        header('Location: user_management.php?error=Failed+to+delete+user');
    }
} catch (PDOException $e) {
    header('Location: user_management.php?error=Database+error');
    // For a real app, you'd log this error.
    // die("Database error: " . $e->getMessage());
}

exit();
?>
