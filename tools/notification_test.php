<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 20px auto; padding: 0 15px; }
        h1 { color: #1D4ED8; }
        .test-case { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; padding: 15px; }
        .test-case h2 { margin-top: 0; font-size: 1.2em; }
        .pass { color: #16a34a; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        .info { color: #666; font-size: 0.9em; margin-top: 5px; }
        .summary { font-size: 1.2em; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Notification System Test</h1>
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../includes/notifications.php';

$test_user_a_email = 'test_user_a@studdybuddy.com';
$test_user_b_email = 'test_user_b@studdybuddy.com';
$test_group_name = 'Notification Test Group';

$user_a = null;
$user_b = null;
$test_group = null;

$total_tests = 0;
$passed_tests = 0;

function test_case($name, $callback) {
    global $total_tests, $passed_tests;
    $total_tests++;
    echo "<div class='test-case'><h2>$name</h2>";
    try {
        $result = $callback();
        if ($result) {
            echo "<p class='pass'>[PASS]</p>";
            $passed_tests++;
        }
    } catch (Exception $e) {
        echo "<p class='fail'>[FAIL]</p>";
        echo "<p class='info'>Exception: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

function assert_true($condition, $message) {
    if (!$condition) {
        echo "<p class='fail'>[FAIL]</p>";
        echo "<p class='info'>Assertion failed: $message</p>";
        throw new Exception($message);
    }
}

function setup_test_users() {
    global $db, $user_a, $user_b, $test_user_a_email, $test_user_b_email;
    $db = get_db();

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$test_user_a_email]);
    $user_a = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt->execute([$test_user_b_email]);
    $user_b = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_a) {
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['test_user_a', $test_user_a_email, password_hash('password', PASSWORD_DEFAULT), 'TestA', 'UserA']);
        $user_a = ['id' => $db->lastInsertId(), 'username' => 'test_user_a'];
    }
    if (!$user_b) {
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['test_user_b', $test_user_b_email, password_hash('password', PASSWORD_DEFAULT), 'TestB', 'UserB']);
        $user_b = ['id' => $db->lastInsertId(), 'username' => 'test_user_b'];
    }
    echo "<p class='info'>Test users are ready (UserA ID: {$user_a['id']}, UserB ID: {$user_b['id']}).</p>";
}

