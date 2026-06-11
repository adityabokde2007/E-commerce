<?php
// admin/categories.php
require_once 'includes/admin_header.php';

// Fetch all categories with products count
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as products_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll();

// Check if we are in edit mode
$edit_id = $_GET['edit'] ?? null;
$edit_cat = null;
if ($edit_id) {
    $stmt_edit = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt_edit->execute([$edit_id]);
    $edit_cat = $stmt_edit->fetch();
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; color: #111827;">Manage Categories</h2>
</div>

<!-- Top Form section -->
<div class="admin-table-container" style="margin-bottom: 30px;">
    <div class="admin-table-header" style="border-bottom: 1px solid var(--admin-border); padding-bottom: 15px; margin-bottom: 20px;">
        <h3 style="color: #111827; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-tags text-primary"></i> 
            <?= $edit_cat ? 'Edit Category' : 'Add New Category' ?>
        </h3>
        <?php if($edit_cat): ?>
            <a href="categories.php" class="btn btn-sm btn-outline">Cancel Edit</a>
        <?php endif; ?>
    </div>
    
    <form action="<?= SITE_URL ?>/admin/actions/category_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $edit_cat ? 'edit' : 'add' ?>">
        <?php if($edit_cat): ?>
            <input type="hidden" name="category_id" value="<?= $edit_cat['id'] ?>">
        <?php endif; ?>
        
        <div class="grid grid-4" style="gap: 20px; align-items: flex-end;">
            
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="admin-form-control" value="<?= $edit_cat ? htmlspecialchars($edit_cat['name']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Image <?= $edit_cat ? '(Optional)' : '<span class="text-danger">*</span>' ?></label>
                <input type="file" name="image" class="admin-form-control" accept="image/*" <?= $edit_cat ? '' : 'required' ?>>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Status</label>
                <select name="status" class="admin-form-control">
                    <option value="active" <?= ($edit_cat && $edit_cat['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($edit_cat && $edit_cat['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px;">
                    <i class="fa-solid fa-save"></i> <?= $edit_cat ? 'Update' : 'Save Category' ?>
                </button>
            </div>
            
        </div>
        
        <?php if($edit_cat && !empty($edit_cat['image'])): ?>
            <?php 
                $current_img = filter_var($edit_cat['image'], FILTER_VALIDATE_URL) ? $edit_cat['image'] : SITE_URL . '/uploads/categories/' . $edit_cat['image'];
            ?>
            <div style="margin-top: 15px; font-size: 0.9rem; color: var(--text-muted);">
                <strong>Current Image:</strong> 
                <img src="<?= $current_img ?>" style="height: 40px; border-radius: 4px; vertical-align: middle; margin-left: 10px;">
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Category List Table -->
<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Products</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($categories) > 0): ?>
                <?php foreach($categories as $c): ?>
                <tr style="<?= ($edit_cat && $edit_cat['id'] == $c['id']) ? 'background-color: #fef2f2;' : '' ?>">
                    <td><?= $c['id'] ?></td>
                    <td>
                        <?php 
                            $img_src = filter_var($c['image'], FILTER_VALIDATE_URL) ? $c['image'] : SITE_URL . '/uploads/categories/' . $c['image'];
                            if(empty($c['image'])) $img_src = 'https://placehold.co/40x40?text=NA';
                        ?>
                        <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($c['name']) ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 8px;">
                    </td>
                    <td style="font-weight: 600; color: #111827;"><?= htmlspecialchars($c['name']) ?></td>
                    <td style="font-family: monospace; color: #6b7280; font-size: 0.9rem;"><?= htmlspecialchars($c['slug']) ?></td>
                    <td>
                        <span class="badge" style="background: #e5e7eb; color: #374151;"><?= $c['products_count'] ?> items</span>
                    </td>
                    <td>
                        <?php if($c['status'] == 'active'): ?>
                            <span class="badge badge-delivered">Active</span>
                        <?php else: ?>
                            <span class="badge badge-cancelled">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="categories.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline" style="color: #3b82f6; border-color: #3b82f6;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                            <form action="<?= SITE_URL ?>/admin/actions/category_action.php" method="POST" onsubmit="return confirm('Delete this category? Ensure it contains no products.');" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline" style="color: #ef4444; border-color: #ef4444;"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No categories found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
