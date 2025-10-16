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

if ($group_id <= 0) {
    $_SESSION['error'] = "Invalid group specified.";
    header("Location: ../study-groups.php");
    exit();
}

try {
    $db = get_db();

    // 2. Get group and membership info
    $stmt_verify = $db->prepare("SELECT creator_id FROM study_groups WHERE group_id = ?");
    $stmt_verify->execute([$group_id]);
    $group = $stmt_verify->fetch(PDO::FETCH_ASSOC);

    $member_check = $db->prepare("SELECT 1 FROM study_group_members WHERE group_id = ? AND user_id = ?");
    $member_check->execute([$group_id, $current_user_id]);
    $is_member = $member_check->fetch();

    // 3. Validation checks
    if (!$group || !$is_member) {
        $_SESSION['error'] = "You are not a member of this group.";
        header("Location: ../study-groups.php");
        exit();
    }

    if ($group['creator_id'] == $current_user_id) {
        $_SESSION['error'] = "Group creators cannot leave a group. You must delete it instead.";
        header("Location: ../group-details.php?id=" . $group_id);
        exit();
    }

    // 4. Perform the deletion
    $stmt_leave = $db->prepare("DELETE FROM study_group_members WHERE group_id = ? AND user_id = ?");
    $stmt_leave->execute([$group_id, $current_user_id]);

    if ($stmt_leave->rowCount() > 0) {
        $_SESSION['success'] = "You have successfully left the group.";
    } else {
        $_SESSION['error'] = "Failed to leave the group.";
    }
    
    header("Location: ../study-groups.php");
    exit();

} catch (Exception $e) {
    error_log("Leave group error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while leaving the group.";
    header("Location: ../group-details.php?id=" . $group_id);
    exit();
}
