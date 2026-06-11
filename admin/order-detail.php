<?php
// admin/order-detail.php
require_once 'includes/admin_header.php';

$id = $_GET['id'] ?? 0;

// Fetch Core Order Data
$stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                       FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Order not found.');
    redirect(SITE_URL . '/admin/orders.php');
}

// Fetch Address
$stmt_addr = $pdo->prepare("SELECT * FROM addresses WHERE id = ?");
$stmt_addr->execute([$order['address_id']]);
$address = $stmt_addr->fetch();

// Fetch Items
$stmt_items = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt_items->execute([$id]);
$items = $stmt_items->fetchAll();

$status_badges = [
    'placed' => 'badge-pending',
    'confirmed' => 'badge-confirmed',
    'shipped' => 'badge-shipped',
    'delivered' => 'badge-delivered',
    'cancelled' => 'badge-cancelled'
];

$payment_badges = [
    'pending' => 'badge-pending',
    'paid' => 'badge-delivered',
    'failed' => 'badge-cancelled'
];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h2 style="margin: 0; color: #111827; display: inline-block; margin-right: 15px;">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h2>
        <span class="badge <?= $status_badges[$order['order_status']] ?>" style="font-size: 0.9rem; padding: 6px 15px; vertical-align: middle;">
            <?= ucfirst($order['order_status']) ?>
        </span>
    </div>
    <a href="orders.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Orders</a>
</div>

<div class="grid grid-2" style="gap: 30px;">
    
    <!-- Left Column: Details -->
    <div>
        <!-- Customer Info -->
        <div class="admin-table-container mb-4">
            <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">Customer Details</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($order['customer_email']) ?>"><?= htmlspecialchars($order['customer_email']) ?></a></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></p>
        </div>

        <!-- Shipping Address -->
        <div class="admin-table-container mb-4">
            <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">Shipping Address</h3>
            <?php if($address): ?>
                <p><strong><?= htmlspecialchars($address['full_name']) ?></strong> (<?= htmlspecialchars($address['phone']) ?>)</p>
                <p class="text-muted" style="line-height: 1.6;">
                    <?= htmlspecialchars($address['address_line']) ?><br>
                    <?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?> - <?= htmlspecialchars($address['pincode']) ?>
                </p>
            <?php else: ?>
                <p class="text-danger">Address information unavailable.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column: Status Update & Payment Info -->
    <div>
        <!-- Admin Action: Update Status -->
        <div class="admin-table-container mb-4" style="border: 2px solid var(--admin-primary);">
            <h3 style="margin-bottom: 15px; color: var(--admin-primary);"><i class="fa-solid fa-sliders"></i> Update Order Status</h3>
            
            <form action="<?= SITE_URL ?>/admin/actions/order_action.php" method="POST">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                
                <div class="form-group mb-3">
                    <label class="form-label" style="font-weight: 600;">Order Status</label>
                    <select name="order_status" class="admin-form-control">
                        <option value="placed" <?= $order['order_status'] === 'placed' ? 'selected' : '' ?>>Placed</option>
                        <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <small class="text-muted mt-1" style="display:block;">Note: Status transitions are strictly enforced.</small>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" style="font-weight: 600;">Payment Status</label>
                    <select name="payment_status" class="admin-form-control">
                        <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-save"></i> Save Changes</button>
            </form>
        </div>

        <!-- Payment Info -->
        <div class="admin-table-container">
            <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">Payment Information</h3>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Payment Method:</span>
                <span style="font-weight: 600; text-transform: uppercase;"><?= str_replace('_', ' ', $order['payment_method']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Payment Status:</span>
                <span class="badge <?= $payment_badges[$order['payment_status']] ?>"><?= ucfirst($order['payment_status']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Order Date:</span>
                <span style="font-weight: 500;"><?= date('F d, Y h:i A', strtotime($order['created_at'])) ?></span>
            </div>
        </div>
    </div>
    
</div>

<!-- Ordered Items Table -->
<div class="admin-table-container mt-4">
    <h3 style="margin-bottom: 15px;">Ordered Items</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th style="text-align: right;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <?php 
                            $img = filter_var($item['image'], FILTER_VALIDATE_URL) ? $item['image'] : SITE_URL . '/uploads/products/' . $item['image'];
                        ?>
                        <img src="<?= $img ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                        <span style="font-weight: 600; color: #111827;"><?= htmlspecialchars($item['name']) ?></span>
                    </div>
                </td>
                <td><?= formatPrice($item['price']) ?></td>
                <td>x<?= $item['quantity'] ?></td>
                <td style="text-align: right; font-weight: 600;"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; padding: 20px 15px; font-size: 1.1rem; color: #6b7280;">Subtotal:</td>
                <td style="text-align: right; padding: 20px 15px; font-size: 1.1rem; font-weight: 600;"><?= formatPrice($order['total_amount']) ?></td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right; padding: 10px 15px; font-size: 1.1rem; color: #6b7280;">Shipping:</td>
                <td style="text-align: right; padding: 10px 15px; font-size: 1.1rem; font-weight: 600; color: #10b981;">Free</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right; padding: 20px 15px; font-size: 1.3rem; font-weight: 700; color: #111827; border-top: 2px solid var(--admin-border);">Grand Total:</td>
                <td style="text-align: right; padding: 20px 15px; font-size: 1.3rem; font-weight: 700; color: var(--admin-primary); border-top: 2px solid var(--admin-border);"><?= formatPrice($order['total_amount']) ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
