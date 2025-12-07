<?php
// api/claim_reward.php
header('Content-Type: application/json');

require_once '../session.php';
require_once '../lib/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db(); // Use the standard PDO connection

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'claim_daily') {
        // --- SCENARIO 1: Claim Daily Reward (Requires all 5 daily tasks) ---
        
        // 1. Check if all daily tasks are completed
        $stmt = $db->prepare("SELECT count(*) FROM user_task_progress utp 
                JOIN tasks t ON utp.task_id = t.id 
                WHERE utp.user_id = ? AND t.type = 'daily' AND utp.is_completed = 1");
        $stmt->execute([$user_id]);
        $completed_count = $stmt->fetchColumn();
        
        // Assuming there are 5 daily tasks
        if ($completed_count < 5) {
            echo json_encode(['success' => false, 'message' => 'Daily tasks not finished yet.']);
            exit();
        }

        // 2. Check if already claimed
        $stmt_check = $db->prepare("SELECT count(*) FROM user_task_progress utp 
                      JOIN tasks t ON utp.task_id = t.id 
                      WHERE utp.user_id = ? AND t.type = 'daily' AND utp.is_claimed = 1");
        $stmt_check->execute([$user_id]);
        $claimed_count = $stmt_check->fetchColumn();

        if ($claimed_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Already claimed today.']);
            exit();
        }

        // 3. Grant Reward and Update Status
        $db->beginTransaction();

        // A. Mark all daily tasks as "claimed"
        $stmt_update = $db->prepare("UPDATE user_task_progress utp 
                       JOIN tasks t ON utp.task_id = t.id 
                       SET utp.is_claimed = 1 
                       WHERE utp.user_id = ? AND t.type = 'daily'");
        $stmt_update->execute([$user_id]);

        // B. Add 1 Card Pack to inventory
        $stmt_inv = $db->prepare("INSERT INTO user_inventory (user_id, card_packs) VALUES (?, 1) 
                    ON DUPLICATE KEY UPDATE card_packs = card_packs + 1");
        $stmt_inv->execute([$user_id]);
        
        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Daily reward claimed!']); 

    } elseif ($action === 'claim_task') {
        // --- SCENARIO 2: Claim Single Task Reward (Weekly Tasks) ---
        $task_id = $data['task_id'] ?? 0;
        if (empty($task_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid Task ID.']);
            exit();
        }

        // 1. Check task status
        $stmt = $db->prepare("SELECT is_completed, is_claimed FROM user_task_progress WHERE user_id = ? AND task_id = ?");
        $stmt->execute([$user_id, $task_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$progress || !$progress['is_completed']) {
            echo json_encode(['success' => false, 'message' => 'Task not completed.']);
            exit();
        }
        if ($progress['is_claimed']) {
            echo json_encode(['success' => false, 'message' => 'Already claimed.']);
            exit();
        }

        // 2. Grant Reward
        $db->beginTransaction();
        
        // A. Mark as claimed
        $stmt_update = $db->prepare("UPDATE user_task_progress SET is_claimed = 1 WHERE user_id = ? AND task_id = ?");
        $stmt_update->execute([$user_id, $task_id]);

        // B. Add 1 Card Pack
        $stmt_inv = $db->prepare("INSERT INTO user_inventory (user_id, card_packs) VALUES (?, 1) 
                    ON DUPLICATE KEY UPDATE card_packs = card_packs + 1");
        $stmt_inv->execute([$user_id]);
        
        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Reward claimed!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Claim Reward API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>