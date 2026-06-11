<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    setFlashMessage('warning', 'Please log in to manage your wishlist.');
    redirect(SITE_URL . '/login.php');
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    setFlashMessage('danger', 'Invalid product.');
    redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/wishlist.php');
}

$returnUrl = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/product.php?id=' . $product_id;
if (strpos($returnUrl, SITE_URL) !== 0) {
    $returnUrl = SITE_URL . '/product.php?id=' . $product_id;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 1 LIMIT 1");
    $stmt->execute([$product_id]);

    if ($stmt->rowCount() === 0) {
        setFlashMessage('danger', 'Product not found.');
        redirect($returnUrl);
    }

    $user_id = $_SESSION['user_id'];

    $stmt_check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt_check->execute([$user_id, $product_id]);

    if ($stmt_check->rowCount() > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt_del->execute([$user_id, $product_id]);
        setFlashMessage('success', 'Removed from wishlist.');
    } else {
        $stmt_add = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt_add->execute([$user_id, $product_id]);
        setFlashMessage('success', 'Added to wishlist!');
    }

    redirect($returnUrl);
} catch (PDOException $e) {
    setFlashMessage('danger', 'Database error occurred.');
    redirect($returnUrl);
}