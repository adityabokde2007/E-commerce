<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
    exit;
}

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

if ($cart_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    // Delete item ensuring it belongs to user
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);

    // Get grand total and new badge count
    $stmt_totals = $pdo->prepare("SELECT SUM(c.quantity) as count, SUM(c.quantity * COALESCE(p.discount_price, p.price)) as total 
                                  FROM cart c JOIN products p ON c.product_id = p.id 
                                  WHERE c.user_id = ?");
    $stmt_totals->execute([$_SESSION['user_id']]);
    $totals = $stmt_totals->fetch();

    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed from cart.',
        'cart_total' => formatPrice($totals['total'] ?? 0),
        'cart_count' => $totals['count'] ?? 0
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}
