<?php
require_once 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Study Buddy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .content-container {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 2rem;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 3rem;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: left;
        }

        h1 {
            font-size: 2.5rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        h2 {
            font-size: 1.8rem;
            color: #1f2937;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        p {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: #4b5563;
        }

        ul {
            margin-bottom: 1rem;
            margin-left: 1.5rem;
            color: #4b5563;
        }

        ul li {
            margin-bottom: 0.5rem;
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

        @media (max-width: 480px) {
            #navbar {
                padding: 1rem;
            }
            .logo {
                font-size: 1.5rem;
            }
            .content-card {
                padding: 2rem;
                margin: 1rem;
                border-radius: 1rem;
            }
            h1 {
                font-size: 2rem;
            }
            h2 {
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

    <div class="content-container">
        <div class="content-card">
            <h1>Terms and Conditions</h1>
            <p>Welcome to Study Buddy! These terms and conditions outline the rules and regulations for the use of Study Buddy's Website, located at [Your Website URL].</p>
            <p>By accessing this website we assume you accept these terms and conditions. Do not continue to use Study Buddy if you do not agree to take all of the terms and conditions stated on this page.</p>

            <h2>1. Intellectual Property Rights</h2>
            <p>Other than the content you own, under these Terms, Study Buddy and/or its licensors own all the intellectual property rights and materials contained in this Website.</p>
            <p>You are granted limited license only for purposes of viewing the material contained on this Website.</p>

            <h2>2. Restrictions</h2>
            <p>You are specifically restricted from all of the following:</p>
            <ul>
                <li>publishing any Website material in any other media;</li>
                <li>selling, sublicensing and/or otherwise commercializing any Website material;</li>
                <li>publicly performing and/or showing any Website material;</li>
                <li>using this Website in any way that is or may be damaging to this Website;</li>
                <li>using this Website in any way that impacts user access to this Website;</li>
                <li>using this Website contrary to applicable laws and regulations, or in any way may cause harm to the Website, or to any person or business entity;</li>
                <li>engaging in any data mining, data harvesting, data extracting or any other similar activity in relation to this Website;</li>
                <li>using this Website to engage in any advertising or marketing.</li>
            </ul>

            <h2>3. Your Content</h2>
            <p>In these Website Standard Terms and Conditions, "Your Content" shall mean any audio, video text, images or other material you choose to display on this Website. By displaying Your Content, you grant Study Buddy a non-exclusive, worldwide irrevocable, sub licensable license to use, reproduce, adapt, publish, translate and distribute it in any and all media.</p>
            <p>Your Content must be your own and must not be invading any third-party’s rights. Study Buddy reserves the right to remove any of Your Content from this Website at any time without notice.</p>

            <h2>4. No warranties</h2>
            <p>This Website is provided "as is," with all faults, and Study Buddy express no representations or warranties, of any kind related to this Website or the materials contained on this Website. Also, nothing contained on this Website shall be interpreted as advising you.</p>

            <h2>5. Limitation of liability</h2>
            <p>In no event shall Study Buddy, nor any of its officers, directors and employees, be held liable for anything arising out of or in any way connected with your use of this Website whether such liability is under contract.  Study Buddy, including its officers, directors and employees shall not be held liable for any indirect, consequential or special liability arising out of or in any way related to your use of this Website.</p>

            <h2>6. Severability</h2>
            <p>If any provision of these Terms is found to be invalid under any applicable law, such provisions shall be deleted without affecting the remaining provisions herein.</p>

            <h2>7. Variation of Terms</h2>
            <p>Study Buddy is permitted to revise these Terms at any time as it sees fit, and by using this Website you are expected to review these Terms on a regular basis.</p>

            <h2>8. Assignment</h2>
            <p>The Study Buddy is allowed to assign, transfer, and subcontract its rights and/or obligations under these Terms without any notification. However, you are not allowed to assign, transfer, or subcontract any of your rights and/or obligations under these Terms.</p>

            <h2>9. Entire Agreement</h2>
            <p>These Terms constitute the entire agreement between Study Buddy and you in relation to your use of this Website, and supersede all prior agreements and understandings.</p>

            <h2>10. Governing Law & Jurisdiction</h2>
            <p>These Terms will be governed by and interpreted in accordance with the laws of the State of [Your State], and you submit to the non-exclusive jurisdiction of the state and federal courts located in [Your State] for the resolution of any disputes.</p>
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