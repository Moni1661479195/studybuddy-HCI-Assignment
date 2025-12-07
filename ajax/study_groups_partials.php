<?php
// This file now contains functions to render specific partials, 
// to be called directly by other PHP scripts (e.g., check_updates.php)
// or via AJAX requests.

require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../lib/db.php';

function get_recommendations_html($current_user_id, $db, $sent_requests = []) {
    ob_start();

    // Fetch current user's study mates
    $stmt_mates = $db->prepare("SELECT u.id FROM study_partners sp JOIN users u ON u.id = IF(sp.user1_id = ?, sp.user2_id, sp.user1_id) WHERE (sp.user1_id = ? OR sp.user2_id = ?) AND sp.is_active = 1");
    $stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
    $study_mate_ids = $stmt_mates->fetchAll(PDO::FETCH_COLUMN);

    // Build the exclusion clause for study mates
    $exclude_mates_clause = '';
    $mate_params = [];
    if (!empty($study_mate_ids)) {
        $placeholders = implode(',', array_fill(0, count($study_mate_ids), '?'));
        $exclude_mates_clause = " AND users.id NOT IN ($placeholders)";
        $mate_params = $study_mate_ids;
    }

    // Show recommended users or random users when no search is performed
    $suggested_users = [];
    $show_random = false;

    $query_params = array_merge([$current_user_id, $current_user_id], $mate_params);

    $stmt_suggested = $db->prepare("
        SELECT id, first_name, last_name, email, profile_picture_path
        FROM users
        WHERE id != ? AND is_admin = 0
        AND id NOT IN (SELECT receiver_id FROM study_requests WHERE sender_id = ? AND status = 'pending')
        {$exclude_mates_clause}
        ORDER BY RAND() LIMIT 5
    ");
    $stmt_suggested->execute($query_params);
    $suggested_users = $stmt_suggested->fetchAll(PDO::FETCH_ASSOC);

    if (empty($suggested_users)) {
        // The fallback query also needs to exclude study mates
        $query_params_fallback = array_merge([$current_user_id, $current_user_id], $mate_params);
        $stmt_suggested = $db->prepare("SELECT id, first_name, last_name, email, profile_picture_path FROM users WHERE id != ? AND is_admin = 0 AND id NOT IN (SELECT receiver_id FROM study_requests WHERE sender_id = ? AND status = 'pending') {$exclude_mates_clause} ORDER BY RAND() LIMIT 5");
        $stmt_suggested->execute($query_params_fallback);
        $suggested_users = $stmt_suggested->fetchAll(PDO::FETCH_ASSOC);
        $show_random = true;
    } else {
        $show_random = false;
    }

    // Extract IDs of users to whom requests have been sent
    $sent_request_receiver_ids = array_column($sent_requests, 'user_id');

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
                // Check if a request has already been sent to this user
                $is_request_sent = in_array($user['id'], $sent_request_receiver_ids);
                $existing_request = null;
                if ($is_request_sent) {
                    // Find the specific sent request to get its request_id
                    foreach ($sent_requests as $req) {
                        if ($req['user_id'] == $user['id']) {
                            $existing_request = $req;
                            break;
                        }
                    }
                }

                $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
            ?>
                <div class="user-result-item">
                    <div class="user-info">
                        <?php if (!empty($user['profile_picture_path'])) : ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture_path']); ?>" alt="User Avatar" class="user-avatar-img">
                        <?php else : ?>
                            <div class="user-avatar"><?php echo $initials; ?></div>
                        <?php endif; ?>
                        <div class="user-details">
                            <h4>
                                <a href="user_profile.php?id=<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </a>
                                <?php if (isset($user['score']) && $user['score']): ?>
                                    <span style="color: #10b981; font-size: 0.8rem; font-weight: 500;">
                                        (<?php echo number_format($user['score'] * 100, 1); ?>% match)
                                    </span>
                                <?php endif; ?>
                            </h4>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <div class="request-status">
                        <?php if ($is_request_sent && $existing_request): ?>
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
    return ob_get_clean();
}

// Handle direct AJAX requests for other partials if needed, but recommendations will be called as a function
if (isset($_GET['partial'])) {
    if ($_GET['partial'] === 'requests') {
        // This part needs to be refactored into a function too if it's to be included
        // For now, I'll leave it as is, assuming it's called directly via AJAX
        // and doesn't need to be included by check_updates.php

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
        
        if (!empty($received_requests)):
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

        if (!empty($sent_requests)):
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