<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

// User authentication check
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporter_id = $_SESSION['user_id'];
    $reported_user_id = isset($_POST['reported_user_id']) ? (int)$_POST['reported_user_id'] : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    // Word count validation
    if (str_word_count($reason) > 10000) {
        header("Location: report.php?reported_user_id=" . $reported_user_id . "&error=" . urlencode("Report cannot exceed 10,000 words."));
        exit();
    }

    if ($reported_user_id > 0 && !empty($reason)) {
        $screenshot_paths = [];

        // Handle file upload
        if (isset($_FILES['screenshot'])) {
            $upload_dir = __DIR__ . '/uploads/reports/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            foreach ($_FILES['screenshot']['name'] as $key => $name) {
                if ($_FILES['screenshot']['error'][$key] == UPLOAD_ERR_OK) {
                    $file_type = $_FILES['screenshot']['type'][$key];
                    $file_size = $_FILES['screenshot']['size'][$key];
                    $tmp_name = $_FILES['screenshot']['tmp_name'][$key];

                    if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                        $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                        $unique_filename = uniqid('report_', true) . '.' . $file_extension;
                        $destination = $upload_dir . $unique_filename;

                        if (move_uploaded_file($tmp_name, $destination)) {
                            $screenshot_paths[] = 'uploads/reports/' . $unique_filename;
                        } else {
                            header("Location: report.php?reported_user_id=" . $reported_user_id . "&error=" . urlencode("Failed to upload one or more screenshots."));
                            exit();
                        }
                    } else {
                        header("Location: report.php?reported_user_id=" . $reported_user_id . "&error=" . urlencode("Invalid file type or size for one or more screenshots."));
                        exit();
                    }
                }
            }
        }

        try {
            $db = get_db();
            $stmt = $db->prepare("INSERT INTO reports (reporter_id, reported_user_id, reason, screenshot_path) VALUES (?, ?, ?, ?)");
            $screenshot_path_json = !empty($screenshot_paths) ? json_encode($screenshot_paths) : null;
            $stmt->execute([$reporter_id, $reported_user_id, $reason, $screenshot_path_json]);

            header("Location: user_profile.php?id=" . $reported_user_id . "&success=" . urlencode("Report submitted successfully."));
            exit();

        } catch (PDOException $e) {
            header("Location: report.php?reported_user_id=" . $reported_user_id . "&error=" . urlencode("Database error: Could not submit report."));
            exit();
        }
    } else {
        header("Location: report.php?reported_user_id=" . $reported_user_id . "&error=" . urlencode("Invalid data provided."));
        exit();
    }
} else {
    // If not a POST request, redirect to the main page or show an error
    header("Location: study-groups.php");
    exit();
}
