<?php
// admin/orders.php
require_once 'includes/admin_header.php';

$status_filter = $_GET['status'] ?? 'all';
$valid_tabs = ['all', 'placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];

if (!in_array($status_filter, $valid_tabs)) {
    $status_filter = 'all';
}

$where = "";
$params = [];
if ($status_filter !== 'all') {
    $where = "WHERE o.order_status = ?";
    $params = [$status_filter];
}

// Fetch orders with item sums
$query = "SELECT o.id, o.created_at, o.total_amount, o.payment_method, o.payment_status, o.order_status, u.name as customer_name,
          (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as items_count 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          $where 
          ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Badge Helpers
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
    <h2 style="margin: 0; color: #111827;">Manage Orders</h2>
</div>

<!-- Filter Tabs -->
<div style="margin-bottom: 20px; border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">
    <?php foreach($valid_tabs as $tab): ?>
        <a href="orders.php?status=<?= $tab ?>" 
           style="text-transform: capitalize; padding: 10px 20px; font-weight: 600; text-decoration: none; color: <?= $status_filter === $tab ? 'var(--admin-primary)' : '#6b7280' ?>; border-bottom: <?= $status_filter === $tab ? '2px solid var(--admin-primary)' : 'none' ?>;">
            <?= $tab ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total Amount</th>
                <th>Payment</th>
                <th>Quick Status Update</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($orders) > 0): ?>
                <?php foreach($orders as $o): ?>
                <tr>
                    <td><strong>#<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                    <td style="font-weight: 500; color: #111827;"><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    <td><span class="badge" style="background: #f3f4f6; color: #374151;"><?= $o['items_count'] ?></span></td>
                    <td style="font-weight: 600;"><?= formatPrice($o['total_amount']) ?></td>
                    <td>
                        <div style="margin-bottom: 5px; font-size: 0.85rem; color: #6b7280; text-transform: uppercase;">
                            <?= str_replace('_', ' ', $o['payment_method']) ?>
                        </div>
                        <span class="badge <?= $payment_badges[$o['payment_status']] ?? 'badge-pending' ?>">
                            <?= ucfirst($o['payment_status']) ?>
                        </span>
                    </td>
                    <td>
                        <!-- Inline Quick Update Form -->
                        <form action="<?= SITE_URL ?>/admin/actions/order_action.php" method="POST" style="display: flex; gap: 5px;">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <input type="hidden" name="payment_status" value="<?= $o['payment_status'] ?>">
                            <select name="order_status" class="admin-form-control" style="padding: 5px; font-size: 0.85rem; width: 120px;" onchange="this.form.submit()">
                                <option value="placed" <?= $o['order_status'] === 'placed' ? 'selected' : '' ?>>Placed</option>
                                <option value="confirmed" <?= $o['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="shipped" <?= $o['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $o['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $o['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <a href="order-detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-eye"></i> View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No orders found for this status.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
