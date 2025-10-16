<?php
require_once 'session.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'lib/db.php';

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = trim($_POST['gender']);
    $password = trim($_POST['password']);
    $retype_password = trim($_POST['retype_password']);

    // Validate input
    if (empty($email) || empty($first_name) || empty($last_name) || empty($gender) || empty($password) || empty($retype_password)) {
        $error_message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($password !== $retype_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // Check if email already exists
        $db = get_db(); // Get the database connection
        $sql = "SELECT id FROM users WHERE email = :email";
        if ($stmt = $db->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $error_message = "This email is already registered.";
            }
            unset($stmt);
        }
    }

    // If no errors, proceed with registration
    if (empty($error_message)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (email, first_name, last_name, gender, password_hash) VALUES (:email, :first_name, :last_name, :gender, :password_hash)";

        if ($stmt = $db->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
            $stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
            $stmt->bindParam(":gender", $gender, PDO::PARAM_STR);
            $stmt->bindParam(":password_hash", $hashed_password, PDO::PARAM_STR);

            if ($stmt->execute()) {
                // Get the ID of the newly created user
                $user_id = $db->lastInsertId();

                // Set session variables for automatic login
                $_SESSION['user_id'] = (int)$user_id;
                $_SESSION['first_name'] = $first_name;

                // Redirect user to dashboard page
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Study Buddy - Join Now</title>
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

        .logo.active {
            background: linear-gradient(45deg, #ef4444, #dc2626) !important; /* Red color for active logo */
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
            border-radius: 0.5rem; /* To match button styling */
            padding: 0.75rem 1.5rem; /* To match button styling */
        }

        #signup-button {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        #signup-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        #login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 3rem;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        #login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #6b7280;
            text-align: center;
            margin-top: 0.5rem;
            margin-bottom: 2.5rem;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-row .input-group {
            flex: 1;
            margin-bottom: 0;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-label {
            display: block;
            color: #374151;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            background: white;
            transition: all 0.3s ease;
            padding: 0 1rem;
        }

        .input-wrapper:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-wrapper.error {
            border-color: #ef4444;
        }

        .input-icon {
            color: #9ca3af;
            font-size: 1rem;
            margin-right: 0.75rem;
        }

        .input-field {
            width: 100%;
            border: none;
            outline: none;
            padding: 1rem 0;
            font-size: 1rem;
            background: transparent;
            color: #1f2937;
            text-align: left;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        select.input-field {
            cursor: pointer;
        }

        select.input-field:invalid {
            color: #9ca3af;
        }

        select.input-field option {
            color: #1f2937;
        }

        select.input-field option[value=""][disabled] {
            display: none;
        }

        .password-toggle {
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            padding: 0.5rem;
            margin-left: 0.5rem;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .strength-bar {
            height: 3px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak {
            width: 25%;
            background: #ef4444;
        }

        .strength-fair {
            width: 50%;
            background: #f59e0b;
        }

        .strength-good {
            width: 75%;
            background: #10b981;
        }

        .strength-strong {
            width: 100%;
            background: #059669;
        }

        #login-button {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        #login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        #login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .button-text {
            position: relative;
            z-index: 1;
        }

        .loading-spinner {
            display: none;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #4f46e5;
        }

        .success-message {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .field-error {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none;
            height: 1em;
            /* Reserve space */
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1.5rem 0;
            font-size: 0.9rem;
        }

        .checkbox-group input[type="checkbox"] {
            margin-top: 0.1rem;
            cursor: pointer;
        }

        .checkbox-group label {
            color: #374151;
            line-height: 1.4;
            cursor: pointer;
        }

        .checkbox-group a {
            color: #667eea;
            text-decoration: none;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
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

        @media (max-width: 480px) {
            #navbar {
                padding: 1rem;
            }

            .logo {
                font-size: 1.5rem;
            }

            #login-card {
                padding: 2rem;
                margin: 1rem;
                border-radius: 1rem;
            }

            .login-title {
                font-size: 1.75rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-row .input-group {
                margin-bottom: 1.5rem;
            }
        }

        @media (max-width: 360px) {
            #login-card {
                padding: 1.5rem;
                margin: 0.5rem;
            }
            .input-field {
                font-size: 0.9rem;
            }
            .input-wrapper {
                padding: 0 0.75rem;
            }
        }

        @media (max-width: 320px) {
            #login-card {
                padding: 1rem;
                margin: 0.25rem;
            }
            .input-field {
                font-size: 0.8rem;
            }
            .input-wrapper {
                padding: 0 0.5rem;
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
            <a href="index.php" class="cta-button primary <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
            <a href="login.php" class="cta-button primary <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Sign In</a>
            <a href="signup.php" class="cta-button primary <?php echo ($currentPage == 'signup.php') ? 'active' : ''; ?>">Sign Up</a>
        </div>
    </nav>

    <div class="login-container">
        <div id="login-card">
            <h1 class="login-title">Join Study Buddy</h1>
            <p class="login-subtitle">Create your account and start your learning journey with thousands of students worldwide</p>

            <?php if (!empty($success_message)): ?>
                <div class="success-message" id="serverSuccessMessage">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="error-message" id="serverErrorMessage">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="error-message" id="errorMessage" style="display: none;">
                <i class="fas fa-exclamation-circle"></i> <span id="errorText">Please fix the errors below.</span>
            </div>

            <form id="signupForm" autocomplete="on" novalidate method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="input-group">
                    <label class="input-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="input-field" placeholder="your@email.com" required autocomplete="email">
                    </div>
                    <div class="field-error" id="emailError">Please enter a valid email address</div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label" for="firstName">First Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="firstName" name="first_name" class="input-field" placeholder="John" required autocomplete="given-name">
                        </div>
                        <div class="field-error" id="firstNameError">First name is required</div>
                    </div>

                    <div class="input-group">
                        <label class="input-label" for="lastName">Last Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="lastName" name="last_name" class="input-field" placeholder="Doe" required autocomplete="family-name">
                        </div>
                        <div class="field-error" id="lastNameError">Last name is required</div>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="gender-select">Gender</label>
                    <div class="input-wrapper">
                        <i class="fas fa-venus-mars input-icon"></i>
                        <select id="gender-select" name="gender" class="input-field" required>
                            <option value="" disabled selected>Select your gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                            <option value="prefer-not-to-say">Prefer not to say</option>
                        </select>
                    </div>
                    <div class="field-error" id="genderError">Please select your gender</div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="input-field" placeholder="Create a strong password" required autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'eyeIcon')">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span id="strengthText">Password strength</span>
                    </div>
                    <div class="field-error" id="passwordError">Password must be at least 8 characters</div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="retype_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="retype_password" name="retype_password" class="input-field" placeholder="Retype your password" required autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('retype_password', 'retypeEyeIcon')">
                            <i class="fas fa-eye" id="retypeEyeIcon"></i>
                        </button>
                    </div>
                    <div class="field-error" id="retypePasswordError">Passwords do not match</div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a></label>
                </div>

                <button type="submit" id="login-button">
                    <span class="button-text">Create My Account</span>
                    <div class="loading-spinner" id="loadingSpinner"></div>
                </button>
            </form>

            <div class="login-footer">
                <a href="#" onclick="history.back(); return false;" style="margin-right: 1rem;"><i class="fas fa-arrow-left"></i> Back</a>
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
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
        // Force gender placeholder on page load
        document.addEventListener('DOMContentLoaded', function() {
            const genderSelect = document.getElementById('gender-select');
            if (genderSelect) {
                genderSelect.selectedIndex = 0;
            }
        });

        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(iconId);

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            const strengthIndicator = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');

            if (password.length === 0) {
                strengthIndicator.style.display = 'none';
                return;
            }

            strengthIndicator.style.display = 'block';

            let strength = 0;
            let strengthClass = '';
            let strengthLabel = '';

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    strengthClass = 'strength-weak';
                    strengthLabel = 'Weak';
                    break;
                case 2:
                case 3:
                    strengthClass = 'strength-fair';
                    strengthLabel = 'Fair';
                    break;
                case 4:
                    strengthClass = 'strength-good';
                    strengthLabel = 'Good';
                    break;
                case 5:
                    strengthClass = 'strength-strong';
                    strengthLabel = 'Strong';
                    break;
            }

            strengthFill.className = `strength-fill ${strengthClass}`;
            strengthText.textContent = `Password strength: ${strengthLabel}`;
        }

        // Hides all field-level error messages and removes 'error' styles
        function hideAllFieldErrors() {
            document.querySelectorAll('.field-error').forEach(err => err.style.display = 'none');
            document.querySelectorAll('.input-wrapper.error').forEach(w => w.classList.remove('error'));
        }

        // Validate a single field and show its field-error (but does NOT touch other fields)
        function validateField(fieldId, errorId, validationFn) {
            const field = document.getElementById(fieldId);
            const wrapper = field.closest('.input-wrapper');
            const error = document.getElementById(errorId);
            const isValid = validationFn(field.value);

            if (isValid) {
                wrapper.classList.remove('error');
                error.style.display = 'none';
                return true;
            } else {
                // show only this field's error
                wrapper.classList.add('error');
                error.style.display = 'block';

                // hide after the duration while keeping the main error banner (if shown) until corrected
                setTimeout(() => {
                    wrapper.classList.remove('error');
                    error.style.display = 'none';
                }, 5000);

                return false;
            }
        }

        // Validate form in order and show only the first failing validation message
        function validateForm() {
            // hide any previous field-level errors so we can show exactly one
            hideAllFieldErrors();
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');

            // Ordered checks (the order user expects)
            const checks = [{
                    fieldId: 'email',
                    errorId: 'emailError',
                    fn: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)
                },
                {
                    fieldId: 'firstName',
                    errorId: 'firstNameError',
                    fn: (v) => v.trim().length > 0
                },
                {
                    fieldId: 'lastName',
                    errorId: 'lastNameError',
                    fn: (v) => v.trim().length > 0
                },
                {
                    fieldId: 'gender-select',
                    errorId: 'genderError',
                    fn: (v) => v !== ''
                },
                {
                    fieldId: 'password',
                    errorId: 'passwordError',
                    fn: (v) => v.length >= 8
                }
            ];

            for (let chk of checks) {
                const field = document.getElementById(chk.fieldId);
                const ok = chk.fn(field.value);
                if (!ok) {
                    // show the single failing field error
                    const wrapper = field.closest('.input-wrapper');
                    const err = document.getElementById(chk.errorId);
                    wrapper.classList.add('error');
                    err.style.display = 'block';

                    // ensure main banner shows the same message
                    errorText.textContent = err.textContent;
                    errorMessage.style.display = 'block';

                    // focus the field for convenience
                    field.focus();
                    return false;
                }
            }

            // check password match (separately, but still only one message allowed)
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('retype_password').value;
            if (password !== confirmPassword) {
                const retypeField = document.getElementById('retype_password');
                const retypeWrapper = retypeField.closest('.input-wrapper');
                const retypeErr = document.getElementById('retypePasswordError');

                hideAllFieldErrors();
                retypeWrapper.classList.add('error');
                retypeErr.style.display = 'block';
                errorText.textContent = retypeErr.textContent;
                errorMessage.style.display = 'block';
                retypeField.focus();
                return false;
            }

            // all good
            errorMessage.style.display = 'none';
            hideAllFieldErrors();
            return true;
        }

        // Real-time validation (on blur) — shows only that field's message (no other field errors)
        document.getElementById('email').addEventListener('blur', () => {
            document.getElementById('errorMessage').style.display = 'none';
            validateField('email', 'emailError', (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value));
        });
        document.getElementById('firstName').addEventListener('blur', () => {
            document.getElementById('errorMessage').style.display = 'none';
            validateField('firstName', 'firstNameError', (value) => value.trim().length > 0);
        });
        document.getElementById('lastName').addEventListener('blur', () => {
            document.getElementById('errorMessage').style.display = 'none';
            validateField('lastName', 'lastNameError', (value) => value.trim().length > 0);
        });
        document.getElementById('gender-select').addEventListener('blur', () => {
            document.getElementById('errorMessage').style.display = 'none';
            validateField('gender-select', 'genderError', (value) => value !== '');
        });
        document.getElementById('password').addEventListener('input', (e) => checkPasswordStrength(e.target.value));
        document.getElementById('password').addEventListener('blur', () => {
            document.getElementById('errorMessage').style.display = 'none';
            validateField('password', 'passwordError', (value) => value.length >= 8);
        });
        document.getElementById('retype_password').addEventListener('blur', () => {
            document.getElementById('errorMessage').style.display = 'none';
            // show mismatch error only when it actually mismatches
            const pw = document.getElementById('password').value;
            const rp = document.getElementById('retype_password').value;
            if (pw !== rp) {
                const retypeField = document.getElementById('retype_password');
                const retypeWrapper = retypeField.closest('.input-wrapper');
                const retypeErr = document.getElementById('retypePasswordError');
                hideAllFieldErrors();
                retypeWrapper.classList.add('error');
                retypeErr.style.display = 'block';
                setTimeout(() => {
                    retypeWrapper.classList.remove('error');
                    retypeErr.style.display = 'none';
                }, 5000);
            } else {
                // clear any retype error
                document.getElementById('retypePasswordError').style.display = 'none';
                document.getElementById('retype_password').closest('.input-wrapper').classList.remove('error');
            }
        });

        // Handle form submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            // Only prevent default if client-side validation fails
            if (!validateForm()) {
                e.preventDefault();
                return;
            }

            if (!document.getElementById('terms').checked) {
                e.preventDefault();
                alert('You must agree to the Terms of Service and Privacy Policy to create an account.');
                return;
            }
            
            // If client-side validation passes, allow form submission to PHP
            // The PHP will handle server-side validation and redirection
            const submitButton = document.getElementById('login-button');
            const buttonText = submitButton.querySelector('.button-text');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            submitButton.disabled = true;
            buttonText.style.opacity = '0';
            loadingSpinner.style.display = 'block';
        });

        document.querySelectorAll('.input-field').forEach(field => {
            field.addEventListener('input', () => {
                document.getElementById('errorMessage').style.display = 'none';
                const serverErrorMessage = document.getElementById('serverErrorMessage');
                if (serverErrorMessage) {
                    serverErrorMessage.style.display = 'none';
                }
                const serverSuccessMessage = document.getElementById('serverSuccessMessage');
                if (serverSuccessMessage) {
                    serverSuccessMessage.style.display = 'none';
                }
            });
        });

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