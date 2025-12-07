
<div class="auth-card">
    <button class="modal-close-btn">&times;</button>
    <h1 class="auth-title">Create Your Account</h1>
    <p class="auth-subtitle">Join thousands of students on their learning journey.</p>
    <div id="signup-modal-message" class="error-message" style="display: none;"></div>
    <form id="modalSignupForm" method="POST">
         <div class="form-row">
            <div class="input-group">
                <label class="input-label" for="signup-firstName">First Name</label>
                <div class="input-wrapper">
                    <input type="text" id="signup-firstName" name="first_name" class="input-field" placeholder="John" required autocomplete="given-name">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>
            <div class="input-group">
                <label class="input-label" for="signup-lastName">Last Name</label>
                <div class="input-wrapper">
                    <input type="text" id="signup-lastName" name="last_name" class="input-field" placeholder="Doe" required autocomplete="family-name">
                </div>
            </div>
        </div>
        <div class="input-group">
            <label class="input-label" for="signup-gender">Gender</label>
            <div class="input-wrapper">
                <select id="signup-gender" name="gender" class="input-field" required>
                    <option value="" disabled selected>Select your gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <i class="fas fa-venus-mars input-icon"></i>
            </div>
        </div>
        <div class="input-group">
            <label class="input-label" for="signup-email">Email Address</label>
            <div class="input-wrapper email-wrapper">
                <input type="email" id="signup-email" name="email" class="input-field" placeholder="your@email.com" required autocomplete="email">
                <button type="button" id="modal-get-code-btn" class="get-code-btn">Get Code</button>
            </div>
        </div>
        <div class="input-group">
            <label class="input-label" for="verification_code">Verification Code</label>
            <div class="input-wrapper">
                <input type="text" id="verification_code" name="verification_code" class="input-field" placeholder="Enter 6-digit code" required>
                <i class="fas fa-shield-alt input-icon"></i>
            </div>
        </div>
        <div class="input-group">
            <label class="input-label" for="signup-password">Password</label>
            <div class="input-wrapper">
                <input type="password" id="signup-password" name="password" class="input-field" placeholder="Create a strong password" required autocomplete="new-password">
                <i class="fas fa-lock input-icon"></i>
            </div>
        </div>
        <div class="input-group">
            <label class="input-label" for="signup-retype-password">Retype Password</label>
            <div class="input-wrapper">
                <input type="password" id="signup-retype-password" name="retype_password" class="input-field" placeholder="Retype your password" required autocomplete="new-password">
                <i class="fas fa-lock input-icon"></i>
            </div>
        </div>
        <div class="checkbox-group">
            <input type="checkbox" id="signup-terms" name="terms" required>
            <label for="signup-terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
        </div>
        <button type="submit" class="auth-button">Create My Account</button>
    </form>
    <div class="auth-footer">
        Already have an account? <a href="#" class="modal-switch" data-target="ajax/login_form.php">Sign in here</a>
    </div>
</div>
