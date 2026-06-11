<?php
// actions/profile/update_profile.php
error_reporting(0); // Strictly disable all error output
ini_set('display_errors', 0);

require_once '../../config/db.php';
require_once '../../includes/functions.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function respond($success, $message, $redirectUrl = '', $field = '', $code = '') {
    global $is_ajax;
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'redirect' => $redirectUrl,
            'field' => $field,
            'code' => $code ?: ($success ? 'success' : 'error')
        ]);
        exit;
    }
    setFlashMessage($success ? 'success' : 'error', $message);
    redirect($redirectUrl);
}

if (!isLoggedIn()) {
    if ($is_ajax) {
        respond(false, 'Your session has expired. Please log in again.', SITE_URL . '/login.php', '', 'auth_error');
    }
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        respond(false, 'Invalid request method.');
    }
    redirect(SITE_URL . '/profile.php');
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ---- UPDATE PERSONAL INFO ----
if ($action === 'update_info') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($phone)) {
        respond(false, 'Name, Email, and Phone Number are required.', SITE_URL . '/profile.php?tab=personal', 'name');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email format.', SITE_URL . '/profile.php?tab=personal', 'email');
    }

    try {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt_check->execute([$email, $user_id]);
        if ($stmt_check->rowCount() > 0) {
            respond(false, 'This email is already taken by another account.', SITE_URL . '/profile.php?tab=personal', 'email');
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $user_id]);

        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        respond(true, 'Profile updated successfully!', SITE_URL . '/profile.php?tab=personal');
    } catch (PDOException $e) {
        respond(false, 'Failed to update profile. Please try again.', SITE_URL . '/profile.php?tab=personal');
    }
    exit;
}

// ---- CHANGE PASSWORD ----
if ($action === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        respond(false, 'Please fill in all password fields.', SITE_URL . '/profile.php?tab=password', 'current_password');
    }

    if (strlen($new_password) < 6) {
        respond(false, 'New password must be at least 6 characters long.', SITE_URL . '/profile.php?tab=password', 'new_password');
    }

    if ($new_password !== $confirm_password) {
        respond(false, 'New password and confirm password do not match.', SITE_URL . '/profile.php?tab=password', 'confirm_password');
    }

    if ($current_password === $new_password) {
        respond(false, 'New password must be different from your current password.', SITE_URL . '/profile.php?tab=password', 'new_password');
    }

    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || empty($user['password'])) {
            respond(false, 'We could not verify your account. Please login again.', SITE_URL . '/login.php', 'current_password');
        }

        $stored_password = $user['password'];

        if (!password_verify($current_password, $stored_password)) {
            respond(false, 'Current password is incorrect.', SITE_URL . '/profile.php?tab=password', 'current_password');
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt_update->execute([$hashed, $user_id]);

        respond(true, 'Password changed successfully!', SITE_URL . '/profile.php?tab=password');
    } catch (PDOException $e) {
        respond(false, 'Failed to change password. Please try again.', SITE_URL . '/profile.php?tab=password');
    }
    exit;
}

redirect(SITE_URL . '/profile.php');
