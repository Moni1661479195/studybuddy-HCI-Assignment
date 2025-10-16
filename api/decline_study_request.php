require_once __DIR__ . '/../lib/db.php';
require_once '../session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit();
}

try {
    $db = get_db();

    // Verify the request exists and belongs to the current user as receiver, and is pending
    $stmt_verify = $db->prepare("SELECT sender_id FROM study_requests WHERE request_id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt_verify->execute([$request_id, $user_id]);
    $sender_id = $stmt_verify->fetchColumn();

    if (!$sender_id) {
        // Check if the request exists but is not pending
        $stmt_check_status = $db->prepare("SELECT status FROM study_requests WHERE request_id = ? AND receiver_id = ?");
        $stmt_check_status->execute([$request_id, $user_id]);
        $current_status = $stmt_check_status->fetchColumn();

        if ($current_status === 'accepted') {
            echo json_encode(['success' => false, 'message' => 'This request has already been accepted by you.']);
        } elseif ($current_status === 'declined') {
            echo json_encode(['success' => false, 'message' => 'This request was already declined by you.']);
        } elseif ($current_status) {
            echo json_encode(['success' => false, 'message' => 'This request is no longer pending (it might have been cancelled by the sender).']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
        }
        exit();
    }

    // Instead of updating status to declined, we delete the request to keep the state consistent for both users
    $stmt_delete = $db->prepare("DELETE FROM study_requests WHERE request_id = ?");
    $stmt_delete->execute([$request_id]);

    if ($stmt_delete->rowCount() > 0) {
        // Notify the sender that their request was declined
        $receiver_name_stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $receiver_name_stmt->execute([$user_id]);
        $receiver_name = $receiver_name_stmt->fetch(PDO::FETCH_ASSOC);
        $message = htmlspecialchars($receiver_name['first_name'] . ' ' . $receiver_name['last_name']) . " declined your study request.";
        
        $stmt_notification = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt_notification->execute([$sender_id, $message]);

        echo json_encode(['success' => true, 'message' => 'Study request declined successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to decline request.']);
    }
} catch (Exception $e) {
    error_log("Error declining study request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while declining the request.']);
}