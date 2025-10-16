<?php
// ===== api/session_actions.php (最终正式版本) =====

require_once '../session.php';
require_once '../lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'end_session') {
    
    $current_user_id = (int)$_SESSION['user_id'];
    $session_id = (int)$_POST['session_id'];

    if ($session_id > 0) {
        try {
            $db = get_db();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 安全检查：确保当前用户是这个会话的参与者之一
            $check_stmt = $db->prepare("
                SELECT id 
                FROM study_sessions 
                WHERE id = ? 
                  AND (user_id = ? OR JSON_CONTAINS(metadata, CAST(? AS CHAR), '$.group'))
            ");
            $check_stmt->execute([$session_id, $current_user_id, $current_user_id]);
            
            if ($check_stmt->fetch()) {
                // 验证通过，更新会话状态
                $update_stmt = $db->prepare("
                    UPDATE study_sessions 
                    SET status = 'completed', ended_at = NOW() 
                    WHERE id = ? AND status = 'active'
                ");
                $update_stmt->execute([$session_id]);
            }
            
        } catch (Exception $e) {
            error_log("Failed to end session {$session_id}: " . $e->getMessage());
        }
    }
}

// 操作完成后，重定向回 study-groups.php 页面
header("Location: ../study-groups.php");
exit();