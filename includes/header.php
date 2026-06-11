<?php
// includes/header.php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Get current page name for active navigation link
$current_page = basename($_SERVER['PHP_SELF']);

// Dummy count variables for now (will be replaced by actual DB queries later)
$cart_count = 0;
$wishlist_count = 0;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Fetch Cart Count
    $stmt_cart = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt_cart->execute([$user_id]);
    $cart_count = (int)$stmt_cart->fetchColumn();
    
    // Fetch Wishlist Count
    $stmt_wish = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt_wish->execute([$user_id]);
    $wishlist_count = (int)$stmt_wish->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> | Your One-Stop Shop</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css?v=2.0">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/responsive.css?v=2.0">
</head>
<body>

    <!-- Top Announcement Bar -->
    <div class="top-bar">
        <div class="container">
            <p>Free shipping on orders over ₹1000! Shop Now.</p>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-container">
            <!-- Logo -->
            <a href="<?= SITE_URL ?>/index.php" class="logo">
                <img src="<?= ASSETS_URL ?>images/logo.png" alt="<?= SITE_NAME ?>">
            </a>

            <!-- Search Bar -->
            <div class="search-bar">
                <form action="<?= SITE_URL ?>/search.php" method="GET">
                    <input type="text" name="q" placeholder="Search for products..." required>
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <!-- Header Icons -->
            <div class="header-icons">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/wishlist.php" class="icon-btn" title="Wishlist">
                        <i class="fa-regular fa-heart"></i>
                        <span class="badge"><?= $wishlist_count ?></span>
                    </a>
                <?php endif; ?>

                <a href="<?= SITE_URL ?>/cart.php" class="icon-btn" title="Cart">

                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="badge"><?= $cart_count ?></span>
                </a>

                <div class="user-dropdown">
                    <a href="#" class="icon-btn user-btn">
                        <i class="fa-regular fa-user"></i>
                    </a>
                    <div class="dropdown-menu">
                        <?php if (isLoggedIn()): ?>
                            <div class="dropdown-header">
                                <strong>Hello, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong>
                            </div>
                            <?php if (isAdmin()): ?>
                                <a href="<?= SITE_URL ?>/admin/index.php"><i class="fa-solid fa-gauge"></i> Admin Panel</a>
                            <?php endif; ?>
                            <a href="<?= SITE_URL ?>/profile.php"><i class="fa-regular fa-circle-user"></i> My Profile</a>
                            <a href="<?= SITE_URL ?>/orders.php"><i class="fa-solid fa-box"></i> My Orders</a>
                            <hr>
                            <a href="<?= SITE_URL ?>/actions/auth/logout.php" class="text-danger logout-link" data-logout-link><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>/login.php"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
                            <a href="<?= SITE_URL ?>/register.php"><i class="fa-solid fa-user-plus"></i> Register</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" id="menuToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="main-nav" id="mainNav">
        <div class="container">
            <ul class="nav-links">
                <li><a href="<?= SITE_URL ?>/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= SITE_URL ?>/shop.php" class="<?= $current_page == 'shop.php' ? 'active' : '' ?>">Shop</a></li>
                <!-- Category Dropdown Example -->
                <li class="has-dropdown">
                    <a href="#">Categories <i class="fa-solid fa-chevron-down"></i></a>
                    <ul class="sub-menu">
                        <li><a href="<?= SITE_URL ?>/shop.php?category=electronics">Electronics</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=fashion">Fashion</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=home-kitchen">Home & Kitchen</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=sports">Sports</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=beauty">Beauty</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php?category=books">Books</a></li>
                    </ul>
                </li>
                <li><a href="<?= SITE_URL ?>/about.php" class="<?= $current_page == 'about.php' ? 'active' : '' ?>">About Us</a></li>
                <li><a href="<?= SITE_URL ?>/contact.php" class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Flash messages are handled by toast notifications in the frontend -->
