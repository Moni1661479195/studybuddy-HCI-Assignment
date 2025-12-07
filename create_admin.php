<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/lib/db.php';

echo "<h1>Creating Admin Account...</h1>";

try {
    $db = get_db();

    $username = 'admin';
    $email = 'admin@studybuddy.com';
    $password = 'admin';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $first_name = 'Admin';
    $last_name = 'User';

    // Check if the user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->execute([':username' => $username, ':email' => $email]);

    if ($stmt->fetch()) {
        echo "<h2>Admin account already exists.</h2>";
        echo "<p>An account with username '{$username}' or email '{$email}' already exists.</p>";
    } else {
        $stmt = $db->prepare(
            "INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin) 
             VALUES (:username, :email, :password_hash, :first_name, :last_name, 1)"
        );
    
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password_hash', $password_hash);
        $stmt->bindValue(':first_name', $first_name);
        $stmt->bindValue(':last_name', $last_name);
        
        $stmt->execute();

        echo "<h2>Successfully created admin account!</h2>";
        echo "<p><b>Username:</b> admin</p>";
        echo "<p><b>Password:</b> admin</p>";
    }

    echo "<p style='color:red; font-weight:bold;'>Please delete this file (create_admin.php) now for security reasons.</p>";

} catch (Exception $e) {
    echo "<h2>An error occurred:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please ensure your 'users' table schema is correct and try again.</p>";
}