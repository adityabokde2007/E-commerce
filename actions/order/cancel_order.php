<?php
// actions/order/cancel_order.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/orders.php');
}

$order_id = (int)($_POST['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    redirect(SITE_URL . '/orders.php');
}

try {
    // Verify order belongs to user and is in 'placed' status
    $stmt = $pdo->prepare("SELECT id, order_status FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlashMessage('error', 'Order not found.');
        redirect(SITE_URL . '/orders.php');
    }

    if ($order['order_status'] !== 'placed') {
        setFlashMessage('error', 'This order can no longer be cancelled.');
        redirect(SITE_URL . '/order-detail.php?id=' . $order_id);
    }

    // Begin transaction to cancel order and restore stock
    $pdo->beginTransaction();

    // Cancel the order
    $stmt_cancel = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
    $stmt_cancel->execute([$order_id]);

    // Restore stock for each order item
    $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll();

    $stmt_restock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    foreach ($items as $item) {
        $stmt_restock->execute([$item['quantity'], $item['product_id']]);
    }

    $pdo->commit();

    setFlashMessage('success', 'Your order has been cancelled and stock has been restored.');
    redirect(SITE_URL . '/order-detail.php?id=' . $order_id);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlashMessage('error', 'An error occurred while cancelling your order.');
    redirect(SITE_URL . '/order-detail.php?id=' . $order_id);
}
