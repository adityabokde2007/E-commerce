<?php
// order-confirmation.php
require_once 'config/db.php';
require_once 'includes/header.php';

// Must be logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

// Get Order ID
$order_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id === 0) {
    redirect(SITE_URL . '/index.php');
}

// Fetch Order - ensure it belongs to the logged-in user
$stmt = $pdo->prepare("SELECT o.*, a.full_name as addr_name, a.phone as addr_phone, a.address_line as addr_address, a.city, a.state, a.pincode 
                       FROM orders o 
                       LEFT JOIN addresses a ON o.address_id = a.id 
                       WHERE o.id = ? AND o.user_id = ? LIMIT 1");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="container mt-5 mb-5 text-center"><h2>Order not found.</h2><a href="index.php" class="btn btn-primary mt-3">Go Home</a></div>';
    require_once 'includes/footer.php';
    exit;
}

// Fetch Order Items
$stmt_items = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll();

// Estimated Delivery Date (7 days from order)
$order_date = new DateTime($order['created_at']);
$delivery_date = clone $order_date;
$delivery_date->modify('+7 days');
?>

<style>
/* Success Animation */
.success-container { text-align: center; padding: 40px 20px; }

.checkmark-circle { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; animation: scaleIn 0.5s ease forwards; }
.checkmark-circle i { font-size: 3rem; color: white; animation: fadeInUp 0.5s 0.3s ease forwards; opacity: 0; }

@keyframes scaleIn {
    0% { transform: scale(0); background: #e2e8f0; }
    60% { transform: scale(1.15); background: #28a745; }
    100% { transform: scale(1); background: #28a745; }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.order-confirm-card { background: var(--bg-white); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); padding: 40px; max-width: 800px; margin: 0 auto; }
.order-meta { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; margin: 25px 0 30px; padding: 20px; background: var(--bg-light); border-radius: var(--border-radius); }
.order-meta-item { text-align: center; }
.order-meta-item span { display: block; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 5px; }
.order-meta-item strong { font-size: 1.1rem; color: var(--secondary); }

.confirm-items { text-align: left; margin-bottom: 25px; }
.confirm-item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
.confirm-item img { width: 55px; height: 55px; object-fit: cover; border-radius: var(--border-radius-sm); }
.confirm-item-info { flex: 1; }
.confirm-item-info h5 { margin-bottom: 3px; font-size: 0.95rem; }

.confirm-summary { text-align: left; background: var(--bg-light); padding: 20px; border-radius: var(--border-radius); margin-bottom: 30px; }
.confirm-summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
.confirm-summary-row.total { font-size: 1.3rem; font-weight: 700; border-top: 2px solid var(--border-color); padding-top: 10px; margin-top: 5px; }

.confirm-address { text-align: left; background: var(--bg-light); padding: 20px; border-radius: var(--border-radius); margin-bottom: 30px; }

.confirm-actions { display: flex; gap: 15px; justify-content: center; }

@media (max-width: 576px) {
    .order-meta { gap: 20px; }
    .confirm-actions { flex-direction: column; }
}
</style>

<div class="container mt-4 mb-5">
    <div class="order-confirm-card">
        
        <!-- Success Animation -->
        <div class="success-container">
            <div class="checkmark-circle">
                <i class="fa-solid fa-check"></i>
            </div>
            <h1 style="color: #28a745; font-size: 2rem; margin-bottom: 10px;">Order Placed Successfully!</h1>
            <p class="text-muted" style="font-size: 1.1rem;">Thank you for your purchase. Your order has been confirmed.</p>
        </div>

        <!-- Order Meta Info -->
        <div class="order-meta">
            <div class="order-meta-item">
                <span>Order ID</span>
                <strong>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div class="order-meta-item">
                <span>Order Date</span>
                <strong><?= $order_date->format('M d, Y') ?></strong>
            </div>
            <div class="order-meta-item">
                <span>Est. Delivery</span>
                <strong class="text-primary"><?= $delivery_date->format('M d, Y') ?></strong>
            </div>
            <div class="order-meta-item">
                <span>Payment</span>
                <strong><?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?></strong>
            </div>
        </div>

        <!-- Order Items -->
        <div class="confirm-items">
            <h4 style="margin-bottom: 15px;">Items Ordered</h4>
            <?php foreach ($order_items as $item): 
                $img = ASSETS_URL . 'images/products/' . ($item['image'] ?? 'placeholder.jpg');
            ?>
                <div class="confirm-item">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://placehold.co/55x55/F8F9FA/1A1A2E?text=Item'">
                    <div class="confirm-item-info">
                        <h5><?= htmlspecialchars($item['name']) ?></h5>
                        <span class="text-muted" style="font-size: 0.85rem;">Qty: <?= $item['quantity'] ?></span>
                    </div>
                    <div style="font-weight: 600;"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Price Summary -->
        <div class="confirm-summary">
            <div class="confirm-summary-row">
                <span class="text-muted">Subtotal</span>
                <span><?= formatPrice($order['total_amount']) ?></span>
            </div>
            <div class="confirm-summary-row">
                <span class="text-muted">Shipping</span>
                <span class="text-success">FREE</span>
            </div>
            <div class="confirm-summary-row total">
                <span>Total Paid</span>
                <span class="text-primary"><?= formatPrice($order['total_amount']) ?></span>
            </div>
        </div>

        <!-- Shipping Address -->
        <?php if (!empty($order['addr_name'])): ?>
        <div class="confirm-address">
            <h4 style="margin-bottom: 10px;"><i class="fa-solid fa-location-dot text-primary"></i> Shipping To</h4>
            <p style="margin-bottom: 5px;"><strong><?= htmlspecialchars($order['addr_name']) ?></strong> &middot; <?= htmlspecialchars($order['addr_phone']) ?></p>
            <p class="text-muted" style="margin-bottom: 0;"><?= htmlspecialchars($order['addr_address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="confirm-actions">
            <a href="<?= SITE_URL ?>/order-detail.php?id=<?= $order['id'] ?>" class="btn btn-primary" style="padding: 12px 30px;">
                <i class="fa-solid fa-truck"></i> Track Order
            </a>
            <a href="<?= SITE_URL ?>/shop.php" class="btn btn-outline" style="padding: 12px 30px;">
                <i class="fa-solid fa-bag-shopping"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
