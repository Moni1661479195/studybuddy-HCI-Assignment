<?php



if (isset($_GET['partial'])) {
    if ($_GET['partial'] === 'recommendations') {
        require_once 'session.php';
        if (!isset($_SESSION['user_id'])) { exit('Authentication required.'); }
        require_once __DIR__ . '/lib/db.php';
        $current_user_id = (int)$_SESSION['user_id'];
        $db = get_db();
        ob_start();
        
        // Show recommended users or random users when no search is performed
        $suggested_users = [];
        $show_random = false;

        $stmt_suggested = $db->prepare(" 
            SELECT u.id, u.first_name, u.last_name, u.email, r.score
            FROM recommendations r
            JOIN users u ON r.candidate_user_id = u.id
            WHERE r.user_id = ?
            ORDER BY r.score DESC
            LIMIT 5
        ");
        $stmt_suggested->execute([$current_user_id]);
        $suggested_users = $stmt_suggested->fetchAll(PDO::FETCH_ASSOC);

        if (empty($suggested_users)) {
            $stmt_suggested = $db->prepare("SELECT id, first_name, last_name, email FROM users WHERE id != ? ORDER BY RAND() LIMIT 5");
            $stmt_suggested->execute([$current_user_id]);
            $suggested_users = $stmt_suggested->fetchAll(PDO::FETCH_ASSOC);
            $show_random = true;
        } else {
            $show_random = false;
        }
        
        if ($suggested_users): ?>
            <div class="recommendations-header">
                <h3 class="recommendations-title">
                    <?php echo ($show_random) ? 'Available Study Partners' : 'Recommended Study Partners'; ?>
                </h3>
                <button type="button" id="refresh-suggestions-button" class="cta-button primary small">Refresh</button>
            </div>
            <p style="margin-bottom: 1rem; color: #6b7280; font-size: 0.9rem;">
                <i class="fas fa-lightbulb"></i> 
                <?php echo ($show_random) ? 'Here are some other users you might be interested in.' : 'These users are recommended based on your interests, skill level, and study patterns.'; ?>
            </p>
            <div class="user-results-list">
                <?php foreach ($suggested_users as $user): 
                    // Check if a request has already been sent
                    $stmt_check_request = $db->prepare("SELECT * FROM study_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
                    $stmt_check_request->execute([$current_user_id, $user['id']]);
                    $existing_request = $stmt_check_request->fetch(PDO::FETCH_ASSOC);
                    
                    $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                ?>
                    <div class="user-result-item">
                        <div class="user-info">
                            <div class="user-avatar"><?php echo $initials; ?></div>
                            <div class="user-details">
                                <h4>
                                    <a href="user_profile.php?id=<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </a>
                                    <?php if (isset($user['score']) && $user['score']):
                                    ?>
                                        <span style="color: #10b981; font-size: 0.8rem; font-weight: 500;">
                                            (<?php echo number_format($user['score'] * 100, 1); ?>% match)
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        <div class="request-status">
                            <?php if ($existing_request): ?>
                                <span class="status-text pending">
                                    <i class="fas fa-clock"></i> Request Sent
                                </span>
                                <form action="study-groups.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="cancel_study_request">
                                    <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                                    <button type="submit" class="cta-button danger small">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="study-groups.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="send_study_request">
                                    <input type="hidden" name="receiver_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="cta-button primary small">
                                        <i class="fas fa-paper-plane"></i> Send Request
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif;
        echo ob_get_clean();
        exit();
    }

    require_once 'session.php';
    if (!isset($_SESSION['user_id'])) { exit('Authentication required.'); }
    require_once __DIR__ . '/lib/db.php';
    $current_user_id = (int)$_SESSION['user_id'];
    $db = get_db();

    if ($_GET['partial'] === 'requests') {
        ob_start();
        
        $stmt_received = $db->prepare(" 
            SELECT sr.request_id, sr.sender_id, sr.requested_at, u.first_name, u.last_name, u.email 
            FROM study_requests sr 
            JOIN users u ON sr.sender_id = u.id 
            WHERE sr.receiver_id = ? AND sr.status = 'pending' 
            ORDER BY sr.requested_at DESC
        ");
        $stmt_received->execute([$current_user_id]);
        $received_requests = $stmt_received->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($received_requests)): ?>
            <h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>
            <div class="requests-list">
                <?php foreach ($received_requests as $request): 
                    $sender_initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                ?>
                    <div class="request-item">
                        <div class="user-info">
                            <div class="user-avatar"><?php echo $sender_initials; ?></div>
                            <div class="user-details">
                                <h4><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h4>
                                <p><?php echo htmlspecialchars($request['email']); ?></p>
                            </div>
                        </div>
                        <div class="request-actions">
                            <form action="study-groups.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="accept_request"><input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>"><button type="submit" class="cta-button primary small">Accept</button></form>
                            <form action="study-groups.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="decline_request"><input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>"><button type="submit" class="cta-button danger small">Decline</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>
            <div class="alert info"><i class="fas fa-inbox"></i> No pending study requests.</div>
        <?php endif; 
        
        echo ob_get_clean();
        exit(); 
    }

    if ($_GET['partial'] === 'sent_requests') {
        ob_start();
        
        $stmt_sent = $db->prepare(" 
            SELECT sr.request_id, u.id AS user_id, u.first_name, u.last_name, u.email
            FROM study_requests sr
            JOIN users u ON sr.receiver_id = u.id
            WHERE sr.sender_id = ? AND sr.status = 'pending'
        ");
        $stmt_sent->execute([$current_user_id]);
        $sent_requests = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($sent_requests)): ?>
            <h2 class="section-title"><i class="fas fa-paper-plane"></i> Sent Study Requests</h2>
            <div class="user-results-list">
                <?php foreach ($sent_requests as $request): ?>
                    <div class="user-result-item">
                        <div class="user-info">
                            <div class="user-avatar"><?php echo strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1)); ?></div>
                            <div class="user-details">
                                <h4><a href="user_profile.php?id=<?php echo $request['user_id']; ?>"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></a></h4>
                                <p><?php echo htmlspecialchars($request['email']); ?></p>
                            </div>
                        </div>
                        <div class="request-status">
                            <span class="status-text pending"><i class="fas fa-clock"></i> Request Sent</span>
                            <form action="study-groups.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="cancel_study_request"><input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>"><button type="submit" class="cta-button danger small">Cancel</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php endif;

        echo ob_get_clean();
        exit();
    }
}


