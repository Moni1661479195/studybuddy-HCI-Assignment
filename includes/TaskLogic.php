<?php
// TaskLogic.php

/**
 * Get the logical "Start Time" of the current cycle based on the 6:00 AM rule.
 * @param string $type 'daily' or 'weekly'
 * @return int Timestamp of when the current cycle started
 */
function getCycleStartTime($type) {
    $now = time();
    $today6am = strtotime("today 6:00");
    
    if ($type === 'daily') {
        if ($now < $today6am) {
            return strtotime("yesterday 6:00");
        }
        return $today6am;
    } 
    elseif ($type === 'weekly') {
        $thisWeekMonday6am = strtotime("this week monday 6:00");
        if ($now < $thisWeekMonday6am && date('N') == 1) { // 1 = Monday
             return strtotime("last week monday 6:00");
        }
        if ($now < $thisWeekMonday6am) {
             return strtotime("last week monday 6:00");
        }
        return $thisWeekMonday6am;
    }
    return 0;
}

/**
 * Check and reset tasks if the cycle has passed. Also initializes tasks for new users.
 * @param PDO $db PDO Database connection
 * @param int $user_id User ID
 */
function checkAndSyncTasks(PDO $db, $user_id) {
    // 1. Get all defined tasks from the system
    $stmt_all_tasks = $db->query("SELECT id, task_key, type FROM tasks");
    $all_tasks = $stmt_all_tasks->fetchAll(PDO::FETCH_ASSOC);

    // 2. Loop through each task to check status for this user
    foreach ($all_tasks as $task) {
        $task_id = $task['id'];
        $task_type = $task['type'];
        
        // Check if user already has a record for this task
        $stmt_check = $db->prepare("SELECT id, last_updated, is_completed, is_claimed FROM user_task_progress WHERE user_id = ? AND task_id = ?");
        $stmt_check->execute([$user_id, $task_id]);
        $progress = $stmt_check->fetch(PDO::FETCH_ASSOC);

        $cycle_start_time = getCycleStartTime($task_type);

        if (!$progress) {
            // Case A: User has NO record -> Insert 0 progress
            $stmt_insert = $db->prepare("INSERT INTO user_task_progress (user_id, task_id, current_value, is_completed, is_claimed, last_updated) VALUES (?, ?, 0, 0, 0, NOW())");
            $stmt_insert->execute([$user_id, $task_id]);
        } else {
            // Case B: User has record -> Check if we need to RESET it
            $last_updated_ts = strtotime($progress['last_updated']);
            
            // If the last update was BEFORE the current cycle started, it's old data. Reset it.
            if ($last_updated_ts < $cycle_start_time) {
                $stmt_reset = $db->prepare("UPDATE user_task_progress SET current_value = 0, is_completed = 0, is_claimed = 0, last_updated = NOW() WHERE id = ?");
                $stmt_reset->execute([$progress['id']]);
            }
        }
    }
}

/**
 * Fetch all tasks with user progress for display
 * @param PDO $db PDO Database connection
 * @param int $user_id User ID
 */
function getUserTasks(PDO $db, $user_id) {
    // First, ensure data is synced/reset
    checkAndSyncTasks($db, $user_id);

    // Query to join tasks with user progress
    $stmt = $db->prepare("SELECT 
                t.*, 
                up.current_value, 
                up.is_completed, 
                up.is_claimed 
            FROM tasks t
            LEFT JOIN user_task_progress up ON t.id = up.task_id AND up.user_id = ?
            ORDER BY t.type ASC, t.id ASC");
            
    $stmt->execute([$user_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tasks = [
        'daily' => [],
        'weekly' => []
    ];
    
    foreach ($result as $row) {
        $tasks[$row['type']][] = $row;
    }
    
    return $tasks;
}

/**
 * Calculate time remaining until next refresh
 */
function getTimeUntilRefresh($type) {
    $now = time();
    $target = 0;
    
    if ($type === 'daily') {
        $today6am = strtotime("today 6:00");
        if ($now < $today6am) {
            $target = $today6am;
        } else {
            $target = strtotime("tomorrow 6:00");
        }
    } else {
        $target = strtotime("next monday 6:00");
    }
    
    $diff = $target - $now;
    $hours = floor($diff / 3600);
    $minutes = floor(($diff / 60) % 60);
    
    return sprintf("%02dh %02dm", $hours, $minutes);
}


/**
 * Update task progress for a specific user and task key.
 * Contains logic to prevent spamming (e.g., Weekly Login only once per day).
 * @param PDO $db PDO Database connection
 * @param int $user_id User ID
 * @param string $task_key The unique key (e.g., 'daily_login', 'daily_focus')
 * @param int $increment How much to add to progress (default 1)
 * @return bool True on success
 */
function updateTaskProgress(PDO $db, $user_id, $task_key, $increment = 1) {
    // 1. Get Task ID and Target Value
    $stmt_task = $db->prepare("SELECT id, target_value FROM tasks WHERE task_key = ?");
    $stmt_task->execute([$task_key]);
    $task = $stmt_task->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) return false; // Task doesn't exist

    $task_id = $task['id'];
    $target_value = $task['target_value'];

    // 2. Ensure user record exists (Sync/Reset first)
    checkAndSyncTasks($db, $user_id);

    // 3. Get current progress (Must include 'last_updated' for spam check)
    $stmt_prog = $db->prepare("SELECT id, current_value, is_completed, last_updated FROM user_task_progress WHERE user_id = ? AND task_id = ?");
    $stmt_prog->execute([$user_id, $task_id]);
    $progress = $stmt_prog->fetch(PDO::FETCH_ASSOC);

    if ($progress) {
        // --- 🛡️ BUG FIX: PREVENT SPAMMING (Specifically for Weekly Login) ---
        // For 'weekly_login', we only allow one increment per day.
        if ($task_key === 'weekly_login') {
            $last_updated_date = date('Y-m-d', strtotime($progress['last_updated']));
            $today_date = date('Y-m-d');
            
            // If the record was already updated TODAY, and the value is > 0, do not increment again.
            // (We allow it if value is 0, which happens right after a weekly reset)
            if ($last_updated_date === $today_date && $progress['current_value'] > 0) {
                return false; // Skip update, user already logged in today
            }
        }
        // ---------------------------------------------------------------------

        if ($progress['is_completed']) return true;

        $new_value = $progress['current_value'] + $increment;
        $is_completed = ($new_value >= $target_value) ? 1 : 0;
        
        if ($new_value > $target_value) $new_value = $target_value;

        // 4. Update the Database
        $stmt_update = $db->prepare("UPDATE user_task_progress SET current_value = ?, is_completed = ?, last_updated = NOW() WHERE id = ?");
        $stmt_update->execute([$new_value, $is_completed, $progress['id']]);
        
        return true;
    }
    return false;
}
?>