<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Your Ultimate Learning Companion</title>
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

        .hero-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 2rem;
            color: white;
            
        }

        .hero-headline {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .hero-subheadline {
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            max-width: 800px;
            opacity: 0.9;
        }

        .hero-ctas {
            display: flex;
            gap: 1.5rem;
            min-height: 60px;
        }

        .cta-button {
            display: inline-block;
            padding: 1.2rem 2.5rem;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .cta-button.primary {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            border: none;
        }

        .cta-button.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }

        .cta-button.secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .cta-button.secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
        }

        .features-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            padding: 4rem 2rem;
            text-align: center;
            color: #333;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #4b5563;
            margin-bottom: 3rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-item {
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-item i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1.5rem;
        }

        .feature-item h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .feature-item p {
            color: #4b5563;
            line-height: 1.6;
        }

        .testimonial-section {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            padding: 5rem 2rem;
            text-align: center;
            color: white;
        }

        .testimonial-quote {
            font-size: 1.8rem;
            font-style: italic;
            max-width: 900px;
            margin: 0 auto 2rem auto;
            line-height: 1.5;
            opacity: 0.95;
        }

        .testimonial-author {
            font-size: 1.2rem;
            font-weight: 600;
            opacity: 0.8;
        }

        .final-cta-section {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            padding: 4rem 2rem;
            text-align: center;
            color: #333;
        }

        .final-cta-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
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
            .hero-headline {
                font-size: 2.5rem;
            }
            .hero-subheadline {
                font-size: 1.2rem;
            }
            .hero-ctas {
                flex-direction: column;
                gap: 1rem;
            }
            .cta-button {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
            .section-title {
                font-size: 2rem;
            }
            .section-subtitle {
                font-size: 1rem;
            }
            .feature-item {
                padding: 2rem;
            }
            .feature-item h3 {
                font-size: 1.3rem;
            }
            .testimonial-quote {
                font-size: 1.4rem;
            }
            .final-cta-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            #navbar {
                padding: 1rem;
            }
            .logo {
                font-size: 1.5rem;
            }
            .hero-section {
                padding: 3rem 1rem;
            }
            .hero-headline {
                font-size: 2rem;
            }
            .hero-subheadline {
                font-size: 1rem;
            }
            .features-section, .testimonial-section, .final-cta-section {
                padding: 3rem 1rem;
            }
            .section-title {
                font-size: 1.8rem;
            }
            .section-subtitle {
                font-size: 0.9rem;
            }
            .feature-item {
                padding: 1.5rem;
            }
            .feature-item i {
                font-size: 2.5rem;
            }
            .feature-item h3 {
                font-size: 1.2rem;
            }
            .testimonial-quote {
                font-size: 1.2rem;
            }
            .final-cta-title {
                font-size: 1.5rem;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/css/responsive.css">
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
                <a href="dashboard.php" class="cta-button primary <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
                <a href="logout.php" class="cta-button primary">Logout</a>
            <?php else: ?>
                <a href="index.php" class="cta-button primary <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="login.php" class="cta-button primary <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Sign In</a>
                <a href="signup.php" class="cta-button primary <?php echo ($currentPage == 'signup.php') ? 'active' : ''; ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero-section">
        <h1 class="hero-headline">Study Buddy: Your Ultimate Learning Companion</h1>
        <p class="hero-subheadline">Master your subjects with personalized tools, collaborative study groups, and expert resources.</p>
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="hero-ctas">
        <a href="signup.php" class="cta-button primary">Get Started Free</a>
        <a href="login.php" class="cta-button secondary">Sign In</a>
    </div>
<?php endif; ?>
</div>

    <div class="features-section">
        <h2 class="section-title">Why Choose Study Buddy?</h2>
        <p class="section-subtitle">We provide everything you need to succeed in your academic journey.</p>
        <div class="features-grid">
            <div class="feature-item">
                <i class="fas fa-calendar-alt"></i>
                <h3>Personalized Study Plans</h3>
                <p>Create custom study schedules tailored to your goals and learning style.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-question-circle"></i>
                <h3>Interactive Quizzes & Flashcards</h3>
                <p>Test your knowledge and reinforce learning with engaging tools.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-users"></i>
                <h3>Collaborative Study Groups</h3>
                <p>Connect with peers, share knowledge, and learn together effectively.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-chart-line"></i>
                <h3>Progress Tracking & Analytics</h3>
                <p>Monitor your performance and identify areas for improvement.</p>
            </div>
        </div>
    </div>

    <div class="testimonial-section">
        <p class="testimonial-quote">"Study Buddy transformed my grades! The personalized plans and interactive quizzes made learning enjoyable and effective."</p>
        <p class="testimonial-author">- A Happy Student</p>
    </div>

 <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="final-cta-section">
        <h2 class="final-cta-title">Ready to Boost Your Grades?</h2>
        <a href="signup.php" class="cta-button primary">Join Study Buddy Today!</a>
    </div>
<?php endif; ?>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Study Buddy. All rights reserved.</p>
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="terms.php">Terms of Service</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
    </footer>

    <script>
        // Navbar hide/show on scroll
        let lastScrollY = window.scrollY;
        const navbar = document.getElementById('navbar');

        window.addEventListener('scroll', () => {
            if (navbar) {
                if (window.scrollY < lastScrollY) {
                    // Scrolling up: show navbar immediately
                    navbar.style.transform = 'translateY(0)';
                } else if (window.scrollY > lastScrollY && window.scrollY > 50) {
                    // Scrolling down and past initial threshold: hide navbar
                    navbar.style.transform = 'translateY(-100%)';
                }
                // If scrolling down but still within the top 50px, navbar remains visible (default state)
                lastScrollY = window.scrollY;
            }
        });
    </script>
    <script src="assets/js/responsive.js" defer></script>
</body>
</html>