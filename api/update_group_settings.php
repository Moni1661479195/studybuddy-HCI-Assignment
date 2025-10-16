<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

// 1. Check authentication and request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$group_id = (int)($_POST['group_id'] ?? 0);
$group_name = trim($_POST['group_name'] ?? '');
$description = trim($_POST['description'] ?? '');

$redirect_url = '../group-details.php?id=' . $group_id;

// 2. Validate input
if ($group_id <= 0 || empty($group_name)) {
    $_SESSION['error'] = "Invalid data provided. Group name cannot be empty.";
    header("Location: " . $redirect_url);
    exit();
}

try {
    $db = get_db();

    // 3. Verify user is the creator of the group
    $stmt_verify = $db->prepare("SELECT creator_id FROM study_groups WHERE group_id = ?");
    $stmt_verify->execute([$group_id]);
    $group = $stmt_verify->fetch(PDO::FETCH_ASSOC);

    if (!$group || $group['creator_id'] != $current_user_id) {
        $_SESSION['error'] = "You do not have permission to edit this group.";
        header("Location: " . $redirect_url);
        exit();
    }

    // 4. Perform the update
    $stmt_update = $db->prepare("
        UPDATE study_groups 
        SET group_name = ?, description = ? 
        WHERE group_id = ?
    ");
    $stmt_update->execute([$group_name, $description, $group_id]);

    $_SESSION['success'] = "Group settings updated successfully!";
    header("Location: " . $redirect_url);
    exit();

} catch (Exception $e) {
    error_log("Update group settings error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while updating the settings.";
    header("Location: " . $redirect_url);
    exit();
}
