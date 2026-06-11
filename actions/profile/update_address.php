<?php
// actions/profile/update_address.php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../config/db.php';
require_once '../../includes/functions.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function respond($success, $message, $redirectUrl = '', $field = '', $code = '') {
    global $is_ajax;
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'redirect' => $redirectUrl,
            'field' => $field,
            'code' => $code ?: ($success ? 'success' : 'error')
        ]);
        exit;
    }
    setFlashMessage($success ? 'success' : 'error', $message);
    redirect($redirectUrl);
}

if (!isLoggedIn()) {
    if ($is_ajax) {
        respond(false, 'Your session has expired. Please log in again.', SITE_URL . '/login.php', '', 'auth_error');
    }
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        respond(false, 'Invalid request method.');
    }
    redirect(SITE_URL . '/profile.php?tab=addresses');
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ---- ADD NEW ADDRESS ----
if ($action === 'add') {
    $full_name    = sanitize($_POST['full_name'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $address_line = sanitize($_POST['address_line'] ?? '');
    $city    = sanitize($_POST['city'] ?? '');
    $state   = sanitize($_POST['state'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');

    if (empty($full_name) || empty($phone) || empty($address_line) || empty($city) || empty($state) || empty($pincode)) {
        respond(false, 'All address fields are required.', SITE_URL . '/profile.php?tab=addresses');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line, city, state, pincode) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $phone, $address_line, $city, $state, $pincode]);
        respond(true, 'Address added successfully!', SITE_URL . '/profile.php?tab=addresses');
    } catch (PDOException $e) {
        respond(false, 'Failed to add address. Please try again.', SITE_URL . '/profile.php?tab=addresses');
    }
}

// ---- UPDATE EXISTING ADDRESS ----
if ($action === 'update') {
    $address_id = (int)($_POST['address_id'] ?? 0);
    $full_name       = sanitize($_POST['full_name'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');
    $address_line    = sanitize($_POST['address_line'] ?? '');
    $city       = sanitize($_POST['city'] ?? '');
    $state      = sanitize($_POST['state'] ?? '');
    $pincode    = sanitize($_POST['pincode'] ?? '');

    if ($address_id <= 0 || empty($full_name) || empty($phone) || empty($address_line) || empty($city) || empty($state) || empty($pincode)) {
        respond(false, 'All address fields are required.', SITE_URL . '/profile.php?tab=addresses');
    }

    try {
        // Ensure the address belongs to the user
        $stmt = $pdo->prepare("UPDATE addresses SET full_name = ?, phone = ?, address_line = ?, city = ?, state = ?, pincode = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$full_name, $phone, $address_line, $city, $state, $pincode, $address_id, $user_id]);
        respond(true, 'Address updated successfully!', SITE_URL . '/profile.php?tab=addresses');
    } catch (PDOException $e) {
        respond(false, 'Failed to update address. Please try again.', SITE_URL . '/profile.php?tab=addresses');
    }
}

// ---- DELETE ADDRESS ----
if ($action === 'delete') {
    $address_id = (int)($_POST['address_id'] ?? 0);

    if ($address_id <= 0) {
        respond(false, 'Invalid address.', SITE_URL . '/profile.php?tab=addresses');
    }

    try {
        // Ensure the address belongs to the user before deleting
        $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$address_id, $user_id]);
        respond(true, 'Address deleted successfully.', SITE_URL . '/profile.php?tab=addresses');
    } catch (PDOException $e) {
        respond(false, 'Failed to delete address. Please try again.', SITE_URL . '/profile.php?tab=addresses');
    }
}

// Fallback
respond(false, 'Invalid action.', SITE_URL . '/profile.php?tab=addresses');
