<?php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['id'] ?? null;
$action = $input['action'] ?? 'mark_read'; // Default action

if (!$notification_id && $action !== 'clear_all') { // notification_id is not required for clear_all
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Notification ID is required for mark_read action.']);
    exit();
}

try {
    $db = get_db();

    if ($action === 'clear_all') {
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$current_user_id]);
    } elseif ($action === 'mark_read') {
        if ($notification_id === 'all') {
            // Mark all notifications for the user as read
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$current_user_id]);
        } else {
            // Handle specific notification IDs (e.g., 'sr_1', 'gi_5', or a numeric ID)
            $parts = explode('_', $notification_id);
            if (count($parts) === 2) {
                // Dynamic notifications are not stored in the 'notifications' table,
                // so marking them as read means they are handled by the UI.
                // No DB action needed here for dynamic notifications.
            } else {
                // This is a standard notification from the `notifications` table
                $id = (int)$notification_id;
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $current_user_id]);
            }
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Notification action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}
?>