<?php require_once 'includes/signup_handler.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Join Now</title>
    <link rel="stylesheet" href="assets/css/modern_auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <?php
            // Check if SMTP settings are still the default ones
            if (defined('SMTP_USER') && (SMTP_USER === 'your_email@gmail.com' || SMTP_PASS === 'your_app_password')) {
                echo '<div class="warning-message" style="padding: 1rem; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px; color: #856404; margin-bottom: 1.5rem; text-align: center;">';
                echo '<i class="fas fa-exclamation-triangle"></i> <strong>Action Required:</strong> Please configure your SMTP settings in <code>config.php</code> to enable email verification.';
                echo '</div>';
            }
            ?>
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <a href="index.php" class="logo" style="text-decoration: none; color: var(--primary-blue); font-weight: 700; font-size: 1.8rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Study Buddy
                </a>
            </div>
            <h1 class="auth-title">Create Your Account</h1>
            <p class="auth-subtitle">Join thousands of students on their learning journey.</p>

            <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !$response['success']): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $response['message']; ?></div>
            <?php endif; ?>

            <form id="signupForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="input-group">
                    <label class="input-label" for="email">Email Address</label>
                    <div class="input-wrapper email-wrapper">
                        <input type="email" id="email" name="email" class="input-field" placeholder="your@email.com" required autocomplete="email">
                        <button type="button" id="get-code-btn" class="get-code-btn">Get Code</button>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="verification_code">Verification Code</label>
                    <div class="input-wrapper">
                        <input type="text" id="verification_code" name="verification_code" class="input-field" placeholder="Enter 6-digit code" required>
                        <i class="fas fa-shield-alt input-icon"></i>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label" for="firstName">First Name</label>
                        <div class="input-wrapper">
                            <input type="text" id="firstName" name="first_name" class="input-field" placeholder="John" required autocomplete="given-name">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="lastName">Last Name</label>
                        <div class="input-wrapper">
                            <input type="text" id="lastName" name="last_name" class="input-field" placeholder="Doe" required autocomplete="family-name">
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="gender">Gender</label>
                    <div class="input-wrapper">
                        <select id="gender" name="gender" class="input-field" required>
                            <option value="" disabled selected>Select your gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        <i class="fas fa-venus-mars input-icon"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="input-field" placeholder="Create a strong password" required autocomplete="new-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'eyeIcon')">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="retype_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="retype_password" name="retype_password" class="input-field" placeholder="Retype your password" required autocomplete="new-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('retype_password', 'retypeEyeIcon')">
                            <i class="fas fa-eye" id="retypeEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                </div>

                <button type="submit" class="auth-button">Create My Account</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(fieldId, iconId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(iconId);
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    document.getElementById('get-code-btn').addEventListener('click', async function() {
        const emailField = document.getElementById('email');
        const email = emailField.value;
        const getCodeBtn = this;

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('Please enter a valid email address.');
            return;
        }

        getCodeBtn.disabled = true;
        getCodeBtn.textContent = 'Sending...';

        try {
            const response = await fetch('api/send_verification_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            });
            const data = await response.json();
            if (data.success) {
                alert('Verification code sent! Please check your email.');
                let countdown = 60;
                getCodeBtn.textContent = `Wait ${countdown}s`;
                const interval = setInterval(() => {
                    countdown--;
                    getCodeBtn.textContent = `Wait ${countdown}s`;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        getCodeBtn.textContent = 'Get Code';
                        getCodeBtn.disabled = false;
                    }
                }, 1000);
            } else {
                alert('Failed to send code: ' + (data.message || 'Unknown error'));
                getCodeBtn.disabled = false;
                getCodeBtn.textContent = 'Get Code';
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
            getCodeBtn.disabled = false;
            getCodeBtn.textContent = 'Get Code';
        }
    });
    </script>
</body>

</html>