<?php
// index.php
require_once 'config/db.php';
require_once 'includes/header.php';

// Fetch Active Banners
$stmt = $pdo->query("SELECT * FROM banners WHERE status = 1 ORDER BY position ASC");
$banners = $stmt->fetchAll();

// Fetch Active Categories (Limit 6)
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 1 LIMIT 6");
$categories = $stmt->fetchAll();

// Fetch Featured Products
$stmt = $pdo->query("SELECT p.*, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating FROM products p WHERE p.status = 1 AND p.featured = 1 LIMIT 8");
$featured_products = $stmt->fetchAll();

// Fetch New Arrivals (Latest 8 non-featured products)
$stmt = $pdo->query("SELECT p.*, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating FROM products p WHERE p.status = 1 AND p.featured = 0 ORDER BY p.created_at DESC LIMIT 8");
$new_arrivals = $stmt->fetchAll();

/**
 * Helper function to render a product card
 * Keeping this inline for simplicity, or it could go in functions.php
 */
function renderProductCard($product) {
    // Check if there is a discount
    $has_discount = !empty($product['discount_price']) && $product['discount_price'] < $product['price'];
    $display_price = $has_discount ? $product['discount_price'] : $product['price'];
    
    // Use a placeholder image if the product image doesn't exist
    $image_path = ASSETS_URL . 'images/products/' . ($product['image'] ?? 'placeholder.jpg');
    
    ob_start();
    ?>
    <div class="card product-card">
        <?php if ($has_discount): ?>
            <div class="badge badge-danger" style="position: absolute; top: 10px; left: 10px; z-index: 2;">Sale</div>
        <?php endif; ?>
        
        <div class="product-img-wrapper">
            <a href="<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>">
                <!-- Fallback to a UI placeholder if image not actually on disk for demo -->
                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='https://picsum.photos/seed/<?= $product['id'] ?>/400/400'">
            </a>
            
            <div class="product-actions">
                <button type="button" class="btn btn-primary btn-sm add-to-cart-btn" data-id="<?= $product['id'] ?>">
                    <i class="fa-solid fa-cart-plus"></i> Add
                </button>
                <button type="button" class="btn btn-outline btn-sm wishlist-btn" data-id="<?= $product['id'] ?>">
                    <i class="fa-regular fa-heart"></i>
                </button>
            </div>
        </div>
        
        <div class="card-body text-center">
            <div class="product-rating text-warning mb-1" style="font-size: 0.8rem;">
                <?php
                $rating = $product['avg_rating'] ?? 0;
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= floor($rating)) echo '<i class="fa-solid fa-star"></i>';
                    elseif ($i - 0.5 <= $rating) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                    else echo '<i class="fa-regular fa-star"></i>';
                }
                ?>
                <span class="text-muted" style="margin-left: 5px;"><?= $rating > 0 ? round($rating, 1) : 'No reviews' ?></span>
            </div>
            <h3 class="card-title" style="font-size: 1.1rem; margin-bottom: 5px;">
                <a href="<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>" class="text-main">
                    <?= htmlspecialchars($product['name']) ?>
                </a>
            </h3>
            <div class="product-price" style="font-weight: 600; font-size: 1.1rem;">
                <?php if ($has_discount): ?>
                    <span class="text-danger"><?= formatPrice($display_price) ?></span>
                    <span class="text-muted" style="text-decoration: line-through; font-size: 0.9rem; margin-left: 5px;"><?= formatPrice($product['price']) ?></span>
                <?php else: ?>
                    <span class="text-primary"><?= formatPrice($product['price']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

.<!-- Add extra page-specific styles -->
<style>
.hero-slider { position: relative; overflow: hidden; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); width: 100%; aspect-ratio: 16/9; height: auto; display: flex; align-items: center; justify-content: center; }
.slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; visibility: hidden; transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out; }
.slide.active { opacity: 1; visibility: visible; z-index: 1; }
.slide::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.05); z-index: 1; pointer-events: none; }
.slide img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
.slide-content { position: absolute; top: 50%; left: 8%; transform: translateY(-50%); background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 40px; border-radius: 20px; max-width: 480px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); z-index: 2; border: 1px solid rgba(255, 255, 255, 0.15); }
.slide-content h2 { font-size: 2.4rem; margin-bottom: 15px; color: #ffffff; word-wrap: break-word; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
.slide-content p { color: #f0f0f0; font-size: 1.1rem; margin-bottom: 25px; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
.slider-arrows { position: absolute; top: 50%; width: 100%; display: flex; justify-content: space-between; transform: translateY(-50%); padding: 0 20px; z-index: 10; pointer-events: none; }
.slider-arrow { pointer-events: auto; width: 45px; height: 45px; background: rgba(255,255,255,0.5); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.5); border-radius: 50%; font-size: 1.2rem; cursor: pointer; color: var(--secondary); transition: all var(--transition-fast); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.slider-arrow:hover { background: var(--primary); color: white; }
.slider-dots { position: absolute; bottom: 20px; width: 100%; display: flex; justify-content: center; gap: 10px; z-index: 10; }
.dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: all var(--transition-fast); }
.dot.active { background: var(--primary); transform: scale(1.2); box-shadow: 0 0 5px rgba(0,0,0,0.5); }

/* Responsive adjustments for smaller screens */
@media (max-width: 992px) {
    .hero-slider { max-height: 450px; }
    .slide-content { left: 6%; padding: 28px; max-width: 420px; }
    .slide-content h2 { font-size: 2rem; }
}

@media (max-width: 576px) {
    .hero-slider { max-height: 420px; }
    .slide-content { left: 4%; padding: 18px; max-width: 300px; border-radius: 14px; }
    .slide-content h2 { font-size: 1.4rem; }
    .slide-content p { font-size: 0.95rem; }
}

@keyframes fade { from { opacity: 0.4; } to { opacity: 1; } }

/* Categories Showcase */
.category-card { text-align: center; display: block; padding: 20px; border-radius: var(--border-radius); background: var(--bg-white); box-shadow: var(--shadow-sm); transition: all var(--transition-fast); }
.category-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-bottom: 3px solid var(--primary); }
.category-icon { width: 80px; height: 80px; margin: 0 auto 15px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 2rem; }

