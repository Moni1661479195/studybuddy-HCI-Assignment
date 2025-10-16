<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include '../config.php';
include '../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db(); // Get PDO connection

// Fetch notifications (logic from api/get_notifications.php)
$stmt_notifications = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt_notifications->execute([$user_id]);
$notifications = $stmt_notifications->fetchAll(PDO::FETCH_ASSOC);

// For user activity updates, since no clear source was found, return an empty array for now.
// This needs further investigation to determine the actual source of user activity updates.
$user_activity_updates = [];

// Fetch received study requests
$stmt_requests = $db->prepare("
    SELECT sr.request_id, sr.sender_id, sr.requested_at, u.first_name, u.last_name, u.email 
    FROM study_requests sr 
    JOIN users u ON sr.sender_id = u.id 
    WHERE sr.receiver_id = ? AND sr.status = 'pending' 
    ORDER BY sr.requested_at DESC
");
$stmt_requests->execute([$user_id]);
$received_requests = $stmt_requests->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'notifications' => $notifications,
    'activity_updates' => $user_activity_updates,
    'received_requests' => $received_requests
]);
?>