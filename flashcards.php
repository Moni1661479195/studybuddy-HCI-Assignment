<?php
require_once 'session.php';

// Prevent browser caching so Back button forces a fresh request (and triggers session check)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - My Flashcards</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #333;
        }

        #navbar {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: background 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dashboard-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 3rem;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .welcome-message {
            font-size: 1.2rem;
            color: #4b5563;
            margin-bottom: 2rem;
        }

        .nav-links .cta-button {
            display: inline-block;
            padding: 0.75rem 1.5rem; /* Adjusted for navbar */
            border-radius: 0.5rem;
            font-size: 1rem; /* Adjusted for navbar */
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .nav-links .cta-button.primary {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            border: none;
        }

        .nav-links .cta-button.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .nav-links .active {
            background: linear-gradient(45deg, #ef4444, #dc2626) !important; /* Red color for active button */
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        .footer {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer p {
            margin-bottom: 0.5rem;
            opacity: 0.8;
            color: white;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            margin: 0 0.75rem;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .footer-links a:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .dashboard-card {
                padding: 2rem;
            }
            .welcome-title {
                font-size: 2rem;
            }
            .welcome-message {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            #navbar {
                padding: 1rem;
            }
            .logo {
                font-size: 1.5rem;
            }
            .dashboard-card {
                padding: 1.5rem;
            }
            .welcome-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <nav id="navbar">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <a href="index.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            Study Buddy
        </a>

        <!-- mobile hamburger -->
        <button id="nav-toggle" class="nav-toggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php" class="cta-button primary <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="logout.php" class="cta-button primary">Logout</a>
            <?php else: ?>
                <a href="index.php" class="cta-button primary <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="login.php" class="cta-button primary <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Sign In</a>
                <a href="signup.php" class="cta-button primary <?php echo ($currentPage == 'signup.php') ? 'active' : ''; ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-card">
            <h1 class="welcome-title">My Flashcards</h1>
            <p class="welcome-message">Create, organize, and review your flashcards here.</p>

            <div class="flashcards-list">
                <div class="flashcard-item">
                    <h3>Question 1: What is the capital of France?</h3>
                    <p>Answer: Paris</p>
                    <div class="flashcard-actions">
                        <a href="#" class="cta-button primary small">Edit</a>
                        <a href="#" class="cta-button secondary small">Delete</a>
                    </div>
                </div>
                <div class="flashcard-item">
                    <h3>Question 2: What is the chemical symbol for water?</h3>
                    <p>Answer: H2O</p>
                    <div class="flashcard-actions">
                        <a href="#" class="cta-button primary small">Edit</a>
                        <a href="#" class="cta-button secondary small">Delete</a>
                    </div>
                </div>
                <div class="flashcard-item">
                    <h3>Question 3: Who wrote 'Romeo and Juliet'?</h3>
                    <p>Answer: William Shakespeare</p>
                    <div class="flashcard-actions">
                        <a href="#" class="cta-button primary small">Edit</a>
                        <a href="#" class="cta-button secondary small">Delete</a>
                    </div>
                </div>
            </div>

            <button class="cta-button primary large" style="margin-top: 1rem;"><i class="fas fa-plus"></i> Create New Flashcard</button>

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
    <script src="assets/js/responsive.js" defer></script>
    <script>
        // Ensure pressing browser Back from this protected page goes to index.php.
        // Replace current history state so we can detect popstate and redirect to index.php.
        try {
            history.replaceState({page: 'flashcards'}, '', location.href);
            window.addEventListener('popstate', function () {
                // When user navigates back, send them to index.php (home)
                window.location.href = 'index.php';
            });

            // Also handle pageshow where the page is loaded from bfcache (back-forward cache)
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    // if page was loaded from cache, force a navigation to index to trigger session check
                    window.location.href = 'index.php';
                }
            });
        } catch (e) {
            // ignore any history API errors
        }
    </script>
</body>
</html>