<?php
require_once 'session.php';

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        /* All of your original CSS is 100% preserved */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; flex-direction: column; color: #333; }
        #navbar { background: rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; transition: transform 0.25s ease; }
        .logo { font-size: 2rem; font-weight: 700; color: white; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); display: flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .nav-links { display: flex; align-items: center; } /* Added for alignment */
        .nav-links a { color: white; text-decoration: none; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 0.5rem; transition: background 0.3s ease; }
        .nav-links a:hover { background: rgba(255, 255, 255, 0.1); }
        .dashboard-container { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; }
        .dashboard-card { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); border-radius: 1.5rem; padding: 3rem; width: 100%; max-width: 900px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); margin-bottom: 2rem; }
        .welcome-title { font-size: 2.5rem; font-weight: 700; color: #1f2937; margin-bottom: 1rem; }
        .welcome-message { font-size: 1.2rem; color: #4b5563; margin-bottom: 2rem; }
        .dashboard-links-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .dashboard-link-item { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 1.5rem; border-radius: 0.75rem; text-decoration: none; font-weight: 600; font-size: 1.1rem; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        .dashboard-link-item:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); background: linear-gradient(45deg, #764ba2, #667eea); }
        .dashboard-link-item i { font-size: 2.5rem; margin-bottom: 0.75rem; }
        .nav-links .cta-button { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease; position: relative; overflow: hidden; z-index: 1; }
        .nav-links .cta-button.primary { background: linear-gradient(45deg, #10b981, #059669); color: white; border: none; }
        .nav-links .cta-button.primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3); }
        .nav-links .active { background: linear-gradient(45deg, #ef4444, #dc2626) !important; box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3); }
        .footer { background: rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); color: white; text-align: center; padding: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        /* ... other original styles ... */
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-card">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>!</h1>
            <p class="welcome-message">Here's a quick overview of your learning journey.</p>
            
            <div class="dashboard-links-grid">
                <a href="study-plans.php" class="dashboard-link-item">
                    <i class="fas fa-book"></i>
                    <span>My Study Plans</span>
                </a>
                <a href="quizzes.php" class="dashboard-link-item">
                    <i class="fas fa-question-circle"></i>
                    <span>My Quizzes</span>
                </a>
                <a href="flashcards.php" class="dashboard-link-item">
                    <i class="fas fa-clone"></i>
                    <span>My Flashcards</span>
                </a>
                <a href="study-groups.php" class="dashboard-link-item">
                    <i class="fas fa-users"></i>
                    <span>Study Groups</span>
                </a>
                <a href="messages.php" class="dashboard-link-item">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                </a>
                <a href="settings.php" class="dashboard-link-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>

            <p style="margin-top: 1.5rem;"><a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 500;"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Study Buddy. All rights reserved.</p>
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="terms.php">Terms of Service</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
    </footer>

    <script>
        // ... (all your original JavaScript for navbar hiding, etc.) ...
    </script>
    <script src="assets/js/responsive.js" defer></script>
</body>
</html>