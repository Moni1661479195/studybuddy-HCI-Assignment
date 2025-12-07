<?php
// tools/delete_logs.php

// This script deletes all .log files in the root directory of the project.

$project_root = __DIR__ . '/../';
$log_files = glob($project_root . '*.log');

foreach ($log_files as $file) {
    if (is_file($file)) {
        unlink($file);
        echo "Deleted log file: " . basename($file) . "\n";
    }
}

echo "Log file cleanup complete.\n";
?>