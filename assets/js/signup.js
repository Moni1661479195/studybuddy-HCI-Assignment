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