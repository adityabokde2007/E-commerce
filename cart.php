<?php
// cart.php
require_once 'config/db.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    echo '<div class="container mt-5 mb-5 text-center">
            <i class="fa-solid fa-lock" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
            <h2>Please Log In</h2>
            <p class="text-muted mb-4">You need to be logged in to view your shopping cart.</p>
            <a href="login.php" class="btn btn-primary">Log In</a>
          </div>';
    require_once 'includes/footer.php';
    exit;
}

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.discount_price, p.image, p.stock 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$total_price = 0;
?>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="#" class="text-primary">Shopping Cart</a></li>
        </ul>
    </div>
</div>

<div class="container mt-4 mb-5">
    <h1 class="mb-4">Your Shopping Cart</h1>

    <?php if (count($cart_items) > 0): ?>
        <div class="cart-table-wrapper" style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--bg-light); border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 15px; text-align: left;">Product</th>
                        <th style="padding: 15px; text-align: center;">Price</th>
                        <th style="padding: 15px; text-align: center;">Quantity</th>
                        <th style="padding: 15px; text-align: right;">Subtotal</th>
                        <th style="padding: 15px; text-align: center;">Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): 
                        $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
                        $subtotal = $price * $item['quantity'];
                        $total_price += $subtotal;
                        $image = ASSETS_URL . 'images/products/' . ($item['image'] ?? 'placeholder.jpg');
                    ?>
                    <tr id="cart-row-<?= $item['cart_id'] ?>" style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <a href="product.php?id=<?= $item['product_id'] ?>">
                                    <img src="<?= $image ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--border-radius-sm);" onerror="this.src='https://placehold.co/100x100/F8F9FA/1A1A2E?text=Product'">
                                </a>
                                <div>
                                    <a href="product.php?id=<?= $item['product_id'] ?>" class="text-main" style="font-weight: 600; font-size: 1.1rem; text-decoration: none;">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 15px; text-align: center; font-weight: 500;">
                            <?= formatPrice($price) ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <div class="qty-selector" style="display: inline-flex; align-items: center; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); overflow: hidden;">
                                <button type="button" class="qty-btn" onclick="updateCart(<?= $item['cart_id'] ?>, -1, <?= $item['stock'] ?>)" style="width: 32px; height: 32px; background: var(--bg-light); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem;">-</button>
                                <input type="number" id="qty-<?= $item['cart_id'] ?>" value="<?= $item['quantity'] ?>" readonly style="width: 42px; height: 32px; border: none; border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color); text-align: center; font-weight: 600; padding: 0; box-sizing: border-box; -moz-appearance: textfield;">
                                <button type="button" class="qty-btn" onclick="updateCart(<?= $item['cart_id'] ?>, 1, <?= $item['stock'] ?>)" style="width: 32px; height: 32px; background: var(--bg-light); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem;">+</button>
                            </div>
                        </td>
                        <td style="padding: 15px; text-align: right; font-weight: 600; color: var(--primary);" id="subtotal-<?= $item['cart_id'] ?>">
                            <?= formatPrice($subtotal) ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeFromCart(<?= $item['cart_id'] ?>)" style="padding: 8px 12px;">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-footer mt-4" style="display: flex; justify-content: flex-end;">
            <div class="cart-summary" style="background: var(--bg-light); padding: 30px; border-radius: var(--border-radius); width: 100%; max-width: 400px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                    <strong>Total:</strong>
                    <strong id="cartTotal" class="text-primary"><?= formatPrice($total_price) ?></strong>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="checkout.php" class="btn btn-primary btn-block p-3" style="font-weight: 600; font-size: 1.1rem; text-align: center; display: block;">Proceed to Checkout</a>
                    <a href="shop.php" class="btn btn-outline btn-block p-3" style="text-align: center; display: block;">Continue Shopping</a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty Cart State -->
        <div class="text-center" style="padding: 60px 20px; background: var(--bg-white); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-cart-shopping" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
            <h2 class="mb-3">Your cart is currently empty.</h2>
            <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
            <a href="shop.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">Start Shopping</a>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>