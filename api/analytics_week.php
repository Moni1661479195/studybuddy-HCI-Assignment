<?php
session_start();
require_once __DIR__.'/../lib/db.php';
$db = get_db();
$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { http_response_code(401); echo json_encode([]); exit; }
$stmt = $db->prepare("
  SELECT DATE(started_at) as d, COALESCE(SUM(TIMESTAMPDIFF(SECOND, started_at, ended_at))/3600,0) AS hours
  FROM study_sessions
  WHERE user_id = ? AND started_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND ended_at IS NOT NULL
  GROUP BY d ORDER BY d
");
$stmt->execute([$uid]);
$data = $stmt->fetchAll();
echo json_encode($data);
?>