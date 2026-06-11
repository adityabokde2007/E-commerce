<?php
// actions/search_action.php

require_once '../config/db.php';
require_once '../includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Get search query safely
$query = isset($_GET['q']) ? sanitize(trim($_GET['q'])) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

try {
    // Prepare LIKE statement
    $search_term = "%" . $query . "%";
    
    // Fetch top 5 matching products
    $stmt = $pdo->prepare("SELECT id, name, price, discount_price, image 
                           FROM products 
                           WHERE status = 1 AND (name LIKE ? OR description LIKE ?) 
                           ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$search_term, $search_term]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format prices for JSON output so JS doesn't have to guess
    $formatted_results = array_map(function($item) {
        $has_discount = !empty($item['discount_price']) && $item['discount_price'] < $item['price'];
        $display_price = $has_discount ? $item['discount_price'] : $item['price'];
        
        return [
            'id' => $item['id'],
            'name' => htmlspecialchars($item['name']),
            'image_url' => ASSETS_URL . 'images/products/' . ($item['image'] ?? 'placeholder.jpg'),
            'price_html' => formatPrice($display_price)
        ];
    }, $results);
    
    echo json_encode($formatted_results);
    
} catch (PDOException $e) {
    // Return empty array on error to fail silently for the user
    echo json_encode([]);
}
