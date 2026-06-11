<?php
// login_and_test_ajax.php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Create a cookie jar
$cookieFile = __DIR__ . '/cookie.txt';

// Step 1: Login to get a valid session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/E-commerce/actions/auth/login.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => 'admin@shopease.com',
    'password' => 'admin123',
    'action' => 'login'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// We want to follow redirect to see if login succeeds
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$login_result = curl_exec($ch);
curl_close($ch);

// Step 2: Make the AJAX request to update profile
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, "http://localhost/E-commerce/actions/profile/update_profile.php");
curl_setopt($ch2, CURLOPT_POST, 1);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'update_info',
    'name' => 'Admin Updated',
    'email' => 'admin@shopease.com',
    'phone' => '9876543210'
]));
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest'
]);
$update_result = curl_exec($ch2);
$http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $update_result . "\n";
