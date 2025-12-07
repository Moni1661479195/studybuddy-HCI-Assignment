<?php
/**
 * Study Plans API
 * Handles all CRUD operations for study plans and tasks
 */

require_once '../session.php';
require_once '../lib/db.php';

// Set JSON response headers
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            // Get all plans for current user with progress
            getAllPlans($db, $user_id);
            break;
            
        case 'get_plan':
            // Get single plan with all tasks
            $plan_id = $_GET['plan_id'] ?? null;
            if (!$plan_id) {
                throw new Exception('Plan ID is required');
            }
            getPlanDetails($db, $user_id, $plan_id);
            break;
            
        case 'create':
            // Create new plan with tasks
            if ($method !== 'POST') {
                throw new Exception('POST method required');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            createPlan($db, $user_id, $data);
            break;
            
        case 'update':
            // Update existing plan
            if ($method !== 'POST') {
                throw new Exception('POST method required');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            updatePlan($db, $user_id, $data);
            break;
            
        case 'delete':
            // Delete plan
            if ($method !== 'POST') {
                throw new Exception('POST method required');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $plan_id = $data['plan_id'] ?? null;
            if (!$plan_id) {
                throw new Exception('Plan ID is required');
            }
            deletePlan($db, $user_id, $plan_id);
            break;
            
        case 'toggle_task':
            // Toggle task completion status
            if ($method !== 'POST') {
                throw new Exception('POST method required');
            }
            $data = json_decode(file_get_contents('php://input'), true);
            toggleTaskCompletion($db, $user_id, $data);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get all study plans for a user with progress calculation
 */
function getAllPlans($db, $user_id) {
    $search = $_GET['search'] ?? '';
    $params = [$user_id];
    
    $sql = "SELECT 
                    sp.id,
                    sp.name,
                    sp.description,
                    sp.due_date,
                    sp.status,
                    sp.created_at,
                    COUNT(spt.id) AS total_tasks,
                    COALESCE(SUM(CASE WHEN spt.is_completed = 1 THEN 1 ELSE 0 END), 0) AS completed_tasks,
                    CASE 
                        WHEN COUNT(spt.id) = 0 THEN 0
                        ELSE ROUND((COALESCE(SUM(CASE WHEN spt.is_completed = 1 THEN 1 ELSE 0 END), 0) / COUNT(spt.id)) * 100, 0)
                    END AS progress
                FROM study_plans sp
                LEFT JOIN study_plan_tasks spt ON sp.id = spt.plan_id
                WHERE sp.user_id = ?";
                
    if (!empty($search)) {
        $sql .= " AND sp.name LIKE ?";
        $params[] = "%$search%";
    }
    
    $sql .= " GROUP BY sp.id, sp.name, sp.description, sp.due_date, sp.status, sp.created_at
              ORDER BY sp.due_date ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check and update status for each plan
    $current_date = date('Y-m-d');
    foreach ($plans as &$plan) {
        // Determine actual status
        if ($plan['completed_tasks'] == $plan['total_tasks'] && $plan['total_tasks'] > 0) {
            $plan['status'] = 'completed';
        } elseif ($plan['due_date'] < $current_date) {
            $plan['status'] = 'overdue';
        } else {
            $plan['status'] = 'active';
        }
        
        // Update status in database if needed
        $update_sql = "UPDATE study_plans SET status = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->execute([$plan['status'], $plan['id']]);
    }
    
    echo json_encode(['success' => true, 'plans' => $plans]);
}

/**
 * Get detailed information about a specific plan including all tasks
 */
function getPlanDetails($db, $user_id, $plan_id) {
    // Get plan info - Allow viewing any plan if you have the ID (for sharing)
    // We removed 'AND user_id = ?' to allow shared access
    $sql = "SELECT * FROM study_plans WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        throw new Exception('Plan not found');
    }

    // Determine ownership
    $is_owner = ($plan['user_id'] == $user_id);
    
    // Get all tasks for this plan
    $task_sql = "SELECT * FROM study_plan_tasks WHERE plan_id = ? ORDER BY display_order ASC, id ASC";
    $task_stmt = $db->prepare($task_sql);
    $task_stmt->execute([$plan_id]);
    $tasks = $task_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate progress
    $total_tasks = count($tasks);
    $completed_tasks = array_reduce($tasks, function($carry, $task) {
        return $carry + ($task['is_completed'] ? 1 : 0);
    }, 0);
    
    $progress = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100, 0) : 0;
    
    $plan['tasks'] = $tasks;
    $plan['total_tasks'] = $total_tasks;
    $plan['completed_tasks'] = $completed_tasks;
    $plan['progress'] = $progress;
    $plan['is_owner'] = $is_owner; // Add ownership flag
    
    echo json_encode(['success' => true, 'plan' => $plan]);
}

/**
 * Create a new study plan with tasks
 */
function createPlan($db, $user_id, $data) {
    // Validate input
    if (empty($data['name'])) {
        throw new Exception('Plan name is required');
    }
    if (empty($data['due_date'])) {
        throw new Exception('Due date is required');
    }
    if (empty($data['tasks']) || count($data['tasks']) < 1) {
        throw new Exception('At least one task is required');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Insert plan
        $sql = "INSERT INTO study_plans (user_id, name, description, due_date) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $user_id,
            $data['name'],
            $data['description'] ?? '',
            $data['due_date']
        ]);
        
        $plan_id = $db->lastInsertId();
        
        // Insert tasks
        $task_sql = "INSERT INTO study_plan_tasks (plan_id, task_name, task_description, due_date, display_order) 
                     VALUES (?, ?, ?, ?, ?)";
        $task_stmt = $db->prepare($task_sql);
        
        foreach ($data['tasks'] as $index => $task) {
            if (empty($task['task_name'])) {
                throw new Exception('Task name is required for all tasks');
            }
            
            $task_stmt->execute([
                $plan_id,
                $task['task_name'],
                $task['task_description'] ?? '',
                $task['due_date'] ?? null,
                $index + 1
            ]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Plan created successfully',
            'plan_id' => $plan_id
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Update an existing study plan
 */
function updatePlan($db, $user_id, $data) {
    $plan_id = $data['plan_id'] ?? null;
    
    if (!$plan_id) {
        throw new Exception('Plan ID is required');
    }
    
    // Verify ownership
    $check_sql = "SELECT id FROM study_plans WHERE id = ? AND user_id = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([$plan_id, $user_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Plan not found or access denied');
    }
    
    // Validate input
    if (empty($data['name'])) {
        throw new Exception('Plan name is required');
    }
    if (empty($data['due_date'])) {
        throw new Exception('Due date is required');
    }
    if (empty($data['tasks']) || count($data['tasks']) < 1) {
        throw new Exception('At least one task is required');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update plan
        $sql = "UPDATE study_plans SET name = ?, description = ?, due_date = ? WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['due_date'],
            $plan_id,
            $user_id
        ]);
        
        // Delete existing tasks
        $delete_sql = "DELETE FROM study_plan_tasks WHERE plan_id = ?";
        $delete_stmt = $db->prepare($delete_sql);
        $delete_stmt->execute([$plan_id]);
        
        // Insert updated tasks
        $task_sql = "INSERT INTO study_plan_tasks (plan_id, task_name, task_description, is_completed, due_date, display_order) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        $task_stmt = $db->prepare($task_sql);
        
        foreach ($data['tasks'] as $index => $task) {
            if (empty($task['task_name'])) {
                throw new Exception('Task name is required for all tasks');
            }
            
            $task_stmt->execute([
                $plan_id,
                $task['task_name'],
                $task['task_description'] ?? '',
                $task['is_completed'] ?? 0,
                $task['due_date'] ?? null,
                $index + 1
            ]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Plan updated successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Delete a study plan
 */
function deletePlan($db, $user_id, $plan_id) {
    // Verify ownership
    $check_sql = "SELECT id FROM study_plans WHERE id = ? AND user_id = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([$plan_id, $user_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Plan not found or access denied');
    }
    
    // Delete plan (tasks will be deleted automatically due to CASCADE)
    $sql = "DELETE FROM study_plans WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$plan_id, $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Plan deleted successfully'
    ]);
}

/**
 * Toggle task completion status
 */
function toggleTaskCompletion($db, $user_id, $data) {
    $task_id = $data['task_id'] ?? null;
    $is_completed = $data['is_completed'] ?? 0;
    
    if (!$task_id) {
        throw new Exception('Task ID is required');
    }
    
    // Verify task belongs to user's plan
    $check_sql = "SELECT spt.id 
                  FROM study_plan_tasks spt 
                  JOIN study_plans sp ON spt.plan_id = sp.id 
                  WHERE spt.id = ? AND sp.user_id = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([$task_id, $user_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Task not found or access denied');
    }
    
    // Update task completion status
    $sql = "UPDATE study_plan_tasks SET is_completed = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$is_completed, $task_id]);
    
    // Check if Plan is fully completed
    if ($is_completed) {
        // Get plan_id for this task
        $stmt_pid = $db->prepare("SELECT plan_id FROM study_plan_tasks WHERE id = ?");
        $stmt_pid->execute([$task_id]);
        $plan_id = $stmt_pid->fetchColumn();

        if ($plan_id) {
            // Check if any incomplete tasks remain
            $stmt_check = $db->prepare("SELECT COUNT(*) FROM study_plan_tasks WHERE plan_id = ? AND is_completed = 0");
            $stmt_check->execute([$plan_id]);
            $incomplete_count = $stmt_check->fetchColumn();

            if ($incomplete_count == 0) {
                // Task: Master Planner (Weekly)
                require_once __DIR__ . '/../includes/TaskLogic.php';
                updateTaskProgress($db, $user_id, 'weekly_plan');
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Task status updated successfully'
    ]);
}
?>
