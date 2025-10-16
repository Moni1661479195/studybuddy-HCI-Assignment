<?php
// api/chat_api.php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

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
    $stmt = $db->prepare("\n        SELECT \n            cr.id as room_id, \n            cr.room_type, \n            CASE\n                WHEN cr.room_type = 'direct' THEN (\n                    SELECT CONCAT(u.first_name, ' ', u.last_name) \n                    FROM users u \n                    WHERE u.id = IF(cr.partner1_id = :user_id_1, cr.partner2_id, cr.partner1_id)\n                )\n                WHEN cr.room_type = 'group' THEN (\n                    SELECT sg.group_name \n                    FROM study_groups sg \n                    WHERE sg.group_id = cr.group_id\n                )\n            END as name,\n            (SELECT message FROM chat_messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message,\n            (SELECT u.first_name FROM chat_messages cm JOIN users u ON cm.user_id = u.id WHERE cm.room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message_sender,\n            (SELECT user_id FROM chat_messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message_sender_id,\n            (SELECT created_at FROM chat_messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message_time,\n            (SELECT id FROM chat_messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message_id,\n            (SELECT last_read_message_id FROM user_chat_room_status WHERE user_id = :current_user_id_for_read_status AND room_id = cr.id) as user_last_read_message_id\n        FROM chat_rooms cr\n        WHERE\n            (cr.room_type = 'direct' AND (cr.partner1_id = :user_id_2 OR cr.partner2_id = :user_id_3))\n            OR\n            (cr.room_type = 'group' AND cr.group_id IN (SELECT group_id FROM study_group_members WHERE user_id = :user_id_4))\n        ORDER BY last_message_time DESC\n    ");

    $stmt->execute([
        ':user_id_1' => $user_id,
        ':user_id_2' => $user_id,
        ':user_id_3' => $user_id,
        ':user_id_4' => $user_id,
        ':current_user_id_for_read_status' => $user_id
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

    $stmt = $db->prepare("INSERT INTO chat_messages (room_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$room_id, $user_id, $message]);

    echo json_encode(['success' => true, 'message_id' => $db->lastInsertId()]);
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

    $stmt = $db->prepare("
        SELECT 
            cm.id,
            cm.user_id,
            cm.message,
            cm.created_at,
            CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.room_id = ? AND cm.id > ?
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$room_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
}
?>