<?php
// orders.php
require_once 'config/db.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Please login to view your orders.');
    redirect(SITE_URL . '/login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch all orders with item count
$stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as items_count 
                       FROM orders o 
                       LEFT JOIN order_items oi ON o.id = oi.order_id 
                       WHERE o.user_id = ? 
                       GROUP BY o.id 
                       ORDER BY o.created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Status badge config
$status_config = [
    'placed'     => ['color' => '#3b82f6', 'bg' => '#eff6ff', 'label' => 'Placed'],
    'confirmed'  => ['color' => '#f59e0b', 'bg' => '#fffbeb', 'label' => 'Confirmed'],
    'shipped'    => ['color' => '#8b5cf6', 'bg' => '#f5f3ff', 'label' => 'Shipped'],
    'delivered'  => ['color' => '#22c55e', 'bg' => '#f0fdf4', 'label' => 'Delivered'],
    'cancelled'  => ['color' => '#ef4444', 'bg' => '#fef2f2', 'label' => 'Cancelled'],
];
?>

<style>
.orders-table { width: 100%; border-collapse: collapse; }
.orders-table th { padding: 15px; text-align: left; background: var(--bg-light); border-bottom: 2px solid var(--border-color); font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.orders-table td { padding: 18px 15px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
.orders-table tbody tr { cursor: pointer; transition: background var(--transition-fast); }
.orders-table tbody tr:hover { background: var(--bg-light); }

.status-badge { display: inline-block; padding: 5px 14px; border-radius: var(--border-radius-pill); font-size: 0.8rem; font-weight: 600; }

/* Mobile Card Layout */
.order-card-mobile { display: none; }

@media (max-width: 768px) {
    .orders-table { display: none; }
    .order-card-mobile { display: block; }
    .order-card { background: var(--bg-white); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 20px; margin-bottom: 15px; cursor: pointer; transition: box-shadow var(--transition-fast); }
    .order-card:hover { box-shadow: var(--shadow-md); }
    .order-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .order-card-body { display: flex; justify-content: space-between; color: var(--text-muted); font-size: 0.9rem; }
}
</style>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/profile.php">My Account</a></li>
            <li><a href="#" class="text-primary">My Orders</a></li>
        </ul>
    </div>
</div>

<div class="container mt-4 mb-5">
    <h1 class="mb-4">My Orders</h1>

    <?php if (count($orders) > 0): ?>

        <!-- Desktop Table -->
        <div style="background: var(--bg-white); border-radius: var(--border-radius); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order):
                        $cfg = $status_config[$order['order_status']] ?? $status_config['pending'];
                    ?>
                        <tr onclick="window.location='<?= SITE_URL ?>/order-detail.php?id=<?= $order['id'] ?>'">
                            <td><strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                            <td><?= $order['items_count'] ?> item(s)</td>
                            <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                            <td><?= $order['payment_method'] === 'cod' ? 'COD' : 'Online' ?></td>
                            <td>
                                <span class="status-badge" style="color: <?= $cfg['color'] ?>; background: <?= $cfg['bg'] ?>;">
                                    <?= $cfg['label'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="order-card-mobile">
            <?php foreach ($orders as $order):
                $cfg = $status_config[$order['order_status']] ?? $status_config['pending'];
            ?>
                <div class="order-card" onclick="window.location='<?= SITE_URL ?>/order-detail.php?id=<?= $order['id'] ?>'">
                    <div class="order-card-header">
                        <strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                        <span class="status-badge" style="color: <?= $cfg['color'] ?>; background: <?= $cfg['bg'] ?>;"><?= $cfg['label'] ?></span>
                    </div>
                    <div class="order-card-body">
                        <span><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                        <span><?= $order['items_count'] ?> item(s)</span>
                        <strong><?= formatPrice($order['total_amount']) ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="text-center" style="padding: 60px 20px; background: var(--bg-white); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-bag-shopping" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
            <h2 class="mb-3">No orders yet</h2>
            <p class="text-muted mb-4">You haven't placed any orders. Start shopping and your orders will appear here!</p>
            <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary" style="padding: 12px 30px;">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
