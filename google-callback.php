<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

session_start();

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

// Helper function to log in a user
function loginUser($user_id, $db) {
    $stmt = $db->prepare("SELECT id, first_name, profile_picture_path, is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Destroy any old session and start a new one
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        session_regenerate_id(true);

        $_SESSION["user_id"] = (int)$user['id'];
        $_SESSION["first_name"] = $user['first_name'];
        $_SESSION["user_avatar"] = $user['profile_picture_path'];
        $_SESSION["is_admin"] = (bool)$user['is_admin'];

        // Update user's online status and session ID
        $update_sql = "UPDATE users SET is_online = 1, last_seen = NOW(), session_id = :session_id WHERE id = :user_id";
        $update_stmt = $db->prepare($update_sql);
        $session_id = session_id();
        $update_stmt->bindParam(":session_id", $session_id, PDO::PARAM_STR);
        $update_stmt->bindParam(":user_id", $user['id'], PDO::PARAM_INT);
        $update_stmt->execute();

        header("Location: dashboard.php");
        exit();
    } else {
        // Handle error: user not found after creation/lookup
        header("Location: login.php?error=user_not_found");
        exit();
    }
}

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            throw new Exception('Failed to retrieve access token: ' . $token['error_description']);
        }
        $client->setAccessToken($token);

        $oauth2 = new Google_Service_Oauth2($client);
        $google_account_info = $oauth2->userinfo->get();

        $google_id = $google_account_info->getId();
        $email = $google_account_info->getEmail();
        $first_name = $google_account_info->getGivenName();
        $last_name = $google_account_info->getFamilyName();
        // Not all Google accounts have a family name
        $last_name = $last_name ? $last_name : '';

        $db = get_db();

        // Check if user exists with this google_id
        $stmt = $db->prepare("SELECT id FROM users WHERE google_id = ?");
        $stmt->execute([$google_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // User exists, log them in
            loginUser($user['id'], $db);
        } else {
            // User does not exist with this google_id, check by email
            $stmt = $db->prepare("SELECT id, google_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user_by_email = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_by_email) {
                // Email exists. Link the Google ID to this account and log in.
                $update_stmt = $db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $update_stmt->execute([$google_id, $user_by_email['id']]);
                loginUser($user_by_email['id'], $db);
            } else {
                // New user. Create an account.
                // The 'username' and 'password_hash' columns are NOT NULL.
                // We'll generate a default username from the email and a random password hash.
                $username = strstr($email, '@', true) . rand(100, 999);
                $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, google_id) VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($sql);
                $insert_stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $google_id]);
                
                $new_user_id = $db->lastInsertId();
                loginUser($new_user_id, $db);
            }
        }
    } catch (Exception $e) {
        // Log the error and redirect
        error_log('Google Login Error: ' . $e->getMessage());
        header('Location: login.php?error=google_login_failed');
        exit();
    }
} else {
    // No code parameter, redirect to login
    header('Location: login.php');
    exit();
}
?>