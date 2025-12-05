<?php
require_once __DIR__ . '/../config.php';

// Check if variant tables exist
$variant_tables_exist = false;
try {
    $pdo->query("SELECT 1 FROM product_variants LIMIT 1");
    $pdo->query("SELECT 1 FROM variant_stock LIMIT 1");
    $variant_tables_exist = true;
} catch (PDOException $e) {
    // Tables don't exist yet
}

$category_filter = $_GET['category'] ?? '';
$gender_filter = $_GET['gender'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$size_filter = $_GET['size'] ?? '';
$color_filter = $_GET['color'] ?? '';

$where_conditions = ["p.status = 'published'"];
$params = [];

if ($category_filter) {
    $where_conditions[] = "c.slug = ?";
    $params[] = $category_filter;
}

if ($gender_filter) {
    $where_conditions[] = "p.gender = ?";
    $params[] = $gender_filter;
}

if ($min_price) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
}

if ($size_filter && $variant_tables_exist) {
    $where_conditions[] = "EXISTS (SELECT 1 FROM product_variants pv JOIN variant_stock vs ON pv.id = vs.variant_id WHERE pv.product_id = p.id AND vs.size = ?)";
    $params[] = $size_filter;
}

if ($color_filter && $variant_tables_exist) {
    $where_conditions[] = "EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id AND pv.color LIKE ?)";
    $params[] = "%{$color_filter}%";
}

$where_sql = implode(' AND ', $where_conditions);

if ($sort === 'price_low') {
    $order_by = 'p.price ASC';
} elseif ($sort === 'price_high') {
    $order_by = 'p.price DESC';
} elseif ($sort === 'bestseller') {
    $order_by = 'p.is_best_seller DESC, p.created_at DESC';
} else {
    $order_by = 'p.created_at DESC';
}

// Simple query without variant joins
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE {$where_sql}
        ORDER BY {$order_by}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get available sizes and stock for each product (only if tables exist)
