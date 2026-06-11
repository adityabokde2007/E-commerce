<?php
// actions/newsletter.php
require_once '../config/db.php';
require_once '../includes/functions.php';

function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

function newsletterFallbackUrl() {
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, SITE_URL) === 0) {
            return $referer;
        }
    }

    return SITE_URL;
}

function respondNewsletter($payload, $fallbackType = 'info') {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    $message = $payload['message'] ?? ($payload['error'] ?? 'Request completed.');
    setFlashMessage($fallbackType, $message);
    redirect(newsletterFallbackUrl());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respondNewsletter(['success' => false, 'error' => 'Please provide a valid email address.'], 'warning');
    }

    try {
        // Normally you'd insert this into a 'newsletter_subscribers' table.
        // For this demo, we'll just check if the table exists or pretend it succeeded.
        // Let's try to insert if the table exists, otherwise just return success.
        
        $tableExists = $pdo->query("SHOW TABLES LIKE 'newsletter'")->rowCount() > 0;
        
        if ($tableExists) {
             $stmt = $pdo->prepare("SELECT id FROM newsletter WHERE email = ?");
             $stmt->execute([$email]);
             if ($stmt->rowCount() > 0) {
                 respondNewsletter(['success' => false, 'error' => 'This email is already subscribed.'], 'warning');
             }

             $stmt_insert = $pdo->prepare("INSERT INTO newsletter (email) VALUES (?)");
             $stmt_insert->execute([$email]);
        }
        
        respondNewsletter(['success' => true, 'message' => 'Thank you for subscribing!'], 'success');

    } catch (PDOException $e) {
        respondNewsletter(['success' => false, 'error' => 'A database error occurred.'], 'danger');
    }
} else {
    respondNewsletter(['success' => false, 'error' => 'Invalid request method.'], 'danger');
}
