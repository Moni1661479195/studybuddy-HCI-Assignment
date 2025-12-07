<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$mate_id = isset($_POST['mate_id']) ? (int)$_POST['mate_id'] : 0;

if ($mate_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid study mate ID.']);
    exit();
}

if ($current_user_id == $mate_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cannot remove yourself as a study mate.']);
    exit();
}

try {
    $db = get_db();

    $user1 = min($current_user_id, $mate_id);
    $user2 = max($current_user_id, $mate_id);

    $stmt = $db->prepare("UPDATE study_partners SET is_active = 0 WHERE user1_id = ? AND user2_id = ? AND is_active = 1");
    $stmt->execute([$user1, $user2]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Study mate removed successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Could not find an active study partnership to remove.']);
    }

} catch (Exception $e) {
    error_log("Error removing study mate: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while removing the study mate.']);
}
?>