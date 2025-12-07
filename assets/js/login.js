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
            fieldId: 'password',
            errorId: 'passwordError',
            fn: (v) => v.length > 0
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
document.getElementById('password').addEventListener('blur', () => {
    document.getElementById('errorMessage').style.display = 'none';
    validateField('password', 'passwordError', (value) => value.length > 0);
});

// Handle form submission
document.getElementById('loginForm').addEventListener('submit', function(e) {
    // Only prevent default if client-side validation fails
    if (!validateForm()) {
        e.preventDefault();
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