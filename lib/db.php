<?php
// Include configuration file from parent directory
require_once __DIR__ . '/../config.php';

$pdo = null; // Global PDO instance

/**
 * Get a PDO database connection (singleton pattern)
 * @return PDO
 * @throws PDOException
 */
function get_db(): PDO {
    global $pdo;
    global $db_options; // Access global db_options from config.php
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // Use enhanced options from config.php, fall back to basic if not available
    $opts = $db_options ?? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        return $pdo;
    } catch (PDOException $e) {
        echo "<h1>Database Connection Error</h1>";
        echo "<p>A database connection could not be established. Please check your database server status and credentials.</p>";
        echo "<p><strong>Actual PDO Error:</strong> " . $e->getMessage() . "</p>";
        exit();
    }
}

/**
 * Close the PDO database connection
 */
function close_db() {
    global $pdo;
    $pdo = null; // Nullifying the global PDO instance closes the connection
}

register_shutdown_function('close_db'); // Register to close connection on script shutdown
?>