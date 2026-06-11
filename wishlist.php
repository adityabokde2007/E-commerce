<?php
// wishlist.php
require_once 'config/db.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Please login to view your wishlist.');
    redirect(SITE_URL . '/login.php');
}

// Fetch wishlisted products
$stmt = $pdo->prepare("SELECT p.*, w.id as wishlist_id, c.name as category_name 
                       FROM wishlist w 
                       JOIN products p ON w.product_id = p.id 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE w.user_id = ? 
                       ORDER BY w.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$wishlist_items = $stmt->fetchAll();
?>

<style>
.wishlist-card { position: relative; background: var(--bg-white); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); overflow: hidden; transition: all var(--transition-fast); }
.wishlist-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }

.wishlist-img { position: relative; overflow: hidden; height: 220px; background: var(--bg-light); }
.wishlist-img img { width: 100%; height: 100%; object-fit: contain; transition: transform 0.3s ease; padding: 10px; }
.wishlist-card:hover .wishlist-img img { transform: scale(1.05); }

.remove-wishlist-btn { position: absolute; top: 12px; right: 12px; width: 38px; height: 38px; border-radius: 50%; background: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all var(--transition-fast); z-index: 2; color: #ef4444; font-size: 1.1rem; }
.remove-wishlist-btn:hover { background: #ef4444; color: white; transform: scale(1.1); }

.sale-tag { position: absolute; top: 12px; left: 12px; z-index: 2; }

.wishlist-body { padding: 18px; }
.wishlist-category { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
.wishlist-name { font-size: 1.05rem; font-weight: 600; margin-bottom: 8px; line-height: 1.4; }
.wishlist-name a { color: var(--text-main); }
.wishlist-name a:hover { color: var(--primary); }

.wishlist-price { margin-bottom: 15px; font-size: 1.15rem; font-weight: 700; }

.wishlist-actions { display: flex; gap: 10px; }
.wishlist-actions .btn { flex: 1; text-align: center; font-size: 0.9rem; }

/* Fade-out animation */
@keyframes fadeOut { from { opacity: 1; transform: scale(1); } to { opacity: 0; transform: scale(0.9); } }
.wishlist-removing { animation: fadeOut 0.3s ease forwards; }
</style>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="#" class="text-primary">My Wishlist</a></li>
        </ul>
    </div>
</div>

<div class="container mt-4 mb-5">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h1 style="margin-bottom: 0;">My Wishlist</h1>
        <?php if (count($wishlist_items) > 0): ?>
            <span class="text-muted"><?= count($wishlist_items) ?> item(s)</span>
        <?php endif; ?>
    </div>

    <?php if (count($wishlist_items) > 0): ?>
        <div class="grid grid-4">
            <?php foreach ($wishlist_items as $item):
                $has_discount = !empty($item['discount_price']) && $item['discount_price'] < $item['price'];
                $display_price = $has_discount ? $item['discount_price'] : $item['price'];
                $image = ASSETS_URL . 'images/products/' . ($item['image'] ?? 'placeholder.jpg');
            ?>
                <div class="wishlist-card" id="wishlist-card-<?= $item['id'] ?>">
                    <div class="wishlist-img">
                        <?php if ($has_discount): ?>
                            <span class="badge badge-danger sale-tag">Sale</span>
                        <?php endif; ?>
                        <button class="remove-wishlist-btn" onclick="removeFromWishlist(<?= $item['id'] ?>)" title="Remove from Wishlist">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                        <a href="<?= SITE_URL ?>/product.php?id=<?= $item['id'] ?>">
                            <img src="<?= $image ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://placehold.co/400x300/F8F9FA/1A1A2E?text=Product'">
                        </a>
                    </div>
                    <div class="wishlist-body">
                        <div class="wishlist-category"><?= htmlspecialchars($item['category_name'] ?? '') ?></div>
                        <div class="wishlist-name">
                            <a href="<?= SITE_URL ?>/product.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                        </div>
                        <div class="wishlist-price">
                            <?php if ($has_discount): ?>
                                <span class="text-danger"><?= formatPrice($display_price) ?></span>
                                <span class="text-muted" style="text-decoration: line-through; font-size: 0.85rem; font-weight: 400; margin-left: 5px;"><?= formatPrice($item['price']) ?></span>
                            <?php else: ?>
                                <span class="text-primary"><?= formatPrice($display_price) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="wishlist-actions">
                            <button class="btn btn-primary btn-sm add-to-cart-btn" data-id="<?= $item['id'] ?>">
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                            <a href="<?= SITE_URL ?>/product.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center" style="padding: 60px 20px; background: var(--bg-white); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
            <i class="fa-regular fa-heart" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
            <h2 class="mb-3">Your wishlist is empty</h2>
            <p class="text-muted mb-4">Browse our shop and click the heart icon on products you love to save them here!</p>
            <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary" style="padding: 12px 30px;">Explore Products</a>
        </div>
    <?php endif; ?>
</div>

<script>
// Remove from wishlist on this page
function removeFromWishlist(productId) {
    const card = document.getElementById('wishlist-card-' + productId);
    
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('actions/wishlist/remove_wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            card.classList.add('wishlist-removing');
            setTimeout(() => {
                card.remove();
                // Check if grid is now empty
                const grid = document.querySelector('.grid.grid-4');
                if (grid && grid.children.length === 0) {
                    location.reload();
                }
            }, 300);
            if (typeof showToast === 'function') showToast('Wishlist', data.message, 'success');
        } else {
            if (typeof showToast === 'function') showToast('Error', data.message, 'error');
        }
    })
    .catch(() => {
        if (typeof showToast === 'function') showToast('Error', 'Network error.', 'error');
    });
}

// Global: Toggle wishlist from any page (product cards, product.php, etc.)
document.body.addEventListener('click', function(e) {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;
    e.preventDefault();

    const productId = btn.getAttribute('data-id');
    if (!productId) return;

    const icon = btn.querySelector('i');
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('actions/wishlist/add_wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.action === 'added') {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                btn.style.color = '#ef4444';
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                btn.style.color = '';
            }
            if (typeof showToast === 'function') showToast('Wishlist', data.message, 'success');
        } else {
            if (typeof showToast === 'function') showToast('Notice', data.message, 'warning');
        }
    })
    .catch(() => {
        if (typeof showToast === 'function') showToast('Error', 'Network error.', 'error');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