function cleanup() {
    global $db, $user_a, $user_b, $test_group_name;
    echo "<p class='info'>Cleaning up previous test data...</p>";
    
    // Delete notifications
    $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ? OR user_id = ?");
    $stmt->execute([$user_a['id'], $user_b['id']]);

    // Delete study requests
    $stmt = $db->prepare("DELETE FROM study_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$user_a['id'], $user_b['id'], $user_b['id'], $user_a['id']]);

    // Delete study partners
    $stmt = $db->prepare("DELETE FROM study_partners WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$user_a['id'], $user_b['id'], $user_b['id'], $user_a['id']]);

    // Delete group and invitations
    $stmt = $db->prepare("SELECT group_id FROM study_groups WHERE group_name = ? AND creator_id = ?");
    $stmt->execute([$test_group_name, $user_a['id']]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($group) {
        $group_id = $group['group_id'];
        $db->prepare("DELETE FROM study_group_members WHERE group_id = ?")->execute([$group_id]);
        $db->prepare("DELETE FROM study_group_invitations WHERE group_id = ?")->execute([$group_id]);
        $db->prepare("DELETE FROM chat_messages WHERE room_id IN (SELECT id FROM chat_rooms WHERE group_id = ?)")->execute([$group_id]);
        $db->prepare("DELETE FROM chat_rooms WHERE group_id = ?")->execute([$group_id]);
        $db->prepare("DELETE FROM study_groups WHERE group_id = ?")->execute([$group_id]);
    }
     echo "<p class='info'>Cleanup complete.</p>";
}

// --- Test Cases ---

setup_test_users();
cleanup();

test_case("Study Partner Request", function() {
    global $db, $user_a, $user_b;
    
    // 1. A sends request to B
    $stmt = $db->prepare("INSERT INTO study_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_a['id'], $user_b['id']]);
    $request_id = $db->lastInsertId();
    create_notification($user_b['id'], 'study_request', "Request from {$user_a['username']}", "dashboard.php");

    // 2. Check if B gets notification
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'study_request'");
    $stmt->execute([$user_b['id']]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true($notif, "User B should receive a 'study_request' notification.");

    // 3. B accepts request
    $stmt = $db->prepare("UPDATE study_requests SET status = 'accepted' WHERE request_id = ?");
    $stmt->execute([$request_id]);
    create_notification($user_a['id'], 'study_request_accepted', "{$user_b['username']} accepted", "user_profile.php?id={$user_b['id']}");

    // 4. Check if A gets notification
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'study_request_accepted'");
    $stmt->execute([$user_a['id']]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true($notif, "User A should receive a 'study_request_accepted' notification.");

    return true;
});

test_case("Group Invitation", function() {
    global $db, $user_a, $user_b, $test_group_name;
    
    // 1. A creates a group
    $stmt = $db->prepare("INSERT INTO study_groups (group_name, description, creator_id) VALUES (?, ?, ?)");
    $stmt->execute([$test_group_name, 'Test Desc', $user_a['id']]);
    $group_id = $db->lastInsertId();

    // 2. A invites B
    $stmt = $db->prepare("INSERT INTO study_group_invitations (group_id, sender_id, receiver_id, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$group_id, $user_a['id'], $user_b['id']]);
    $invitation_id = $db->lastInsertId();
    create_notification($user_b['id'], 'group_invitation', "Invitation to {$test_group_name}", "my-groups.php");

    // 3. Check if B gets notification
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'group_invitation'");
    $stmt->execute([$user_b['id']]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true($notif, "User B should receive a 'group_invitation' notification.");

    // 4. B declines invitation
    $stmt = $db->prepare("UPDATE study_group_invitations SET status = 'declined' WHERE id = ?");
    $stmt->execute([$invitation_id]);
    create_notification($user_a['id'], 'group_invite_declined', "{$user_b['username']} declined", "group-details.php?id=$group_id");

    // 5. Check if A gets notification
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'group_invite_declined'");
    $stmt->execute([$user_a['id']]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true($notif, "User A should receive a 'group_invite_declined' notification.");

    return true;
});

test_case("Direct & Group Messages", function() {
    global $db, $user_a, $user_b, $test_group_name;

    // Direct Message
    // 1. Create a direct chat room
    $stmt = $db->prepare("INSERT INTO chat_rooms (room_type, partner1_id, partner2_id) VALUES ('direct', ?, ?)");
    $stmt->execute([$user_a['id'], $user_b['id']]);
    $room_id = $db->lastInsertId();

    // 2. A sends message to B
    create_notification($user_b['id'], 'direct_message', 'New message from A', "chat.php?user_id={$user_a['id']}");
    
    // 3. Check if B gets notification
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'direct_message'");
    $stmt->execute([$user_b['id']]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true($notif, "User B should receive a 'direct_message' notification.");

    // Group Message
    // 1. Get group and add B to it
    $stmt = $db->prepare("SELECT group_id FROM study_groups WHERE group_name = ?");
    $stmt->execute([$test_group_name]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    $group_id = $group['group_id'];
    $stmt = $db->prepare("INSERT INTO study_group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
    $stmt->execute([$group_id, $user_b['id']]);

    // 2. A sends a message to the group
    create_notification($user_b['id'], 'group_message', 'New message in group', "group-chat.php?group_id=$group_id");

    // 3. Check if B gets notification
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND type = 'group_message'");
    $stmt->execute([$user_b['id']]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);
    assert_true($notif, "User B should receive a 'group_message' notification.");

    return true;
});

?>
    <div class="summary">
        <?php
            echo "Test Summary: $passed_tests / $total_tests passed.";
            if ($passed_tests === $total_tests) {
                echo " <span class='pass'>All tests passed!</span>";
            } else {
                echo " <span class='fail'>Some tests failed.</span>";
            }
        ?>
    </div>
</body>
</html>