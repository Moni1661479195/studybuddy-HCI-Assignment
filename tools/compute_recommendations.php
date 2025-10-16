<?php
// Include configuration file from parent directory
require_once __DIR__ . '/../config.php';

/**
 * Get a PDO database connection (singleton pattern)
 * @return PDO
 * @throws PDOException
 */
function get_db(): PDO {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // Use enhanced options from config.php, fall back to basic if not available
    global $db_options;
    $opts = $db_options ?? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new PDOException("Database connection failed. Please try again later.");
    }
}
?>