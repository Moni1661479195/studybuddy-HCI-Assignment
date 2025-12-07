
<div class="auth-card">
    <button class="modal-close-btn">&times;</button>
    <h1 class="auth-title">Welcome Back!</h1>
    <p class="auth-subtitle">Sign in to continue your learning journey.</p>
    <div id="login-modal-message" class="error-message" style="display: none;"></div>
    <form id="modalLoginForm" method="POST">
        <div class="input-group">
            <label class="input-label" for="login-email">Email Address</label>
            <div class="input-wrapper">
                <input type="email" id="login-email" name="email" class="input-field" placeholder="your@email.com" required autocomplete="email">
                <i class="fas fa-envelope input-icon"></i>
            </div>
        </div>
        <div class="input-group">
            <label class="input-label" for="login-password">Password</label>
            <div class="input-wrapper">
                <input type="password" id="login-password" name="password" class="input-field" placeholder="Enter your password" required autocomplete="current-password">
                <i class="fas fa-lock input-icon"></i>
            </div>
        </div>
        <button type="submit" class="auth-button">Sign In</button>
    </form>

    <div style="text-align: center; margin-top: 15px; margin-bottom: 15px;">
        <a href="google-login-init.php" class="auth-button google-button" style="background-color: #55ba46ff; color: white; text-decoration: none; display: block; padding: 10px 20px; border-radius: 5px;">
            <i class="fab fa-google"></i> Log in with Google
        </a>
    </div>

    <div class="auth-footer">
        Don't have an account? <a href="#" class="modal-switch" data-target="ajax/signup_form.php">Sign up here</a>
    </div>
</div>
