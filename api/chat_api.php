<?php
// api/chat_api.php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../includes/notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
} else { // GET
    $action = $_GET['action'] ?? '';
}

try {
    switch ($action) {
        case 'send_message':
            handle_send_message($db, $current_user_id, $input);
            break;
        case 'get_messages':
            handle_get_messages($db, $current_user_id);
            break;
        case 'get_conversations':
            handle_get_conversations($db, $current_user_id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Chat API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}

exit();

// --- Functions ---

function handle_get_conversations(PDO $db, int $user_id): void {
    $stmt = $db->prepare("
        SELECT
            cr.id as room_id,
            cr.room_type,
            -- Simplified name and avatar selection using JOINs
            CASE
                WHEN cr.room_type = 'direct' THEN CONCAT(partner.first_name, ' ', partner.last_name)
                WHEN cr.room_type = 'group' THEN sg.group_name
            END as name,
            partner.profile_picture_path, -- This is NULL for group conversations

            cm.message as last_message,
            sender.first_name as last_message_sender,
            cm.user_id as last_message_sender_id,
            cm.created_at as last_message_time,
            cm.id as last_message_id,
            ucrs.last_read_message_id as user_last_read_message_id
        FROM (
            -- This subquery correctly finds the single latest room ID for each distinct conversation
            SELECT MAX(id) as id
            FROM chat_rooms
            WHERE
                (room_type = 'direct' AND (partner1_id = :user_id_1 OR partner2_id = :user_id_2))
                OR
                (room_type = 'group' AND group_id IN (SELECT group_id FROM study_group_members WHERE user_id = :user_id_3))
            GROUP BY
                room_type,
                CASE WHEN room_type = 'group' THEN group_id ELSE LEAST(partner1_id, partner2_id) END,
                CASE WHEN room_type = 'group' THEN group_id ELSE GREATEST(partner1_id, partner2_id) END
        ) as distinct_conversations
        JOIN chat_rooms cr ON cr.id = distinct_conversations.id

        -- JOIN to get partner details for direct chats
        LEFT JOIN users partner ON partner.id = IF(cr.room_type = 'direct', IF(cr.partner1_id = :user_id_4, cr.partner2_id, cr.partner1_id), NULL)

        -- JOIN to get group details for group chats
        LEFT JOIN study_groups sg ON sg.group_id = IF(cr.room_type = 'group', cr.group_id, NULL)

        -- JOIN to get the last message details
        LEFT JOIN chat_messages cm ON cm.id = (
            SELECT MAX(sub_cm.id)
            FROM chat_messages sub_cm
            JOIN chat_rooms sub_cr ON sub_cm.room_id = sub_cr.id
            WHERE
                (cr.room_type = 'group' AND sub_cr.group_id = sg.group_id)
                OR
                (cr.room_type = 'direct' AND (
                    (sub_cr.partner1_id = cr.partner1_id AND sub_cr.partner2_id = cr.partner2_id) OR
                    (sub_cr.partner1_id = cr.partner2_id AND sub_cr.partner2_id = cr.partner1_id)
                ))
        )
        LEFT JOIN users sender ON sender.id = cm.user_id
        LEFT JOIN user_chat_room_status ucrs ON ucrs.room_id = cr.id AND ucrs.user_id = :user_id_5
        ORDER BY cm.created_at DESC
    ");

    $stmt->execute([
        ':user_id_1' => $user_id,
        ':user_id_2' => $user_id,
        ':user_id_3' => $user_id,
        ':user_id_4' => $user_id,
        ':user_id_5' => $user_id
    ]);

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

function verify_room_access(PDO $db, int $user_id, int $room_id): bool {
    $stmt = $db->prepare("
        SELECT 1 FROM chat_rooms 
        WHERE id = ? AND (partner1_id = ? OR partner2_id = ? OR group_id IN (
            SELECT group_id FROM study_group_members WHERE user_id = ?
        ))
    ");
    $stmt->execute([$room_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchColumn() !== false;
}

function handle_send_message(PDO $db, int $user_id, array $input): void {
    $room_id = (int)($input['room_id'] ?? 0);
    $message = trim($input['message'] ?? '');

    if (!$room_id || $message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Room ID and message are required.']);
        return;
    }

    if (!verify_room_access($db, $user_id, $room_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this chat room.']);
        return;
    }

    $stmt = $db->prepare("INSERT INTO chat_messages (room_id, user_id, message, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$room_id, $user_id, $message, null]);
    $message_id = $db->lastInsertId();

    // --- Notification Logic ---
    try {
        $room_stmt = $db->prepare("SELECT * FROM chat_rooms WHERE id = ?");
        $room_stmt->execute([$room_id]);
        $room = $room_stmt->fetch(PDO::FETCH_ASSOC);

        if ($room) {
            // Fetch sender's name from DB for a more reliable notification message
            $sender_stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $sender_stmt->execute([$user_id]);
            $sender = $sender_stmt->fetch(PDO::FETCH_ASSOC);
            $sender_name = ($sender) ? trim($sender['first_name'] . ' ' . $sender['last_name']) : 'A user';

            if ($room['room_type'] === 'direct') {
                $receiver_id = ($room['partner1_id'] == $user_id) ? $room['partner2_id'] : $room['partner1_id'];
                if ($receiver_id) {
                    $notification_message = "New message from " . htmlspecialchars($sender_name);
                    $link = "chat.php?user_id=" . $user_id;
                    create_notification($receiver_id, 'direct_message', $notification_message, $link);
                }
            } elseif ($room['room_type'] === 'group' && $room['group_id']) {
                $group_stmt = $db->prepare("SELECT group_name FROM study_groups WHERE group_id = ?");
                $group_stmt->execute([$room['group_id']]);
                $group_name = $group_stmt->fetchColumn();

                $members_stmt = $db->prepare("SELECT user_id FROM study_group_members WHERE group_id = ? AND user_id != ?");
                $members_stmt->execute([$room['group_id'], $user_id]);
                $members = $members_stmt->fetchAll(PDO::FETCH_COLUMN);

                if ($group_name && !empty($members)) {
                    $notification_message = "New message in group: " . htmlspecialchars($group_name);
                    $link = "group-chat.php?group_id=" . $room['group_id'];
                    foreach ($members as $member_id) {
                        create_notification($member_id, 'group_message', $notification_message, $link);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Notification creation failed after sending message: " . $e->getMessage());
    }

    // Task: Knowledge Share
    require_once __DIR__ . '/../includes/TaskLogic.php';
    updateTaskProgress($db, $user_id, 'daily_chat');

    // Send response to client AFTER all processing is done.
    echo json_encode(['success' => true, 'message_id' => $message_id]);
}

function handle_get_messages(PDO $db, int $user_id): void {
    $room_id = (int)($_GET['room_id'] ?? 0);
    $last_id = (int)($_GET['last_id'] ?? 0);

    if (!$room_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Room ID is required.']);
        return;
    }

    if (!verify_room_access($db, $user_id, $room_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this chat room.']);
        return;
    }

    // Get current room details to find all associated rooms
    $stmt = $db->prepare("SELECT * FROM chat_rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $current_room = $stmt->fetch();

    if (!$current_room) {
        echo json_encode(['success' => false, 'message' => 'Internal error: Room not found after verification.']);
        return;
    }

    $all_room_ids = [];
    if ($current_room['room_type'] === 'group' && $current_room['group_id']) {
        $stmt = $db->prepare("SELECT id FROM chat_rooms WHERE group_id = ?");
        $stmt->execute([$current_room['group_id']]);
        $all_room_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($current_room['room_type'] === 'direct') {
        $p1 = $current_room['partner1_id'];
        $p2 = $current_room['partner2_id'];
        $stmt = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'direct' AND ((partner1_id = ? AND partner2_id = ?) OR (partner1_id = ? AND partner2_id = ?))");
        $stmt->execute([$p1, $p2, $p2, $p1]);
        $all_room_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    if (empty($all_room_ids)) {
        $all_room_ids = [$room_id]; // Fallback, should not be empty
    }

    $placeholders = implode(',', array_fill(0, count($all_room_ids), '?'));
    $params = array_merge($all_room_ids, [$last_id]);

    $stmt = $db->prepare("\n        SELECT \n            cm.id, cm.user_id, cm.message, cm.file_path, cm.created_at,\n            CONCAT(u.first_name, ' ', u.last_name) as user_name,\n            u.profile_picture_path\n        FROM chat_messages cm\n        JOIN users u ON cm.user_id = u.id\n        WHERE cm.room_id IN ($placeholders) AND cm.id > ?\n        ORDER BY cm.created_at ASC\n    ");
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
}
?>