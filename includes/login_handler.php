<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../session.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../lib/db.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($email) || empty($password)) {
        $response['message'] = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id, first_name, password_hash, profile_picture_path, is_admin FROM users WHERE email = :email";

        $db = get_db(); // Get the database connection
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if email exists, if yes then verify password
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                        $user_id = $row['id'];
                        $first_name = $row['first_name'];
                        $hashed_password = $row['password_hash'];
                        if (password_verify($password, $hashed_password)) {
                            // Check if there is an existing session
                            $sql_check_session = "SELECT session_id FROM users WHERE id = :user_id";
                            if ($check_stmt = $db->prepare($sql_check_session)) {
                                $check_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                                $check_stmt->execute();
                                if ($session_row = $check_stmt->fetch()) { // Use a different variable name to avoid conflict
                                    if (!empty($session_row['session_id'])) {
                                        // Destroy the old session
                                        session_id($session_row['session_id']);
                                        session_destroy();
                                    }
                                }
                            }

                            // Start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["user_id"] = (int)$user_id;
                            $_SESSION["first_name"] = $first_name;
                            $_SESSION["user_avatar"] = $row['profile_picture_path'];
                            $_SESSION["is_admin"] = (bool)$row['is_admin'];

                            // Update user's online status and session ID
                            $update_sql = "UPDATE users SET is_online = 1, last_seen = NOW(), session_id = :session_id WHERE id = :user_id";
                            if ($update_stmt = $db->prepare($update_sql)) {
                                $session_id = session_id();
                                $update_stmt->bindParam(":session_id", $session_id, PDO::PARAM_STR);
                                $update_stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                                $update_stmt->execute();
                            }

                            $response['success'] = true;
                            $response['message'] = 'Login successful!';
                            $response['redirect'] = 'dashboard.php';
                        } else {
                            // Display an error message if password is not valid
                            $response['message'] = "Invalid email or password.";
                        }
                    }
                } else {
                    // Display an error message if email doesn't exist
                    $response['message'] = "Invalid email or password.";
                }
            } else {
                $response['message'] = "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
    }
}

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else if ($response['success'] && isset($response['redirect'])) {
    header("Location: " . $response['redirect']);
    exit();
} else if (!$response['success']) {
    // For non-AJAX requests, if login fails, we might want to set a session variable
    // or pass the error message differently to the login.php page.
    // For now, we'll just let the login.php page display the error message if it's not AJAX.
    // This part might need further refinement depending on how login.php handles non-AJAX errors.
}
?>