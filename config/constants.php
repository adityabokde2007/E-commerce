<?php
// config/constants.php

// Define Site Constants
define('SITE_NAME', 'ShopEase');
// Autodetect SITE_URL robustly based on actual directory structure
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$domainName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
$dir = str_replace('\\', '/', __DIR__);
$projectDir = dirname($dir); // Gets the path to the main E-commerce folder
$basePath = $docRoot ? str_replace($docRoot, '', $projectDir) : '/E-commerce'; // Fallback if docRoot is missing
define('SITE_URL', $protocol . $domainName . $basePath);
// Admin contact for site messages
define('ADMIN_EMAIL', 'admin@shopease.com');

// Define Database Constants
define('DB_HOST', 'localhost;port=3307');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'ecommerce');

// Define Path Constants
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('ASSETS_URL', SITE_URL . '/assets/');

// Set Default Timezone
date_default_timezone_set('Asia/Kolkata'); // Adjust timezone if needed
