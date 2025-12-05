<?php
require_once __DIR__ . '/config.php';

$stmt = $pdo->query("SELECT p.*, pi.image_path, c.name as category_name,
                     COALESCE(
                         (SELECT SUM(vs.stock)
                          FROM product_variants pv
                          JOIN variant_stock vs ON pv.id = vs.variant_id
                          WHERE pv.product_id = p.id),
                     0) as total_stock
                     FROM products p
                     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.is_new_collection = 1 AND p.status = 'published'
                     ORDER BY p.created_at DESC
                     LIMIT 8");
$new_arrivals = $stmt->fetchAll();

$stmt = $pdo->query("SELECT p.*, pi.image_path, c.name as category_name,
                     COALESCE(
                         (SELECT SUM(vs.stock)
                          FROM product_variants pv
                          JOIN variant_stock vs ON pv.id = vs.variant_id
                          WHERE pv.product_id = p.id),
                     0) as total_stock
                     FROM products p
                     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.is_best_seller = 1 AND p.status = 'published'
                     ORDER BY p.created_at DESC
                     LIMIT 8");
$best_sellers = $stmt->fetchAll();

// Get available sizes for each product
foreach ($new_arrivals as &$product) {
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

foreach ($best_sellers as &$product) {
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

$stmt = $pdo->query("SELECT * FROM categories WHERE is_featured = 1 ORDER BY sort_order ASC, name ASC");
$featured_categories = $stmt->fetchAll();

$page_title = 'Dorve House - Baju Pria Wanita Trendy Termurah | Fashion Online #1';
$page_description = 'Pusat fashion pria wanita terpercaya Indonesia. Koleksi lengkap: baju pria, baju wanita, fashion unisex. Harga termurah mulai 50rb, gratis ongkir, COD tersedia. 100% original, kualitas premium, model trendy & kekinian 2024.';
$page_keywords = 'baju pria, baju wanita, fashion pria, fashion wanita, baju trendy, baju kekinian, baju online, toko baju online, pusat fashion, fashion termurah, baju online cod, gratis ongkir, dorve house';
$og_type = 'website';

// Add JSON-LD schemas (DISABLED)
$json_schemas = [];
// $json_schemas = [
//     generateOrganizationSchema(),
//     generateWebsiteSchema()
// ];
// if (!empty($new_arrivals)) {
//     $json_schemas[] = generateItemListSchema($new_arrivals, '/index.php');
// }

include __DIR__ . '/includes/header.php';
?>

<style>
    .hero-slider {
        position: relative;
        height: 80vh;
        min-height: 600px;
        overflow: hidden;
    }

    .hero-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1s ease;
        background-size: cover;
        background-position: center;
    }

    .hero-slide.active {
        opacity: 1;
    }

    .hero-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: var(--white);
        z-index: 10;
        max-width: 800px;
        padding: 40px;
    }

    .hero-title {
        font-family: 'Playfair Display', serif;
        font-size: 64px;
        font-weight: 600;
        margin-bottom: 20px;
        line-height: 1.1;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }

    .hero-subtitle {
        font-size: 18px;
        margin-bottom: 40px;
        letter-spacing: 1px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    .hero-btn {
        display: inline-block;
        padding: 16px 48px;
        background: var(--white);
        color: var(--charcoal);
        text-decoration: none;
        font-weight: 500;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 14px;
        transition: all 0.3s;
    }

    .hero-btn:hover {
        background: var(--latte);
        transform: translateY(-2px);
    }

    .section {
        padding: 100px 0;
    }

    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        font-weight: 500;
        margin-bottom: 16px;
    }

    .section-subtitle {
        color: var(--grey);
        font-size: 16px;
        letter-spacing: 0.5px;
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
        margin-bottom: 20px;
        background: var(--cream);
    }

    .product-card.out-of-stock .product-image {
        filter: grayscale(100%);
        opacity: 0.5;
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
        top: 16px;
        left: 16px;
        background: var(--charcoal);
        color: var(--white);
        padding: 6px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        z-index: 1;
    }

    .product-badge.out-of-stock {
        background: #6C757D;
        color: white;
        font-weight: 600;
    }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
    }

    .category-card {
        position: relative;
        height: 350px;
        background: var(--cream);
        overflow: hidden;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s;
    }

    .category-card:hover {
        transform: scale(1.05);
    }

    .category-name {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        color: var(--charcoal);
        z-index: 10;
        text-transform: capitalize;
    }

    .brand-story {
        background: var(--cream);
        padding: 100px 40px;
        text-align: center;
    }

    .brand-story-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .brand-story h2 {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        margin-bottom: 30px;
    }

    .brand-story p {
        font-size: 16px;
        line-height: 1.8;
        color: var(--grey);
        margin-bottom: 40px;
    }

    .seo-content {
        max-width: 1000px;
        margin: 0 auto;
        padding: 80px 40px;
        line-height: 1.8;
        color: var(--grey);
    }

    .seo-content h2 {
        font-family: 'Playfair Display', serif;
        color: var(--charcoal);
        font-size: 32px;
        margin: 40px 0 20px;
    }

    .seo-content h3 {
        font-family: 'Playfair Display', serif;
        color: var(--charcoal);
        font-size: 24px;
        margin: 30px 0 16px;
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 40px;
        }

        .section-title {
            font-size: 32px;
        }

        .product-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .category-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .category-card {
            height: 250px;
        }
    }
</style>

