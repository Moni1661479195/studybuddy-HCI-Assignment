<?php
require_once 'session.php';
require_once 'check_profile_status.php';

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
    
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    </head>
<body>
    <?php include 'header.php'; ?>

<div class="dashboard-container mt-24 md:mt-28">
                <div class="dashboard-content-card">
            <div class="dashboard-header">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>! 👋</h1>
                <p class="welcome-message">Here's a quick overview of your learning journey. Jump back in or start something new.</p>
            </div>

            <h2 class="section-title">Your Tools</h2>
            <div class="dashboard-links-grid">
                <a href="study-plans.php" class="dashboard-link-item">
                    <i class="fas fa-book"></i>
                    <span>My Study Plans</span>
                    <p class="card-description">View your planned schedule and progress.</p>
                </a>
                <a href="quizzes.php" class="dashboard-link-item">
                    <i class="fas fa-question-circle"></i>
                    <span>My Quizzes</span>
                    <p class="card-description">Test your knowledge with custom quizzes.</p>
                </a>
                <a href="flashcards.php" class="dashboard-link-item">
                    <i class="fas fa-clone"></i>
                    <span>My Flashcards</span>
                    <p class="card-description">Memorize key concepts with flashcards.</p>
                </a>
                <a href="study-groups.php" class="dashboard-link-item">
                    <i class="fas fa-users"></i>
                    <span>Study Groups</span>
                    <p class="card-description">Collaborate with peers in study groups.</p>
                </a>
                <a href="messages.php" class="dashboard-link-item">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                    <p class="card-description">Communicate with your study mates.</p>
                </a>
                <a href="settings.php" class="dashboard-link-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                    <p class="card-description">Manage your profile and preferences.</p>
                </a>
            </div> <!-- Close dashboard-links-grid -->

            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <div class="admin-section">
                <h2 class="section-title">Admin Tools</h2>
                <div class="dashboard-links-grid">
                    <a href="user_management.php" class="dashboard-link-item admin-link">
                        <i class="fas fa-user-shield"></i>
                        <span>User Management</span>
                        <p class="card-description">Oversee user accounts and roles.</p>
                    </a>
                    <a href="report_inbox.php" class="dashboard-link-item admin-link">
                        <i class="fas fa-inbox"></i>
                        <span>Report Inbox</span>
                        <p class="card-description">Review and manage user reports.</p>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Mainpage</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // ... (all your original JavaScript for navbar hiding, etc.) ...
    </script>
    <script src="assets/js/responsive.js" defer></script>
</body>
</html>