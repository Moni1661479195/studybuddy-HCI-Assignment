<?php
session_start();
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

// Admins cannot be matched
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Administrators cannot use the quick match feature.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

$request_data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $request_data['action'] ?? null;

try {
    switch ($action) {
        case 'join_queue':
            $desired_skill_level = $request_data['desiredSkillLevel'] ?? 'any';
            $desired_gender = $request_data['desiredGender'] ?? 'any';
            
            // Insert or update the user's entry in the queue
            $sql = "INSERT INTO quick_match_queue (user_id, desired_skill_level, desired_gender, status, requested_at) 
                    VALUES (?, ?, ?, 'open', NOW()) 
                    ON DUPLICATE KEY UPDATE 
                        desired_skill_level = VALUES(desired_skill_level), 
                        desired_gender = VALUES(desired_gender), 
                        status = 'open', 
                        requested_at = NOW()";
            $stmt = $db->prepare($sql);
            $stmt->execute([$current_user_id, $desired_skill_level, $desired_gender]);

            echo json_encode(['success' => true, 'message' => 'Joined quick match queue.']);
            break;

        case 'leave_queue':
            $stmt = $db->prepare("DELETE FROM quick_match_queue WHERE user_id = ?");
            $stmt->execute([$current_user_id]);
            echo json_encode(['success' => true, 'message' => 'Left quick match queue.']);
            break;

        case 'check_match':
            $stmt = $db->prepare("SELECT q.status, q.matched_with, u.id, u.first_name, u.last_name, u.skill_level, u.gender 
                                 FROM quick_match_queue q 
                                 LEFT JOIN users u ON q.matched_with = u.id 
                                 WHERE q.user_id = ?");
            $stmt->execute([$current_user_id]);
            $my_status = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($my_status && $my_status['status'] === 'matched') {
                echo json_encode([
                    'matched' => true,
                    'user' => [
                        'id' => $my_status['id'],
                        'first_name' => $my_status['first_name'],
                        'last_name' => $my_status['last_name'],
                        'skill_level' => $my_status['skill_level'],
                        'gender' => $my_status['gender']
                    ]
                ]);
                // Clean up the matched entry for the current user
                $cleanup_stmt = $db->prepare("DELETE FROM quick_match_queue WHERE user_id = ?");
                $cleanup_stmt->execute([$current_user_id]);
            } else {
                echo json_encode(['matched' => false]);
            }
            break;

        case 'queue_count':
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM quick_match_queue WHERE status = 'open'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'count' => (int)$result['count']]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} catch (PDOException $e) {
    error_log("Quick Match API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
} catch (Exception $e) {
    error_log("Quick Match API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}
?>