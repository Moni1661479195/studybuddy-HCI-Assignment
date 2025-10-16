<?php
session_start();
require_once __DIR__.'/lib/db.php';
$db = get_db();
$data = json_decode(file_get_contents('php://input'), true);
$sid = $data['session_id'] ?? null;
if (!$sid) { http_response_code(400); echo json_encode(['error'=>'bad_request']); exit; }
$stmt = $db->prepare("UPDATE study_sessions SET ended_at = NOW() WHERE id = ? AND ended_at IS NULL");
$stmt->execute([$sid]);
echo json_encode(['ok'=>true]);
?>