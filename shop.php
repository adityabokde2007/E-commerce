<?php
// shop.php
require_once 'config/db.php';
require_once 'includes/header.php';

// --- 1. Get filter parameters from URL ---
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$min_price = isset($_GET['min']) && is_numeric($_GET['min']) ? (float)$_GET['min'] : null;
$max_price = isset($_GET['max']) && is_numeric($_GET['max']) ? (float)$_GET['max'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

$price_expression = "CASE WHEN p.discount_price IS NOT NULL AND p.discount_price > 0 AND p.discount_price < p.price THEN p.discount_price ELSE p.price END";

if (($min_price !== null && $max_price === null) || ($min_price === null && $max_price !== null)) {
    setFlashMessage('warning', 'Please enter both minimum and maximum values to search products.');

    $redirect_params = $_GET;
    unset($redirect_params['min'], $redirect_params['max']);
    redirect(SITE_URL . '/shop.php' . (!empty($redirect_params) ? '?' . http_build_query($redirect_params) : ''));
}

// Pagination configuration
$limit = 10;
$offset = ($page - 1) * $limit;

// --- 2. Build Dynamic SQL Query ---
$where_clauses = ["p.status = 1"];
$params = [];

// Handle Category Filter
if (!empty($category_filter)) {
    // We are joining categories to filter by slug
    $where_clauses[] = "c.slug = :category";
    $params[':category'] = $category_filter;
}

// Handle Price Filter
if ($min_price !== null) {
    $where_clauses[] = "$price_expression >= :min";
    $params[':min'] = $min_price;
}
if ($max_price !== null) {
    $where_clauses[] = "$price_expression <= :max";
    $params[':max'] = $max_price;
}

// Build WHERE string
$where_sql = implode(' AND ', $where_clauses);

// Build ORDER BY string
switch ($sort) {
    case 'price-low':
        $order_sql = "ORDER BY $price_expression ASC";
        break;
    case 'price-high':
        $order_sql = "ORDER BY $price_expression DESC";
        break;
    case 'name-a-z':
        $order_sql = "ORDER BY p.name ASC";
        break;
    case 'latest':
    default:
        $order_sql = "ORDER BY p.created_at DESC";
        break;
}

// --- 3. Execute Queries ---
// Query to get Total Count (for pagination)
$count_query = "SELECT COUNT(p.id) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $where_sql";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params);
$total_products = $stmt_count->fetch()['total'];
$total_pages = ceil($total_products / $limit);

