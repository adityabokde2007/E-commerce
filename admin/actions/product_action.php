<?php
// admin/actions/product_action.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/products.php');
}

$action = $_POST['action'] ?? '';
$upload_dir = dirname(__DIR__, 2) . '/uploads/products/';

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($action === 'add' || $action === 'edit') {
    $name = sanitize($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $stock = (int)($_POST['stock'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';

    if ($action === 'add') {
        // Base slug generation
        $slug = generateSlug($name) . '-' . time();
        
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'prod_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, price, discount_price, stock, image, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $slug, $description, $price, $discount_price, $stock, $image, $is_featured, $status]);
            setFlashMessage('success', 'Product added successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding product: ' . $e->getMessage());
        }
        redirect(SITE_URL . '/admin/products.php');

    } elseif ($action === 'edit') {
        $product_id = (int)$_POST['product_id'];
        
        // Fetch old image to delete if a new one is uploaded
        $stmt_img = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt_img->execute([$product_id]);
        $old_image = $stmt_img->fetchColumn();
        
        $image = $old_image; // Default to old image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'prod_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
            
            // Delete old file if it exists and is not a placeholder
            if ($old_image && file_exists($upload_dir . $old_image) && !filter_var($old_image, FILTER_VALIDATE_URL)) {
                unlink($upload_dir . $old_image);
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, discount_price = ?, stock = ?, image = ?, is_featured = ?, status = ? WHERE id = ?");
            $stmt->execute([$category_id, $name, $description, $price, $discount_price, $stock, $image, $is_featured, $status, $product_id]);
            setFlashMessage('success', 'Product updated successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating product: ' . $e->getMessage());
        }
        redirect(SITE_URL . '/admin/products.php');
    }
} elseif ($action === 'delete') {
    $product_id = (int)$_POST['product_id'];
    
    // Fetch image
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $image = $stmt->fetchColumn();
    
    try {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);
        
        // Delete physical file
        if ($image && file_exists($upload_dir . $image) && !filter_var($image, FILTER_VALIDATE_URL)) {
            unlink($upload_dir . $image);
        }
        setFlashMessage('success', 'Product deleted successfully.');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Cannot delete product. It may be linked to existing orders.');
    }
    
    redirect(SITE_URL . '/admin/products.php');
}
