<?php
// login.php
require_once 'config/db.php';

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
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
        <h2>Welcome Back!</h2>
        <p>Log in to access your account, check order status, and continue shopping.</p>
    </div>

    <!-- Right Side: Login Form -->
    <div class="auth-form-wrapper">
        <div class="auth-header">
            <h3>Login to Your Account</h3>
            <p>Please enter your credentials.</p>
        </div>

        <form action="<?= SITE_URL ?>/actions/auth/login_action.php" method="POST" class="auth-form" id="loginForm">
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com">
                <i class="fa-regular fa-envelope input-icon"></i>
                <span class="error-message" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password">
                <i class="fa-solid fa-lock input-icon"></i>
                <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                    <i class="fa-regular fa-eye"></i>
                </button>
                <span class="error-message" id="passwordError"></span>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-2">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
            </button>

            <div class="auth-footer">
                Don't have an account? <a href="<?= SITE_URL ?>/register.php" class="text-primary">Register here</a>
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

    // AJAX login submit with toast notifications
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        let isValid = true;

        // Reset errors
        document.querySelectorAll('.form-group').forEach(group => group.classList.remove('has-error'));
        document.querySelectorAll('.error-message').forEach(span => span.innerText = '');

        // Email Validation
        if (email.value.trim() === '') {
            showError(email, 'emailError', 'Email address is required');
            isValid = false;
        }

        // Password Validation
        if (password.value === '') {
            showError(password, 'passwordError', 'Password is required');
            isValid = false;
        }

        if (!isValid) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Logging in...';

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
                showToast('Success', data.message || 'Login successful.', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || '<?= SITE_URL ?>/index.php';
                }, 700);
            } else {
                showToast('Error', data.message || 'Login failed.', 'error');
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
