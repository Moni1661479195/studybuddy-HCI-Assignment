<?php
require_once '../config.php';
require_once '../session.php';

$current_user_id = $_SESSION['user_id'];

$sql = "
    SELECT sr.request_id, sr.receiver_id, sr.requested_at, u.first_name, u.last_name, u.email 
    FROM study_requests sr 
    JOIN users u ON sr.receiver_id = u.id 
    WHERE sr.sender_id = ? AND sr.status = 'pending' 
    ORDER BY sr.requested_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$sent_requests = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($sent_requests);
?>