<div class="hero-slider">
    <div class="hero-slide active" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=1920') center/cover;">
        <div class="hero-content">
            <h1 class="hero-title">New Spring Collection</h1>
            <p class="hero-subtitle">Discover our latest designs crafted with elegance and precision</p>
            <a href="/pages/new-collection.php" class="hero-btn">Explore Now</a>
        </div>
    </div>

    <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.pexels.com/photos/1055691/pexels-photo-1055691.jpeg?auto=compress&cs=tinysrgb&w=1920') center/cover;">
        <div class="hero-content">
            <h1 class="hero-title">Timeless Elegance</h1>
            <p class="hero-subtitle">Premium quality pieces for the modern woman</p>
            <a href="/pages/all-products.php" class="hero-btn">Shop All</a>
        </div>
    </div>

    <div class="hero-slide" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.pexels.com/photos/1536619/pexels-photo-1536619.jpeg?auto=compress&cs=tinysrgb&w=1920') center/cover;">
        <div class="hero-content">
            <h1 class="hero-title">Crafted With Love</h1>
            <p class="hero-subtitle">Every piece tells a story of dedication and artistry</p>
            <a href="/pages/our-story.php" class="hero-btn">Our Story</a>
        </div>
    </div>
</div>

<?php if (!empty($new_arrivals)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">New Arrivals</h2>
            <p class="section-subtitle">Fresh styles just landed</p>
        </div>

        <div class="product-grid">
            <?php foreach ($new_arrivals as $product):
                $is_out_of_stock = ($product['total_stock'] <= 0);
            ?>
                <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>"
                   class="product-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                    <div style="position: relative;">
                        <img src="<?php echo $product['image_path'] ? UPLOAD_URL . $product['image_path'] : 'https://images.pexels.com/photos/1926769/pexels-photo-1926769.jpeg?auto=compress&cs=tinysrgb&w=600'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <?php if ($is_out_of_stock): ?>
                            <div class="product-badge out-of-stock">Out of Stock</div>
                        <?php elseif ($product['is_new_collection']): ?>
                            <div class="product-badge">New</div>
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

        <div style="text-align: center; margin-top: 60px;">
            <a href="/pages/new-collection.php" class="hero-btn">View All New Arrivals</a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section" style="background: var(--cream);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Shop By Category</h2>
            <p class="section-subtitle">Find your perfect style</p>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <a href="/pages/all-products.php?category=<?php echo $category['slug']; ?>" class="category-card">
                    <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($best_sellers)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Best Sellers</h2>
            <p class="section-subtitle">Customer favorites</p>
        </div>

        <div class="product-grid">
            <?php foreach ($best_sellers as $product):
                $is_out_of_stock = ($product['total_stock'] <= 0);
            ?>
                <a href="/pages/product-detail.php?slug=<?php echo $product['slug']; ?>"
                   class="product-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                    <div style="position: relative;">
                        <img src="<?php echo $product['image_path'] ? UPLOAD_URL . $product['image_path'] : 'https://images.pexels.com/photos/1055691/pexels-photo-1055691.jpeg?auto=compress&cs=tinysrgb&w=600'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <?php if ($is_out_of_stock): ?>
                            <div class="product-badge out-of-stock">Out of Stock</div>
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
    </div>
</section>
<?php endif; ?>

<div class="brand-story">
    <div class="brand-story-content">
        <h2>The Dorve Story</h2>
        <p>
            At Dorve, we believe that fashion is more than just clothing—it's a form of self-expression,
            a celebration of individuality, and a testament to craftsmanship. Every piece in our collection
            is thoughtfully designed and meticulously crafted to empower women to feel confident,
            elegant, and authentically themselves.
        </p>
        <a href="/pages/our-story.php" class="hero-btn" style="background: var(--charcoal); color: var(--white);">Discover Our Journey</a>
    </div>
</div>

<div class="seo-content">
    <h2>Premium Women's Fashion at Dorve.co</h2>
    <p>
        Welcome to Dorve, Indonesia's premier destination for sophisticated women's fashion. Our carefully curated
        collections blend timeless elegance with contemporary design, offering pieces that transcend seasonal trends
        and become cherished staples in your wardrobe.
    </p>

    <h3>Uncompromising Quality</h3>
    <p>
        At Dorve, quality is never negotiable. We partner with skilled artisans and use only the finest materials
        to create garments that not only look beautiful but stand the test of time. From luxurious fabrics to
        meticulous stitching, every detail is crafted with care and precision.
    </p>

    <h3>Sustainable Fashion</h3>
    <p>
        We're committed to responsible fashion practices. Our collections prioritize sustainability without
        compromising on style or quality. By choosing Dorve, you're supporting ethical production methods
        and contributing to a more sustainable fashion industry.
    </p>

    <h3>Designed for the Modern Woman</h3>
    <p>
        Our designs celebrate the multifaceted nature of modern femininity. Whether you're dressing for a
        boardroom meeting, a casual weekend brunch, or an elegant evening event, Dorve has the perfect piece
        to make you feel confident and beautiful. Our versatile collections seamlessly transition from day to night,
        season to season.
    </p>

    <h3>Customer-Centric Experience</h3>
    <p>
        Your satisfaction is our priority. We offer hassle-free returns, secure payment options, and fast shipping
        across Indonesia. Our dedicated customer service team is always ready to assist you in finding your perfect fit
        and style. Join thousands of satisfied customers who have made Dorve their go-to destination for premium fashion.
    </p>
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');

    function showSlide(n) {
        slides.forEach(slide => slide.classList.remove('active'));
        currentSlide = (n + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    setInterval(() => {
        showSlide(currentSlide + 1);
    }, 5000);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
