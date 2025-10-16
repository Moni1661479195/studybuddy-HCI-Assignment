<?php
require_once '../session.php';
require_once '../lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_mate') {
    
    $current_user_id = (int)$_SESSION['user_id'];
    $partner_id = (int)$_POST['partner_id']; 

    if ($partner_id > 0) {
        try {
            $db = get_db();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 为了确保查询条件正确，总是让小ID在前
            $user1 = min($current_user_id, $partner_id);
            $user2 = max($current_user_id, $partner_id);

            // 更新关系状态为 is_active = 0
            $stmt = $db->prepare("
                UPDATE study_partners 
                SET is_active = 0, last_activity = NOW() 
                WHERE user1_id = ? AND user2_id = ? AND is_active = 1
            ");
            $stmt->execute([$user1, $user2]);

        } catch (Exception $e) {
            error_log("Failed to remove study mate: " . $e->getMessage());
        }
    }
}

// 操作完成后，重定向回 study-groups.php 列表页
header("Location: ../study-groups.php");
exit();