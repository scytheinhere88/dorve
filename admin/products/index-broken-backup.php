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
            (SELECT SUM(vs.stock)
             FROM product_variants pv
             JOIN variant_stock vs ON pv.id = vs.variant_id
             WHERE pv.product_id = p.id),
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/admin-style.css">
    <style>
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .filter-group label {
            font-weight: 500;
            margin: 0;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #DEE2E6;
            border-radius: 6px;
            font-size: 14px;
        }

        .gender-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid #E9ECEF;
            padding-bottom: 0;
        }

        .gender-tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #6C757D;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }

        .gender-tab.active {
            color: #1A1A1A;
            border-bottom-color: #1A1A1A;
        }

        .gender-tab:hover {
            color: #1A1A1A;
        }

        .gender-section {
            display: none;
        }

        .gender-section.active {
            display: block;
        }

        .category-group {
            margin-bottom: 40px;
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #E9ECEF;
        }

        .category-title {
            font-size: 20px;
            font-weight: 600;
        }

        .category-count {
            color: #6C757D;
            font-size: 14px;
        }

        .product-list {
            display: grid;
            gap: 16px;
        }

        .product-item {
            background: white;
            border-radius: 8px;
            padding: 16px;
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 16px;
            align-items: center;
            transition: all 0.3s;
            border: 1px solid #E9ECEF;
        }

        .product-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .product-item.out-of-stock {
            opacity: 0.6;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }

        .product-item.out-of-stock .product-image {
            filter: grayscale(100%);
        }

        .product-info h3 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 600;
        }

        .product-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: #6C757D;
        }

        .stock-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .stock-badge.in-stock {
            background: #D4EDDA;
            color: #155724;
        }

        .stock-badge.out-of-stock {
            background: #F8D7DA;
            color: #721C24;
        }

        .product-actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .empty-category {
            text-align: center;
            padding: 40px;
            color: #6C757D;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-header.php'; ?>

    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

        <div class="admin-content">
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
                    <a href="/admin/products/index.php" class="btn btn-secondary btn-sm">Reset Filter</a>
                <?php endif; ?>
            </form>

            <!-- Gender Tabs -->
            <div class="gender-tabs">
                <button class="gender-tab active" data-gender="all">Semua (<?php echo count($products); ?>)</button>
                <button class="gender-tab" data-gender="women">Women (<?php echo count($products_by_gender['women']); ?>)</button>
                <button class="gender-tab" data-gender="men">Men (<?php echo count($products_by_gender['men']); ?>)</button>
                <button class="gender-tab" data-gender="unisex">Unisex (<?php echo count($products_by_gender['unisex']); ?>)</button>
            </div>

            <!-- All Products Section -->
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
                                <div class="product-item <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                                    <img src="<?php echo $product['image_path'] ? UPLOAD_URL . $product['image_path'] : 'https://via.placeholder.com/80'; ?>"
                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">

                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="product-meta">
                                            <span><?php echo formatPrice($product['price']); ?></span>
                                            <span><?php echo ucfirst($product['gender']); ?></span>
                                            <span><?php echo $product['variant_count']; ?> varian</span>
                                            <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                                <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="product-actions">
                                        <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Women Section -->
            <div class="gender-section" data-section="women">
                <?php
                $women_by_category = [];
                foreach ($products_by_gender['women'] as $product) {
                    $cat_name = $product['category_name'] ?? 'Uncategorized';
                    if (!isset($women_by_category[$cat_name])) {
                        $women_by_category[$cat_name] = [];
                    }
                    $women_by_category[$cat_name][] = $product;
                }

                if (empty($women_by_category)):
                ?>
                    <div class="empty-category">Belum ada produk Women</div>
                <?php else:
                    foreach ($women_by_category as $cat_name => $cat_products):
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
                                <div class="product-item <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                                    <img src="<?php echo $product['image_path'] ? UPLOAD_URL . $product['image_path'] : 'https://via.placeholder.com/80'; ?>"
                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">

                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="product-meta">
                                            <span><?php echo formatPrice($product['price']); ?></span>
                                            <span><?php echo $product['variant_count']; ?> varian</span>
                                            <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                                <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="product-actions">
                                        <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>

            <!-- Men Section -->
            <div class="gender-section" data-section="men">
                <?php
                $men_by_category = [];
                foreach ($products_by_gender['men'] as $product) {
                    $cat_name = $product['category_name'] ?? 'Uncategorized';
                    if (!isset($men_by_category[$cat_name])) {
                        $men_by_category[$cat_name] = [];
                    }
                    $men_by_category[$cat_name][] = $product;
                }

                if (empty($men_by_category)):
                ?>
                    <div class="empty-category">Belum ada produk Men</div>
                <?php else:
                    foreach ($men_by_category as $cat_name => $cat_products):
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
                                <div class="product-item <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                                    <img src="<?php echo $product['image_path'] ? UPLOAD_URL . $product['image_path'] : 'https://via.placeholder.com/80'; ?>"
                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">

                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="product-meta">
                                            <span><?php echo formatPrice($product['price']); ?></span>
                                            <span><?php echo $product['variant_count']; ?> varian</span>
                                            <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                                <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="product-actions">
                                        <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>

            <!-- Unisex Section -->
            <div class="gender-section" data-section="unisex">
                <?php
                $unisex_by_category = [];
                foreach ($products_by_gender['unisex'] as $product) {
                    $cat_name = $product['category_name'] ?? 'Uncategorized';
                    if (!isset($unisex_by_category[$cat_name])) {
                        $unisex_by_category[$cat_name] = [];
                    }
                    $unisex_by_category[$cat_name][] = $product;
                }

                if (empty($unisex_by_category)):
                ?>
                    <div class="empty-category">Belum ada produk Unisex</div>
                <?php else:
                    foreach ($unisex_by_category as $cat_name => $cat_products):
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
                                <div class="product-item <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                                    <img src="<?php echo $product['image_path'] ? UPLOAD_URL . $product['image_path'] : 'https://via.placeholder.com/80'; ?>"
                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">

                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="product-meta">
                                            <span><?php echo formatPrice($product['price']); ?></span>
                                            <span><?php echo $product['variant_count']; ?> varian</span>
                                            <span class="stock-badge <?php echo $is_out_of_stock ? 'out-of-stock' : 'in-stock'; ?>">
                                                <?php echo $is_out_of_stock ? 'Out of Stock' : $product['total_stock'] . ' stock'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="product-actions">
                                        <a href="/admin/products/edit.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>

        </div>
    </div>

    <script>
        // Gender tabs functionality
        const tabs = document.querySelectorAll('.gender-tab');
        const sections = document.querySelectorAll('.gender-section');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const gender = tab.dataset.gender;

                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Update active section
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
</body>
</html>
