<?php
// api/send_verification_code.php

// Set the default timezone to UTC to ensure consistent time calculations
date_default_timezone_set('UTC');

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../config.php'; // Load config file

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address provided.']);
    exit();
}

$db = get_db();

// Rate Limiting: Check if a code was sent recently (Temporarily Disabled for Debugging)
/*
$stmt = $db->prepare("SELECT created_at FROM email_verifications WHERE email = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$email]);
$last_code = $stmt->fetch(PDO::FETCH_ASSOC);

if ($last_code) {
    $last_code_time = new DateTime($last_code['created_at']);
    $now = new DateTime();
    if (($now->getTimestamp() - $last_code_time->getTimestamp()) < 60) { // 60-second cooldown
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Please wait a minute before requesting another code.']);
        exit();
    }
}
*/
// Generate a 6-digit code
try {
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate a secure code.']);
    exit();
}


// Store the code in the database
$stmt = $db->prepare("INSERT INTO email_verifications (email, code) VALUES (?, ?)");
if (!$stmt->execute([$email, $code])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to store verification code.']);
    exit();
}

// --- Email Sending --- //
// Load PHPMailer manually
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    // Configure SMTP settings from config.php
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = SMTP_AUTH;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;

    //Recipients
    $mail->setFrom(SMTP_USER, 'StudyBuddy'); // Sender email and name
    $mail->addAddress($email);

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Your StudyBuddy Verification Code';
    $mail->Body    = "Your verification code is: <b>{$code}</b>";
    $mail->AltBody = "Your verification code is: {$code}";

    $mail->send();
} catch (Exception $e) {
    // Log error and inform the user
    error_log("Mailer Error: {$mail->ErrorInfo}");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again later.']);
    exit();
}

echo json_encode(['success' => true, 'message' => 'A verification code has been sent to your email address.']);
?>
