<?php
// admin/add-product.php
require_once 'includes/admin_header.php';

// Fetch Categories for the dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; color: #111827;">Add New Product</h2>
    <a href="products.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Products</a>
</div>

<div class="admin-table-container">
    <form action="<?= SITE_URL ?>/admin/actions/product_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        
        <div class="grid grid-2" style="gap: 30px;">
            <!-- Left Column: Primary Data -->
            <div>
                <div class="form-group mb-3">
                    <label class="form-label" style="font-weight: 600;">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="admin-form-control" required placeholder="e.g., Wireless Noise-Cancelling Headphones">
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label" style="font-weight: 600;">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="admin-form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-2" style="gap: 20px;">
                    <div class="form-group mb-3">
                        <label class="form-label" style="font-weight: 600;">Regular Price ($) <span class="text-danger">*</span></label>
                        <input type="number" name="price" step="0.01" min="0" class="admin-form-control" required placeholder="0.00">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label" style="font-weight: 600;">Discount Price ($)</label>
                        <input type="number" name="discount_price" step="0.01" min="0" class="admin-form-control" placeholder="Optional">
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label" style="font-weight: 600;">Stock Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="stock" class="admin-form-control" value="10" min="0" required>
                </div>
            </div>
            
            <!-- Right Column: Media & Metadata -->
            <div>
                <div class="form-group mb-3">
                    <label class="form-label" style="font-weight: 600;">Main Image <span class="text-danger">*</span></label>
                    <input type="file" name="image" class="admin-form-control" accept="image/*" id="imgInp" required>
                    <div class="mt-2 text-center" style="background: #f9fafb; border: 1px dashed var(--admin-border); padding: 10px; border-radius: 6px;">
                        <img id="imgPreview" src="https://placehold.co/200x200?text=Image+Preview" alt="Preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label" style="font-weight: 600;">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="admin-form-control" rows="5" required placeholder="Write a compelling product description..."></textarea>
                </div>
                
                <div class="grid grid-2" style="gap: 20px;">
                    <div class="form-group mb-3">
                        <label class="form-label" style="font-weight: 600;">Visibility Status</label>
                        <select name="status" class="admin-form-control">
                            <option value="active">Active (Visible)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                        </select>
                    </div>
                    <div class="form-group mb-3" style="display: flex; align-items: flex-end;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 10px; padding: 10px; border: 1px solid var(--admin-border); border-radius: 6px; width: 100%;">
                            <input type="checkbox" name="is_featured" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-weight: 600; color: #111827;">Mark as Featured</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 20px 0;">
        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary px-5 py-3" style="font-size: 1.05rem; font-weight: 600;"><i class="fa-solid fa-save"></i> Save Product</button>
        </div>
    </form>
</div>

<script>
// Dynamic Image Preview Script
document.getElementById('imgInp').onchange = evt => {
    const [file] = document.getElementById('imgInp').files
    if (file) {
        document.getElementById('imgPreview').src = URL.createObjectURL(file)
    }
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
