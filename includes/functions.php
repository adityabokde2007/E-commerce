<?php
// includes/functions.php

/**
 * Redirect to a specific page
 * @param string $page The page URL to redirect to
 */
function redirect($page) {
    header("Location: " . $page);
    exit;
}

/**
 * Check if the user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an admin
 * @return bool
 */
function isAdmin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

/**
 * Sanitize user input to prevent XSS
 * @param string $data The input data
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Format a number as a price
 * @param float|string $price The price
 * @param string $currency The currency symbol (default: $)
 * @return string Formatted price
 */
function formatPrice($price, $currency = '₹') {
    return $currency . number_format((float)$price, 2);
}

/**
 * Generate a URL-friendly slug from a string
 * @param string $string The string to convert
 * @return string The generated slug
 */
function generateSlug($string) {
    // Replace non-letter or digits by -
    $string = preg_replace('~[^\pL\d]+~u', '-', $string);
    // Transliterate
    $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
    // Remove unwanted characters
    $string = preg_replace('~[^-\w]+~', '', $string);
    // Trim
    $string = trim($string, '-');
    // Remove duplicate -
    $string = preg_replace('~-+~', '-', $string);
    // Lowercase
    $string = strtolower($string);
    
    if (empty($string)) {
        return 'n-a';
    }
    return $string;
}

/**
 * Set a flash message in the session
 * @param string $type The message type (success, error, warning, info)
 * @param string $message The message text
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display the flash message if it exists
 * @return string|null HTML string for the flash message or null
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = addslashes($_SESSION['flash_message']['message']);
        
        // Clear the message after displaying it
        unset($_SESSION['flash_message']);
        
        $title = ucfirst($type);
        
        // Return a script block to trigger the toast instead of inline HTML
        return "<script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.showToast === 'function') {
                    window.showToast('$title', '$message', '$type');
                }
            });
        </script>";
    }
    return null;
}
