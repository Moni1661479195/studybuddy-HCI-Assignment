<?php
// Database configuration
define('DB_HOST', 'localhost:3326');
define('DB_NAME', 'studybuddy');
define('DB_USER', 'sb_user');

define('DB_PASS', 'YOUR_DB_PASSWORD_HERE');

// PDO options
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// PHPMailer SMTP configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_AUTH', true);
define('SMTP_USER', 'YOUR_SMTP_EMAIL_HERE'); // Replace with your Gmail address
define('SMTP_PASS', 'YOUR_SMTP_APP_PASSWORD_HERE');   // Replace with your Gmail app password
define('SMTP_SECURE', 'tls');
define('SMTP_PORT', 587);
// Gemini API Key
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');

// Google OAuth 2.0 Credentials
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', 'http://localhost:8081/studybuddy/google-callback.php');
?>