/* Promotional Banner */
.promo-banner { background: linear-gradient(135deg, var(--secondary) 0%, #3a3a5c 100%); color: white; padding: 60px 0; text-align: center; border-radius: var(--border-radius-lg); margin: 60px 0; position: relative; overflow: hidden; }
.promo-banner::before { content: ''; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; }

/* Why Choose Us */
.feature-card { text-align: center; padding: 30px 20px; }
.feature-icon { font-size: 3rem; color: var(--primary); margin-bottom: 20px; }

/* Section Headers */
.section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; }
.section-header h2 { margin-bottom: 0; position: relative; }
.section-header h2::after { content: ''; position: absolute; left: 0; bottom: -12px; width: 60px; height: 3px; background: var(--primary); }
</style>

<?php 
// Hardcode the 3 banners you provided
$banners = [
    [
        'image' => 'banner1.png', 
        'title' => 'Summer Sale', 
        'description' => 'Discover the hottest deals of the season. Big savings on top brands.',
        'link' => 'shop.php'
    ],
    [
        'image' => 'banner2.png', 
        'title' => 'New Tech Arrivals', 
        'description' => 'Discover the next generation of performance and smart wearables. Up to 50% off.',
        'link' => 'shop.php'
    ],
    [
        'image' => 'banner3.png', 
        'title' => 'Fitness Essentials', 
        'description' => 'Equip yourself for every goal. Shop the best fitness gear and apparel today.',
        'link' => 'shop.php'
    ]
];
?>

<!-- 1. Hero Banner Slider -->
<section class="hero-slider" id="heroSlider">
    <?php if (!empty($banners)): ?>
        <?php foreach ($banners as $index => $banner): ?>
            <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                <!-- Fallback to placehold.co if image doesn't exist -->
                <img src="<?= ASSETS_URL ?>images/banners/<?= $banner['image'] ?>" alt="<?= htmlspecialchars($banner['title']) ?>" onerror="this.src='https://picsum.photos/seed/<?= $banner['id'] ?? $index ?>/1920/600'">
            </div>
        <?php endforeach; ?>
        
        <?php if (count($banners) > 1): ?>
            <div class="slider-arrows">
                <button class="slider-arrow" onclick="changeSlide(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="slider-arrow" onclick="changeSlide(1)"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="slider-dots">
                <?php foreach ($banners as $index => $banner): ?>
                    <span class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index ?>)"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- 2. Category Showcase -->
