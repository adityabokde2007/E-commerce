<?php
// admin/actions/category_action.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/categories.php');
}

$action = $_POST['action'] ?? '';
$upload_dir = dirname(__DIR__, 2) . '/uploads/categories/';

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($action === 'add' || $action === 'edit') {
    $name = sanitize($_POST['name'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Generate Slug
    $slug = generateSlug($name);
    
    if ($action === 'add') {
        // Check duplicate slug
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
        $stmt_check->execute([$slug]);
        if ($stmt_check->fetchColumn() > 0) {
            $slug = $slug . '-' . time();
        }

        // Image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'cat_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, image, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $image, $status]);
            setFlashMessage('success', 'Category added successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding category: ' . $e->getMessage());
        }

    } elseif ($action === 'edit') {
        $category_id = (int)$_POST['category_id'];
        
        // Check duplicate slug excluding self
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?");
        $stmt_check->execute([$slug, $category_id]);
        if ($stmt_check->fetchColumn() > 0) {
            $slug = $slug . '-' . time();
        }

        // Fetch old image
        $stmt_img = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $stmt_img->execute([$category_id]);
        $old_image = $stmt_img->fetchColumn();

        $image = $old_image; // Default to old image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'cat_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
            
            if ($old_image && file_exists($upload_dir . $old_image) && !filter_var($old_image, FILTER_VALIDATE_URL)) {
                unlink($upload_dir . $old_image);
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, image = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $image, $status, $category_id]);
            setFlashMessage('success', 'Category updated successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating category.');
        }
    }
    
    redirect(SITE_URL . '/admin/categories.php');

} elseif ($action === 'delete') {
    $category_id = (int)$_POST['category_id'];
    
    // Check if category has products
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt_check->execute([$category_id]);
    if ($stmt_check->fetchColumn() > 0) {
        setFlashMessage('error', 'Cannot delete category because it contains existing products. Delete or move the products first.');
        redirect(SITE_URL . '/admin/categories.php');
    }

    // Fetch image to delete
    $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $image = $stmt->fetchColumn();
    
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$category_id]);
        
        if ($image && file_exists($upload_dir . $image) && !filter_var($image, FILTER_VALIDATE_URL)) {
            unlink($upload_dir . $image);
        }
        setFlashMessage('success', 'Category deleted successfully.');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error deleting category.');
    }
    
    redirect(SITE_URL . '/admin/categories.php');
}
