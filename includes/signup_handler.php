<?php
error_log("signup_handler.php: Script started.");
require_once __DIR__ . '/../session.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
error_log("signup_handler.php: is_ajax = " . ($is_ajax ? 'true' : 'false'));

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    error_log("signup_handler.php: User already logged in, redirecting to dashboard.");
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../lib/db.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("signup_handler.php: POST request received.");
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $password = trim($_POST['password']);
    $retype_password = trim($_POST['retype_password']);
    $verification_code = trim($_POST['verification_code']);

    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($gender)) {
        $errors[] = "Please select your gender.";
    }
    if ($password !== $retype_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!isset($_POST['terms'])) {
        $errors[] = "You must agree to the terms of service.";
    }

    if (empty($errors)) {
        $db = get_db(); // Get the database connection

        // Verify the email code first
        $code_stmt = $db->prepare("SELECT * FROM email_verifications WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $code_stmt->execute([$email]);
        $stored_code = $code_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stored_code || $stored_code['code'] !== $verification_code) {
            $errors[] = "Invalid verification code.";
        } else {
            $code_time = new DateTime($stored_code['created_at']);
            $now = new DateTime();
            if (($now->getTimestamp() - $code_time->getTimestamp()) > 600) { // 10 minutes expiry
                $errors[] = "Verification code has expired.";
            }
        }

        // Check if email already exists
        if (empty($errors)) {
            $sql = "SELECT id FROM users WHERE email = :email";
            if ($stmt = $db->prepare($sql)) {
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors[] = "This email is already registered.";
                }
                unset($stmt);
            }
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (email, first_name, last_name, gender, password_hash) VALUES (:email, :first_name, :last_name, :gender, :password_hash)";

        if ($stmt = $db->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
            $stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
            $stmt->bindParam(":gender", $gender, PDO::PARAM_STR);
            $stmt->bindParam(":password_hash", $hashed_password, PDO::PARAM_STR);

            if ($stmt->execute()) {
                // Get the ID of the newly created user
                $user_id = $db->lastInsertId();

                // Invalidate the verification code
                $delete_stmt = $db->prepare("DELETE FROM email_verifications WHERE email = ?");
                $delete_stmt->execute([$email]);

                // --- Start Full Auto-Login Logic ---
                // Fetch the full user record to get all necessary data
                $user_stmt = $db->prepare("SELECT id, first_name, profile_picture_path, is_admin FROM users WHERE id = ?");
                $user_stmt->execute([$user_id]);
                $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_data) {
                    // Start a new, clean session to prevent session fixation
                    session_regenerate_id(true);

                    // Store all necessary data in session variables
                    $_SESSION["user_id"] = (int)$user_data['id'];
                    $_SESSION["first_name"] = $user_data['first_name'];
                    $_SESSION["user_avatar"] = $user_data['profile_picture_path'];
                    $_SESSION["is_admin"] = (bool)$user_data['is_admin'];

                    // Update user's online status and session ID in the database
                    $update_sql = "UPDATE users SET is_online = 1, last_seen = NOW(), session_id = :session_id WHERE id = :user_id";
                    if ($update_stmt = $db->prepare($update_sql)) {
                        $session_id = session_id();
                        $update_stmt->bindParam(":session_id", $session_id, PDO::PARAM_STR);
                        $update_stmt->bindParam(":user_id", $user_data['id'], PDO::PARAM_INT);
                        $update_stmt->execute();
                    }

                    $response['success'] = true;
                    $response['message'] = 'Registration successful! Logging you in...';
                    $response['redirect'] = 'user_profile.php?id=' . $user_id . '&new_user=true';
                    error_log("signup_handler.php: Auto-login successful. Redirect set to user_profile.php for new user.");
                } else {
                    // This is a fallback and should not normally be reached
                    $errors[] = "Could not fetch new user data for auto-login.";
                    error_log("signup_handler.php: Auto-login failed: Could not fetch new user data.");
                }
                // --- End Full Auto-Login Logic ---
            } else {
                $errors[] = "Something went wrong. Please try again later.";
                error_log("signup_handler.php: Insert user failed.");
            }
            unset($stmt);
        }
    }

    if (!empty($errors)) {
        $response['message'] = implode("<br>", $errors);
        error_log("signup_handler.php: Errors found: " . $response['message']);
    }
} else {
    error_log("signup_handler.php: Not a POST request.");
}

error_log("signup_handler.php: Final response before output/redirect: " . json_encode($response));

if ($is_ajax) {
    error_log("signup_handler.php: AJAX request detected. Sending JSON response.");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else if ($response['success'] && isset($response['redirect'])) {
    error_log("signup_handler.php: Non-AJAX success. Redirecting to " . $response['redirect']);
    session_write_close(); // Ensure session data is saved before redirecting
    header("Location: " . $response['redirect']);
    exit();
} else if (!$response['success']) {
    error_log("signup_handler.php: Non-AJAX failure. No redirect.");
    // For non-AJAX requests, if signup fails, we might want to set a session variable
    // or pass the error message differently to the signup.php page.
}
?>