<?php
// api/track_progress.php
header('Content-Type: application/json');

require_once '../session.php';
require_once '../lib/db.php';
require_once '../TaskLogic.php'; 

// 1. 检查登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// 2. 获取请求参数
$type = $data['type'] ?? ''; // e.g., 'minute_update' or 'session_complete'

// 创建数据库连接 (PDO)
$db = get_db();

// 为了兼容 TaskLogic (它可能需要 mysqli 或 PDO，取决于你上一步的最终版本)
// 如果你的 TaskLogic 使用 PDO，直接传 $db。
// 如果你的 TaskLogic 还是用 mysqli，你需要在这里创建一个 mysqli 连接。
// 假设我们已经统一用 PDO 了 (根据你上一次的修改):

if ($type === 'minute_tick') {
    // --- 场景 A：每分钟更新一次 (用于 Weekly Study Marathon) ---
    // 任务 key: 'weekly_study_time'
    // 每次增加 1 (分钟)
    $result = updateTaskProgress($db, $user_id, 'weekly_study_time', 1);
    echo json_encode(['success' => $result]);

} elseif ($type === 'session_complete') {
    // --- 场景 B：完成一次专注学习 (用于 Daily Focused Study) ---
    // 任务 key: 'daily_focus'
    // 只有当用户真的坚持了 20 分钟，前端才会发送这个请求
    // 目标是 1 (完成 1 次)，所以我们加 1
    $result = updateTaskProgress($db, $user_id, 'daily_focus', 1);
    echo json_encode(['success' => $result]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
}
?><?php
// api/track_progress.php
header('Content-Type: application/json');

require_once '../session.php';
require_once '../lib/db.php';
require_once '../TaskLogic.php'; 

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// 2. Get Request Parameters
$type = $data['type'] ?? ''; // e.g., 'minute_tick' or 'session_complete'

// Create DB Connection (PDO)
$db = get_db();

if ($type === 'minute_tick') {
    // --- Scenario A: Update every minute (For Weekly Study Marathon) ---
    // Task Key: 'weekly_study_time'
    // Increment: 1 (minute)
    $result = updateTaskProgress($db, $user_id, 'weekly_study_time', 1);
    echo json_encode(['success' => $result]);

} elseif ($type === 'session_complete') {
    // --- Scenario B: Complete a focused session (For Daily Focused Study) ---
    // Task Key: 'daily_focus'
    // This is triggered only when the frontend timer reaches 20 minutes.
    // Increment: 1 (session count)
    $result = updateTaskProgress($db, $user_id, 'daily_focus', 1);
    echo json_encode(['success' => $result]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
}
?>