// Main Query to get Products
$products_query = "SELECT p.*, c.name as category_name, c.slug as category_slug, 
                   (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE $where_sql $order_sql LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($products_query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
// PDO requires explicit casting for LIMIT/OFFSET binding
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch all categories for the sidebar
$stmt_cat = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");
$all_categories = $stmt_cat->fetchAll();

// --- 4. Helper Function for Product Cards (Imported/Copied from index.php logic) ---
function renderShopProductCard($product) {
    $has_discount = !empty($product['discount_price']) && $product['discount_price'] < $product['price'];
    $display_price = $has_discount ? $product['discount_price'] : $product['price'];
    $image_path = ASSETS_URL . 'images/products/' . ($product['image'] ?? 'placeholder.jpg');
    
    ob_start();
    ?>
    <div class="card product-card">
        <?php if ($has_discount): ?>
            <div class="badge badge-danger" style="position: absolute; top: 10px; left: 10px; z-index: 2;">Sale</div>
        <?php endif; ?>
        <div class="product-img-wrapper">
            <a href="<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>">
                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='https://placehold.co/400x400/F8F9FA/1A1A2E?text=<?= urlencode($product['name']) ?>'">
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
            <div class="text-muted mb-1" style="font-size: 0.8rem;"><?= htmlspecialchars($product['category_name'] ?? '') ?></div>
            <h3 class="card-title" style="font-size: 1rem; margin-bottom: 5px;">
                <a href="<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>" class="text-main">
                    <?= htmlspecialchars($product['name']) ?>
                </a>
            </h3>
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

<style>
/* Shop Page Specific Styles */
.shop-layout { display: flex; gap: 30px; margin-top: 20px; }
.sidebar { width: 280px; flex-shrink: 0; }
.shop-content { flex: 1; }

.filter-box { background: var(--bg-white); border-radius: var(--border-radius); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px; border: 1px solid var(--border-color); }
.filter-box h4 { font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }

.category-list li { margin-bottom: 10px; }
.category-list a { color: var(--text-main); display: flex; justify-content: space-between; align-items: center; }
.category-list a:hover, .category-list a.active { color: var(--primary); }

.price-inputs { display: flex; align-items: center; gap: 10px; }
.price-inputs input { width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); }

.shop-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: var(--bg-white); padding: 15px 20px; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
.sidebar-toggle-btn { display: none; }
</style>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/shop.php">Shop</a></li>
            <?php if (!empty($category_filter)): ?>
                <li><a href="#" class="text-primary" style="text-transform: capitalize;"><?= str_replace('-', ' ', $category_filter) ?></a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="container">
    <div class="shop-layout">
        
        <!-- Mobile Toggle Button -->
        <button class="btn btn-outline sidebar-toggle-btn" onclick="document.getElementById('shopSidebar').classList.toggle('active')">
            <i class="fa-solid fa-filter"></i> Filters
        </button>

        <!-- Left Sidebar (Filters) -->
        <aside class="sidebar" id="shopSidebar">
            <form action="<?= SITE_URL ?>/shop.php" method="GET" id="filterForm">
                
                <!-- Category Filter -->
                <div class="filter-box">
                    <h4>Categories</h4>
                    <ul class="category-list">
                        <li>
                            <a href="<?= SITE_URL ?>/shop.php" class="<?= empty($category_filter) ? 'active' : '' ?>">
                                All Products
                            </a>
                        </li>
                        <?php foreach ($all_categories as $cat): ?>
                            <li>
                                <a href="?category=<?= $cat['slug'] ?>" class="<?= $category_filter === $cat['slug'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <!-- Hidden input to preserve category when submitting price form -->
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>" <?= empty($category_filter) ? 'disabled' : '' ?>>
                </div>

                <!-- Price Filter -->
                <div class="filter-box">
                    <h4>Price Range</h4>
                    <div class="price-inputs mb-3">
                        <input type="number" name="min" placeholder="Min ₹" value="<?= $min_price !== null ? $min_price : '' ?>" min="0" step="1">
                        <span>-</span>
                        <input type="number" name="max" placeholder="Max ₹" value="<?= $max_price !== null ? $max_price : '' ?>" min="0" step="1">
                    </div>
                    <!-- Preserve sort param -->
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                    <button type="submit" class="btn btn-primary btn-block btn-sm">Apply Filter</button>
                    <?php if ($min_price !== null || $max_price !== null): ?>
                        <a href="<?= SITE_URL ?>/shop.php<?= !empty($category_filter) ? '?category='.$category_filter : '' ?>" class="btn btn-outline btn-block btn-sm mt-2">Clear Price</a>
                    <?php endif; ?>
                </div>
            </form>
        </aside>

        <!-- Right Content (Products) -->
        <div class="shop-content">
            
            <!-- Shop Header bar -->
            <div class="shop-header">
                <div>
                    <span class="text-muted">Showing <?= count($products) ?> of <?= $total_products ?> products</span>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <label for="sortDropdown" class="text-muted" style="white-space: nowrap;">Sort By:</label>
                    <select id="sortDropdown" class="form-control" style="padding: 8px; width: auto;" onchange="updateSort(this.value)">
                        <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest Arrivals</option>
                        <option value="price-low" <?= $sort === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name-a-z" <?= $sort === 'name-a-z' ? 'selected' : '' ?>>Name: A to Z</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if ($total_products > 0): ?>
                <div class="grid grid-3">
                    <?php foreach ($products as $product): ?>
                        <?= renderShopProductCard($product) ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <?php
                    // Rebuild base URL for pagination links preserving existing filters
                    $query_params = $_GET;
                    ?>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <?php $query_params['page'] = $page - 1; ?>
                            <li><a href="?<?= http_build_query($query_params) ?>" class="page-item"><i class="fa-solid fa-chevron-left"></i></a></li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php $query_params['page'] = $i; ?>
                            <li><a href="?<?= http_build_query($query_params) ?>" class="page-item <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <?php $query_params['page'] = $page + 1; ?>
                            <li><a href="?<?= http_build_query($query_params) ?>" class="page-item"><i class="fa-solid fa-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center" style="padding: 50px 20px; background: var(--bg-white); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                    <i class="fa-solid fa-box-open" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
                    <h3>No products found!</h3>
                    <p class="text-muted">Try adjusting your filters or search criteria.</p>
                    <a href="<?= SITE_URL ?>/shop.php" class="btn btn-primary mt-3">Clear All Filters</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Script to handle Sort Dropdown change
function updateSort(sortValue) {
    // We use URL API to safely modify the current URL parameters
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    // Reset to page 1 when sorting changes
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;

    filterForm.addEventListener('submit', function(event) {
        const minInput = filterForm.querySelector('input[name="min"]');
        const maxInput = filterForm.querySelector('input[name="max"]');
        const minValue = minInput ? minInput.value.trim() : '';
        const maxValue = maxInput ? maxInput.value.trim() : '';

        if ((minValue && !maxValue) || (!minValue && maxValue)) {
            event.preventDefault();

            if (typeof window.showToast === 'function') {
                window.showToast('warning', 'Please enter both minimum and maximum values to search products.');
            } else {
                alert('Please enter both minimum and maximum values to search products.');
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
