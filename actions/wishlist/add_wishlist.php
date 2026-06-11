<?php
// actions/wishlist/add_wishlist.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to manage your wishlist.']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Check if product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 1 LIMIT 1");
    $stmt->execute([$product_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit;
    }

    // Check if already in wishlist
    $stmt_check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt_check->execute([$user_id, $product_id]);

    if ($stmt_check->rowCount() > 0) {
        // Already exists — remove it (toggle off)
        $stmt_del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt_del->execute([$user_id, $product_id]);

        $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
        $stmt_count->execute([$user_id]);
        $count = $stmt_count->fetch()['total'];

        echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Removed from wishlist.', 'wishlist_count' => $count]);
    } else {
        // Add to wishlist
        $stmt_add = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt_add->execute([$user_id, $product_id]);

        $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
        $stmt_count->execute([$user_id]);
        $count = $stmt_count->fetch()['total'];

        echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Added to wishlist!', 'wishlist_count' => $count]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
