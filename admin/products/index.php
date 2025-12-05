<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Get filter parameters
$gender_filter = $_GET['gender'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if ($gender_filter) {
    $where_conditions[] = "p.gender = ?";
    $params[] = $gender_filter;
}

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.slug LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_sql = implode(' AND ', $where_conditions);

// Get products with stock info
$sql = "SELECT p.*, c.name as category_name,
        COALESCE(
            (SELECT SUM(stock)
             FROM product_variants
             WHERE product_id = p.id),
        0) as total_stock,
        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE {$where_sql}
        ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Group products by gender
$products_by_gender = [
    'women' => [],
    'men' => [],
    'unisex' => []
];

foreach ($products as $product) {
    $products_by_gender[$product['gender']][] = $product;
}

$page_title = 'Kelola Produk - Admin';

// Include header
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
/* Page-specific styles */
.filters {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    border: 1px solid #E5E7EB;
}
.filter-group {
    display: flex;
    gap: 8px;
    align-items: center;
}
.gender-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #E5E7EB;
}
.gender-tab {
    padding: 12px 24px;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: #6B7280;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}
.gender-tab.active {
    color: #3B82F6;
    border-bottom-color: #3B82F6;
}
.gender-section {
    display: none;
}
.gender-section.active {
    display: block;
}
.product-list {
    display: grid;
    gap: 16px;
}
.product-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: grid;
    grid-template-columns: 100px 1fr auto;
    gap: 20px;
    align-items: center;
    border: 1px solid #E5E7EB;
    transition: all 0.2s;
}
.product-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.product-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}
.product-info h3 {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 600;
}
.product-meta {
    display: flex;
    gap: 16px;
    font-size: 14px;
    color: #6B7280;
    flex-wrap: wrap;
}
.product-actions {
    display: flex;
    gap: 8px;
}
.stock-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.stock-badge.in-stock {
    background: #ECFDF5;
    color: #059669;
}
.stock-badge.out-of-stock {
    background: #FEF2F2;
    color: #DC2626;
}
.category-group {
    margin-bottom: 32px;
}
.category-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E5E7EB;
}
.category-title {
    font-size: 18px;
    font-weight: 700;
}
.category-count {
    color: #6B7280;
}
</style>

<div class="header">
    <h1>Kelola Produk</h1>
    <a href="/admin/products/add.php" class="btn btn-primary">+ Tambah Produk</a>
</div>

<!-- Filters -->
<form method="GET" class="filters">
    <div class="filter-group">
        <label>Search:</label>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari produk...">
    </div>
    <div class="filter-group">
        <label>Gender:</label>
        <select name="gender" onchange="this.form.submit()">
            <option value="">Semua</option>
            <option value="women" <?php echo $gender_filter === 'women' ? 'selected' : ''; ?>>Women</option>
            <option value="men" <?php echo $gender_filter === 'men' ? 'selected' : ''; ?>>Men</option>
            <option value="unisex" <?php echo $gender_filter === 'unisex' ? 'selected' : ''; ?>>Unisex</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Kategori:</label>
        <select name="category" onchange="this.form.submit()">
            <option value="">Semua</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($search || $gender_filter || $category_filter): ?>
        <a href="/admin/products/index.php" class="btn btn-secondary">Reset</a>
    <?php endif; ?>
</form>

<!-- Gender Tabs -->
<div class="gender-tabs">
    <button type="button" class="gender-tab active" data-gender="all">Semua (<?php echo count($products); ?>)</button>
    <button type="button" class="gender-tab" data-gender="women">Women (<?php echo count($products_by_gender['women']); ?>)</button>
    <button type="button" class="gender-tab" data-gender="men">Men (<?php echo count($products_by_gender['men']); ?>)</button>
    <button type="button" class="gender-tab" data-gender="unisex">Unisex (<?php echo count($products_by_gender['unisex']); ?>)</button>
</div>

<!-- All Products -->
<div class="gender-section active" data-section="all">
    <?php
    $products_by_category = [];
    foreach ($products as $product) {
        $cat_name = $product['category_name'] ?? 'Uncategorized';
        if (!isset($products_by_category[$cat_name])) {
            $products_by_category[$cat_name] = [];
        }
        $products_by_category[$cat_name][] = $product;
    }

    foreach ($products_by_category as $cat_name => $cat_products):
    ?>
        <div class="category-group">
            <div class="category-header">
                <div class="category-title"><?php echo htmlspecialchars($cat_name); ?></div>
                <div class="category-count"><?php echo count($cat_products); ?> produk</div>
            </div>
            <div class="product-list">
                <?php foreach ($cat_products as $product):
                    $is_out_of_stock = ($product['total_stock'] <= 0);
                ?>
                    <div class="product-item">
                        <img src="<?php echo $product['image'] ? $product['image'] : 'https://via.placeholder.com/100'; ?>"
                             class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-meta">
                                <span><strong>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></strong></span>
                                <span><?php echo ucfirst($product['gender']); ?></span>
                                <span><?php echo $product['variant_count']; ?> varian</span>
                                <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                    <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="product-actions">
                            <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">Edit</a>
                            <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Women Section -->
<div class="gender-section" data-section="women">
    <?php if (!empty($products_by_gender['women'])): ?>
        <div class="product-list">
            <?php foreach ($products_by_gender['women'] as $product): $is_out_of_stock = ($product['total_stock'] <= 0); ?>
                <div class="product-item">
                    <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/100'; ?>" class="product-image" alt="">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-meta">
                            <span><strong>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></strong></span>
                            <span><?php echo $product['variant_count']; ?> varian</span>
                            <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="product-actions">
                        <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#6B7280;padding:40px;">Tidak ada produk women</p>
    <?php endif; ?>
</div>

<!-- Men Section -->
<div class="gender-section" data-section="men">
    <?php if (!empty($products_by_gender['men'])): ?>
        <div class="product-list">
            <?php foreach ($products_by_gender['men'] as $product): $is_out_of_stock = ($product['total_stock'] <= 0); ?>
                <div class="product-item">
                    <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/100'; ?>" class="product-image" alt="">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-meta">
                            <span><strong>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></strong></span>
                            <span><?php echo $product['variant_count']; ?> varian</span>
                            <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="product-actions">
                        <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#6B7280;padding:40px;">Tidak ada produk men</p>
    <?php endif; ?>
</div>

<!-- Unisex Section -->
<div class="gender-section" data-section="unisex">
    <?php if (!empty($products_by_gender['unisex'])): ?>
        <div class="product-list">
            <?php foreach ($products_by_gender['unisex'] as $product): $is_out_of_stock = ($product['total_stock'] <= 0); ?>
                <div class="product-item">
                    <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/100'; ?>" class="product-image" alt="">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-meta">
                            <span><strong>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></strong></span>
                            <span><?php echo $product['variant_count']; ?> varian</span>
                            <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="product-actions">
                        <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#6B7280;padding:40px;">Tidak ada produk unisex</p>
    <?php endif; ?>
</div>

<script>
// Gender tabs functionality
const tabs = document.querySelectorAll('.gender-tab');
const sections = document.querySelectorAll('.gender-section');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const gender = tab.dataset.gender;
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        sections.forEach(section => {
            if (section.dataset.section === gender) {
                section.classList.add('active');
            } else {
                section.classList.remove('active');
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
