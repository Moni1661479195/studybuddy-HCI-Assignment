<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['user_id'])) {
        $response = ['success' => true, 'redirect' => 'dashboard.php'];
        echo json_encode($response);
        exit();
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $response['message'] = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
    } else {
        try {
            $db = get_db();
            $sql = "SELECT id, first_name, password_hash, profile_picture_path, is_admin FROM users WHERE email = :email";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            
            if ($stmt->execute() && $stmt->rowCount() == 1) {
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (password_verify($password, $row['password_hash'])) {
                        session_regenerate_id(true);

                        $_SESSION["user_id"] = (int)$row['id'];
                        $_SESSION["first_name"] = $row['first_name'];
                        $_SESSION["user_avatar"] = $row['profile_picture_path'];
                        $_SESSION["is_admin"] = (bool)$row['is_admin'];

                        $update_sql = "UPDATE users SET is_online = 1, last_seen = NOW(), session_id = :session_id WHERE id = :user_id";
                        $update_stmt = $db->prepare($update_sql);
                        $session_id = session_id();
                        $update_stmt->bindParam(":session_id", $session_id, PDO::PARAM_STR);
                        $update_stmt->bindParam(":user_id", $_SESSION["user_id"], PDO::PARAM_INT);
                        $update_stmt->execute();

                        $response['success'] = true;
                        $response['message'] = 'Login successful!';
                        $response['redirect'] = 'dashboard.php';
                    } else {
                        $response['message'] = "Invalid email or password.";
                    }
                } else {
                     $response['message'] = "Invalid email or password.";
                }
            } else {
                $response['message'] = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);
exit();
?>