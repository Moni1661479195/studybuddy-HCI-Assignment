<?php
require_once '../session.php';
require_once '../lib/db.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

// Handle GET requests for fetching recipients
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $type = $_GET['type'] ?? ''; // 'user' or 'group'

    if ($action === 'get_recipients') {
        if ($type === 'user') {
            // Fetch friends (study mates) who are NOT admins
            // Assuming "study mates" means accepted study requests
            $stmt = $db->prepare("
                SELECT u.id, u.first_name, u.last_name 
                FROM users u
                JOIN study_requests sr ON (sr.sender_id = u.id OR sr.receiver_id = u.id)
                WHERE sr.status = 'accepted'
                  AND (sr.sender_id = ? OR sr.receiver_id = ?)
                  AND u.id != ?
                  AND u.is_admin = 0
                ORDER BY u.first_name ASC
            ");
            $stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format names
            foreach ($recipients as &$r) {
                $r['name'] = $r['first_name'] . ' ' . $r['last_name'];
            }
        } elseif ($type === 'group') {
            // Fetch groups the user belongs to
            $stmt = $db->prepare("
                SELECT sg.group_id as id, sg.group_name as name
                FROM study_groups sg
                JOIN study_group_members sgm ON sg.group_id = sgm.group_id
                WHERE sgm.user_id = ?
                ORDER BY sg.group_name ASC
            ");
            $stmt->execute([$current_user_id]);
            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit();
        }

        echo json_encode(['success' => true, 'recipients' => $recipients]);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$plan_id = $data['plan_id'] ?? null;
$recipient_type = $data['recipient_type'] ?? null; // 'user' or 'group'
$recipient_id = $data['recipient_id'] ?? null;

if (!$plan_id || !$recipient_type || !$recipient_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // 1. Get Plan Details
    $stmt = $db->prepare("SELECT name, due_date FROM study_plans WHERE id = ? AND user_id = ?");
    $stmt->execute([$plan_id, $current_user_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        throw new Exception("Study plan not found or you don't have permission to share it.");
    }

    // 2. Resolve Room ID
    $room_id = null;

    if ($recipient_type === 'group') {
        // Find chat room for the group
        $stmt = $db->prepare("SELECT id FROM chat_rooms WHERE group_id = ? AND room_type = 'group'");
        $stmt->execute([$recipient_id]);
        $room_id = $stmt->fetchColumn();

        if (!$room_id) {
            // Create if not exists (though it usually should)
            $stmt = $db->prepare("INSERT INTO chat_rooms (room_type, group_id) VALUES ('group', ?)");
            $stmt->execute([$recipient_id]);
            $room_id = $db->lastInsertId();
        }
    } elseif ($recipient_type === 'user') {
        // Find direct chat room
        $stmt = $db->prepare("
            SELECT id FROM chat_rooms 
            WHERE room_type = 'direct' 
            AND ((partner1_id = ? AND partner2_id = ?) OR (partner1_id = ? AND partner2_id = ?))
        ");
        $stmt->execute([$current_user_id, $recipient_id, $recipient_id, $current_user_id]);
        $room_id = $stmt->fetchColumn();

        if (!$room_id) {
            // Create new direct room
            $stmt = $db->prepare("INSERT INTO chat_rooms (room_type, partner1_id, partner2_id) VALUES ('direct', ?, ?)");
            $stmt->execute([$current_user_id, $recipient_id]);
            $room_id = $db->lastInsertId();
        }
    } else {
        throw new Exception("Invalid recipient type.");
    }

    if (!$room_id) {
        throw new Exception("Could not determine chat room.");
    }

    // 3. Send Message
    $plan_url = "view_plan.php?id=" . $plan_id;
    
    // Create a rich HTML message
    $message = "
        <div style='border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background-color: #f9fafb;'>
            <p style='margin: 0 0 8px 0; font-weight: 600; color: #1f2937;'>Shared Study Plan</p>
            <p style='margin: 0 0 4px 0; font-size: 1.1em; color: #111827;'>📝 <strong>" . htmlspecialchars($plan['name']) . "</strong></p>
            <p style='margin: 0 0 12px 0; font-size: 0.9em; color: #6b7280;'>Due: " . htmlspecialchars($plan['due_date']) . "</p>
            <a href='" . $plan_url . "' style='display: inline-block; background-color: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9em; font-weight: 500;'>View Plan Details</a>
        </div>
    ";
    
    $stmt = $db->prepare("INSERT INTO chat_messages (room_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$room_id, $current_user_id, $message]);
    
    // 4. Trigger Notification (Simplified from chat_api.php)
    if ($recipient_type === 'user') {
        $link = "chat.php?user_id=" . $current_user_id;
        create_notification($recipient_id, 'direct_message', "Shared a study plan with you", $link);
    } elseif ($recipient_type === 'group') {
        // Notify group members (excluding self)
        $stmt = $db->prepare("SELECT user_id FROM study_group_members WHERE group_id = ? AND user_id != ?");
        $stmt->execute([$recipient_id, $current_user_id]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $link = "group-chat.php?group_id=" . $recipient_id;
        foreach ($members as $member_id) {
            create_notification($member_id, 'group_message', "New study plan shared in group", $link);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Plan shared successfully!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>