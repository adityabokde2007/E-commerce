<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in.']);
    exit;
}

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($cart_id <= 0 || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    // Check cart item belongs to user and check stock
    $stmt = $pdo->prepare("SELECT c.id, c.product_id, p.stock, p.price, p.discount_price 
                           FROM cart c JOIN products p ON c.product_id = p.id 
                           WHERE c.id = ? AND c.user_id = ? LIMIT 1");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch();

    if (!$cart_item) {
        echo json_encode(['status' => 'error', 'message' => 'Item not found in cart.']);
        exit;
    }

    if ($quantity > $cart_item['stock']) {
        echo json_encode(['status' => 'error', 'message' => "Only {$cart_item['stock']} units available."]);
        exit;
    }

    // Update quantity
    $stmt_update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt_update->execute([$quantity, $cart_id]);

    // Calculate subtotal
    $item_price = $cart_item['discount_price'] > 0 ? $cart_item['discount_price'] : $cart_item['price'];
    $item_subtotal = $item_price * $quantity;

    // Get grand total and new badge count
    $stmt_totals = $pdo->prepare("SELECT SUM(c.quantity) as count, SUM(c.quantity * COALESCE(p.discount_price, p.price)) as total 
                                  FROM cart c JOIN products p ON c.product_id = p.id 
                                  WHERE c.user_id = ?");
    $stmt_totals->execute([$_SESSION['user_id']]);
    $totals = $stmt_totals->fetch();

    echo json_encode([
        'status' => 'success',
        'item_subtotal' => formatPrice($item_subtotal),
        'cart_total' => formatPrice($totals['total'] ?? 0),
        'cart_count' => $totals['count'] ?? 0
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}
