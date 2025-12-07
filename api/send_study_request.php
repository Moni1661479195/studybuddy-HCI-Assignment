<?php
// api/send_study_request.php
require_once '../session.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../includes/notifications.php';

// Always redirect back to the quick-match page
$redirect_url = '../quick-match.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $redirect_url);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? ''); // Kept for future use if you add a message field

if (!$receiver_id || $receiver_id === $current_user_id) {
    $_SESSION['error'] = "Invalid user to send a request to.";
    header("Location: " . $redirect_url);
    exit();
}

try {
    $db = get_db();
    
    // Check if a pending request already exists (either way)
    $check_stmt = $db->prepare("
        SELECT 1 FROM study_requests 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND status = 'pending'
    ");
    $check_stmt->execute([$current_user_id, $receiver_id, $receiver_id, $current_user_id]);
    
    if ($check_stmt->fetch()) {
        $_SESSION['info'] = "A study request is already pending with this user.";
        header("Location: " . $redirect_url);
        exit();
    }
    
    // Check if you are already partners
    $partner_check = $db->prepare("
        SELECT 1 FROM study_partners 
        WHERE ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?))
        AND is_active = 1
    ");
    $partner_check->execute([$current_user_id, $receiver_id, $receiver_id, $current_user_id]);
    
    if ($partner_check->fetch()) {
        $_SESSION['info'] = "You are already study partners with this user.";
        header("Location: " . $redirect_url);
        exit();
    }
    
    // If all checks pass, insert the study request
    $stmt = $db->prepare("
        INSERT INTO study_requests (sender_id, receiver_id, message, status, requested_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$current_user_id, $receiver_id, $message]);

    // Create a notification for the recipient
    $sender_username = $_SESSION['username'] ?? 'A user';
    $notification_message = htmlspecialchars($sender_username) . ' has sent you a study partner request.';
    create_notification($receiver_id, 'study_request', $notification_message, 'dashboard.php#study-requests');
    
    $_SESSION['success'] = "Study request sent successfully!";
    
} catch (Exception $e) {
    error_log("Send request error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to send the request. Please try again.";
}

header("Location: " . $redirect_url);
exit();
?>