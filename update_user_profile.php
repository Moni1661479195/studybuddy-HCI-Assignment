<?php
require_once 'session.php';
require_once 'lib/db.php';

// 1. Security Checks
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Invalid request method.');
}

// Check if user ID is provided
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    header('Location: user_management.php?error=Invalid+user');
    exit();
}

$user_id_to_update = (int)$_POST['user_id'];

// If the user is trying to update someone else's profile AND they are not an admin, deny access.
if ($user_id_to_update !== $current_user_id && (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied. You can only update your own profile unless you are an admin.');
}

// 2. Get Data
$user_id_to_update = (int)$_POST['user_id'];
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$major = trim($_POST['major'] ?? '');
$skill_level = trim($_POST['skill_level'] ?? 'beginner');

// Basic validation
if (empty($first_name) || empty($last_name)) {
    header('Location: user_profile.php?id=' . $user_id_to_update . '&error=First+and+Last+name+cannot+be+empty');
    exit();
}

// 3. Database Update
try {
    $db = get_db();
    $sql = "UPDATE users SET 
                first_name = :first_name, 
                last_name = :last_name, 
                bio = :bio, 
                major = :major, 
                skill_level = :skill_level
            WHERE id = :id";

    $stmt = $db->prepare($sql);

    $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
    $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
    $stmt->bindParam(':bio', $bio, PDO::PARAM_STR);
    $stmt->bindParam(':major', $major, PDO::PARAM_STR);
    $stmt->bindParam(':skill_level', $skill_level, PDO::PARAM_STR);
    $stmt->bindParam(':id', $user_id_to_update, PDO::PARAM_INT);

    $redirect_url = 'user_profile.php?id=' . $user_id_to_update;
    if (isset($_POST['origin']) && $_POST['origin'] === 'admin_management' && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        $redirect_url = 'user_management.php';
    }

    $param_separator = strpos($redirect_url, '?') === false ? '?' : '&';

    if ($stmt->execute()) {
        // 4. Redirect with success
        header('Location: ' . $redirect_url . $param_separator . 'success=Profile+updated+successfully');
    } else {
        header('Location: ' . $redirect_url . $param_separator . 'error=Failed+to+update+profile');
    }
} catch (PDOException $e) {
    // For a real app, log the error
    header('Location: ' . $redirect_url . $param_separator . 'error=Database+error');
}

exit();
?>