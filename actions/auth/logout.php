<?php
// actions/auth/logout.php

// 1. We only need the session started and constants/functions
require_once '../../config/constants.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all of the session variables
$_SESSION = array();

// 3. If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session.
session_destroy();

// 5. Start a new session just to store the flash message
session_start();

// Need to require functions.php AFTER restarting the session so we can set the flash message
require_once '../../includes/functions.php';

setFlashMessage('success', "You have been successfully logged out.");

// 6. Redirect to home page
redirect(SITE_URL . '/index.php');
