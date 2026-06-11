<?php
// admin/actions/order_action.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/orders.php');
}

$order_id = (int)$_POST['order_id'];
$new_order_status = $_POST['order_status'] ?? '';
$new_payment_status = $_POST['payment_status'] ?? '';

// Fetch current status
$stmt = $pdo->prepare("SELECT order_status, payment_status FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$current = $stmt->fetch();

if (!$current) {
    setFlashMessage('error', 'Order not found.');
    redirect(SITE_URL . '/admin/orders.php');
}

$current_status = $current['order_status'];

// Define valid strict state transitions
$valid_transitions = [
    'placed' => ['confirmed', 'cancelled'],
    'confirmed' => ['shipped', 'cancelled'],
    'shipped' => ['delivered'],
    'delivered' => [], // Terminal state
    'cancelled' => []  // Terminal state
];

$is_valid = true;

// Validate order status transition if it's changing
if ($new_order_status !== $current_status) {
    if (!in_array($new_order_status, $valid_transitions[$current_status])) {
        $is_valid = false;
        setFlashMessage('error', "Invalid order status transition from '" . ucfirst($current_status) . "' to '" . ucfirst($new_order_status) . "'.");
    }
}

if ($is_valid) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$new_order_status, $new_payment_status, $order_id]);
        setFlashMessage('success', "Order #{$order_id} has been updated successfully.");
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error updating order: ' . $e->getMessage());
    }
}

// Redirect back to wherever the form was submitted from
redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/admin/orders.php');
