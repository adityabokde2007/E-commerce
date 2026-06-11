<?php
// register.php
require_once 'config/db.php';

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'includes/header.php';
?>

<!-- Include Auth Stylesheet -->
<link rel="stylesheet" href="<?= ASSETS_URL ?>css/auth.css?v=<?= time() ?>">

<!-- Add auth-page class to body via JS since header is already loaded -->
<script>document.body.classList.add('auth-page');</script>

<div class="auth-container">
    <!-- Left Side: Branding / Illustration -->
    <div class="auth-illustration">
        <a href="<?= SITE_URL ?>/index.php" class="logo">
            <img src="<?= ASSETS_URL ?>images/logo.png" alt="<?= SITE_NAME ?>" style="height: 150px; margin-bottom: 20px;">
        </a>
        <h2>Join Our Community</h2>
        <p>Create an account to track your orders, save your favorite items, and enjoy exclusive discounts.</p>
    </div>

    <!-- Right Side: Registration Form -->
    <div class="auth-form-wrapper">
        <div class="auth-header">
            <h3>Create an Account</h3>
            <p>Please fill in your details to register.</p>
        </div>

        <form action="<?= SITE_URL ?>/actions/auth/register_action.php" method="POST" class="auth-form" id="registerForm">
            
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your name">
                <i class="fa-regular fa-user input-icon"></i>
                <span class="error-message" id="nameError"></span>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address">
                <i class="fa-regular fa-envelope input-icon"></i>
                <span class="error-message" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter phone number">
                <i class="fa-solid fa-phone input-icon"></i>
                <span class="error-message" id="phoneError"></span>
            </div>

            <div class="grid grid-2" style="gap: 15px;">
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm password">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                    <span class="error-message" id="confirmPasswordError"></span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-2">
                <i class="fa-solid fa-user-plus"></i> Register
            </button>

            <div class="auth-footer">
                Already have an account? <a href="<?= SITE_URL ?>/login.php" class="text-primary">Login here</a>
            </div>
        </form>
    </div>
</div>

<!-- Client-side Validation Script -->
<script>
    // Password visibility toggle
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // AJAX register submit with toast notifications
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const name = document.getElementById('name');
        const email = document.getElementById('email');
        const phone = document.getElementById('phone');
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');
        let isValid = true;

        // Reset errors
        document.querySelectorAll('.form-group').forEach(group => group.classList.remove('has-error'));
        document.querySelectorAll('.error-message').forEach(span => span.innerText = '');

        // Name Validation
        if (name.value.trim() === '') {
            showError(name, 'nameError', 'Full name is required');
            isValid = false;
        }

        // Email Validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email.value.trim() === '') {
            showError(email, 'emailError', 'Email address is required');
            isValid = false;
        } else if (!emailRegex.test(email.value.trim())) {
            showError(email, 'emailError', 'Please enter a valid email address');
            isValid = false;
        }

        // Phone Validation (Basic check)
        if (phone.value.trim() === '') {
            showError(phone, 'phoneError', 'Phone number is required');
            isValid = false;
        }

        // Password Validation
        if (password.value === '') {
            showError(password, 'passwordError', 'Password is required');
            isValid = false;
        } else if (password.value.length < 6) {
            showError(password, 'passwordError', 'Password must be at least 6 characters');
            isValid = false;
        }

        // Confirm Password Validation
        if (confirm_password.value === '') {
            showError(confirm_password, 'confirmPasswordError', 'Please confirm your password');
            isValid = false;
        } else if (password.value !== confirm_password.value) {
            showError(confirm_password, 'confirmPasswordError', 'Passwords do not match');
            isValid = false;
        }

        if (!isValid) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form)
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;

            if (ok && data.status === 'success') {
                showToast('Success', data.message || 'Registration successful.', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || '<?= SITE_URL ?>/shop.php';
                }, 900);
            } else {
                showToast('Error', data.message || 'Registration failed.', 'error');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
            showToast('Error', 'Network error. Please try again.', 'error');
        });
    });

    function showError(inputElement, errorId, message) {
        inputElement.closest('.form-group').classList.add('has-error');
        document.getElementById(errorId).innerText = message;
    }
</script>

<?php require_once 'includes/footer.php'; ?>
