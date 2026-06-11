<?php
// order-detail.php
require_once 'config/db.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$order_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) { redirect(SITE_URL . '/orders.php'); }

// Fetch order with address
$stmt = $pdo->prepare("SELECT o.*, a.full_name as addr_name, a.phone as addr_phone, a.address_line as addr_address, a.city, a.state, a.pincode 
                       FROM orders o 
                       LEFT JOIN addresses a ON o.address_id = a.id 
                       WHERE o.id = ? AND o.user_id = ? LIMIT 1");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="container mt-5 mb-5 text-center"><h2>Order not found.</h2><a href="orders.php" class="btn btn-primary mt-3">My Orders</a></div>';
    require_once 'includes/footer.php';
    exit;
}

// Fetch order items
$stmt_items = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// Status config & timeline
$statuses = ['placed', 'confirmed', 'shipped', 'delivered'];
$status_labels = ['placed' => 'Order Placed', 'confirmed' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$status_icons  = ['placed' => 'fa-box', 'confirmed' => 'fa-circle-check', 'shipped' => 'fa-truck-fast', 'delivered' => 'fa-house-circle-check'];

$is_cancelled = $order['order_status'] === 'cancelled';
$current_index = array_search($order['order_status'], $statuses);
if ($current_index === false) $current_index = -1;

$order_date = new DateTime($order['created_at']);
$delivery_date = clone $order_date;
$delivery_date->modify('+7 days');
?>

<style>
/* Status Timeline */
.timeline { display: flex; justify-content: space-between; align-items: flex-start; padding: 30px 0; position: relative; }
.timeline::before { content: ''; position: absolute; top: 45px; left: 50px; right: 50px; height: 4px; background: #e2e8f0; z-index: 0; }
.timeline-step { text-align: center; position: relative; z-index: 1; flex: 1; }
.timeline-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 1.2rem; border: 3px solid #e2e8f0; background: white; color: #cbd5e1; transition: all 0.3s ease; }
.timeline-step.completed .timeline-icon { background: #22c55e; border-color: #22c55e; color: white; }
.timeline-step.active .timeline-icon { background: var(--primary); border-color: var(--primary); color: white; animation: pulse 2s infinite; }
.timeline-step.cancelled .timeline-icon { background: #ef4444; border-color: #ef4444; color: white; }
.timeline-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
.timeline-step.completed .timeline-label, .timeline-step.active .timeline-label { color: var(--secondary); }

@keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(255,107,53,0.4); } 50% { box-shadow: 0 0 0 10px rgba(255,107,53,0); } }

/* Detail Boxes */
.detail-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 20px; }
.detail-box { background: var(--bg-white); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 25px; }
.detail-box h4 { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); font-size: 1.1rem; }

.item-row { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
.item-row:last-child { border-bottom: none; }
.item-row img { width: 60px; height: 60px; object-fit: cover; border-radius: var(--border-radius-sm); }
.item-row-info { flex: 1; }
.item-row-info h5 { font-size: 0.95rem; margin-bottom: 3px; }

.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
.info-row:last-child { border-bottom: none; }
.info-row.total { font-weight: 700; font-size: 1.15rem; border-top: 2px solid var(--border-color); padding-top: 12px; margin-top: 5px; }

.cancel-section { margin-top: 30px; padding: 20px; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--border-radius); }

@media (max-width: 768px) {
    .detail-grid { grid-template-columns: 1fr; }
    .timeline { flex-direction: column; align-items: flex-start; gap: 15px; padding-left: 30px; }
    .timeline::before { top: 25px; bottom: 25px; left: 54px; width: 4px; height: auto; right: auto; }
    .timeline-step { display: flex; align-items: center; gap: 15px; text-align: left; }
}
</style>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/orders.php">My Orders</a></li>
            <li><a href="#" class="text-primary">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></a></li>
        </ul>
    </div>
</div>

