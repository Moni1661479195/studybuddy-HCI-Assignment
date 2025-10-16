<?php
// Database configuration
define('DB_HOST', 'localhost:3316');
define('DB_NAME', 'studybuddy');
define('DB_USER', 'sb_user');
define('DB_PASS', 'YourStrongPassword123!');

// PDO options
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
?>