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

    // 2. Verify user is the creator of the group
    $stmt_verify = $db->prepare("SELECT creator_id FROM study_groups WHERE group_id = ?");
    $stmt_verify->execute([$group_id]);
    $group = $stmt_verify->fetch(PDO::FETCH_ASSOC);

    if (!$group || $group['creator_id'] != $current_user_id) {
        $_SESSION['error'] = "You do not have permission to delete this group.";
        header("Location: ../group-details.php?id=" . $group_id);
        exit();
    }

    // 3. Begin transaction for safe deletion
    $db->beginTransaction();

    // Step A: Get the associated chat room ID before deleting it
    $stmt_get_room = $db->prepare("SELECT id FROM chat_rooms WHERE group_id = ?");
    $stmt_get_room->execute([$group_id]);
    $room = $stmt_get_room->fetch(PDO::FETCH_ASSOC);

    // Step B: Delete from study_group_invitations
    $stmt_del_invites = $db->prepare("DELETE FROM study_group_invitations WHERE group_id = ?");
    $stmt_del_invites->execute([$group_id]);

    // Step C: If a chat room exists, delete its messages
    if ($room) {
        $room_id = $room['id'];
        $stmt_del_messages = $db->prepare("DELETE FROM chat_messages WHERE room_id = ?");
        $stmt_del_messages->execute([$room_id]);
    }

    // Step D: Delete from chat_rooms
    $stmt_del_room = $db->prepare("DELETE FROM chat_rooms WHERE group_id = ?");
    $stmt_del_room->execute([$group_id]);

    // Step E: Delete from study_group_members
    $stmt_del_members = $db->prepare("DELETE FROM study_group_members WHERE group_id = ?");
    $stmt_del_members->execute([$group_id]);

    // Step F: Finally, delete the group itself
    $stmt_del_group = $db->prepare("DELETE FROM study_groups WHERE group_id = ?");
    $stmt_del_group->execute([$group_id]);

    // 4. Commit transaction
    $db->commit();

    $_SESSION['success'] = "Group has been permanently deleted.";
    header("Location: ../study-groups.php");
    exit();

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete group error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while deleting the group.";
    header("Location: ../group-details.php?id=" . $group_id);
    exit();
}
