<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Sign In</title>
    <link rel="stylesheet" href="assets/css/modern_auth.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <?php
            require_once __DIR__ . '/config.php';
            // Check if SMTP settings are still the default ones
            if (defined('SMTP_USER') && (SMTP_USER === 'your_email@gmail.com' || SMTP_PASS === 'your_app_password')) {
                echo '<div class="warning-message" style="padding: 1rem; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px; color: #856404; margin-bottom: 1.5rem; text-align: center;">';
                echo '<i class="fas fa-exclamation-triangle"></i> <strong>Action Required:</strong> Please configure your SMTP settings in <code>config.php</code> to enable email features.';
                echo '</div>';
            }
            ?>
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <a href="index.php" class="logo" style="text-decoration: none; color: var(--primary-blue); font-weight: 700; font-size: 1.8rem;">
                    <i class="fas fa-graduation-cap"></i>
                    Study Buddy
                </a>
            </div>
            <h1 class="auth-title">Welcome Back!</h1>
            <p class="auth-subtitle">Sign in to continue your learning journey.</p>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" novalidate method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="input-group">
                    <label class="input-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" class="input-field" placeholder="your@email.com" required autocomplete="email">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="input-field" placeholder="Enter your password" required autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'eyeIcon')">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-button">Sign In</button>
            </form>

            <div style="text-align: center; margin-top: 15px; margin-bottom: 15px;">
                <a href="google-login-init.php" class="auth-button google-button" style="background-color: #db4437; color: white; text-decoration: none; display: block; padding: 10px 20px; border-radius: 5px;">
                    <i class="fab fa-google"></i> 使用谷歌登录
                </a>
            </div>

            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Sign up here</a>
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
    </script>
</body>

</html>