require_once 'session.php';

// Prevent browser caching so Back button forces a fresh request (and triggers session check)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies

// Add these new cache-busting headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/lib/db.php';

// Helper function to highlight search terms
function highlight_search_term(string $text, string $term): string {
    if (empty($term)) {
        return htmlspecialchars($text);
    }
    $term = preg_quote($term, '/');
    return preg_replace("/($term)/i", '<span class="highlight">$1</span>', htmlspecialchars($text));
}

try {
    $db = get_db();
} catch (Exception $e) {
    error_log("DB error on study-groups page: " . $e->getMessage());
    // show a simple message and stop — avoids sending partial HTML then trying to redirect
    http_response_code(500);
    echo "Database connection failed. Please try again later.";
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// Handle search redirect for exact match
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_user']) && !empty(trim($_GET['search_user']))) {
    $search_user_redirect = trim($_GET['search_user']);
    $search_lower_redirect = "%" . strtolower($search_user_redirect) . "%";

    $stmt_redirect = $db->prepare("
        SELECT id, first_name, last_name, email FROM users
        WHERE (
            LOWER(first_name) LIKE ? OR
            LOWER(last_name) LIKE ? OR
            LOWER(email) LIKE ? OR
            LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?
        ) AND id != ?
        LIMIT 10
    ");
    $stmt_redirect->execute([$search_lower_redirect, $search_lower_redirect, $search_lower_redirect, $search_lower_redirect, $current_user_id]);
    $found_users_redirect = $stmt_redirect->fetchAll(PDO::FETCH_ASSOC);

    if (count($found_users_redirect) === 1) {
        $user_redirect = $found_users_redirect[0];
        if (
            strtolower($user_redirect['first_name'] . ' ' . $user_redirect['last_name']) === strtolower($search_user_redirect) ||
            strtolower($user_redirect['email']) === strtolower($search_user_redirect)
        ) {
            header('Location: user_profile.php?id=' . $user_redirect['id']);
            exit();
        }
    }
}

// Fetch study mates
$stmt_mates = $db->prepare("
    SELECT 
        u.id, 
        u.first_name, 
        u.last_name, 
        u.email 
    FROM study_partners sp
    JOIN users u ON u.id = IF(sp.user1_id = ?, sp.user2_id, sp.user1_id)
    WHERE (sp.user1_id = ? OR sp.user2_id = ?) AND sp.is_active = 1
");
$stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
$study_mates = $stmt_mates->fetchAll(PDO::FETCH_ASSOC);
$stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
$study_mates = $stmt_mates->fetchAll(PDO::FETCH_ASSOC);
$stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
$study_mate_ids = $stmt_mates->fetchAll(PDO::FETCH_COLUMN, 0);

// Get pending group invitations
$invitations_stmt = $db->prepare("
    SELECT 
        sgi.*,
        sg.group_name as group_name,
        CONCAT(u.first_name, ' ', u.last_name) as sender_name
    FROM study_group_invitations sgi
    JOIN study_groups sg ON sgi.group_id = sg.group_id
    JOIN users u ON sgi.sender_id = u.id
    WHERE sgi.receiver_id = ? AND sgi.status = 'pending'
    ORDER BY sgi.invited_at DESC
");
$invitations_stmt->execute([$current_user_id]);
$invitations = $invitations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending received study requests
$stmt_received = $db->prepare(" 
    SELECT sr.request_id, sr.sender_id, sr.requested_at, u.first_name, u.last_name, u.email 
    FROM study_requests sr 
    JOIN users u ON sr.sender_id = u.id 
    WHERE sr.receiver_id = ? AND sr.status = 'pending' 
    ORDER BY sr.requested_at DESC
");
$stmt_received->execute([$current_user_id]);
$received_requests = $stmt_received->fetchAll(PDO::FETCH_ASSOC);

// Handle quick match POSTs before any output so we can use Location headers reliably.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle group invitation response
    if (isset($_POST['invitation_action'])) {
        $action = $_POST['invitation_action'];
        $invitation_id = (int)($_POST['invitation_id'] ?? 0);
        
        if ($invitation_id) {
            try {
                $db->beginTransaction();

                $new_status = ($action === 'accept') ? 'accepted' : 'declined';
                $stmt = $db->prepare("
                    UPDATE study_group_invitations 
                    SET status = ?, responded_at = NOW()
                    WHERE id = ? AND receiver_id = ?
                ");
                $stmt->execute([$new_status, $invitation_id, $current_user_id]);
                
                if ($action === 'accept') {
                    // Get group ID and add user as member
                    $inv_stmt = $db->prepare("SELECT group_id FROM study_group_invitations WHERE id = ?");
                    $inv_stmt->execute([$invitation_id]);
                    $inv = $inv_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($inv) {
                        // Check if not already a member to be safe
                        $check_stmt = $db->prepare("SELECT 1 FROM study_group_members WHERE group_id = ? AND user_id = ?");
                        $check_stmt->execute([$inv['group_id'], $current_user_id]);
                        if (!$check_stmt->fetch()) {
                            $member_stmt = $db->prepare("
                                INSERT INTO study_group_members (group_id, user_id, role, joined_at)
                                VALUES (?, ?, 'member', NOW())
                            ");
                            $member_stmt->execute([$inv['group_id'], $current_user_id]);
                        }
                    }
                    $_SESSION['success'] = "You joined the study group!";
                } else {
                    $_SESSION['success'] = "Invitation declined.";
                }
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Invitation response error: " . $e->getMessage());
                $_SESSION['error'] = "Failed to process invitation.";
            }
        }
        header("Location: study-groups.php");
        exit();
    }


    // Handle group creation
    if (isset($_POST['create_group'])) {
        $name = trim($_POST['group_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (!empty($name)) {
            try {
                $db->beginTransaction();

                $stmt_group = $db->prepare("
                    INSERT INTO study_groups (group_name, description, creator_id, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
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

                // Create a study session record for the pair
                $metadata = json_encode(['group' => [$current_user_id, $other_id]]);
                $create = $db->prepare("INSERT INTO study_sessions (user_id, is_group, metadata, started_at) VALUES (?, 1, ?, NOW())");
                $create->execute([$current_user_id, $metadata]);
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

                    $partner_stmt = $db->prepare("
                        INSERT INTO study_partners (user1_id, user2_id, is_active, last_activity) 
                        VALUES (?, ?, 1, NOW())
                        ON DUPLICATE KEY UPDATE is_active = 1, last_activity = NOW()
                    ");
                    $partner_stmt->execute([$user1, $user2]);
                }
            }
        }
        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log("Error handling study request: " . $e->getMessage());
    }
    
    header('Location: study-groups.php');
    exit();
}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Study Groups</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/responsive.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Match Modal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/study-groups.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-card">
            <h1 class="welcome-title">My Study Groups</h1>
            <p class="welcome-message">Connect with your study partners and collaborate on your learning journey.</p>

                        <?php

                        // Display success/error messages
            if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> Study request sent successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['info']) && $_GET['info'] == '1'): ?>
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> You have already sent a request to this user.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i> Failed to send study request. Please try again.
                </div>
            <?php endif; ?>

            <?php
            // Get user's permanent groups (ultra-safe N+1 query approach, PK fix)
            $stmt_get_ids = $db->prepare("SELECT group_id FROM study_group_members WHERE user_id = ?");
            $stmt_get_ids->execute([$current_user_id]);
            $group_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);

            $my_groups = [];
            if (!empty($group_ids)) {
                $in_clause = implode(',', array_fill(0, count($group_ids), '?'));
                // Step 1: Get the groups
                $groups_stmt = $db->prepare("
                    SELECT *
                    FROM study_groups
                    WHERE group_id IN ($in_clause) AND is_active = 1
                    ORDER BY created_at DESC
                ");
                $groups_stmt->execute($group_ids);
                $my_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

                // Step 2: Get member count for each group
                $count_stmt = $db->prepare("SELECT COUNT(*) FROM study_group_members WHERE group_id = ?");
                foreach ($my_groups as $i => $group) {
                    $count_stmt->execute([$group['group_id']]);
                    $my_groups[$i]['member_count'] = $count_stmt->fetchColumn();
                }
            }
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="section-title"><i class="fas fa-layer-group"></i> My Study Groups</h2>
                <button onclick="openCreateModal()" class="cta-button secondary small">
                    <i class="fas fa-plus"></i> Add Group
                </button>
            </div>

            <?php if (empty($my_groups)):
            ?>
                <div class="alert info" style="margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i> You are not a member of any study groups yet.
                </div>
            <?php else:
            ?>
                <div class="study-groups-list">
                    <?php foreach ($my_groups as $group):
                        // Get chat room for this group
                        $room_stmt = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'group' AND group_id = ?");
                        $room_stmt->execute([$group['group_id']]);
                        $room = $room_stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                        <div class="study-group-item">
                            <i class="fas fa-users group-icon"></i>
                            <div class="group-details">
                                <h3><?php echo htmlspecialchars($group['group_name']); ?></h3>
                                <p><?php echo $group['member_count']; ?> members</p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <?php if ($room): ?>
                                    <a href="group-chat.php?room_id=<?php echo $room['id']; ?>" class="cta-button secondary small">Group Chat</a>
                                <?php endif; ?>
                                <a href="group-details.php?id=<?php echo $group['group_id']; ?>" class="cta-button secondary small">Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Pending Group Invitations -->
            <?php if (!empty($invitations)):
            ?>
            <div style="margin-top: 2rem;">
                <h2 class="section-title"><i class="fas fa-envelope"></i> Group Invitations</h2>
                <div class="study-groups-list">
                    <?php foreach ($invitations as $inv):
                    ?>
                        <div class="study-group-item">
                            <i class="fas fa-envelope-open-text group-icon"></i>
                            <div class="group-details">
                                <h3><?php echo htmlspecialchars($inv['group_name']); ?></h3>
                                <p>Invited by: <?php echo htmlspecialchars($inv['sender_name']); ?></p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <form method="POST" action="study-groups.php" style="margin: 0;" class="no-ajax">
                                    <input type="hidden" name="invitation_action" value="accept">
                                    <input type="hidden" name="invitation_id" value="<?php echo $inv['id']; ?>">
                                    <button type="submit" class="cta-button primary small">Accept</button>
                                </form>
                                <form method="POST" action="study-groups.php" style="margin: 0;" class="no-ajax">
                                    <input type="hidden" name="invitation_action" value="decline">
                                    <input type="hidden" name="invitation_id" value="<?php echo $inv['id']; ?>">
                                    <button type="submit" class="cta-button danger small">Decline</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

                <!-- Quick Match Modal -->
    <div id="quick-match-modal" class="quick-match-modal">
        
        <div class="modal-content">
            
            <!-- Searching state -->
            <div id="searching-state">
                <div class="spinner-container">
                    <div class="spinner"></div>
                </div>
                <h2 class="searching-text">Finding your study partner...</h2>
                <p class="searching-description">
                    We're matching you with someone who shares your interests
                </p>
                <div id="queue-info" class="queue-info" style="display: none;">
                    <i class="fas fa-users"></i>
                    <span id="queue-count">0</span> users in queue
                </div>
                <button id="cancel-search" class="modal-button danger">
                    <i class="fas fa-times"></i> Cancel Search
                </button>
            </div>

            <!-- Match found state -->
            <div id="match-found-state" class="match-found">
                <div class="match-found-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="match-found-text">Match Found! 🎉</h2>
                <p class="searching-description">
                    You've been matched with a study partner!
                </p>
                
                <!-- Matched user card -->
                <div id="matched-user-card" class="matched-user-card">
                    <!-- Will be filled by JS -->
                </div>

                <div id="match-found-buttons" style="display: flex; gap: 1rem; justify-content: center;">
    <button id="send-request-from-match" class="modal-button primary">
        <i class="fas fa-paper-plane"></i> Send Study Request
    </button>
    <button id="view-profile-from-match" class="modal-button secondary">
        <i class="fas fa-user"></i> View Profile
    </button>
</div>
<button id="close-match-modal" class="modal-button danger" style="margin-top: 1rem;">Close</button>
            </div>
        </div>
    </div>

            <?php


            // Fetch sent study requests
            $stmt_sent_requests = $db->prepare(" 
                SELECT sr.request_id, u.id AS user_id, u.first_name, u.last_name, u.email
                FROM study_requests sr
                JOIN users u ON sr.receiver_id = u.id
                WHERE sr.sender_id = ? AND sr.status = 'pending'
            ");
            $stmt_sent_requests->execute([$current_user_id]);
            $sent_requests = $stmt_sent_requests->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($study_mates)):
            ?>
                <h2 class="section-title"><i class="fas fa-user-friends"></i> My Study Mates</h2>
                <div class="study-groups-list">
                    <?php foreach ($study_mates as $mate) {
                        $initials = strtoupper(substr($mate['first_name'], 0, 1) . substr($mate['last_name'], 0, 1));
                    ?>
                        <div class="study-group-item">
                            <div class="user-avatar"><?php echo $initials; ?></div>
                            <div class="group-details">
                                <h3><?php echo htmlspecialchars($mate['first_name'] . ' ' . $mate['last_name']); ?></h3>
                                <p><?php echo htmlspecialchars($mate['email']); ?></p>
                            </div>
                            <a href="user_profile.php?id=<?php echo $mate['id']; ?>" class="cta-button secondary small">View Profile</a>
                        </div>
                    <?php } ?>
                </div>
            <?php endif; ?>

            <div id="sent-requests-section">
            <?php if (!empty($sent_requests)):
            ?>
                <h2 class="section-title"><i class="fas fa-paper-plane"></i> Sent Study Requests</h2>
                <div class="user-results-list">
                    <?php foreach ($sent_requests as $request): ?>
                        <div class="user-result-item">
                            <div class="user-info">
                                <div class="user-avatar"><?php echo strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1)); ?></div>
                                <div class="user-details">
                                    <h4><a href="user_profile.php?id=<?php echo $request['user_id']; ?>"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></a></h4>
                                    <p><?php echo htmlspecialchars($request['email']); ?></p>
                                </div>
                            </div>
                            <div class="request-status">
                                <span class="status-text pending">
                                    <i class="fas fa-clock"></i> Request Sent
                                </span>
                                <form action="study-groups.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="cancel_study_request">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <button type="submit" class="cta-button danger small">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </div>

            <div id="requests-section">
            <?php if (!empty($received_requests)):
            ?>
                <h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>
                <div class="requests-list">
                    <?php foreach ($received_requests as $request):
                        $sender_initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                    ?>
                        <div class="request-item">
                            <div class="user-info">
                                <div class="user-avatar"><?php echo $sender_initials; ?></div>
                                <div class="user-details">
                                    <h4><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($request['email']); ?></p>
                                    <p style="font-size: 0.8rem; color: #9ca3af;">
                                        Sent: <?php echo date('M d, Y H:i', strtotime($request['requested_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="request-actions">
                                <form action="study-groups.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="accept_request">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <button type="submit" id="accept-request-<?php echo $request['request_id']; ?>" class="cta-button primary small">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                </form>
                                <form action="study-groups.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="decline_request">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <button type="submit" id="decline-request-<?php echo $request['request_id']; ?>" class="cta-button danger small">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>
                <div class="alert info">
                    <i class="fas fa-inbox"></i> No pending study requests.
                </div>
            <?php endif; ?>
            </div>

            <h2 class="section-title"><i class="fas fa-user-search"></i> Find Study Partners</h2>
            <div class="find-users-form">
                <div class="quick-match-form" style="text-align: center; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 0.75rem; background: #f8fafc;">
    <h3 style="margin-top: 0; font-size: 1.2rem; color: #1f2937;">Find a Study Partner Instantly</h3>
    <p style="color: #6b7280; margin: 0.5rem 0 1.5rem 0;">We'll match you with a partner based on your profile's skill level and interests.</p>
    
    <div style="margin: 1rem 0;">
        <label for="desired-skill-level-select" style="font-weight: 500; margin-right: 0.5rem;">I'm looking for a partner with skill level:</label>
        <select id="desired-skill-level-select" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #d1d5db;">
            <option value="any">Any Level</option>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
        </select>
    </div>
    
    <button type="button" id="start-quick-match-btn" class="cta-button primary large">
        <i class="fas fa-bolt"></i> Start Quick Match
    </button>
</div>
    
                <form id="search-form" action="study-groups.php" method="GET" class="search-form">
                    <input type="text" 
                           name="search_user" 
                           placeholder="Search by name or email..." 
                           value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>" 
                           required
                           autocomplete="off">
                    <button type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <div id="search-suggestions"></div>
                </form>
            </div>

            <div class="search-results">
                <?php
                if (isset($_GET['search_user']) && !empty($_GET['search_user'])) {
                    $search_user = trim($_GET['search_user']);
                    
                    // Enhanced search with multiple strategies
                    $search_term = "%$search_user%";
                    $search_lower = "%" . strtolower($search_user) . "%";
                    
                    // Try case-insensitive search first
                    $stmt = $db->prepare(" 
                        SELECT id, first_name, last_name, email FROM users 
                        WHERE (
                            LOWER(first_name) LIKE ? OR 
                            LOWER(last_name) LIKE ? OR 
                            LOWER(email) LIKE ? OR
                            LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?
                        ) AND id != ? 
                        ORDER BY 
                            CASE 
                                WHEN LOWER(first_name) = LOWER(?) THEN 1
                                WHEN LOWER(last_name) = LOWER(?) THEN 1
                                WHEN LOWER(first_name) LIKE ? THEN 2
                                WHEN LOWER(last_name) LIKE ? THEN 2
                                ELSE 3
                            END
                        LIMIT 10
                    ");
                    $stmt->execute([
                        $search_lower, $search_lower, $search_lower, $search_lower, $current_user_id,
                        $search_user, $search_user, $search_lower, $search_lower
                    ]);
                    $found_users = $stmt->fetchAll(PDO::FETCH_ASSOC);


                    
                    // If still no results, get recommendations from the system
                    if (empty($found_users)) {
                        $stmt_rec = $db->prepare(" 
                            SELECT u.id, u.first_name, u.last_name, u.email, r.score 
                            FROM recommendations r 
                            JOIN users u ON r.candidate_user_id = u.id 
                            WHERE r.user_id = ? 
                            ORDER BY r.score DESC 
                            LIMIT 10
                        ");
                        $stmt_rec->execute([$current_user_id]);
                        $found_users = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);
                        
                        // If no recommendations, show random users
                        if (empty($found_users)) {
                            $stmt_all = $db->prepare("SELECT id, first_name, last_name, email FROM users WHERE id != ? ORDER BY RAND() LIMIT 10");
                            $stmt_all->execute([$current_user_id]);
                            $found_users = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
                        }
                        $show_no_match_message = true;
                    }

                    if ($found_users): ?>
                        <?php if (isset($show_no_match_message)): ?>
                            <div class="alert info">
                                <i class="fas fa-info-circle"></i> No matches found for "<?php echo htmlspecialchars($search_user); ?>". 
                                <?php if (isset($stmt_rec) && $stmt_rec->rowCount() > 0): ?>
                                    Showing recommended study partners based on your profile:
                                <?php else: ?>
                                    Showing other available users:
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <h3 style="margin-bottom: 1rem; color: #1f2937;">Search Results for "<?php echo htmlspecialchars($search_user); ?>"</h3>
                        <?php endif; ?>
                        <div class="user-results-list">
                            <?php foreach ($found_users as $user) {
                                // Check if a request has already been sent
                                $stmt_check_request = $db->prepare("SELECT * FROM study_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
                                $stmt_check_request->execute([$current_user_id, $user['id'], $user['id'], $current_user_id]);
                                $existing_request = $stmt_check_request->fetch(PDO::FETCH_ASSOC);

                                $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                            ?>
                                <a href="user_profile.php?id=<?php echo $user['id']; ?>" class="user-result-item-link">
                                <div class="user-result-item">
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo $initials; ?></div>
                                        <div class="user-details">
                                            <h4>
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                <?php if (in_array($user['id'], $study_mate_ids)):
                                                ?>
                                                    <span class="badge" style="background-color: #10b981; color: white; padding: 0.2rem 0.5rem; border-radius: 0.5rem; font-size: 0.8rem;">Study Mate</span>
                                                <?php endif; ?>
                                                <?php if (isset($user['score'])):
                                                ?>
                                                    <span style="color: #10b981; font-size: 0.8rem; font-weight: 500;">
                                                        (<?php echo number_format($user['score'] * 100, 1); ?>% match)
                                                    </span>
                                                <?php endif; ?>
                                            </h4>
                                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                    <div class="request-status">
                                        <?php if ($existing_request && $existing_request['status'] == 'pending' && $existing_request['sender_id'] == $current_user_id): ?>
                                            <span class="status-text pending">
                                                <i class="fas fa-clock"></i> Request Sent
                                            </span>
                                            <form action="study-groups.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="cancel_study_request">
                                                <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                                                <input type="hidden" name="original_search_user" value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>">
                                                <button type="submit" class="cta-button danger small">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php elseif ($existing_request && $existing_request['status'] == 'pending' && $existing_request['receiver_id'] == $current_user_id): ?>
                                            <form action="study-groups.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="accept_request">
                                                <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                                                <button type="submit" class="cta-button primary small">
                                                    <i class="fas fa-check"></i> Accept
                                                </button>
                                            </form>
                                            <form action="study-groups.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="decline_request">
                                                <input type="hidden" name="request_id" value="<?php echo $existing_request['request_id']; ?>">
                                                <button type="submit" class="cta-button danger small">
                                                    <i class="fas fa-times"></i> Decline
                                                </button>
                                            </form>
                                        <?php elseif ($existing_request && $existing_request['status'] == 'accepted'): ?>
                                            <span class="badge" style="background-color: #10b981; color: white; padding: 0.2rem 0.5rem; border-radius: 0.5rem; font-size: 0.8rem;">Study Mate</span>
                                        <?php else: ?>
                                            <form action="study-groups.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="send_study_request">
                                                <input type="hidden" name="receiver_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="original_search_user" value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>">
                                                <button type="submit" id="send-request-<?php echo $user['id']; ?>" class="cta-button primary small">
                                                    <i class="fas fa-paper-plane"></i> Send Request
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                </a>
                            <?php } ?>
                        </div>
                    <?php else: ?>
                        <div class="alert info">
                            <i class="fas fa-search"></i> No users available at the moment.
                        </div>
                    <?php endif; ?>
                <?php } else { ?>
                    <div id="recommendations-section"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const recommendationsSection = document.getElementById('recommendations-section');
                            fetch('study-groups.php?partial=recommendations&_=' + new Date().getTime())
                                .then(response => response.text())
                                .then(html => {
                                    recommendationsSection.innerHTML = html;
                                });
                        });
                    </script>
                <?php } ?>
            </div>


                <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </p>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Study Buddy. All rights reserved.</p>
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="terms.php">Terms of Service</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
    </footer>

    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header"><i class="fas fa-plus-circle"></i> Create Study Group</h2>
            <form method="POST" action="study-groups.php">
                <input type="hidden" name="create_group" value="1">
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Group Name *</label>
                    <input type="text" name="group_name" required placeholder="e.g., CS50 Study Group">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" placeholder="What will your group study? What are the goals?"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="closeCreateModal()" class="cta-button danger small">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="cta-button primary small">
                        <i class="fas fa-check"></i> Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
    
        <script src="assets/js/responsive.js" defer></script>
    
            <script>
        function openCreateModal() {
            document.getElementById('createGroupModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createGroupModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('createGroupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateModal();
            }
        });

            // Quick Match Modal Controller
    class QuickMatchModal {
        constructor() {
            this.modal = document.getElementById('quick-match-modal');
            this.searchingState = document.getElementById('searching-state');
            this.matchFoundState = document.getElementById('match-found-state');
            this.queueInfo = document.getElementById('queue-info');
            this.queueCount = document.getElementById('queue-count');
            
            this.matchedUser = null; // Store matched user info
            this.searchInterval = null;
            this.queueCheckInterval = null;
        }
    
        open() {
            this.modal.classList.add('active');
            this.showSearching();
            this.startSearch();
        }
    
        close() {
            this.modal.classList.remove('active');
            this.stopSearch();
            this.matchedUser = null; // Clear user info
            // Restore button state for next use
            const sendBtn = document.getElementById('send-request-from-match');
            if(sendBtn) {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Study Request';
            }
        }
    
        showSearching() {
            this.searchingState.style.display = 'block';
            this.matchFoundState.classList.remove('active');
        }
    
        showMatchFound(user) {
            this.matchedUser = user; // Key: store the matched user
            this.searchingState.style.display = 'none';
            this.matchFoundState.classList.add('active');
            
            const userCard = document.getElementById('matched-user-card');
            const initials = (user.first_name[0] + user.last_name[0]).toUpperCase();
            
            userCard.innerHTML = `
                <div class="matched-user-avatar">${initials}</div>
                <div class="matched-user-info">
                    <div class="matched-user-name">${user.first_name} ${user.last_name}</div>
                    <div class="matched-user-email">Skill Level: ${user.skill_level || 'Not set'}</div>
                </div>
            `;
        }
    
        async startSearch() {
            const desiredSkillLevel = document.getElementById('desired-skill-level-select').value;
            try {
                const response = await fetch('api/quick_match.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'join_queue',
                        desiredSkillLevel: desiredSkillLevel
                    })
                });
                const result = await response.json();
                if (result.success) {
                    this.searchInterval = setInterval(() => this.checkForMatch(), 3000);
                    this.queueCheckInterval = setInterval(() => this.updateQueueCount(), 5000);
                    this.updateQueueCount();
                } else {
                    throw new Error(result.message || 'Failed to join queue');
                }
            } catch (error) {
                console.error('Search error:', error);
                alert('Failed to start search. Please try again.');
                this.close();
            }
        }
    
        async checkForMatch() {
            try {
                const response = await fetch('api/quick_match.php?action=check_match');
                const result = await response.json();
                if (result.matched) {
                    this.stopSearch();
                    this.showMatchFound(result.user);
                }
            } catch (error) {
                console.error('Check match error:', error);
            }
        }
    
        async updateQueueCount() {
            try {
                const response = await fetch('api/quick_match.php?action=queue_count');
                const result = await response.json();
                if (this.queueInfo && this.queueCount) {
                    if (result.count > 1) {
                        this.queueInfo.style.display = 'block';
                        this.queueCount.textContent = result.count;
                    } else {
                        this.queueInfo.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Queue count error:', error);
            }
        }
    
        stopSearch() {
            clearInterval(this.searchInterval);
            this.searchInterval = null;
            clearInterval(this.queueCheckInterval);
            this.queueCheckInterval = null;
        }
    
        async cancelSearch() {
            try {
                await fetch('api/quick_match.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'leave_queue' })
                });
            } catch (error) {
                console.error('Cancel error:', error);
            } finally {
                this.close();
            }
        }
    
        // --- New Method: Send Study Request ---
        async sendRequest() {
            if (!this.matchedUser) return;
    
            const sendBtn = document.getElementById('send-request-from-match');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
            const formData = new FormData();
            formData.append('action', 'send_study_request');
            formData.append('receiver_id', this.matchedUser.id);
    
            try {
                const response = await fetch('study-groups.php', {
                    method: 'POST',
                    body: formData
                });
    
                // Regardless of backend response, show confirmation on frontend
                sendBtn.innerHTML = '<i class="fas fa-check"></i> Request Sent!';
                // Close modal after 2 seconds
                setTimeout(() => {
                    this.close();
                }, 2000);
    
            } catch (error) {
                console.error('Send request error:', error);
                alert('Failed to send the request. Please try again.');
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Study Request';
            }
        }
    
        viewProfile() {
            if (this.matchedUser) {
                window.location.href = `user_profile.php?id=${this.matchedUser.id}`;
            }
        }
    }
    
    // Initialize modal
    const quickMatchModal = new QuickMatchModal();
    
    // --- Updated Event Listeners ---
    document.addEventListener('DOMContentLoaded', () => {
        // Listen for main button
        document.getElementById('start-quick-match-btn')?.addEventListener('click', () => {
            quickMatchModal.open();
        });
    
        // Listen for modal internal buttons
        document.getElementById('cancel-search')?.addEventListener('click', () => {
            quickMatchModal.cancelSearch();
        });
    
        document.getElementById('send-request-from-match')?.addEventListener('click', () => {
            quickMatchModal.sendRequest();
        });
    
        document.getElementById('view-profile-from-match')?.addEventListener('click', () => {
            quickMatchModal.viewProfile();
        });
        
        document.getElementById('close-match-modal')?.addEventListener('click', () => {
            quickMatchModal.close();
        });
    
        // ... Keep your existing AJAX form submission logic ...
        // ... For example: dashboardCard.addEventListener('submit', e => { ... });
    });
    
            // Expose to global scope for use in other scripts
            window.quickMatchModal = quickMatchModal;
    
            // Example usage: Add this to your quick match button
            // document.getElementById('quick-match-button').addEventListener('click', () => {
            //     quickMatchModal.open();
            // });

        document.addEventListener('DOMContentLoaded', () => {
            const dashboardCard = document.querySelector('.dashboard-card');
            if (!dashboardCard) return;
    
            const preserveScroll = (promise) => {
                const scrollY = window.scrollY;
                return promise.then(() => {
                    window.scrollTo(0, scrollY);
                });
            };
    
            dashboardCard.addEventListener('submit', e => {
                const form = e.target;
    
            if (form.matches('form[action="study-groups.php"]') && form.id !== 'search-form' && !form.classList.contains('no-ajax')) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const action = formData.get('action');
    
                    let promise = null;
    
                    if (action === 'send_study_request' || action === 'cancel_study_request') {
                        let selector = form.closest('.search-results') ? '.search-results' : '#recommendations-section';
                        promise = fetch('study-groups.php', { method: 'POST', body: formData })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSearchSection = doc.querySelector(selector);
                                if (newSearchSection) {
                                    document.querySelector(selector).innerHTML = newSearchSection.innerHTML;
                                }
                                const newSentRequestsSection = doc.querySelector('#sent-requests-section');
                                if (newSentRequestsSection) {
                                    document.getElementById('sent-requests-section').innerHTML = newSentRequestsSection.innerHTML;
                                }
                            });
                    } else if (action === 'accept_request' || action === 'decline_request') {
                        promise = fetch('study-groups.php', { method: 'POST', body: formData })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.querySelector('#requests-section');
                                if (newSection) {
                                    document.querySelector('#requests-section').innerHTML = newSection.innerHTML;
                                }
                            });
                    } else if (formData.has('quick_match') || formData.has('cancel_match')) {
                        promise = fetch('study-groups.php', { method: 'POST', body: formData })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newCard = doc.querySelector('.dashboard-card');
                                if (newCard) {
                                    dashboardCard.innerHTML = newCard.innerHTML;
                                }
                            });
                    }
                    
                    if (promise) {
                        preserveScroll(promise);
                    }
                }
            });
    
                    // Use event delegation for the refresh button
    
                    dashboardCard.addEventListener('click', e => {
    
                        if (e.target && e.target.id === 'refresh-suggestions-button') {
    
                            const recommendationsSection = document.getElementById('recommendations-section');
    
                            if (recommendationsSection) {
    
                                // Show a loading indicator (optional)
    
                                recommendationsSection.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading recommendations...</div>';
    
            
    
                                // Fetch new content for the recommendations section
    
                                                                fetch('study-groups.php?partial=recommendations&refresh=1&_=' + new Date().getTime()) // Add unique timestamp
    
                                
    
                                                                    .then(response => response.text())
    
                                
    
                                                                    .then(html => {
    
                                
    
                                                                        recommendationsSection.innerHTML = html;
    
                                
    
                                                                    })
    
                                
    
                                                                    .catch(error => {
    
                                
    
                                                                        console.error('Error refreshing recommendations:', error);
    
                                
    
                                                                        recommendationsSection.innerHTML = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Failed to load recommendations.</div>';
    
                                
    
                                                                    });
    
                            }
    
                        }
    
                    });
    
            
    
                    // Live updates for invitations
    
                    const checkForUpdates = async () => {
                        try {
                            const response = await fetch(`ajax/check_updates.php?_=${new Date().getTime()}`);
                            const data = await response.json();

                            // Update received requests
                            const requestsSection = document.getElementById('requests-section');
                            if (requestsSection) {
                                let requestsHtml = '<h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>';
                                if (data.received_requests && data.received_requests.length > 0) {
                                    requestsHtml += '<div class="requests-list">';
                                    data.received_requests.forEach(request => {
                                        const sender_initials = (request.first_name[0] + request.last_name[0]).toUpperCase();
                                        requestsHtml += `
                                            <div class="request-item">
                                                <div class="user-info">
                                                    <div class="user-avatar">${sender_initials}</div>
                                                    <div class="user-details">
                                                        <h4>${request.first_name} ${request.last_name}</h4>
                                                        <p>${request.email}</p>
                                                    </div>
                                                </div>
                                                <div class="request-actions">
                                                    <form action="study-groups.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="accept_request"><input type="hidden" name="request_id" value="${request.request_id}"><button type="submit" class="cta-button primary small">Accept</button></form>
                                                    <form action="study-groups.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="decline_request"><input type="hidden" name="request_id" value="${request.request_id}"><button type="submit" class="cta-button danger small">Decline</button></form>
                                                </div>
                                            </div>
                                        `;
                                    });
                                    requestsHtml += '</div>';
                                } else {
                                    requestsHtml += '<div class="alert info"><i class="fas fa-inbox"></i> No pending study requests.</div>';
                                }
                                requestsSection.innerHTML = requestsHtml;
                            }
                        } catch (error) {
                            console.error('Error checking for updates:', error);
                        }
                    };
    
            
    
                    setInterval(checkForUpdates, 1000); // Poll every 1 second
    
                });

        const searchInput = document.querySelector('input[name="search_user"]');
        const suggestionsContainer = document.getElementById('search-suggestions');
        const searchForm = document.getElementById('search-form');

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.trim();

            if (searchTerm.length < 2) {
                suggestionsContainer.style.display = 'none';
                return;
            }

            // For debugging, make the container visible immediately
            suggestionsContainer.style.display = 'block';
            suggestionsContainer.innerHTML = '<div class="suggestion-item">Loading...</div>';

            fetch(`ajax/search_suggestions.php?q=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    suggestionsContainer.innerHTML = ''; // Clear "Loading..."
                    if (data.suggestions && data.suggestions.length > 0) {
                        data.suggestions.forEach(user => {
                            const suggestionItem = document.createElement('div');
                            suggestionItem.classList.add('suggestion-item');
                            suggestionItem.textContent = `${user.first_name} ${user.last_name} (${user.email})`;
                            suggestionItem.addEventListener('click', () => {
                                window.location.href = `user_profile.php?id=${user.id}`;
                            });
                            suggestionsContainer.appendChild(suggestionItem);
                        });
                    } else {
                        suggestionsContainer.innerHTML = '<div class="suggestion-item" style="color: #6c757d;">No users found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    suggestionsContainer.innerHTML = `<div class="suggestion-item" style="color: #dc3545;">Error: ${error.message}</div>`;
                });
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchForm.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    </script>
    
    </body>
</html>