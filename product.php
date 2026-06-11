<?php
// product.php
require_once 'config/db.php';
require_once 'includes/header.php';

// 1. Validate Product ID
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    redirect(SITE_URL . '/404.php'); // We'll assume a 404 page exists or create later
}

// 2. Fetch Product Details with Category
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = ? AND p.status = 1 LIMIT 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    // If product not found
    echo "<div class='container mt-4 mb-4 text-center'><h3>Product not found!</h3><a href='shop.php' class='btn btn-primary mt-2'>Back to Shop</a></div>";
    require_once 'includes/footer.php';
    exit;
}

// 3. Variables & Pricing Logic
$has_discount = !empty($product['discount_price']) && $product['discount_price'] < $product['price'];
$display_price = $has_discount ? $product['discount_price'] : $product['price'];
$main_image = ASSETS_URL . 'images/products/' . ($product['image'] ?? 'placeholder.jpg');

// Handle Gallery JSON (if any)
$gallery_images = [];
if (!empty($product['gallery'])) {
    $decoded = json_decode($product['gallery'], true);
    if (is_array($decoded)) {
        $gallery_images = $decoded;
    }
}

// 4. Fetch Reviews & Average Rating
$stmt_reviews = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt_reviews->execute([$product_id]);
$reviews = $stmt_reviews->fetchAll();

$total_reviews = count($reviews);
$avg_rating = 0;
if ($total_reviews > 0) {
    $sum = 0;
    foreach ($reviews as $rev) { $sum += $rev['rating']; }
    $avg_rating = round($sum / $total_reviews, 1);
}

// 5. Check if User Purchased Product (to allow reviewing)
$can_review = false;
if (isLoggedIn()) {
    $stmt_check_purchase = $pdo->prepare("SELECT oi.id FROM order_items oi 
                                          JOIN orders o ON oi.order_id = o.id 
                                          WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'delivered' LIMIT 1");
    $stmt_check_purchase->execute([$_SESSION['user_id'], $product_id]);
    if ($stmt_check_purchase->rowCount() > 0) {
        // Now check if they already reviewed it
        $stmt_check_reviewed = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
        $stmt_check_reviewed->execute([$_SESSION['user_id'], $product_id]);
        if ($stmt_check_reviewed->rowCount() === 0) {
            $can_review = true; // Purchased, Delivered, and Not Reviewed yet
        }
    }
}

// 6. Fetch Related Products
$stmt_related = $pdo->prepare("SELECT p.*, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating 
                               FROM products p 
                               WHERE category_id = ? AND id != ? AND status = 1 LIMIT 4");
$stmt_related->execute([$product['category_id'], $product_id]);
$related_products = $stmt_related->fetchAll();

// Import renderShopProductCard function from shop.php context (Redeclaring safely)
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
                <div class="product-rating text-warning mb-1" style="font-size: 0.8rem;">
                    <?php
                    $rating = $prod['avg_rating'] ?? 0;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= floor($rating)) echo '<i class="fa-solid fa-star"></i>';
                        elseif ($i - 0.5 <= $rating) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                        else echo '<i class="fa-regular fa-star"></i>';
                    }
                    ?>
                </div>
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