if ($variant_tables_exist) {
    foreach ($products as &$product) {
        // Get total stock
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(vs.stock), 0) as total_stock
            FROM product_variants pv
            LEFT JOIN variant_stock vs ON pv.id = vs.variant_id
            WHERE pv.product_id = ?
        ");
        $stmt->execute([$product['id']]);
        $result = $stmt->fetch();
        $product['total_stock'] = $result['total_stock'] ?? 0;

        // Get available sizes
        $stmt = $pdo->prepare("
            SELECT DISTINCT vs.size
            FROM product_variants pv
            JOIN variant_stock vs ON pv.id = vs.variant_id
            WHERE pv.product_id = ? AND vs.stock > 0
            ORDER BY FIELD(vs.size, 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', 'One Size')
        ");
        $stmt->execute([$product['id']]);
        $product['available_sizes'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} else {
    // Tables don't exist, set defaults
    foreach ($products as &$product) {
        $product['total_stock'] = 1;
        $product['available_sizes'] = [];
    }
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get available sizes only if tables exist
if ($variant_tables_exist) {
    $stmt = $pdo->query("SELECT DISTINCT size FROM variant_stock WHERE size IS NOT NULL ORDER BY FIELD(size, 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL')");
    $sizes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $sizes = [];
}

// Sizes already fetched above based on variant_tables_exist check

$category_data = null;
if ($category_filter) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$category_filter]);
    $category_data = $stmt->fetch();
}

// Auto-generate SEO (DISABLED)
if ($category_data) {
    $page_title = ($category_data['name'] ?? 'Produk') . ' Terbaru | Dorve House';
    $page_description = 'Belanja ' . ($category_data['name'] ?? 'produk') . ' trendy di Dorve House. Harga terjangkau, kualitas premium.';
    // $page_title = generateCategorySeoTitle($category_data);
    // $page_description = generateCategorySeoDescription($category_data);
    $page_keywords = strtolower($category_data['name']) . " pria, {$category_data['name']} wanita, jual {$category_data['name']}, beli {$category_data['name']} online, {$category_data['name']} murah, {$category_data['name']} terbaru, {$category_data['name']} trendy, {$category_data['name']} kekinian, dorve house";
    $og_image = $category_data['image'] ?? null;
} else {
    $page_title = 'Baju Pria Wanita Online Trendy & Kekinian 2024 | Dorve House';
    $page_description = 'Belanja baju pria wanita online trendy & kekinian di Dorve House. Koleksi lengkap untuk semua: fashion pria, fashion wanita, fashion unisex. Harga termurah mulai 50rb, gratis ongkir, COD, 100% original, kualitas premium terpercaya.';
    $page_keywords = 'baju pria online, baju wanita online, fashion pria, fashion wanita, fashion unisex, baju trendy, baju kekinian, baju online, toko baju online, pusat fashion, dorve house';
}

// Add JSON-LD schemas (DISABLED)
$json_schemas = [];
// $json_schemas = [
//     generateOrganizationSchema()
// ];
// if (!empty($products)) {
//     $json_schemas[] = generateItemListSchema($products, $_SERVER['REQUEST_URI']);
// }
// if ($category_data) {
//     $json_schemas[] = generateBreadcrumbSchema([
//         'Home' => '/',
//         $category_data['name'] => '/pages/all-products.php?category=' . $category_data['slug']
//     ]);
// }

include __DIR__ . '/../includes/header.php';
?>

<style>
    .page-hero {
        background: var(--cream);
        padding: 80px 40px;
        text-align: center;
    }

    .page-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 48px;
        margin-bottom: 16px;
    }

    .page-hero p {
        color: var(--grey);
        font-size: 16px;
    }

    .products-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 60px;
        padding: 80px 40px;
        max-width: 1600px;
        margin: 0 auto;
    }

    .filters-sidebar {
        position: sticky;
        top: 120px;
        height: fit-content;
    }

    .filter-section {
        margin-bottom: 40px;
        padding-bottom: 40px;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .filter-section:last-child {
        border-bottom: none;
    }

    .filter-title {
        font-family: 'Playfair Display', serif;
        font-size: 18px;
        margin-bottom: 20px;
    }

    .filter-option {
        margin-bottom: 12px;
    }

    .filter-option a {
        color: var(--grey);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s;
    }

    .filter-option a:hover,
    .filter-option a.active {
        color: var(--charcoal);
        font-weight: 500;
    }

    .filter-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid rgba(0,0,0,0.15);
        border-radius: 4px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        margin-bottom: 8px;
    }

    .products-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .products-count {
        color: var(--grey);
        font-size: 14px;
    }

    .sort-select {
        padding: 10px 16px;
        border: 1px solid rgba(0,0,0,0.15);
        border-radius: 4px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 40px;
    }

    .product-card {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform 0.3s;
    }

    .product-card:hover {
        transform: translateY(-8px);
    }

    .product-card.out-of-stock {
        opacity: 0.6;
    }

    .product-card.out-of-stock:hover {
        transform: none;
        cursor: not-allowed;
    }

    .product-image {
        width: 100%;
        aspect-ratio: 3/4;
        object-fit: cover;
        margin-bottom: 16px;
    }

    .product-card.out-of-stock .product-image {
        filter: grayscale(100%);
        opacity: 0.5;
        background: var(--cream);
    }

    .product-category {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--grey);
        margin-bottom: 8px;
    }

    .product-name {
        font-family: 'Playfair Display', serif;
        font-size: 18px;
        margin-bottom: 12px;
        font-weight: 500;
    }

    .product-price {
        font-size: 16px;
        font-weight: 600;
    }

    .product-price.discount {
        color: var(--grey);
        text-decoration: line-through;
        margin-right: 8px;
    }

    .product-sizes {
        display: flex;
        gap: 6px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .size-badge {
        display: inline-block;
        padding: 4px 8px;
        font-size: 10px;
        border: 1px solid #DEE2E6;
        border-radius: 4px;
        color: #495057;
        background: white;
        font-weight: 500;
        text-transform: uppercase;
    }

    .product-card.out-of-stock .size-badge {
        opacity: 0.5;
    }

    .product-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: var(--charcoal);
        color: var(--white);
        padding: 6px 12px;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        z-index: 1;
    }

    .product-badge.out-of-stock {
        background: #6C757D;
        color: white;
        font-weight: 600;
    }

    .no-products {
        text-align: center;
        padding: 80px 20px;
        color: var(--grey);
    }

    .gender-toggle-center {
        background: var(--white);
        padding: 60px 40px;
        text-align: center;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .gender-tabs-center {
        display: inline-flex;
        gap: 0;
        border: 2px solid var(--charcoal);
        overflow: hidden;
    }

    .gender-tab {
        padding: 14px 40px;
        background: var(--white);
        color: var(--charcoal);
        text-decoration: none;
        font-weight: 500;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 13px;
        transition: all 0.3s;
        border-right: 2px solid var(--charcoal);
    }

    .gender-tab:last-child {
        border-right: none;
    }

    .gender-tab.active {
        background: var(--charcoal);
        color: var(--white);
    }

    .gender-tab:hover:not(.active) {
        background: var(--cream);
    }

    @media (max-width: 968px) {
        .products-layout {
            grid-template-columns: 1fr;
            gap: 40px;
            padding: 40px 24px;
        }

        .filters-sidebar {
            position: static;
        }

        .product-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .gender-tabs-center {
            flex-wrap: wrap;
            width: 100%;
        }

        .gender-tab {
            flex: 1;
            padding: 12px 20px;
            font-size: 11px;
        }
    }
</style>

<div class="page-hero">
    <h1>All Products</h1>
    <p>Discover our complete collection of premium fashion</p>
</div>

<!-- Centered Gender Toggle -->
<div class="gender-toggle-center">
    <div class="gender-tabs-center">
        <a href="?<?php echo http_build_query(array_merge(array_diff_key($_GET, ['gender' => '']), [])); ?>"
           class="gender-tab <?php echo !$gender_filter ? 'active' : ''; ?>">
            All
        </a>
        <a href="?<?php echo http_build_query(array_merge(array_diff_key($_GET, ['gender' => '']), ['gender' => 'women'])); ?>"
           class="gender-tab <?php echo $gender_filter === 'women' ? 'active' : ''; ?>">
            Women
        </a>
        <a href="?<?php echo http_build_query(array_merge(array_diff_key($_GET, ['gender' => '']), ['gender' => 'men'])); ?>"
           class="gender-tab <?php echo $gender_filter === 'men' ? 'active' : ''; ?>">
            Men
        </a>
        <a href="?<?php echo http_build_query(array_merge(array_diff_key($_GET, ['gender' => '']), ['gender' => 'unisex'])); ?>"
           class="gender-tab <?php echo $gender_filter === 'unisex' ? 'active' : ''; ?>">
            Unisex
        </a>
    </div>
</div>

<div class="products-layout">
    <aside class="filters-sidebar">
        <div class="filter-section">
            <h3 class="filter-title">Gender</h3>
            <div class="filter-option">
                <a href="?<?php echo http_build_query(array_diff_key($_GET, ['gender' => ''])); ?>"
                   class="<?php echo empty($gender_filter) ? 'active' : ''; ?>">Semua Gender</a>
            </div>
            <div class="filter-option">
                <a href="?gender=women&<?php echo http_build_query(array_diff_key($_GET, ['gender' => ''])); ?>"
                   class="<?php echo $gender_filter === 'women' ? 'active' : ''; ?>">👗 Women (Wanita)</a>
            </div>
            <div class="filter-option">
                <a href="?gender=men&<?php echo http_build_query(array_diff_key($_GET, ['gender' => ''])); ?>"
                   class="<?php echo $gender_filter === 'men' ? 'active' : ''; ?>">👔 Men (Pria)</a>
            </div>
            <div class="filter-option">
                <a href="?gender=unisex&<?php echo http_build_query(array_diff_key($_GET, ['gender' => ''])); ?>"
                   class="<?php echo $gender_filter === 'unisex' ? 'active' : ''; ?>">👫 Unisex (Pria & Wanita)</a>
            </div>
        </div>

        <div class="filter-section">
            <h3 class="filter-title">Categories</h3>
            <div class="filter-option">
                <a href="?<?php echo http_build_query(array_diff_key($_GET, ['category' => ''])); ?>"
                   class="<?php echo empty($category_filter) ? 'active' : ''; ?>">All Categories</a>
            </div>
            <?php foreach ($categories as $category): ?>
                <div class="filter-option">
                    <a href="?category=<?php echo $category['slug']; ?>&<?php echo http_build_query(array_diff_key($_GET, ['category' => ''])); ?>"
                       class="<?php echo $category_filter === $category['slug'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="filter-section">
            <h3 class="filter-title">Price Range</h3>
            <form method="GET">
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key !== 'min_price' && $key !== 'max_price'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <input type="number" name="min_price" placeholder="Min" class="filter-input" value="<?php echo htmlspecialchars($min_price); ?>">
                <input type="number" name="max_price" placeholder="Max" class="filter-input" value="<?php echo htmlspecialchars($max_price); ?>">
                <button type="submit" style="width: 100%; padding: 10px; background: var(--charcoal); color: var(--white); border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Apply</button>
            </form>
        </div>

        <?php if (!empty($sizes)): ?>
        <div class="filter-section">
            <h3 class="filter-title">Size</h3>
            <div class="filter-option">
                <a href="?<?php echo http_build_query(array_diff_key($_GET, ['size' => ''])); ?>"
                   class="<?php echo empty($size_filter) ? 'active' : ''; ?>">All Sizes</a>
            </div>
            <?php foreach ($sizes as $size): ?>
                <div class="filter-option">
                    <a href="?size=<?php echo $size; ?>&<?php echo http_build_query(array_diff_key($_GET, ['size' => ''])); ?>"
                       class="<?php echo $size_filter === $size ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($size); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </aside>

    <div>
        <div class="products-header">
            <div class="products-count">
                <?php echo count($products); ?> Products
            </div>

            <form method="GET" id="sortForm">
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key !== 'sort'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <select name="sort" class="sort-select" onchange="document.getElementById('sortForm').submit()">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="bestseller" <?php echo $sort === 'bestseller' ? 'selected' : ''; ?>>Best Sellers</option>
                </select>
            </form>
        </div>

        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>No products found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product):
                    $is_out_of_stock = ($product['total_stock'] <= 0);
                ?>
                    <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>"
                       class="product-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                        <div style="position: relative;">
                            <img src="<?php echo $product['image'] ? UPLOAD_URL . $product['image'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=600'; ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-image">
                            <?php if ($is_out_of_stock): ?>
                                <div class="product-badge out-of-stock">Out of Stock</div>
                            <?php elseif ($product['is_new_collection']): ?>
                                <div class="product-badge">New</div>
                            <?php elseif ($product['is_best_seller']): ?>
                                <div class="product-badge" style="background: var(--latte); color: var(--charcoal);">Bestseller</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>

                        <?php if (!empty($product['available_sizes'])): ?>
                            <div class="product-sizes">
                                <?php foreach ($product['available_sizes'] as $size): ?>
                                    <span class="size-badge"><?php echo htmlspecialchars($size); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 8px;">
                            <?php if ($product['discount_percent'] > 0): ?>
                                <span class="product-price discount"><?php echo formatPrice($product['price']); ?></span>
                                <span class="product-price"><?php echo formatPrice(calculateDiscount($product['price'], $product['discount_percent'])); ?></span>
                            <?php else: ?>
                                <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
