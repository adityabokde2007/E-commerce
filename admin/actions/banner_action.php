<?php
// admin/actions/banner_action.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/banners.php');
}

$action = $_POST['action'] ?? '';
$upload_dir = dirname(__DIR__, 2) . '/uploads/banners/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($action === 'add' || $action === 'edit') {
    $title = sanitize($_POST['title'] ?? '');
    $link_url = sanitize($_POST['link_url'] ?? '');
    $position = sanitize($_POST['position'] ?? 'home_hero');
    $status = $_POST['status'] ?? 'active';

    if ($action === 'add') {
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'banner_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO banners (title, image, link_url, position, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $image, $link_url, $position, $status]);
            setFlashMessage('success', 'Banner added successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding banner.');
        }
        
    } elseif ($action === 'edit') {
        $banner_id = (int)$_POST['banner_id'];
        
        $stmt_img = $pdo->prepare("SELECT image FROM banners WHERE id = ?");
        $stmt_img->execute([$banner_id]);
        $old_image = $stmt_img->fetchColumn();

        $image = $old_image; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = 'banner_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
            
            if ($old_image && file_exists($upload_dir . $old_image) && !filter_var($old_image, FILTER_VALIDATE_URL)) {
                unlink($upload_dir . $old_image);
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE banners SET title = ?, image = ?, link_url = ?, position = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $image, $link_url, $position, $status, $banner_id]);
            setFlashMessage('success', 'Banner updated successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating banner.');
        }
    }
    
    redirect(SITE_URL . '/admin/banners.php');

} elseif ($action === 'delete') {
    $banner_id = (int)$_POST['banner_id'];
    
    $stmt = $pdo->prepare("SELECT image FROM banners WHERE id = ?");
    $stmt->execute([$banner_id]);
    $image = $stmt->fetchColumn();
    
    try {
        $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$banner_id]);
        if ($image && file_exists($upload_dir . $image) && !filter_var($image, FILTER_VALIDATE_URL)) {
            unlink($upload_dir . $image);
        }
        setFlashMessage('success', 'Banner deleted successfully.');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error deleting banner.');
    }
    
    redirect(SITE_URL . '/admin/banners.php');
}
