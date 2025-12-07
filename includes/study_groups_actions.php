<?php
// This file handles all POST requests for study-groups.php

// Ensure session and DB are available
if (!isset($_SESSION)) {
    session_start();
}
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/notifications.php';

// Get database connection
try {
    $db = get_db();
} catch (Exception $e) {
    error_log("DB error in study_groups_actions.php: " . $e->getMessage());
    $_SESSION['error'] = "Database connection failed. Please try again later.";
    header('Location: study-groups.php');
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle group invitation response
    if (isset($_POST['invitation_action'])) {
        $action = $_POST['invitation_action'];
        $invitation_id = (int)($_POST['invitation_id'] ?? 0);
        
        if ($invitation_id) {
            try {
                $db->beginTransaction();

                // Get invitation details BEFORE updating
                $inv_stmt = $db->prepare("
                    SELECT sgi.group_id, sgi.sender_id, sg.name as group_name 
                    FROM study_group_invitations sgi
                    JOIN study_groups sg ON sgi.group_id = sg.group_id
                    WHERE sgi.id = ? AND sgi.receiver_id = ?
                ");
                $inv_stmt->execute([$invitation_id, $current_user_id]);
                $inv = $inv_stmt->fetch(PDO::FETCH_ASSOC);

                if ($inv) {
                    $new_status = ($action === 'accept') ? 'accepted' : 'declined';
                    $stmt = $db->prepare(
                        "UPDATE study_group_invitations 
                        SET status = ?, responded_at = NOW()
                        WHERE id = ? AND receiver_id = ?"
                    );
                    $stmt->execute([$new_status, $invitation_id, $current_user_id]);
                    
                    $responder_username = $_SESSION['username'] ?? 'A user';
                    $group_name = $inv['group_name'];
                    $inviter_id = $inv['sender_id'];
                    $group_id = $inv['group_id'];

                    if ($action === 'accept') {
                        // Add user as member
                        $check_stmt = $db->prepare("SELECT 1 FROM study_group_members WHERE group_id = ? AND user_id = ?");
                        $check_stmt->execute([$group_id, $current_user_id]);
                        if (!$check_stmt->fetch()) {
                            $member_stmt = $db->prepare(
                                "INSERT INTO study_group_members (group_id, user_id, role, joined_at)
                                VALUES (?, ?, 'member', NOW())"
                            );
                            $member_stmt->execute([$group_id, $current_user_id]);
                        }
                        $_SESSION['success'] = "You joined the study group!";
                        $notification_message = htmlspecialchars($responder_username) . " accepted your invitation to join \"" . htmlspecialchars($group_name) . "\".";
                        create_notification($inviter_id, 'group_invite_accepted', $notification_message, "group-details.php?id=$group_id");

                    } else {
                        $_SESSION['success'] = "Invitation declined.";
                        $notification_message = htmlspecialchars($responder_username) . " declined your invitation to join \"" . htmlspecialchars($group_name) . "\".";
                        create_notification($inviter_id, 'group_invite_declined', $notification_message, "group-details.php?id=$group_id");
                    }
                }
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Invitation response error: " . $e->getMessage());
                $_SESSION['error'] = "Failed to process invitation.";
            }
        }
        header("Location: my-groups.php");
        exit();
    }


    // Handle group creation
    if (isset($_POST['create_group'])) {
        $name = trim($_POST['group_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (!empty($name)) {
            try {
                $db->beginTransaction();

                $stmt_group = $db->prepare(
                    "INSERT INTO study_groups (group_name, description, creator_id, created_at)
                    VALUES (?, ?, ?, NOW())"
                );
                $stmt_group->execute([$name, $description, $current_user_id]);
                $group_id = $db->lastInsertId();

                // Add creator as the first member
                $member_stmt = $db->prepare("INSERT INTO study_group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())");
                $member_stmt->execute([$group_id, $current_user_id]);

                // Create a chat room for the group
                $room_stmt = $db->prepare("INSERT INTO chat_rooms (room_type, group_id) VALUES ('group', ?)");
                $room_stmt->execute([$group_id]);

                $db->commit();
                $_SESSION['success'] = "Study group created successfully!";
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Create group error: " . $e->getMessage());
                $_SESSION['error'] = "Failed to create group.";
            }
        } else {
            $_SESSION['error'] = "Group name is required.";
        }
        header('Location: study-groups.php');
        exit();
    }


    if (isset($_POST['quick_match'])) {
        // file_put_contents('study_groups.log', 'Quick match request received for user ' . $current_user_id . "\n", FILE_APPEND);
        try {
            $db->beginTransaction();

            // Ensure current user is in queue only once
            $ins = $db->prepare("INSERT IGNORE INTO quick_match_queue (user_id, requested_at, status) VALUES (?, NOW(), 'open')");
            $ins->execute([$current_user_id]);

            // Attempt to find the oldest waiting other user (lock selected row)
            $sel = $db->prepare("SELECT id, user_id FROM quick_match_queue WHERE status = 'open' AND user_id != ? ORDER BY requested_at ASC LIMIT 1 FOR UPDATE");
            $sel->execute([$current_user_id]);
            $otherRow = $sel->fetch(PDO::FETCH_ASSOC);

            if ($otherRow) {
                $other_id = (int)$otherRow['user_id'];

                // Mark both queue entries as matched (prevent concurrent matches)
                $u1 = $db->prepare("UPDATE quick_match_queue SET status = 'matched' WHERE user_id = ? AND status = 'open'");
                $u1->execute([$current_user_id]);
                $u2 = $db->prepare("UPDATE quick_match_queue SET status = 'matched' WHERE user_id = ? AND status = 'open'");
                $u2->execute([$other_id]);

                // Create a study request from the current user to the matched user
                $create_request = $db->prepare("INSERT INTO study_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
                $create_request->execute([$current_user_id, $other_id]);
            }

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("Quick match error: " . $e->getMessage());
        }

        header('Location: study-groups.php');
        exit();
    }

    if (isset($_POST['cancel_match'])) {
        try {
            $del = $db->prepare("DELETE FROM quick_match_queue WHERE user_id = ? AND status = 'open'");
            $del->execute([$current_user_id]);
        } catch (Exception $e) {
            error_log("Cancel quick match error: " . $e->getMessage());
        }
        header('Location: study-groups.php');
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'cancel_study_request') {
        $request_id = (int)$_POST['request_id'];
        try {
            // Ensure the user can only cancel their own pending requests
            $stmt = $db->prepare("DELETE FROM study_requests WHERE request_id = ? AND sender_id = ? AND status = 'pending'");
            $stmt->execute([$request_id, $current_user_id]);
        } catch (Exception $e) {
            error_log("Error canceling study request: " . $e->getMessage());
        }
        // Redirect back to the same search if applicable
        $redirect_url = 'study-groups.php';
        if (isset($_POST['original_search_user']) && !empty($_POST['original_search_user'])) {
            $redirect_url .= '?search_user=' . urlencode($_POST['original_search_user']);
        }
        header('Location: ' . $redirect_url);
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'send_study_request') {
    $receiver_id = (int)$_POST['receiver_id'];
    $sender_id = $current_user_id;
    // 检查是否有原始搜索词，以便跳转回去
    $original_search = $_POST['original_search_user'] ?? '';
    $redirect_url = 'study-groups.php'; 

    try {
        // 【修改】检查任何已存在的请求，无论状态如何
        $stmt_check = $db->prepare("SELECT * FROM study_requests WHERE sender_id = ? AND receiver_id = ?");
        $stmt_check->execute([$sender_id, $receiver_id]);
        $existing_request = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_request) {
            // 如果已存在请求
            if($existing_request['status'] === 'pending') {
                $info_message = "You have already sent a pending request to this user.";
            } else {
                // 如果是 'accepted' 或 'declined'，我们可以重置它
                $stmt_reset = $db->prepare("UPDATE study_requests SET status = 'pending', requested_at = NOW(), responded_at = NULL WHERE request_id = ?");
                $stmt_reset->execute([$existing_request['request_id']]);
                $success_message = "Study request has been sent again.";
            }
        } else {
            // 如果不存在任何请求，则插入新记录
            $stmt_insert = $db->prepare("INSERT INTO study_requests (sender_id, receiver_id, status, requested_at) VALUES (?, ?, 'pending', NOW())");
            $stmt_insert->execute([$sender_id, $receiver_id]);
            $success_message = "Study request sent successfully!";

            // Create a notification for the receiver
            $sender_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
            $notification_message = htmlspecialchars($sender_name) . " wants to be your study partner.";
            $stmt_notify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt_notify->execute([$receiver_id, $notification_message]);
        }

    } catch (Exception $e) {
        error_log("Error sending study request: " . $e->getMessage());
        $error_message = "Failed to send study request. Please try again.";
    }
    
    // 构造跳转URL
    $query_params = [];
    if (!empty($original_search)) {
        $query_params['search_user'] = $original_search;
    }
    if (isset($success_message)) {
        $query_params['success'] = 1;
    }
    if (isset($info_message)) {
        $query_params['info'] = 1;
    }
    if (isset($error_message)) {
        $query_params['error'] = 1;
    }

    if (!empty($query_params)) {
        $redirect_url .= '?' . http_build_query($query_params);
    }

    header('Location: ' . $redirect_url);
    exit();
}

// Handle accepting/declining study requests
if (isset($_POST['action']) && ($_POST['action'] === 'accept_request' || $_POST['action'] === 'decline_request')) {
    $request_id = (int)$_POST['request_id'];
    $new_status = $_POST['action'] === 'accept_request' ? 'accepted' : 'declined';

    try {
        $db->beginTransaction();

        $stmt_req = $db->prepare("SELECT sender_id, receiver_id FROM study_requests WHERE request_id = ? AND receiver_id = ? AND status = 'pending' FOR UPDATE");
        $stmt_req->execute([$request_id, $current_user_id]);
        $request_data = $stmt_req->fetch(PDO::FETCH_ASSOC);
        
        if ($request_data) {
            $stmt_update = $db->prepare("UPDATE study_requests SET status = ?, responded_at = NOW() WHERE request_id = ?");
            $stmt_update->execute([$new_status, $request_id]);

            if ($new_status === 'accepted') {
                $sender_id = (int)$request_data['sender_id'];
                $receiver_id = (int)$request_data['receiver_id'];

                // BUG FIX: Clean up all other pending requests between these two users
                $stmt_cleanup = $db->prepare("DELETE FROM study_requests WHERE status = 'pending' AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
                $stmt_cleanup->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);

                $user1 = min($sender_id, $receiver_id);
                $user2 = max($sender_id, $receiver_id);

                $check_partner_stmt = $db->prepare("SELECT id FROM study_partners WHERE user1_id = ? AND user2_id = ? AND is_active = 1");
                $check_partner_stmt->execute([$user1, $user2]);
                
                if (!$check_partner_stmt->fetch()) {
                    $metadata = json_encode(['group' => [$receiver_id, $sender_id]]);
                    $create_session = $db->prepare("INSERT INTO study_sessions (user_id, is_group, metadata, started_at, status) VALUES (?, 1, ?, NOW(), 'active')");
                    $create_session->execute([$receiver_id, $metadata]);

                    $partner_stmt = $db->prepare(
                        "INSERT INTO study_partners (user1_id, user2_id, is_active, last_activity) 
                        VALUES (?, ?, 1, NOW())
                        ON DUPLICATE KEY UPDATE is_active = 1, last_activity = NOW()"
                    );
                    $partner_stmt->execute([$user1, $user2]);
                }
                $_SESSION['success'] = "Study request accepted!";
            } else {
                $_SESSION['success'] = "Study request declined.";
            }
        } else {
            $_SESSION['error'] = "Could not find the study request. It may have been canceled.";
        }
        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log("Error handling study request: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }
    
    header('Location: study-groups.php');
    exit();
}
} // Closes the main POST request block
