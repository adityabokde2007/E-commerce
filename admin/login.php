<?php
// admin/login.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// If already logged in AND an admin, redirect straight to dashboard
if (isLoggedIn() && isAdmin()) {
    redirect(SITE_URL . '/admin/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= SITE_NAME ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Common CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css">
    <style>
        body { background: #111827; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; font-family: 'Inter', sans-serif; }
        .admin-login-card { background: white; width: 100%; max-width: 420px; border-radius: 12px; padding: 45px 40px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.4); }
        .admin-logo { text-align: center; margin-bottom: 35px; font-size: 2rem; font-weight: 700; color: #111827; letter-spacing: -0.5px; }
        .admin-logo span { color: #FF6B35; }
    </style>
</head>
<body>

    <div class="admin-login-card">
        <div class="admin-logo" style="text-align: center; margin-bottom: 20px;">
            <img src="<?= ASSETS_URL ?>images/logo.png" alt="<?= SITE_NAME ?>" style="height: 50px; display: inline-block;"> 
            <div style="font-size: 1.2rem; color: #6b7280; margin-top: 10px;">Admin Login</div>
        </div>
        
        <?php displayFlashMessage(); ?>
        
        <!-- Posts to the global login action which handles role routing -->
        <form action="<?= SITE_URL ?>/actions/auth/login_action.php" method="POST">
            <div class="form-group mb-4">
                <label class="form-label" style="color: #4b5563; font-weight: 500;">Admin Email</label>
                <div style="position: relative;">
                    <i class="fa-regular fa-envelope" style="position: absolute; left: 15px; top: 15px; color: #9ca3af;"></i>
                    <input type="email" name="email" class="form-control" style="padding-left: 45px;" required>
                </div>
            </div>
            
            <div class="form-group mb-5">
                <label class="form-label" style="color: #4b5563; font-weight: 500;">Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 15px; top: 15px; color: #9ca3af;"></i>
                    <input type="password" name="password" class="form-control" style="padding-left: 45px;" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block p-3" style="font-size: 1.1rem; font-weight: 600;">
                Login to Dashboard
            </button>
        </form>
        
        <p class="text-center mt-4 mb-0 text-muted" style="font-size: 0.9rem;">
            <a href="<?= SITE_URL ?>" style="color: #6b7280; text-decoration: none; transition: color 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Main Store</a>
        </p>
    </div>

</body>
</html>