<style>
.product-detail-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px; }
.product-gallery { position: relative; }
.main-image-container { border-radius: var(--border-radius); overflow: hidden; background: var(--bg-light); margin-bottom: 15px; cursor: zoom-in; text-align: center;}
.main-image-container img { width: 100%; max-height: 500px; object-fit: contain; transition: transform 0.3s ease; }
.thumbnails { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
.thumbnail { width: 80px; height: 80px; border-radius: var(--border-radius-sm); border: 2px solid transparent; cursor: pointer; overflow: hidden; }
.thumbnail.active { border-color: var(--primary); }
.thumbnail img { width: 100%; height: 100%; object-fit: cover; }

.product-info h1 { font-size: 2.2rem; margin-bottom: 10px; }
.rating-reviews { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
.price-block { font-size: 1.8rem; font-weight: 600; margin-bottom: 20px; }
.stock-status { margin-bottom: 20px; font-weight: 500; }
.stock-in { color: var(--success); }
.stock-out { color: var(--danger); }

.action-row { display: flex; gap: 15px; align-items: center; margin-top: 30px; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid var(--border-color); }
.qty-selector { display: flex; align-items: center; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); overflow: hidden; }
.qty-btn { width: 40px; height: 45px; background: var(--bg-light); border: none; cursor: pointer; font-size: 1.2rem; color: var(--secondary); transition: background var(--transition-fast); }
.qty-btn:hover { background: #e2e6ea; }
.qty-input { width: 50px; height: 45px; border: none; text-align: center; font-weight: 600; font-size: 1.1rem; -moz-appearance: textfield; }
.qty-input::-webkit-outer-spin-button, .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.tabs-container { margin-top: 40px; }
.tabs-nav { display: flex; border-bottom: 1px solid var(--border-color); margin-bottom: 20px; }
.tab-btn { padding: 10px 20px; background: none; border: none; font-size: 1.1rem; font-weight: 500; color: var(--text-muted); cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -1px; }
.tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
.tab-pane { display: none; animation: fadeIn 0.3s ease; }
.tab-pane.active { display: block; }

.review-item { padding: 20px 0; border-bottom: 1px solid var(--border-color); }
.review-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
.star-rating-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; }
.star-rating-input input { display: none; }
.star-rating-input label { color: #ddd; font-size: 1.5rem; cursor: pointer; }
.star-rating-input input:checked ~ label, .star-rating-input label:hover, .star-rating-input label:hover ~ label { color: var(--warning); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@media (max-width: 768px) { .product-detail-layout { grid-template-columns: 1fr; } .action-row { flex-wrap: wrap; } }
</style>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/shop.php">Shop</a></li>
            <li><a href="<?= SITE_URL ?>/shop.php?category=<?= $product['category_slug'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="text-muted"><?= htmlspecialchars($product['name']) ?></li>
        </ul>
    </div>
</div>

<div class="container mt-4 mb-4">
    <div class="product-detail-layout">
        
        <!-- Left: Image Gallery -->
        <div class="product-gallery">
            <div class="main-image-container" id="mainImageContainer">
                <img src="<?= $main_image ?>" id="mainProductImage" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='https://placehold.co/600x600/F8F9FA/1A1A2E?text=Product+Image'">
            </div>
            
            <div class="thumbnails">
                <div class="thumbnail active" onclick="changeMainImage(this, '<?= $main_image ?>')">
                    <img src="<?= $main_image ?>" alt="Thumbnail" onerror="this.src='https://placehold.co/100x100/F8F9FA/1A1A2E?text=Thumb'">
                </div>
                <?php foreach ($gallery_images as $thumb): ?>
                    <div class="thumbnail" onclick="changeMainImage(this, '<?= ASSETS_URL ?>images/products/<?= $thumb ?>')">
                        <img src="<?= ASSETS_URL ?>images/products/<?= $thumb ?>" alt="Thumbnail" onerror="this.src='https://placehold.co/100x100/F8F9FA/1A1A2E?text=Thumb'">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Product Info -->
        <div class="product-info">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="rating-reviews">
                <div class="text-warning">
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= floor($avg_rating)) echo '<i class="fa-solid fa-star"></i>';
                        elseif ($i - 0.5 <= $avg_rating) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                        else echo '<i class="fa-regular fa-star"></i>';
                    }
                    ?>
                </div>
                <span class="text-muted">(<?= $avg_rating ?> / 5) - <a href="#reviewsTab" onclick="openTab('reviewsTab')" class="text-primary"><?= $total_reviews ?> Reviews</a></span>
            </div>
            
            <div class="price-block">
                <?php if ($has_discount): ?>
                    <span class="text-danger"><?= formatPrice($display_price) ?></span>
                    <span class="text-muted" style="text-decoration: line-through; font-size: 1.2rem; margin-left: 10px;"><?= formatPrice($product['price']) ?></span>
                    <span class="badge badge-danger" style="vertical-align: middle; margin-left: 10px;">Sale</span>
                <?php else: ?>
                    <span class="text-primary"><?= formatPrice($product['price']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="stock-status">
                <?php if ($product['stock'] > 10): ?>
                    <span class="stock-in"><i class="fa-solid fa-check-circle"></i> In Stock</span>
                <?php elseif ($product['stock'] > 0): ?>
                    <span class="text-warning"><i class="fa-solid fa-circle-exclamation"></i> Only <?= $product['stock'] ?> left in stock!</span>
                <?php else: ?>
                    <span class="stock-out"><i class="fa-solid fa-xmark-circle"></i> Out of Stock</span>
                <?php endif; ?>
            </div>
            
            <p class="text-muted" style="line-height: 1.8;">
                <?= nl2br(htmlspecialchars(substr($product['description'] ?? '', 0, 200))) ?>...
                <a href="#descriptionTab" onclick="openTab('descriptionTab')" class="text-primary">Read more</a>
            </p>
            
            <!-- Add to Cart Actions -->
            <form action="<?= SITE_URL ?>/actions/cart/add_to_cart.php" method="POST" id="addToCartForm">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                
                <div class="action-row">
                    <div class="qty-selector">
                        <button type="button" class="qty-btn" onclick="updateQty(-1)"><i class="fa-solid fa-minus"></i></button>
                        <input type="number" id="qtyInput" name="quantity" class="qty-input" value="1" min="1" max="<?= $product['stock'] > 0 ? $product['stock'] : 1 ?>">
                        <button type="button" class="qty-btn" onclick="updateQty(1)"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    
                    <button type="button" class="btn btn-primary add-to-cart-btn" data-id="<?= $product['id'] ?>" style="padding: 12px 30px; font-size: 1.1rem; flex: 1;" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-cart-plus"></i> <?= $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                    </button>
                    
                    <button type="button" id="wishlistToggleBtn" class="btn btn-outline" data-product-id="<?= $product['id'] ?>" style="padding: 12px 20px;">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                </div>
            </form>
            
            <div class="d-flex align-items-center gap-3 mt-3 text-muted" style="font-size: 0.9rem;">
                <span><i class="fa-solid fa-shield-halved text-success"></i> 1 Year Warranty</span>
                <span><i class="fa-solid fa-rotate-left text-success"></i> 30 Days Return</span>
            </div>
        </div>
    </div>
    
    <!-- Tabs Container -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="openTab('descriptionTab')" id="btn-descriptionTab">Description</button>
            <button class="tab-btn" onclick="openTab('reviewsTab')" id="btn-reviewsTab">Reviews (<?= $total_reviews ?>)</button>
        </div>
        
        <!-- Description Tab -->
        <div class="tab-pane active" id="descriptionTab">
            <div style="line-height: 1.8; color: var(--text-main);">
                <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
            </div>
        </div>
        
        <!-- Reviews Tab -->
        <div class="tab-pane" id="reviewsTab">
            
            <!-- Write Review Form -->
            <?php 
            $can_review = false;
            if (isLoggedIn()) {
                $stmt_check_reviewed = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
                $stmt_check_reviewed->execute([$_SESSION['user_id'], $product_id]);
                if ($stmt_check_reviewed->rowCount() === 0) {
                    $can_review = true;
                }
            }
            ?>
            <?php if ($can_review): ?>
                <div class="card mb-4" style="background: var(--bg-light); border: none;">
                    <div class="card-body">
                        <h4>Write a Review</h4>
                        <form action="actions/review/add_review.php" method="POST" data-profile-form="review" novalidate>
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <div class="form-group mb-2">
                                <label class="form-label">Your Rating</label>
                                <div class="star-rating-input">
                                    <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star4" name="rating" value="4"/><label for="star4" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star3" name="rating" value="3"/><label for="star3" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star2" name="rating" value="2"/><label for="star2" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star1" name="rating" value="1"/><label for="star1" class="fa-solid fa-star"></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comment" class="form-label">Your Review (Optional)</label>
                                <textarea name="comment" id="comment" class="form-control" rows="3" placeholder="What did you like or dislike?"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                </div>
            <?php elseif (isLoggedIn()): ?>
                <div class="alert alert-info">You have already reviewed this product. Thank you!</div>
            <?php else: ?>
                <div class="alert alert-info">Please <a href="login.php" class="text-primary font-weight-bold">login</a> to write a review.</div>
            <?php endif; ?>
            
            <!-- Review List -->
            <?php if ($total_reviews > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <strong><i class="fa-solid fa-circle-user text-muted"></i> <?= htmlspecialchars($review['user_name']) ?></strong>
                            <span class="text-muted" style="font-size: 0.85rem;"><?= date('F j, Y', strtotime($review['created_at'])) ?></span>
                        </div>
                        <div class="text-warning mb-2" style="font-size: 0.85rem;">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $review['rating'] ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                            }
                            ?>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                            <p class="text-muted m-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-4">No reviews yet. Be the first to review this product!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (count($related_products) > 0): ?>
        <div class="mt-5 pt-4" style="border-top: 1px solid var(--border-color);">
            <div class="section-header">
                <h2>Related Products</h2>
            </div>
            <div class="grid grid-4">
                <?php foreach ($related_products as $rel_prod): ?>
                    <?= renderShopProductCard($rel_prod) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Load Custom JS for Product Logic -->
<script src="<?= ASSETS_URL ?>js/product.js?v=2.0"></script>

<?php require_once 'includes/footer.php'; ?>
