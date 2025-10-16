<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$group_id = (int)($_GET['group_id'] ?? 0);
$term = trim($_GET['term'] ?? '');

if ($group_id <= 0) {
    echo json_encode(['error' => 'Invalid group specified.']);
    exit();
}

if (empty($term)) {
    echo json_encode([]); // Return empty array if search term is empty
    exit();
}

$db = get_db();
$search_like = "%$term%";

// This query finds all of the current user's partners, then filters out those who are already in the target group or have a pending invitation, and finally matches them against the search term.
$stmt = $db->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name
    FROM users u
    WHERE u.id IN (
        -- Subquery to get all partner IDs
        SELECT CASE 
            WHEN sp.user1_id = :current_user_id_1 THEN sp.user2_id
            ELSE sp.user1_id
        END
        FROM study_partners sp
        WHERE (sp.user1_id = :current_user_id_2 OR sp.user2_id = :current_user_id_3) AND sp.is_active = 1
    )
    -- Exclude users already in the group
    AND u.id NOT IN (SELECT user_id FROM study_group_members WHERE group_id = :group_id_1)
    -- Exclude users with a pending invite for this group
    AND u.id NOT IN (SELECT receiver_id FROM study_group_invitations WHERE group_id = :group_id_2 AND status = 'pending')
    -- Filter by search term
    AND CONCAT(u.first_name, ' ', u.last_name) LIKE :search_term
    LIMIT 5
");

$stmt->execute([
    ':current_user_id_1' => $current_user_id,
    ':current_user_id_2' => $current_user_id,
    ':current_user_id_3' => $current_user_id,
    ':group_id_1' => $group_id,
    ':group_id_2' => $group_id,
    ':search_term' => $search_like
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
