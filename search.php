<?php
// search.php
require_once 'config/db.php';
require_once 'includes/header.php';

// Get and sanitize search query
$query = isset($_GET['q']) ? sanitize(trim($_GET['q'])) : '';

$products = [];
$total_results = 0;

if (!empty($query)) {
    // Prepare LIKE statement
    $search_term = "%" . $query . "%";
    
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.status = 1 AND (p.name LIKE ? OR p.description LIKE ?) 
                           ORDER BY p.created_at DESC");
    $stmt->execute([$search_term, $search_term]);
    $products = $stmt->fetchAll();
    $total_results = count($products);
}

// Ensure the helper function exists (just in case)
if (!function_exists('renderShopProductCard')) {
    function renderShopProductCard($prod) {
        $hd = !empty($prod['discount_price']) && $prod['discount_price'] < $prod['price'];
        $dp = $hd ? $prod['discount_price'] : $prod['price'];
        $img = ASSETS_URL . 'images/products/' . ($prod['image'] ?? 'placeholder.jpg');
        ob_start();
        ?>
        <div class="card product-card">
            <?php if ($hd): ?><div class="badge badge-danger" style="position: absolute; top: 10px; left: 10px; z-index: 2;">Sale</div><?php endif; ?>
            <div class="product-img-wrapper">
                <a href="<?= SITE_URL ?>/product.php?id=<?= $prod['id'] ?>"><img src="<?= $img ?>" alt="<?= htmlspecialchars($prod['name']) ?>" onerror="this.src='https://placehold.co/400x400/F8F9FA/1A1A2E?text=Product'"></a>
            </div>
            <div class="card-body text-center">
                <h3 class="card-title" style="font-size: 1rem;"><a href="<?= SITE_URL ?>/product.php?id=<?= $prod['id'] ?>" class="text-main"><?= htmlspecialchars($prod['name']) ?></a></h3>
                <div class="product-price" style="font-weight: 600;">
                    <?php if ($hd): ?>
                        <span class="text-danger"><?= formatPrice($dp) ?></span>
                        <span class="text-muted" style="text-decoration: line-through; font-size: 0.9rem; margin-left: 5px;"><?= formatPrice($prod['price']) ?></span>
                    <?php else: ?><span class="text-primary"><?= formatPrice($prod['price']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}
?>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="#" class="text-primary">Search Results</a></li>
        </ul>
    </div>
</div>

<div class="container mt-5 mb-5">
    
    <?php if (empty($query)): ?>
        <!-- Empty Query State -->
        <div class="text-center" style="padding: 60px 20px; background: var(--bg-white); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-magnifying-glass" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
            <h2>What are you looking for?</h2>
            <p class="text-muted mb-4">Please enter a search term in the search bar above.</p>
            <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary">Browse All Products</a>
        </div>
        
    <?php elseif ($total_results > 0): ?>
        <!-- Results Found -->
        <div class="section-header mb-4">
            <h2>Search Results for "<?= htmlspecialchars($query) ?>"</h2>
            <span class="text-muted">Found <?= $total_results ?> result(s)</span>
        </div>
        
        <div class="grid grid-4">
            <?php foreach ($products as $product): ?>
                <?= renderShopProductCard($product) ?>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <!-- No Results Found -->
        <div class="text-center" style="padding: 60px 20px; background: var(--bg-white); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-face-frown-open" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
            <h2>No results found for "<?= htmlspecialchars($query) ?>"</h2>
            <p class="text-muted mb-4">We couldn't find any products matching your search. Try adjusting your spelling or using more general terms.</p>
            
            <div class="mt-4 pt-4" style="border-top: 1px solid var(--border-color); max-width: 600px; margin: 0 auto; text-align: left;">
                <h4 class="mb-3 text-center">Suggestions:</h4>
                <div class="grid grid-2 text-center" style="gap: 15px;">
                    <a href="<?= SITE_URL ?>/shop.php?category=electronics" class="btn btn-outline"><i class="fa-solid fa-laptop"></i> Try Electronics</a>
                    <a href="<?= SITE_URL ?>/shop.php?category=fashion" class="btn btn-outline"><i class="fa-solid fa-shirt"></i> Try Fashion</a>
                    <a href="<?= SITE_URL ?>/shop.php?category=home-kitchen" class="btn btn-outline"><i class="fa-solid fa-blender"></i> Try Home & Kitchen</a>
                    <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary"><i class="fa-solid fa-box-open"></i> Browse Entire Shop</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