<section class="container mt-4 mb-4 pt-4">
    <div class="section-header">
        <h2>Shop by Category</h2>
    </div>
    
    <div class="grid grid-3" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <?php
        foreach ($categories as $category): 
            $cat_img = !empty($category['image']) ? $category['image'] : 'placeholder.jpg';
            $img_path = ASSETS_URL . 'images/categories/' . $cat_img;
        ?>
            <a href="<?= SITE_URL ?>/shop.php?category=<?= $category['slug'] ?>" class="category-card">
                <div class="category-icon" style="width: 120px; height: 120px; padding: 0; margin: 0 auto 15px auto; overflow: hidden; background: transparent; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                    <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($category['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" onerror="this.src='https://picsum.photos/seed/cat_<?= $category['id'] ?>/120/120'">
                </div>
                <h4 style="margin-bottom:0; font-size: 1.1rem;"><?= htmlspecialchars($category['name']) ?></h4>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- 3. Featured Products -->
<section class="container mb-4">
    <div class="section-header">
        <h2>Featured Products</h2>
        <a href="<?= SITE_URL ?>/shop.php?featured=1" class="text-primary">View All <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    
    <div class="grid grid-4">
        <?php foreach ($featured_products as $product): ?>
            <?= renderProductCard($product) ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- 4. Promotional Banner -->
<section class="container">
    <div class="promo-banner">
        <h2 style="color: white; font-size: 2.5rem; margin-bottom: 20px;">Get 20% Off Your First Order!</h2>
        <p style="font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9;">Sign up today and use code <strong>WELCOME20</strong> at checkout.</p>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 15px 30px;">Create an Account</a>
    </div>
</section>

<!-- 5. New Arrivals -->
<section class="container mb-4">
    <div class="section-header">
        <h2>New Arrivals</h2>
        <a href="<?= SITE_URL ?>/shop.php?sort=latest" class="text-primary">View All <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    
    <div class="grid grid-4">
        <?php foreach ($new_arrivals as $product): ?>
            <?= renderProductCard($product) ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- 6. Why Choose Us -->
<section class="container mb-4 pt-4 pb-4" style="border-top: 1px solid var(--border-color);">
    <div class="grid grid-4">
        <div class="feature-card">
            <i class="fa-solid fa-truck-fast feature-icon"></i>
            <h4>Free Shipping</h4>
            <p class="text-muted">On all orders over $50.</p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-shield-halved feature-icon"></i>
            <h4>Secure Payment</h4>
            <p class="text-muted">100% secure payment processing.</p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-headset feature-icon"></i>
            <h4>24/7 Support</h4>
            <p class="text-muted">Dedicated support anytime.</p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-rotate-left feature-icon"></i>
            <h4>Easy Returns</h4>
            <p class="text-muted">30-day return policy.</p>
        </div>
    </div>
</section>

<!-- Include JS for Slider (Instead of writing in main.js, placing inline here for the slider specifically) -->
<script>
    // Hero Slider Logic
    let slideIndex = 0;
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
    let slideTimer;
    
    // Initialize slider if it exists
    if(slides.length > 0) {
        showSlides(slideIndex);
        
        // Auto slide perfectly every 3 seconds (hover pause removed to ensure it always runs)
        slideTimer = setInterval(function() { changeSlide(1); }, 3000);
    }

    function changeSlide(n) {
        showSlides(slideIndex += n);
    }

    function currentSlide(n) {
        showSlides(slideIndex = n);
    }

    function showSlides(n) {
        if (n >= slides.length) { slideIndex = 0 }    
        if (n < 0) { slideIndex = slides.length - 1 }
        
        for (let i = 0; i < slides.length; i++) {
            slides[i].classList.remove("active");  
        }
        
        if(dots.length > 0) {
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove("active");
            }
            dots[slideIndex].classList.add("active");
        }
        
        slides[slideIndex].classList.add("active");
    }
</script>

<?php require_once 'includes/footer.php'; ?>
