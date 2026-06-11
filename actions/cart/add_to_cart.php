<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

function cartFallbackUrl($product_id = 0) {
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, SITE_URL) === 0) {
            return $referer;
        }
    }

    if ($product_id > 0) {
        return SITE_URL . '/product.php?id=' . $product_id;
    }

    return SITE_URL . '/cart.php';
}

function respondCart($payload, $fallbackUrl, $type = 'info') {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    setFlashMessage($type, $payload['message'] ?? 'Request completed.');
    redirect($fallbackUrl);
}

if (!isLoggedIn()) {
    respondCart(
        ['status' => 'error', 'message' => 'Please login first to add items to cart.'],
        cartFallbackUrl(),
        'warning'
    );
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    respondCart(
        ['status' => 'error', 'message' => 'Invalid request.'],
        cartFallbackUrl($product_id),
        'danger'
    );
}

try {
    // Check if product exists and stock availability
    $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ? AND status = 1 LIMIT 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        respondCart(
            ['status' => 'error', 'message' => 'Product not found.'],
            cartFallbackUrl(),
            'danger'
        );
    }

    // Check existing cart entry
    $stmt_cart = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt_cart->execute([$_SESSION['user_id'], $product_id]);
    $cart_item = $stmt_cart->fetch();

    $new_qty = $cart_item ? $cart_item['quantity'] + $quantity : $quantity;

    if ($new_qty > $product['stock']) {
        respondCart(
            ['status' => 'error', 'message' => "Only {$product['stock']} units available in stock."],
            cartFallbackUrl($product_id),
            'warning'
        );
    }

    if ($cart_item) {
        $stmt_update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt_update->execute([$new_qty, $cart_item['id']]);
        $cart_action = 'updated';
    } else {
        $stmt_insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt_insert->execute([$_SESSION['user_id'], $product_id, $quantity]);
        $cart_action = 'added';
    }

    // Get total items for badge
    $stmt_count = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt_count->execute([$_SESSION['user_id']]);
    $total_items = $stmt_count->fetch()['total'] ?? 0;

    respondCart(
        [
            'status' => 'success',
            'message' => $cart_action === 'updated'
                ? 'Already added to cart. Quantity updated!'
                : 'Added to cart!',
            'cart_count' => $total_items,
            'action' => $cart_action,
            'product_id' => $product_id
        ],
        cartFallbackUrl($product_id),
        'success'
    );

} catch (PDOException $e) {
    respondCart(
        ['status' => 'error', 'message' => 'Database error occurred.'],
        cartFallbackUrl($product_id),
        'danger'
    );
}
