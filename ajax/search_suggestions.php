<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['suggestions' => []]);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 1) {
    echo json_encode(['suggestions' => []]);
    exit();
}

try {
    $db = get_db();

    $search_lower = "%" . strtolower($query) . "%";

    $stmt = $db->prepare(" 
        SELECT id, first_name, last_name, email, profile_picture_path FROM users 
        WHERE (
            LOWER(first_name) LIKE ? OR 
            LOWER(last_name) LIKE ? OR 
            LOWER(email) LIKE ? OR
            LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?
        ) AND id != ? AND is_admin = 0 
        LIMIT 5
    ");
    $stmt->execute([
        $search_lower, $search_lower, $search_lower, $search_lower, $current_user_id
    ]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['suggestions' => $suggestions]);
} catch (Exception $e) {
    error_log("Error fetching search suggestions: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['suggestions' => [], 'error' => 'Failed to fetch suggestions.']);
}
?>