<div class="container mt-4 mb-5">

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <h1 style="margin-bottom: 0;">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
        <span class="text-muted">Placed on <?= $order_date->format('F d, Y \a\t h:i A') ?></span>
    </div>

    <!-- Status Timeline / Stepper -->
    <div class="detail-box mb-4">
        <?php if ($is_cancelled): ?>
            <div class="text-center" style="padding: 20px;">
                <i class="fa-solid fa-circle-xmark" style="font-size: 3rem; color: #ef4444; margin-bottom: 10px;"></i>
                <h3 style="color: #ef4444;">Order Cancelled</h3>
                <p class="text-muted">This order has been cancelled.</p>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($statuses as $i => $status): 
                    $step_class = '';
                    if ($i < $current_index) $step_class = 'completed';
                    elseif ($i === $current_index) $step_class = 'active';
                ?>
                    <div class="timeline-step <?= $step_class ?>">
                        <div class="timeline-icon"><i class="fa-solid <?= $status_icons[$status] ?>"></i></div>
                        <div class="timeline-label"><?= $status_labels[$status] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($order['order_status'] !== 'delivered'): ?>
                <p class="text-center text-muted mt-2" style="font-size: 0.9rem;">
                    <i class="fa-regular fa-calendar"></i> Estimated Delivery: <strong class="text-primary"><?= $delivery_date->format('M d, Y') ?></strong>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="detail-grid">

        <!-- Left: Order Items -->
        <div class="detail-box">
            <h4><i class="fa-solid fa-box-open text-primary"></i> Order Items</h4>
            <?php foreach ($items as $item): 
                $img = ASSETS_URL . 'images/products/' . ($item['image'] ?? 'placeholder.jpg');
            ?>
                <div class="item-row">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://placehold.co/60x60/F8F9FA/1A1A2E?text=Item'">
                    <div class="item-row-info">
                        <h5><a href="<?= SITE_URL ?>/product.php?id=<?= $item['product_id'] ?>" class="text-main"><?= htmlspecialchars($item['name']) ?></a></h5>
                        <span class="text-muted" style="font-size: 0.85rem;">Qty: <?= $item['quantity'] ?> &times; <?= formatPrice($item['price']) ?></span>
                    </div>
                    <div style="font-weight: 600;"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
            <?php endforeach; ?>

            <div style="margin-top: 15px; padding-top: 10px;">
                <div class="info-row"><span class="text-muted">Subtotal</span><span><?= formatPrice($order['total_amount']) ?></span></div>
                <div class="info-row"><span class="text-muted">Shipping</span><span class="text-success">FREE</span></div>
                <div class="info-row total"><span>Total</span><span class="text-primary"><?= formatPrice($order['total_amount']) ?></span></div>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Shipping Address -->
            <div class="detail-box mb-4">
                <h4><i class="fa-solid fa-location-dot text-primary"></i> Shipping Address</h4>
                <?php if (!empty($order['addr_name'])): ?>
                    <p style="margin-bottom: 5px;"><strong><?= htmlspecialchars($order['addr_name']) ?></strong></p>
                    <p class="text-muted" style="margin-bottom: 5px;"><?= htmlspecialchars($order['addr_phone']) ?></p>
                    <p class="text-muted" style="margin-bottom: 0;"><?= htmlspecialchars($order['addr_address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?></p>
                <?php else: ?>
                    <p class="text-muted">Address information not available.</p>
                <?php endif; ?>
            </div>

            <!-- Payment Info -->
            <div class="detail-box mb-4">
                <h4><i class="fa-solid fa-credit-card text-primary"></i> Payment Info</h4>
                <div class="info-row"><span class="text-muted">Method</span><span><?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?></span></div>
                <div class="info-row"><span class="text-muted">Status</span>
                    <span style="color: <?= $order['payment_status'] === 'paid' ? '#22c55e' : '#f59e0b' ?>; font-weight: 600;">
                        <?= ucfirst($order['payment_status']) ?>
                    </span>
                </div>
                <div class="info-row"><span class="text-muted">Amount</span><span style="font-weight: 600;"><?= formatPrice($order['total_amount']) ?></span></div>
            </div>

            <!-- Cancel Order (only if status is pending/placed) -->
            <?php if ($order['order_status'] === 'placed'): ?>
                <div class="cancel-section">
                    <h4 style="color: #ef4444; margin-bottom: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> Cancel Order</h4>
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">You can cancel this order before it's confirmed. This action cannot be undone.</p>
                    <form action="<?= SITE_URL ?>/actions/order/cancel_order.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Cancel This Order</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
