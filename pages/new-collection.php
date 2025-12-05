<?php
require_once __DIR__ . '/../config.php';

// Check if variant tables exist
$variant_tables_exist = false;
try {
    $pdo->query("SELECT 1 FROM product_variants LIMIT 1");
    $variant_tables_exist = true;
} catch (PDOException $e) {
    // Tables don't exist yet
}

// Fetch new collection by gender
$stmt_women = $pdo->query("SELECT p.*, c.name as category_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.is_new = 1 AND p.is_active = 1 AND p.gender = 'women'
                     ORDER BY p.created_at DESC LIMIT 8");
$products_women = $stmt_women->fetchAll();

$stmt_men = $pdo->query("SELECT p.*, c.name as category_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.is_new = 1 AND p.is_active = 1 AND p.gender = 'men'
                     ORDER BY p.created_at DESC LIMIT 8");
$products_men = $stmt_men->fetchAll();

$stmt_unisex = $pdo->query("SELECT p.*, c.name as category_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.is_new = 1 AND p.is_active = 1 AND p.gender = 'unisex'
                     ORDER BY p.created_at DESC LIMIT 8");
$products_unisex = $stmt_unisex->fetchAll();

// Get sizes for products if variant tables exist
if ($variant_tables_exist) {
    foreach ([$products_women, $products_men, $products_unisex] as &$product_list) {
        foreach ($product_list as &$product) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT pv.size
                FROM product_variants pv
                WHERE pv.product_id = ? AND pv.stock > 0 AND pv.is_active = 1
                ORDER BY FIELD(pv.size, 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', 'One Size')
            ");
            $stmt->execute([$product['id']]);
            $product['available_sizes'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
} else {
    // Set defaults if tables don't exist
    foreach ([$products_women, $products_men, $products_unisex] as &$product_list) {
        foreach ($product_list as &$product) {
            $product['available_sizes'] = [];
        }
    }
}

$page_title = 'New Arrival 2024 - Baju Pria Wanita Trendy Kekinian | Dorve House';
$page_description = 'Koleksi baju pria wanita terbaru 2024 di Dorve House. Fashion trendy & kekinian: koleksi women, men, unisex. Model trendy & kekinian, update terkini setiap minggu, harga mulai 50rb. Gratis ongkir, COD tersedia, harga spesial new arrival untuk semua.';
$page_keywords = 'koleksi terbaru, baju baru, new arrival, fashion 2024, model terbaru, baju pria, baju wanita, koleksi baru, trending fashion, baju trendy, baju kekinian, baju hits, fashion unisex, dorve house';

// Add JSON-LD schemas (DISABLED - functions not available)
// $json_schemas = [
//     generateOrganizationSchema()
// ];

$all_products = array_merge($products_women, $products_men, $products_unisex);
// if (!empty($all_products)) {
//     $json_schemas[] = generateItemListSchema($all_products, '/pages/new-collection.php');
// }

include __DIR__ . '/../includes/header.php';
?>

<style>
    .collection-hero {
        background: var(--cream);
        padding: 100px 40px;
        text-align: center;
    }

    .collection-hero h1 {
        font-family: 'Playfair Display', serif;
        font-size: 56px;
        margin-bottom: 20px;
    }

    .collection-hero p {
        font-size: 18px;
        color: var(--grey);
        max-width: 600px;
        margin: 0 auto 40px;
        line-height: 1.7;
    }

    .products-container {
        max-width: 1400px;
        margin: 80px auto;
        padding: 0 40px;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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

    .product-image {
        width: 100%;
        aspect-ratio: 3/4;
        object-fit: cover;
        margin-bottom: 20px;
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
        font-size: 20px;
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

    .product-badge {
        position: absolute;
        top: 16px;
        left: 16px;
        background: var(--charcoal);
        color: var(--white);
        padding: 6px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .product-sizes {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 8px;
        margin-bottom: 8px;
    }

    .size-badge {
        padding: 4px 8px;
        border: 1px solid rgba(0,0,0,0.15);
        font-size: 11px;
        font-weight: 500;
        color: var(--grey);
        border-radius: 2px;
    }

    @media (max-width: 768px) {
        .collection-hero h1 {
            font-size: 36px;
        }

        .products-container {
            padding: 40px 24px;
        }

        .product-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
    }
</style>

<div class="collection-hero">
    <h1>New Collection</h1>
    <p>
        Discover our latest curated pieces designed to elevate your wardrobe.
        Each item is crafted with meticulous attention to detail and timeless elegance.
    </p>
</div>

<div class="products-container">
    <?php if (!empty($products_women)): ?>
    <!-- WOMEN SECTION -->
    <div style="margin-bottom: 80px;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 42px; margin-bottom: 10px;">👗 Women Collection</h2>
            <p style="color: var(--grey); font-size: 16px;">Koleksi Fashion Wanita Terbaru</p>
            <a href="/pages/all-products.php?gender=women" style="display: inline-block; margin-top: 15px; color: var(--charcoal); text-decoration: none; font-size: 14px; border-bottom: 2px solid var(--charcoal); padding-bottom: 2px;">Lihat Semua Women →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($products_women as $product): ?>
                <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" class="product-card">
                    <div style="position: relative;">
                        <img src="<?php echo $product['image'] ? UPLOAD_URL . $product['image'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=600'; ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image">
                        <div class="product-badge">New</div>
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

                    <div>
                        <?php if (!empty($product['discount_price']) && $product['discount_price'] > 0): ?>
                            <span class="product-price discount"><?php echo formatPrice($product['price']); ?></span>
                            <span class="product-price"><?php echo formatPrice($product['discount_price']); ?></span>
                        <?php else: ?>
                            <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($products_men)): ?>
    <!-- MEN SECTION -->
    <div style="margin-bottom: 80px;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 42px; margin-bottom: 10px;">👔 Men Collection</h2>
            <p style="color: var(--grey); font-size: 16px;">Koleksi Fashion Pria Terbaru</p>
            <a href="/pages/all-products.php?gender=men" style="display: inline-block; margin-top: 15px; color: var(--charcoal); text-decoration: none; font-size: 14px; border-bottom: 2px solid var(--charcoal); padding-bottom: 2px;">Lihat Semua Men →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($products_men as $product): ?>
                <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" class="product-card">
                    <div style="position: relative;">
                        <img src="<?php echo $product['image'] ? UPLOAD_URL . $product['image'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=600'; ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image">
                        <div class="product-badge">New</div>
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

                    <div>
                        <?php if (!empty($product['discount_price']) && $product['discount_price'] > 0): ?>
                            <span class="product-price discount"><?php echo formatPrice($product['price']); ?></span>
                            <span class="product-price"><?php echo formatPrice($product['discount_price']); ?></span>
                        <?php else: ?>
                            <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($products_unisex)): ?>
    <!-- UNISEX SECTION -->
    <div style="margin-bottom: 80px;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 42px; margin-bottom: 10px;">👫 Unisex Collection</h2>
            <p style="color: var(--grey); font-size: 16px;">Koleksi Fashion Unisex Untuk Semua</p>
            <a href="/pages/all-products.php?gender=unisex" style="display: inline-block; margin-top: 15px; color: var(--charcoal); text-decoration: none; font-size: 14px; border-bottom: 2px solid var(--charcoal); padding-bottom: 2px;">Lihat Semua Unisex →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($products_unisex as $product): ?>
                <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>" class="product-card">
                    <div style="position: relative;">
                        <img src="<?php echo $product['image'] ? UPLOAD_URL . $product['image'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=600'; ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image">
                        <div class="product-badge">New</div>
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

                    <div>
                        <?php if (!empty($product['discount_price']) && $product['discount_price'] > 0): ?>
                            <span class="product-price discount"><?php echo formatPrice($product['price']); ?></span>
                            <span class="product-price"><?php echo formatPrice($product['discount_price']); ?></span>
                        <?php else: ?>
                            <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($products_women) && empty($products_men) && empty($products_unisex)): ?>
        <div style="text-align: center; padding: 80px 20px;">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 20px;">Coming Soon</h2>
            <p style="color: var(--grey); margin-bottom: 40px;">Koleksi baru kami akan segera tersedia. Stay tuned!</p>
            <a href="/pages/all-products.php" style="display: inline-block; padding: 16px 40px; background: var(--charcoal); color: var(--white); text-decoration: none; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Explore All Products</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
