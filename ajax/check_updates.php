<?php
ini_set('display_errors', 0); // Turn off error reporting for production AJAX
error_reporting(0);

session_start();

// Ensure this path is correct relative to the ajax folder
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$db = get_db();

$response = [];

// --- Helper function to render components ---
function render_component($component_path, $vars = []) {
    extract($vars);
    ob_start();
    // Need to ensure the path is correct from the ajax folder
    include __DIR__ . '/../includes/components/study_groups/' . $component_path;
    return ob_get_clean();
}

// --- Fetch data and render HTML for each section ---

// 1. My Study Groups
$stmt_get_ids = $db->prepare("SELECT group_id FROM study_group_members WHERE user_id = ?");
$stmt_get_ids->execute([$current_user_id]);
$group_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);
$my_groups = [];
if (!empty($group_ids)) {
    $in_clause = implode(',', array_fill(0, count($group_ids), '?'));
    $groups_stmt = $db->prepare("SELECT * FROM study_groups WHERE group_id IN ($in_clause) AND is_active = 1 ORDER BY created_at DESC");
    $groups_stmt->execute($group_ids);
    $my_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM study_group_members WHERE group_id = ?");
    foreach ($my_groups as $i => $group) {
        $count_stmt->execute([$group['group_id']]);
        $my_groups[$i]['member_count'] = $count_stmt->fetchColumn();
    }
}
$response['my_groups_html'] = render_component('my_groups_section.php', ['my_groups' => $my_groups, 'db' => $db]);

// 2. Group Invitations
$invitations_stmt = $db->prepare("SELECT sgi.*, sg.group_name as group_name, CONCAT(u.first_name, ' ', u.last_name) as sender_name, u.profile_picture_path FROM study_group_invitations sgi JOIN study_groups sg ON sgi.group_id = sg.group_id JOIN users u ON sgi.sender_id = u.id WHERE sgi.receiver_id = ? AND sgi.status = 'pending' ORDER BY sgi.invited_at DESC");
$invitations_stmt->execute([$current_user_id]);
$invitations = $invitations_stmt->fetchAll(PDO::FETCH_ASSOC);
$response['invitations_html'] = render_component('invitations_section.php', ['invitations' => $invitations]);

// 3. My Study Mates
$stmt_mates = $db->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.profile_picture_path FROM study_partners sp JOIN users u ON u.id = IF(sp.user1_id = ?, sp.user2_id, sp.user1_id) WHERE (sp.user1_id = ? OR sp.user2_id = ?) AND sp.is_active = 1");
$stmt_mates->execute([$current_user_id, $current_user_id, $current_user_id]);
$study_mates = $stmt_mates->fetchAll(PDO::FETCH_ASSOC);
$response['study_mates_html'] = render_component('study_mates_section.php', ['study_mates' => $study_mates]);

// 4. Received Study Requests
$stmt_received = $db->prepare("SELECT sr.request_id, sr.sender_id, sr.requested_at, u.first_name, u.last_name, u.email, u.profile_picture_path FROM study_requests sr JOIN users u ON sr.sender_id = u.id WHERE sr.receiver_id = ? AND sr.status = 'pending' ORDER BY sr.requested_at DESC");
$stmt_received->execute([$current_user_id]);
$received_requests = $stmt_received->fetchAll(PDO::FETCH_ASSOC);
$response['received_requests_html'] = render_component('received_requests_section.php', ['received_requests' => $received_requests]);

// 5. Sent Study Requests
$sent_requests_query = "SELECT sr.request_id, u.id AS user_id, u.first_name, u.last_name, u.email, u.profile_picture_path FROM study_requests sr JOIN users u ON sr.receiver_id = u.id WHERE sr.sender_id = ? AND sr.status = 'pending'";
$stmt_sent_requests = $db->prepare($sent_requests_query);
    $stmt_sent_requests->execute([$current_user_id]);
    $sent_requests = $stmt_sent_requests->fetchAll(PDO::FETCH_ASSOC);
    $response['sent_requests_html'] = render_component('sent_requests_section.php', ['sent_requests' => $sent_requests]);

// 6. Recommended Study Partners
require_once __DIR__ . '/study_groups_partials.php'; // Include the refactored partials file
$response['recommendations_html'] = get_recommendations_html($current_user_id, $db, $sent_requests);

echo json_encode($response);?>