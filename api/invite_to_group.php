<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

// 1. Check authentication and request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$user_to_invite = (int)($_POST['user_to_invite'] ?? 0);
$group_id = (int)($_POST['group_id'] ?? 0);

// Redirect path in case of errors or success
$redirect_url = '../user_profile.php?id=' . $user_to_invite;

// 2. Validate input
if ($user_to_invite <= 0 || $group_id <= 0) {
    $_SESSION['error'] = "Invalid user or group specified.";
    header("Location: " . $redirect_url);
    exit();
}

if ($current_user_id === $user_to_invite) {
    $_SESSION['error'] = "You cannot invite yourself to a group.";
    header("Location: " . $redirect_url);
    exit();
}

try {
    $db = get_db();

    // 3. Security Check: Verify the inviting user is a member of the group
    $stmt_check_membership = $db->prepare("SELECT 1 FROM study_group_members WHERE group_id = ? AND user_id = ?");
    $stmt_check_membership->execute([$group_id, $current_user_id]);
    if (!$stmt_check_membership->fetch()) {
        $_SESSION['error'] = "You do not have permission to invite users to this group.";
        header("Location: " . $redirect_url);
        exit();
    }

    // 4. Check if the user is already a member
    $stmt_check_existing_member = $db->prepare("SELECT 1 FROM study_group_members WHERE group_id = ? AND user_id = ?");
    $stmt_check_existing_member->execute([$group_id, $user_to_invite]);
    if ($stmt_check_existing_member->fetch()) {
        $_SESSION['info'] = "This user is already a member of the group.";
        header("Location: " . $redirect_url);
        exit();
    }

    // 5. Check for an existing pending invitation
    $stmt_check_pending = $db->prepare("SELECT 1 FROM study_group_invitations WHERE group_id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt_check_pending->execute([$group_id, $user_to_invite]);
    if ($stmt_check_pending->fetch()) {
        $_SESSION['info'] = "An invitation has already been sent to this user for this group.";
        header("Location: " . $redirect_url);
        exit();
    }

    // 6. Create the invitation
    $stmt_invite = $db->prepare("
        INSERT INTO study_group_invitations (group_id, sender_id, receiver_id, status, invited_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt_invite->execute([$group_id, $current_user_id, $user_to_invite]);

    $_SESSION['success'] = "Invitation sent successfully!";
    header("Location: " . $redirect_url);
    exit();

} catch (Exception $e) {
    error_log("Invite to group error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while sending the invitation.";
    header("Location: " . $redirect_url);
    exit();
}
