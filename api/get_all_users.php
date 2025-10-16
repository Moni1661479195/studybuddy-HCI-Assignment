<?php
require_once '../lib/db.php';

try {
    $db = get_db();
    $stmt = $db->query("SELECT id, first_name, last_name, email FROM users ORDER BY first_name, last_name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($users);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch users.']);
}
