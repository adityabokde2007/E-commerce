<?php
// actions/order/place_order.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Ensure User is Logged In
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start Transaction to guarantee data integrity
        $pdo->beginTransaction();

        $user_id = $_SESSION['user_id'];
        
        // 1. Fetch and Validate Cart
        // Using FOR UPDATE to lock the cart and product rows slightly so stock doesn't change concurrently
        $stmt_cart = $pdo->prepare("SELECT c.quantity, p.id as product_id, p.price, p.discount_price, p.stock 
                                    FROM cart c JOIN products p ON c.product_id = p.id 
                                    WHERE c.user_id = ? FOR UPDATE");
        $stmt_cart->execute([$user_id]);
        $cart_items = $stmt_cart->fetchAll();

        if (empty($cart_items)) {
            setFlashMessage('error', 'Your cart is empty. Please add items before checking out.');
            $pdo->rollBack();
            redirect(SITE_URL . '/cart.php');
        }

        // 2. Calculate Totals & Stock Verification
        $total_amount = 0;
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock']) {
                setFlashMessage('error', 'One or more items in your cart exceed available stock. Please review your cart.');
                $pdo->rollBack();
                redirect(SITE_URL . '/cart.php');
            }
            $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
            $total_amount += $price * $item['quantity'];
        }

        // 3. Process Shipping Address
        $address_id = $_POST['address_id'] ?? '';
        $shipping_address_id = 0;

        if ($address_id === 'new') {
            $name = sanitize($_POST['name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $state = sanitize($_POST['state'] ?? '');
            $pincode = sanitize($_POST['pincode'] ?? '');

            if(empty($name) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
                setFlashMessage('error', 'All new address fields are required.');
                $pdo->rollBack();
                redirect(SITE_URL . '/checkout.php');
            }

            // Insert new address directly to DB
            $stmt_add = $pdo->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line, city, state, pincode) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_add->execute([$user_id, $name, $phone, $address, $city, $state, $pincode]);
            $shipping_address_id = $pdo->lastInsertId();
        } else {
            $shipping_address_id = (int)$address_id;
            
            // Validate that the user actually owns this address
            $stmt_verify = $pdo->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt_verify->execute([$shipping_address_id, $user_id]);
            if ($stmt_verify->rowCount() === 0) {
                setFlashMessage('error', 'Invalid shipping address selected.');
                $pdo->rollBack();
                redirect(SITE_URL . '/checkout.php');
            }
        }

        // 4. Payment Method & Status
        $payment_method = $_POST['payment_method'] ?? 'cod';
        // If it's the online dummy payment, we assume it's paid instantly upon submission
        $payment_status = ($payment_method === 'online') ? 'paid' : 'pending';

        // 5. Create Order Record
        $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, total_amount, address_id, payment_method, payment_status, order_status) VALUES (?, ?, ?, ?, ?, 'placed')");
        $stmt_order->execute([$user_id, $total_amount, $shipping_address_id, $payment_method, $payment_status]);
        $order_id = $pdo->lastInsertId();

        // 6. Create Order Items & Reduce Stock simultaneously
        $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($cart_items as $item) {
            $price = $item['discount_price'] > 0 ? $item['discount_price'] : $item['price'];
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $price]);
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        // 7. Clear the User's Cart
        $stmt_clear = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt_clear->execute([$user_id]);

        // Commit Transaction - Everything succeeded!
        $pdo->commit();
        
        // Redirect to success page
        redirect(SITE_URL . '/order-confirmation.php?id=' . $order_id);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlashMessage('error', 'An error occurred while processing your order. Please try again.');
        redirect(SITE_URL . '/checkout.php');
    }
} else {
    redirect(SITE_URL . '/cart.php');
}
