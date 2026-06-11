<?php
// admin/banners.php
require_once 'includes/admin_header.php';

// Fetch all banners
$stmt = $pdo->query("SELECT * FROM banners ORDER BY created_at DESC");
$banners = $stmt->fetchAll();

// Check edit mode
$edit_id = $_GET['edit'] ?? null;
$edit_banner = null;
if ($edit_id) {
    $stmt_edit = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt_edit->execute([$edit_id]);
    $edit_banner = $stmt_edit->fetch();
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; color: #111827;">Manage Promotional Banners</h2>
</div>

<!-- Top Form section -->
<div class="admin-table-container" style="margin-bottom: 30px;">
    <div class="admin-table-header" style="border-bottom: 1px solid var(--admin-border); padding-bottom: 15px; margin-bottom: 20px;">
        <h3 style="color: #111827; display: flex; align-items: center; gap: 10px;">
            <i class="fa-regular fa-image text-primary"></i> 
            <?= $edit_banner ? 'Edit Banner' : 'Add New Banner' ?>
        </h3>
        <?php if($edit_banner): ?>
            <a href="banners.php" class="btn btn-sm btn-outline">Cancel Edit</a>
        <?php endif; ?>
    </div>
    
    <form action="<?= SITE_URL ?>/admin/actions/banner_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $edit_banner ? 'edit' : 'add' ?>">
        <?php if($edit_banner): ?>
            <input type="hidden" name="banner_id" value="<?= $edit_banner['id'] ?>">
        <?php endif; ?>
        
        <div class="grid grid-2" style="gap: 20px;">
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Title / Alt Text <span class="text-danger">*</span></label>
                <input type="text" name="title" class="admin-form-control" value="<?= $edit_banner ? htmlspecialchars($edit_banner['title']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Link URL <span class="text-muted">(Optional)</span></label>
                <input type="text" name="link_url" class="admin-form-control" value="<?= $edit_banner ? htmlspecialchars($edit_banner['link_url']) : '' ?>" placeholder="e.g. /shop.php?category=sale">
            </div>
            
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Banner Image <?= $edit_banner ? '(Optional)' : '<span class="text-danger">*</span>' ?></label>
                <input type="file" name="image" class="admin-form-control" accept="image/*" <?= $edit_banner ? '' : 'required' ?>>
                <small style="display:block; margin-top:8px; color:#6b7280;">Recommended: 16:9 ratio (e.g. 1451×816) for best results.</small>
                <?php if($edit_banner && !empty($edit_banner['image'])): ?>
                    <?php $current_img = filter_var($edit_banner['image'], FILTER_VALIDATE_URL) ? $edit_banner['image'] : SITE_URL . '/uploads/banners/' . $edit_banner['image']; ?>
                    <img src="<?= $current_img ?>" style="height: 60px; object-fit: cover; border-radius: 4px; margin-top: 10px;">
                <?php endif; ?>
            </div>
            
            <div class="grid grid-2" style="gap: 20px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600;">Position</label>
                    <select name="position" class="admin-form-control">
                        <option value="home_hero" <?= ($edit_banner && $edit_banner['position'] == 'home_hero') ? 'selected' : '' ?>>Homepage Hero Slider</option>
                        <option value="home_promo" <?= ($edit_banner && $edit_banner['position'] == 'home_promo') ? 'selected' : '' ?>>Homepage Middle Promo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600;">Status</label>
                    <select name="status" class="admin-form-control">
                        <option value="active" <?= ($edit_banner && $edit_banner['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($edit_banner && $edit_banner['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary px-4 py-2">
                <i class="fa-solid fa-save"></i> <?= $edit_banner ? 'Update Banner' : 'Upload Banner' ?>
            </button>
        </div>
    </form>
</div>

<!-- Banner Grid Layout -->
<h3 style="margin-bottom: 20px; color: #111827;">Current Banners</h3>
<div class="grid grid-3" style="gap: 25px;">
    <?php if(count($banners) > 0): ?>
        <?php foreach($banners as $b): ?>
        <?php $img_src = filter_var($b['image'], FILTER_VALIDATE_URL) ? $b['image'] : SITE_URL . '/uploads/banners/' . $b['image']; ?>
        
        <div style="background: white; border-radius: 10px; overflow: hidden; border: 1px solid var(--admin-border); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="width: 100%; background: #f3f4f6; position: relative; aspect-ratio: 16/9; max-height: 220px; overflow: hidden;">
                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($b['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                
                <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 5px;">
                    <a href="banners.php?edit=<?= $b['id'] ?>" class="btn btn-sm" style="background: white; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: #3b82f6;"><i class="fa-solid fa-pen-to-square"></i></a>
                    <form action="<?= SITE_URL ?>/admin/actions/banner_action.php" method="POST" onsubmit="return confirm('Delete this banner?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background: white; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: #ef4444;"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <div style="padding: 15px;">
                <h4 style="margin: 0 0 5px 0; color: #111827; font-size: 1.1rem;"><?= htmlspecialchars($b['title']) ?></h4>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                    <span style="font-size: 0.85rem; color: #6b7280; font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;"><?= $b['position'] ?></span>
                    <span class="badge <?= $b['status'] == 'active' ? 'badge-delivered' : 'badge-cancelled' ?>" style="font-size: 0.75rem;"><?= ucfirst($b['status']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 10px; border: 1px solid var(--admin-border); color: #6b7280;">
            No banners uploaded yet. Use the form above to add one.
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
