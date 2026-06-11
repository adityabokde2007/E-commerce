<?php
// admin/index.php
require_once 'includes/admin_header.php';

// Fetch Statistics Data
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn();

// Fetch Recent 10 Orders
$stmt_recent = $pdo->query("SELECT o.id, o.total_amount, o.order_status, o.created_at, u.name as customer_name 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            ORDER BY o.created_at DESC LIMIT 10");
$recent_orders = $stmt_recent->fetchAll();

// Badge class helper
$badges = [
    'pending' => 'badge-pending',
    'confirmed' => 'badge-confirmed',
    'shipped' => 'badge-shipped',
    'delivered' => 'badge-delivered',
    'cancelled' => 'badge-cancelled'
];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; color: #111827;">Dashboard</h2>
</div>

<!-- Stat Cards -->
<div class="stat-cards-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <p>Total Revenue</p>
            <h3><?= formatPrice($total_revenue ?? 0) ?></h3>
        </div>
        <div class="stat-card-icon" style="background: #ecfdf5; color: #10b981;">
            <i class="fa-solid fa-dollar-sign"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-info">
            <p>Total Orders</p>
            <h3><?= number_format($total_orders) ?></h3>
        </div>
        <div class="stat-card-icon" style="background: #eff6ff; color: #3b82f6;">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-info">
            <p>Total Products</p>
            <h3><?= number_format($total_products) ?></h3>
        </div>
        <div class="stat-card-icon" style="background: #fffbeb; color: #f59e0b;">
            <i class="fa-solid fa-box-open"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-info">
            <p>Total Customers</p>
            <h3><?= number_format($total_users) ?></h3>
        </div>
        <div class="stat-card-icon" style="background: #f5f3ff; color: #8b5cf6;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="admin-table-container">
    <div class="admin-table-header">
        <h3 style="color: #111827;">Recent Orders</h3>
        <a href="orders.php" class="btn btn-outline btn-sm">View All Orders</a>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($recent_orders) > 0): ?>
                <?php foreach($recent_orders as $o): ?>
                <tr>
                    <td><strong>#<?= str_pad($o['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    <td style="font-weight: 600;"><?= formatPrice($o['total_amount']) ?></td>
                    <td>
                        <span class="badge <?= $badges[$o['order_status']] ?? 'badge-pending' ?>">
                            <?= ucfirst($o['order_status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="order-detail.php?id=<?= $o['id'] ?>" style="color: var(--admin-primary); text-decoration: none; font-weight: 500;">
                            <i class="fa-solid fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 30px;">No recent orders found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
