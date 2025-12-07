<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_log("ajax_signup.php: Script started.");

require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("ajax_signup.php: POST request received.");
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $verification_code = trim($_POST['verification_code'] ?? '');

    if (empty($email) || empty($first_name) || empty($last_name) || empty($password) || empty($verification_code)) {
        $response['message'] = "Please fill in all fields.";
        error_log("ajax_signup.php: Validation error - missing fields.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
        error_log("ajax_signup.php: Validation error - invalid email format.");
    } elseif (strlen($password) < 8) {
        $response['message'] = "Password must be at least 8 characters long.";
        error_log("ajax_signup.php: Validation error - password too short.");
    } else {
        try {
            $db = get_db();

            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $response['message'] = "This email is already registered.";
                error_log("ajax_signup.php: Email already registered.");
                echo json_encode($response);
                exit();
            }

            // Verify the email code
            $code_stmt = $db->prepare("SELECT * FROM email_verifications WHERE email = ? ORDER BY created_at DESC LIMIT 1");
            $code_stmt->execute([$email]);
            $stored_code = $code_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$stored_code || $stored_code['code'] !== $verification_code) {
                $response['message'] = "Invalid verification code.";
                error_log("ajax_signup.php: Invalid verification code.");
            } else {
                $code_time = new DateTime($stored_code['created_at']);
                $now = new DateTime();
                if (($now->getTimestamp() - $code_time->getTimestamp()) > 600) { // 10 minutes expiry
                    $response['message'] = "Verification code has expired.";
                    error_log("ajax_signup.php: Verification code expired.");
                } else {
                    // All checks passed, proceed with registration
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (email, first_name, last_name, password_hash) VALUES (:email, :first_name, :last_name, :password_hash)";
                    $insert_stmt = $db->prepare($sql);
                    $insert_stmt->bindParam(":email", $email, PDO::PARAM_STR);
                    $insert_stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
                    $insert_stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
                    $insert_stmt->bindParam(":password_hash", $hashed_password, PDO::PARAM_STR);

                    if ($insert_stmt->execute()) {
                        $user_id = $db->lastInsertId();

                        // Invalidate the verification code
                        $delete_stmt = $db->prepare("DELETE FROM email_verifications WHERE email = ?");
                        $delete_stmt->execute([$email]);

                        // --- Start Full Auto-Login Logic ---
                        $user_stmt = $db->prepare("SELECT id, first_name, profile_picture_path, is_admin FROM users WHERE id = ?");
                        $user_stmt->execute([$user_id]);
                        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

                        if ($user_data) {
                            session_regenerate_id(true);

                            $_SESSION["user_id"] = (int)$user_data['id'];
                            $_SESSION["first_name"] = $user_data['first_name'];
                            $_SESSION["user_avatar"] = $user_data['profile_picture_path'];
                            $_SESSION["is_admin"] = (bool)$user_data['is_admin'];

                            $update_sql = "UPDATE users SET is_online = 1, last_seen = NOW(), session_id = :session_id WHERE id = :user_id";
                            if ($update_stmt = $db->prepare($update_sql)) {
                                $session_id = session_id();
                                $update_stmt->bindParam(":session_id", $session_id, PDO::PARAM_STR);
                                $update_stmt->bindParam(":user_id", $user_data['id'], PDO::PARAM_INT);
                                $update_stmt->execute();
                            }

                            $response['success'] = true;
                            $response['message'] = 'Registration successful! Logging you in...';
                            $response['redirect'] = 'dashboard.php';
                            error_log("ajax_signup.php: Auto-login successful. Redirect set to dashboard.php");
                        } else {
                            $response['message'] = "Registration succeeded, but auto-login failed.";
                            error_log("ajax_signup.php: Auto-login failed: Could not fetch new user data.");
                        }
                        // --- End Full Auto-Login Logic ---
                    } else {
                        $response['message'] = "Something went wrong during registration. Please try again later.";
                        error_log("ajax_signup.php: Insert user failed.");
                    }
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
            error_log("ajax_signup.php: Database error: " . $e->getMessage());
        }
    }
} else {
    error_log("ajax_signup.php: Not a POST request.");
}

error_log("ajax_signup.php: Final response before output: " . json_encode($response));
echo json_encode($response);
exit();
?>