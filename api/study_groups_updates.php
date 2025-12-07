<?php
require_once '../session.php';
require_once '../lib/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

try {
    $db = get_db();
    
    // Get timestamp for comparison (optional - for more efficient updates)
    $last_check = $_GET['last_check'] ?? 0;
    
    $updates = [];
    
    // 1. My Study Groups
    $stmt_get_ids = $db->prepare("SELECT group_id FROM study_group_members WHERE user_id = ?");
    $stmt_get_ids->execute([$current_user_id]);
    $group_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);
    
    $my_groups = [];
    if (!empty($group_ids)) {
        $in_clause = implode(',', array_fill(0, count($group_ids), '?'));
        $groups_stmt = $db->prepare("
            SELECT *
            FROM study_groups
            WHERE group_id IN ($in_clause) AND is_active = 1
            ORDER BY created_at DESC
        ");
        $groups_stmt->execute($group_ids);
        $my_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get member count for each group
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM study_group_members WHERE group_id = ?");
        foreach ($my_groups as $i => $group) {
            $count_stmt->execute([$group['group_id']]);
            $my_groups[$i]['member_count'] = $count_stmt->fetchColumn();
            
            // Get chat room for this group
            $room_stmt = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'group' AND group_id = ?");
            $room_stmt->execute([$group['group_id']]);
            $room = $room_stmt->fetch(PDO::FETCH_ASSOC);
            $my_groups[$i]['room_id'] = $room ? $room['id'] : null;
        }
    }
    $updates['my_groups'] = $my_groups;
    
    // 2. Group Invitations
    try {
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
        $updates['group_invitations'] = $invitations_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching group invitations: " . $e->getMessage());
        $updates['group_invitations'] = [];
    }
    
    // 3. Study Mates
    $stmt_mates = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email
        FROM study_partners sp
        JOIN users u ON (u.id = sp.user1_id OR u.id = sp.user2_id)
        WHERE (sp.user1_id = ? OR sp.user2_id = ?) AND u.id != ? AND sp.is_active = 1
    ");
    $stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
    $study_mates = $stmt_mates->fetchAll(PDO::FETCH_ASSOC);
    $updates['study_mates'] = $study_mates;
    $study_mate_ids = array_column($study_mates, 'id'); // Extract IDs for filtering
    
    // 4. Received Study Requests
    $stmt_received = $db->prepare(" 
        SELECT sr.request_id, sr.sender_id, sr.requested_at, u.first_name, u.last_name, u.email 
        FROM study_requests sr 
        JOIN users u ON sr.sender_id = u.id 
        WHERE sr.receiver_id = ? AND sr.status = 'pending' 
        ORDER BY sr.requested_at DESC
    ");
    $stmt_received->execute([$current_user_id]);
    $updates['received_requests'] = $stmt_received->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Sent Study Requests (Filter out existing study mates)
    $sent_requests_query = "
        SELECT sr.request_id, u.id AS user_id, u.first_name, u.last_name, u.email
        FROM study_requests sr
        JOIN users u ON sr.receiver_id = u.id
        WHERE sr.sender_id = ? AND sr.status = 'pending'
    ";
    $params = [$current_user_id];

    if (!empty($study_mate_ids)) {
        $in_clause = implode(',', array_fill(0, count($study_mate_ids), '?'));
        $sent_requests_query .= " AND sr.receiver_id NOT IN ($in_clause)";
        $params = array_merge($params, $study_mate_ids);
    }

    $stmt_sent = $db->prepare($sent_requests_query);
    $stmt_sent->execute($params);
    $updates['sent_requests'] = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
    
    // --- HASH CALCULATION ---
    // Hash is calculated based on stable data, BEFORE adding volatile recommendations.
    $updates['data_hash'] = md5(json_encode($updates));
    $updates['timestamp'] = time();

    // 6. Recommendations (for the recommendations section)
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
    }
    
    // Add request status for each recommended user
    foreach ($suggested_users as $i => $user) {
        $stmt_check_request = $db->prepare("SELECT * FROM study_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt_check_request->execute([$current_user_id, $user['id']]);
        $existing_request = $stmt_check_request->fetch(PDO::FETCH_ASSOC);
        $suggested_users[$i]['has_pending_request'] = !empty($existing_request);
        $suggested_users[$i]['request_id'] = $existing_request ? $existing_request['request_id'] : null;
    }
    
    $updates['recommendations'] = [
        'users' => $suggested_users,
        'show_random' => $show_random
    ];
    
    echo json_encode([
        'success' => true,
        'updates' => $updates
    ]);

} catch (Exception $e) {
    error_log("Study groups updates error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
}
?>
