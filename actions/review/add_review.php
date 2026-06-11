<?php
// actions/review/add_review.php
error_reporting(0);
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', SITE_URL . '/index.php');
}

if (!isLoggedIn()) {
    respond(false, 'You must be logged in to leave a review.', SITE_URL . '/login.php', '', 'auth_error');
}

$user_id = $_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = sanitize($_POST['comment'] ?? '');

$redirect_url = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : SITE_URL . "/product.php?id=$product_id";

// Validate inputs
if ($product_id <= 0) {
    respond(false, 'Invalid product.', SITE_URL . '/index.php');
}

if ($rating < 1 || $rating > 5) {
    respond(false, 'Please select a valid star rating.', $redirect_url);
}

// 1. Verify User has actually purchased the product (Skipped for testing/demo purposes)
// 2. Prevent duplicate reviews (1 review per product per user)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
if ($stmt->fetchColumn() > 0) {
    respond(false, 'You have already submitted a review for this product.', $redirect_url);
}

// 3. Insert the review
try {
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $rating, $comment]);
    respond(true, 'Thank you! Your review has been published.', $redirect_url);
} catch (PDOException $e) {
    respond(false, 'A database error occurred while submitting your review.', $redirect_url);
}
