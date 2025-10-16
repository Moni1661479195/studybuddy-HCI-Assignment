<?php
require_once '../config.php';
require_once '../session.php';

$current_user_id = $_SESSION['user_id'];

// Fetch accepted study mates
$sql = "
    SELECT u.id, u.first_name, u.last_name, u.email, 
           (u.last_seen IS NOT NULL AND TIMESTAMPDIFF(SECOND, u.last_seen, NOW()) < 60) AS is_online,
           u.last_seen
    FROM users u
    JOIN study_requests sr ON (sr.sender_id = u.id OR sr.receiver_id = u.id)
    WHERE sr.status = 'accepted'
      AND (sr.sender_id = ? OR sr.receiver_id = ?)
      AND u.id != ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$study_mates = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($study_mates);
?>