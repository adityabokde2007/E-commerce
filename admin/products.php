<?php
// admin/products.php
require_once 'includes/admin_header.php';

// Search handling
$search = $_GET['q'] ?? '';
$where = "";
$params = [];

if (!empty($search)) {
    $where = "WHERE p.name LIKE ? OR c.name LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Fetch products with their category names
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       $where 
                       ORDER BY p.created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; color: #111827;">Manage Products</h2>
    <a href="add-product.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Product</a>
</div>

<div class="admin-table-container">
    <div class="admin-table-header">
        <form method="GET" action="products.php" style="display: flex; gap: 10px;">
            <input type="text" name="q" class="admin-form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
            <button type="submit" class="btn btn-outline">Search</button>
            <?php if(!empty($search)): ?>
                <a href="products.php" class="btn btn-outline text-muted" style="border-color: #d1d5db;">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td>
                        <?php 
                            // Support for absolute URL seed data vs locally uploaded files
                            $img_src = filter_var($p['image'], FILTER_VALIDATE_URL) ? $p['image'] : SITE_URL . '/uploads/products/' . $p['image'];
                            if(empty($p['image'])) $img_src = 'https://placehold.co/50x50?text=No+Img';
                        ?>
                        <img src="<?= $img_src ?>" alt="thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border);">
                    </td>
                    <td style="font-weight: 500; color: #111827;">
                        <?= htmlspecialchars($p['name']) ?>
                        <?php if($p['is_featured']): ?>
                            <i class="fa-solid fa-star" style="color: #f59e0b; font-size: 0.8rem; margin-left: 5px;" title="Featured"></i>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                    <td>
                        <?php if($p['discount_price']): ?>
                            <span style="color: var(--admin-primary); font-weight: 600;"><?= formatPrice($p['discount_price']) ?></span>
                            <br><small style="text-decoration: line-through; color: #9ca3af;"><?= formatPrice($p['price']) ?></small>
                        <?php else: ?>
                            <span style="font-weight: 600;"><?= formatPrice($p['price']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($p['stock'] <= 5): ?>
                            <span class="text-danger" style="font-weight: 600;"><?= $p['stock'] ?> (Low)</span>
                        <?php else: ?>
                            <span style="color: #10b981; font-weight: 600;"><?= $p['stock'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($p['status'] == 'active'): ?>
                            <span class="badge badge-delivered">Active</span>
                        <?php else: ?>
                            <span class="badge badge-cancelled">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="edit-product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" style="color: #3b82f6; border-color: #3b82f6;" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="<?= SITE_URL ?>/admin/actions/product_action.php" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete this product? This action cannot be undone.');" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline" style="color: #ef4444; border-color: #ef4444;" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
