<?php
require_once 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Study Buddy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern_index.css">
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
            <h1>Privacy Policy</h1>
            <p>This Privacy Policy describes Our policies and procedures on the collection, use and disclosure of Your information when You use the Service and tells You about Your privacy rights and how the law protects You.</p>
            <p>We use Your Personal data to provide and improve the Service. By using the Service, You agree to the collection and use of information in accordance with this Privacy Policy.</p>

            <h2>Interpretation and Definitions</h2>
            <h3>Interpretation</h3>
            <p>The words of which the initial letter is capitalized have meanings defined under the following conditions. The following definitions shall have the same meaning regardless of whether they appear in singular or in plural.</p>

            <h3>Definitions</h3>
            <ul>
                <li><strong>Account</strong> means a unique account created for You to access our Service or parts of our Service.</li>
                <li><strong>Company</strong> (referred to as either "the Company", "We", "Us" or "Our" in this Agreement) refers to Study Buddy.</li>
                <li><strong>Cookies</strong> are small files that are placed on Your computer, mobile device or any other device by a website, containing the details of Your browsing history on that website among its many uses.</li>
                <li><strong>Country</strong> refers to: [Your Country]</li>
                <li><strong>Device</strong> means any device that can access the Service such as a computer, a cellphone or a digital tablet.</li>
                <li><strong>Personal Data</strong> is any information that relates to an identified or identifiable individual.</li>
                <li><strong>Service</strong> refers to the Website.</li>
                <li><strong>Service Provider</strong> means any natural or legal person who processes the data on behalf of the Company. It refers to third-party companies or individuals employed by the Company to facilitate the Service, to provide the Service on behalf of the Company, to perform services related to the Service or to assist the Company in analyzing how the Service is used.</li>
                <li><strong>Usage Data</strong> refers to data collected automatically, either generated by the use of the Service or from the Service infrastructure itself (for example, the duration of a page visit).</li>
                <li><strong>Website</strong> refers to Study Buddy, accessible from [Your Website URL]</li>
                <li><strong>You</strong> means the individual accessing or using the Service, or the company, or other legal entity on behalf of which such individual is accessing or using the Service, as applicable.</li>
            </ul>

            <h2>Collecting and Using Your Personal Data</h2>
            <h3>Types of Data Collected</h3>
            <h4>Personal Data</h4>
            <p>While using Our Service, We may ask You to provide Us with certain personally identifiable information that can be used to contact or identify You. Personally identifiable information may include, but is not limited to:</p>
            <ul>
                <li>Email address</li>
                <li>First name and last name</li>
                <li>Usage Data</li>
            </ul>

            <h4>Usage Data</h4>
            <p>Usage Data is collected automatically when using the Service.</p>
            <p>Usage Data may include information such as Your Device's Internet Protocol address (e.g. IP address), browser type, browser version, the pages of our Service that You visit, the time and date of Your visit, the time spent on those pages, unique device identifiers and other diagnostic data.</p>
            <p>When You access the Service by or through a mobile device, We may collect certain information automatically, including, but not limited to, the type of mobile device You use, Your mobile device unique ID, the IP address of Your mobile device, Your mobile operating system, the type of mobile Internet browser You use, unique device identifiers and other diagnostic data.</p>
            <p>We may also collect information that Your browser sends whenever You visit our Service or when You access the Service by or through a mobile device.</p>

            <h3>Use of Your Personal Data</h3>
            <p>The Company may use Personal Data for the following purposes:</p>
            <ul>
                <li><strong>To provide and maintain our Service</strong>, including to monitor the usage of our Service.</li>
                <li><strong>To manage Your Account:</strong> to manage Your registration as a user of the Service. The Personal Data You provide can give You access to different functionalities of the Service that are available to You as a registered user.</li>
                <li><strong>For the performance of a contract:</strong> the development, compliance and undertaking of the purchase contract for the products, items or services You have purchased or of any other contract with Us through the Service.</li>
                <li><strong>To contact You:</strong> To contact You by email, telephone calls, SMS, or other equivalent forms of electronic communication, such as a mobile application's push notifications regarding updates or informative communications related to the functionalities, products or contracted services, including the security updates, when necessary or reasonable for their implementation.</li>
                <li><strong>To provide You</strong> with news, special offers and general information about other goods, services and events which we offer that are similar to those that you have already purchased or enquired about unless You have opted not to receive such information.</li>
                <li><strong>To manage Your requests:</strong> To attend and manage Your requests to Us.</li>
                <li><strong>For business transfers:</strong> We may use Your information to evaluate or conduct a merger, divestiture, restructuring, reorganization, dissolution, or other sale or transfer of some or all of Our assets, whether as a going concern or as part of bankruptcy, liquidation, or similar proceeding, in which Personal Data held by Us about our Service users is among the assets transferred.</li>
                <li><strong>For other purposes</strong>: We may use Your information for other purposes, such as data analysis, identifying usage trends, determining the effectiveness of our promotional campaigns and to evaluate and improve our Service, products, services, marketing and your experience.</li>
            </ul>

            <h3>Disclosure of Your Personal Data</h3>
            <h4>With Service Providers:</h4>
            <p>We may share Your personal information with Service Providers to monitor and analyze the use of our Service, to contact You.</p>

            <h4>For business transfers:</h4>
            <p>We may share or transfer Your personal information in connection with, or during negotiations of, any merger, sale of Company assets, financing, or acquisition of all or a portion of Our business to another company.</p>

            <h4>With Affiliates:</h4>
            <p>We may share Your information with Our affiliates, in which case we will require those affiliates to honor this Privacy Policy. Affiliates include Our parent company and any other subsidiaries, joint venture partners or other companies that We control or that are under common control with Us.</p>

            <h4>With business partners:</h4>
            <p>We may share Your information with Our business partners to offer You certain products, services or promotions.</p>

            <h4>With other users:</h4>
            <p>When You share personal information or otherwise interact in the public areas with other users, such information may be viewed by all users and may be publicly distributed outside.</p>

            <h4>With Your consent:</h4>
            <p>We may disclose Your personal information for any other purpose with Your consent.</p>

            <h2>Security of Your Personal Data</h2>
            <p>The security of Your Personal Data is important to Us, but remember that no method of transmission over the Internet, or method of electronic storage is 100% secure. While We strive to use commercially acceptable means to protect Your Personal Data, We cannot guarantee its absolute security.</p>

            <h2>Links to Other Websites</h2>
            <p>Our Service may contain links to other websites that are not operated by Us. If You click on a third party link, You will be directed to that third party's site. We strongly advise You to review the Privacy Policy of every site You visit.</p>
            <p>We have no control over and assume no responsibility for the content, privacy policies or practices of any third party sites or services.</p>

            <h2>Changes to this Privacy Policy</h2>
            <p>We may update Our Privacy Policy from time to time. We will notify You of any changes by posting the new Privacy Policy on this page.</p>
            <p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>

            <h2>Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, You can contact us:</p>
            <ul>
                <li>By email: [Your Email Address]</li>
                <li>By visiting this page on our website: [Your Website Contact Page URL]</li>
            </ul>
        </div>
    </div>

    <?php include 'footer.php'; ?>

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