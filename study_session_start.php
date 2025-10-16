<?php
session_start();
require_once __DIR__.'/lib/db.php';
$db = get_db();
$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
$stmt = $db->prepare("INSERT INTO study_sessions (user_id, started_at) VALUES (?,NOW())");
$stmt->execute([$uid]);
$id = $db->lastInsertId();
echo json_encode(['session_id'=>$id]);
close_db();
?>