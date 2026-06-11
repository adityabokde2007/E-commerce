<?php
// admin/includes/admin_header.php
require_once 'admin_auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?= SITE_NAME ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Common Style (for buttons, grids, toasts) -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css">
    <!-- Admin Specific CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header" style="text-align: center; height: 60px; display: flex; align-items: center; justify-content: center;">
            <img src="<?= ASSETS_URL ?>images/logo.png" alt="<?= SITE_NAME ?>" style="height: 50px;">
        </div>
        <nav class="admin-nav">
            <ul>
                <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>"><i class="fa-solid fa-box-open"></i> Products</a></li>
                <li><a href="categories.php" class="<?= $current_page == 'categories.php' ? 'active' : '' ?>"><i class="fa-solid fa-tags"></i> Categories</a></li>
                <li><a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>"><i class="fa-solid fa-cart-shopping"></i> Orders</a></li>
                <li><a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> Users</a></li>
                <li><a href="banners.php" class="<?= $current_page == 'banners.php' ? 'active' : '' ?>"><i class="fa-regular fa-image"></i> Banners</a></li>
                <li><a href="messages.php" class="<?= $current_page == 'messages.php' ? 'active' : '' ?>"><i class="fa-regular fa-envelope"></i> Messages</a></li>
            </ul>
        </nav>
        <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="<?= SITE_URL ?>" target="_blank" style="color: var(--admin-sidebar-text); text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View Live Store
            </a>
        </div>
    </aside>

    <!-- Main Content Layout -->
    <main class="admin-main" id="adminMain">
        
        <!-- Top Navigation Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
            </div>
            <div class="topbar-right">
                <a href="#" title="Notifications"><i class="fa-regular fa-bell" style="font-size: 1.2rem;"></i></a>
                <div class="admin-name">
                    <i class="fa-regular fa-circle-user text-primary" style="font-size: 1.5rem; vertical-align: middle; margin-right: 5px;"></i>
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
                <a href="<?= SITE_URL ?>/actions/auth/logout.php" title="Logout" class="logout-link" data-logout-link><i class="fa-solid fa-right-from-bracket" style="color: #ef4444; font-size: 1.2rem;"></i></a>
            </div>
        </header>

        <!-- Flash Message Container -->
        <div style="padding: 20px 30px 0;">
            <?php displayFlashMessage(); ?>
        </div>
        
        <!-- Page Dynamic Content -->
        <div class="admin-content">
