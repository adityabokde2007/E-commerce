<?php
// checkout.php
require_once 'config/db.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please login to proceed to checkout.');
    redirect(SITE_URL . '/login.php');
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Cart Items & Ensure Not Empty
$stmt_cart = $pdo->prepare("SELECT c.quantity, p.id, p.name, p.price, p.discount_price, p.image 
                            FROM cart c JOIN products p ON c.product_id = p.id 
                            WHERE c.user_id = ?");
$stmt_cart->execute([$user_id]);
$cart_items = $stmt_cart->fetchAll();

if (empty($cart_items)) {
    redirect(SITE_URL . '/cart.php');
}

// Calculate Total
$total_amount = 0;
foreach ($cart_items as $item) {
    $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
    $total_amount += $price * $item['quantity'];
}

// 2. Fetch User's Saved Addresses
$stmt_addresses = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
$stmt_addresses->execute([$user_id]);
$addresses = $stmt_addresses->fetchAll();
?>

<style>
/* Checkout Layout Styles */
.checkout-layout { display: flex; gap: 40px; margin-top: 30px; }
.checkout-main { flex: 1.5; }
.checkout-sidebar { flex: 1; }

.checkout-box { background: var(--bg-white); border-radius: var(--border-radius); padding: 30px; margin-bottom: 30px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
.checkout-box h3 { margin-bottom: 20px; font-size: 1.4rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }

/* Address Cards */
.address-card { border: 1px solid var(--border-color); padding: 15px; border-radius: var(--border-radius-sm); margin-bottom: 15px; cursor: pointer; transition: all var(--transition-fast); display: flex; align-items: flex-start; gap: 15px; }
.address-card:hover { border-color: var(--primary); background: var(--bg-light); }
.address-card.selected { border-color: var(--primary); background: rgba(255, 107, 53, 0.05); }
.address-card input[type="radio"] { margin-top: 5px; accent-color: var(--primary); transform: scale(1.2); }
.address-details p { margin-bottom: 5px; font-size: 0.95rem; }

/* New Address Form */
#newAddressForm { display: none; margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-color); animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* Order Summary Box */
.summary-item { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); }
.summary-item img { width: 60px; height: 60px; object-fit: cover; border-radius: var(--border-radius-sm); }
.summary-item-details { flex: 1; }
.summary-item-details h5 { font-size: 0.95rem; margin-bottom: 5px; }

/* Dummy Card Form */
.dummy-card-form { display: none; background: #f8f9fa; padding: 20px; border-radius: var(--border-radius); margin-top: 15px; border: 1px solid #e2e8f0; }
.dummy-card-form input { border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; width: 100%; margin-bottom: 10px; }

/* Loading Overlay */
.checkout-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 9999; display: none; flex-direction: column; justify-content: center; align-items: center; }
.spinner { width: 60px; height: 60px; border: 5px solid var(--border-color); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 992px) { .checkout-layout { flex-direction: column; } }
</style>

<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/cart.php">Cart</a></li>
            <li><a href="#" class="text-primary">Checkout</a></li>
        </ul>
    </div>
</div>

<div class="container mb-5">
    
    <form id="checkoutForm" action="<?= SITE_URL ?>/actions/order/place_order.php" method="POST">
        <div class="checkout-layout">
            
            <!-- Left Column: Address & Payment -->
            <div class="checkout-main">
                
                <!-- Shipping Address Section -->
                <div class="checkout-box">
                    <h3>1. Shipping Address</h3>
                    
                    <?php if (count($addresses) > 0): ?>
                        <?php foreach ($addresses as $index => $address): ?>
                        <label class="address-card <?= $index === 0 ? 'selected' : '' ?>">
                            <input type="radio" name="address_id" value="<?= $address['id'] ?>" <?= $index === 0 ? 'checked' : '' ?> onchange="toggleNewAddressForm()">
                            <div class="address-details">
                                <p><strong><?= htmlspecialchars($address['full_name']) ?></strong> <span class="text-muted ml-2"><?= htmlspecialchars($address['phone']) ?></span></p>
                                <p><?= htmlspecialchars($address['address_line']) ?></p>
                                <p><?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?> - <?= htmlspecialchars($address['pincode']) ?></p>
                            </div>
                        </label>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <label class="address-card <?= count($addresses) === 0 ? 'selected' : '' ?>" style="align-items: center;">
                        <input type="radio" name="address_id" value="new" <?= count($addresses) === 0 ? 'checked' : '' ?> onchange="toggleNewAddressForm()">
                        <div class="address-details" style="font-weight: 600; color: var(--primary);">
                            <i class="fa-solid fa-plus-circle"></i> Add New Address
                        </div>
                    </label>

                    <!-- Hidden New Address Form -->
                    <div id="newAddressForm" style="<?= count($addresses) === 0 ? 'display:block;' : 'display:none;' ?>">
                        <div class="grid grid-2" style="gap:15px;">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control address-field">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control address-field">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Street Address</label>
                            <input type="text" name="address" class="form-control address-field">
                        </div>
                        <div class="grid grid-3" style="gap:15px;">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control address-field">
                            </div>
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control address-field">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Pincode / ZIP</label>
                                <input type="text" name="pincode" class="form-control address-field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="checkout-box">
                    <h3>2. Payment Method</h3>
                    
                    <label class="address-card selected" style="align-items: center;">
                        <input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentForm()">
                        <div class="address-details" style="font-weight: 600;">
                            <i class="fa-solid fa-money-bill-wave text-success"></i> Cash on Delivery
                        </div>
                    </label>

                    <label class="address-card" style="align-items: center;">
                        <input type="radio" name="payment_method" value="online" onchange="togglePaymentForm()">
                        <div class="address-details" style="font-weight: 600;">
                            <i class="fa-regular fa-credit-card text-primary"></i> Pay Online (Debit/Credit Card)
                        </div>
                    </label>

                    <!-- Dummy Online Payment Form -->
                    <div id="dummyCardForm" class="dummy-card-form">
                        <div class="alert alert-info mb-3 text-sm">
                            <i class="fa-solid fa-info-circle"></i> This is a dummy project. Feel free to enter any fake card details. Payment will automatically succeed!
                        </div>
                        <input type="text" placeholder="Card Number (e.g. 4111 1111 1111 1111)" class="card-field">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" placeholder="MM/YY" style="flex:1" class="card-field">
                            <input type="text" placeholder="CVC" style="flex:1" class="card-field">
                        </div>
                        <input type="text" placeholder="Name on Card" class="card-field">
                    </div>
                </div>
                
            </div>

            <!-- Right Column: Order Summary -->
            <div class="checkout-sidebar">
                <div class="checkout-box" style="position: sticky; top: 100px;">
                    <h3>Order Summary</h3>
                    
                    <div style="max-height: 350px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px;">
                        <?php foreach ($cart_items as $item): 
                            $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
                            $image = ASSETS_URL . 'images/products/' . ($item['image'] ?? 'placeholder.jpg');
                        ?>
                            <div class="summary-item">
                                <img src="<?= $image ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://placehold.co/60x60'">
                                <div class="summary-item-details">
                                    <h5><?= htmlspecialchars($item['name']) ?></h5>
                                    <div class="text-muted" style="font-size: 0.85rem;">Qty: <?= $item['quantity'] ?> &times; <?= formatPrice($price) ?></div>
                                </div>
                                <div style="font-weight: 600;">
                                    <?= formatPrice($price * $item['quantity']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1.05rem;">
                        <span class="text-muted">Subtotal</span>
                        <span><?= formatPrice($total_amount) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.05rem; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                        <span class="text-muted">Shipping</span>
                        <span class="text-success font-weight-bold">FREE</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 1.3rem; font-weight: 700;">
                        <span>Total</span>
                        <span class="text-primary"><?= formatPrice($total_amount) ?></span>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block p-3" style="font-size: 1.2rem;">
                        <i class="fa-solid fa-lock"></i> Place Order
                    </button>
                    <p class="text-center text-muted mt-3" style="font-size: 0.85rem;">
                        Your personal data will be used to process your order, support your experience throughout this website, and for other purposes.
                    </p>
                </div>
            </div>
            
        </div>
    </form>
</div>

<!-- Full Screen Processing Overlay -->
<div class="checkout-overlay" id="checkoutOverlay">
    <div class="spinner"></div>
    <h2 style="color: var(--primary);">Processing Payment...</h2>
    <p class="text-muted">Please do not close or refresh this window.</p>
</div>

<script>
// Logic to handle toggling Address input fields
function toggleNewAddressForm() {
    const radio = document.querySelector('input[name="address_id"]:checked');
    const form = document.getElementById('newAddressForm');
    const cards = document.querySelectorAll('.address-card:not([style*="display: none"])');
    const addressFields = document.querySelectorAll('.address-field');
    
    // Update active styles
    cards.forEach(c => {
        const input = c.querySelector('input[name="address_id"]');
        if(input && input.checked) c.classList.add('selected');
        else c.classList.remove('selected');
    });
    
    // Require fields only if "new" is selected
    if (radio && radio.value === 'new') {
        form.style.display = 'block';
        addressFields.forEach(f => f.setAttribute('required', 'true'));
    } else {
        form.style.display = 'none';
        addressFields.forEach(f => f.removeAttribute('required'));
    }
}

// Logic to handle Dummy Payment form
function togglePaymentForm() {
    const radio = document.querySelector('input[name="payment_method"]:checked');
    const form = document.getElementById('dummyCardForm');
    const cards = document.querySelectorAll('input[name="payment_method"]');
    const cardFields = document.querySelectorAll('.card-field');
    
    // Update active styles for payment cards
    cards.forEach(c => {
        if(c.checked) c.closest('.address-card').classList.add('selected');
        else c.closest('.address-card').classList.remove('selected');
    });
    
    if (radio && radio.value === 'online') {
        form.style.display = 'block';
        cardFields.forEach(f => f.setAttribute('required', 'true'));
    } else {
        form.style.display = 'none';
        cardFields.forEach(f => f.removeAttribute('required'));
    }
}

// Handle Form Submission for Dummy Loader
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    
    // If Online Payment is chosen, simulate network delay
    if (paymentMethod === 'online') {
        e.preventDefault(); // Stop instant submission
        
        // Ensure form is actually valid before showing overlay
        if (this.checkValidity()) {
            document.getElementById('checkoutOverlay').style.display = 'flex';
            
            // Wait 2 seconds, then really submit
            setTimeout(() => {
                this.submit();
            }, 2000);
        } else {
            // Trigger native HTML5 validation UI
            this.reportValidity();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
