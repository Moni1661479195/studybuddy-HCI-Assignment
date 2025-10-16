<?php
session_start();
require_once __DIR__.'/../lib/db.php';
$db = get_db();
$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
$skill = $_POST['skill'] ?? null;
$avail = $_POST['availability'] ?? null; // JSON string
$stmt = $db->prepare("INSERT INTO quick_match_queue (user_id, skill_level, availability_window) VALUES (?,?,?)");
$stmt->execute([$uid, $skill, $avail]);
echo json_encode(['ok'=>true]);?>