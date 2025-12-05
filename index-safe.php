<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

// Get featured products - SAFE VERSION
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          WHERE p.is_featured = 1
                          ORDER BY p.created_at DESC
                          LIMIT 8");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback without is_featured
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                              FROM products p
                              LEFT JOIN categories c ON p.category_id = c.id
                              ORDER BY p.created_at DESC
                              LIMIT 8");
        $stmt->execute();
        $featured_products = $stmt->fetchAll();
    } catch (PDOException $e2) {
        $featured_products = [];
    }
}

// Get new arrivals - SAFE VERSION
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          WHERE p.is_new = 1
                          ORDER BY p.created_at DESC
                          LIMIT 8");
    $stmt->execute();
    $new_arrivals = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback without is_new
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                              FROM products p
                              LEFT JOIN categories c ON p.category_id = c.id
                              ORDER BY p.created_at DESC
                              LIMIT 8");
        $stmt->execute();
        $new_arrivals = $stmt->fetchAll();
    } catch (PDOException $e2) {
        $new_arrivals = [];
    }
}

// Get all products for SEO
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         ORDER BY p.created_at DESC
                         LIMIT 12");
    $all_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_products = [];
}

// Get all categories for homepage
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sequence ASC LIMIT 6");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

$page_title = 'Dorve House - Fashion Online Terbaik | Baju Wanita Trendy & Berkualitas';
$page_description = 'Belanja koleksi baju wanita trendy di Dorve House. Temukan dress, tops, hoodies, dan fashion item berkualitas dengan harga terjangkau. Gratis ongkir & COD tersedia!';
$page_keywords = 'baju wanita, fashion online, dress wanita, tops, hoodies, belanja online, fashion murah, dorve house';

try {
    include __DIR__ . '/includes/header.php';
} catch (Exception $e) {
    echo "Header error: " . $e->getMessage();
    die();
}
?>

<style>
    :root {
        --charcoal: #1A1A1A;
        --white: #FFFFFF;
        --off-white: #F8F8F8;
        --latte: #D4C5B9;
        --grey: #6B6B6B;
    }

    .hero-section {
        position: relative;
        height: 75vh;
        min-height: 500px;
        background: linear-gradient(135deg, #F5F5F5 0%, #E8E8E8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 0 24px;
        margin-bottom: 100px;
    }

    .hero-content {
        max-width: 800px;
        animation: fadeInUp 1s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-title {
        font-family: 'Playfair Display', serif;
        font-size: 72px;
        font-weight: 700;
        margin-bottom: 24px;
        color: var(--charcoal);
        line-height: 1.1;
        letter-spacing: -1px;
    }

    .hero-subtitle {
        font-size: 20px;
        color: var(--grey);
        margin-bottom: 48px;
        line-height: 1.7;
        font-weight: 300;
    }

    .hero-cta {
        display: inline-block;
        padding: 20px 56px;
        background: var(--charcoal);
        color: var(--white);
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 3px;
        text-transform: uppercase;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 2px;
    }

    .hero-cta:hover {
        background: var(--latte);
        color: var(--charcoal);
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.2);
    }

    .categories-showcase {
        padding: 100px 0;
        background: var(--white);
    }

    .section-header {
        text-align: center;
        margin-bottom: 72px;
    }

    .section-pretitle {
        font-size: 13px;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: var(--grey);
        margin-bottom: 16px;
        font-weight: 500;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 48px;
        color: var(--charcoal);
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 16px;
    }

    .section-description {
        font-size: 16px;
        color: var(--grey);
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
        margin-bottom: 64px;
    }

    .category-card {
        position: relative;
        text-decoration: none;
        overflow: hidden;
        aspect-ratio: 1;
        background: var(--off-white);
        border-radius: 4px;
        transition: transform 0.4s ease;
    }

    .category-card:hover {
        transform: translateY(-12px);
    }

    .category-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }

    .category-card:hover .category-image {
        transform: scale(1.1);
    }

    .category-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        padding: 40px 32px;
        color: var(--white);
    }

    .category-name {
        font-size: 28px;
        font-weight: 700;
        letter-spacing: 1px;
        margin-bottom: 8px;
        font-family: 'Playfair Display', serif;
    }

    .category-count {
        font-size: 13px;
        letter-spacing: 2px;
        text-transform: uppercase;
        opacity: 0.9;
    }

    .featured-section {
        padding: 120px 0;
        background: var(--off-white);
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 40px;
        margin-bottom: 64px;
    }

    .product-card {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform 0.4s ease;
        background: var(--white);
        border-radius: 4px;
        overflow: hidden;
    }

    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .product-image {
        position: relative;
        padding-bottom: 125%;
        background: var(--off-white);
        overflow: hidden;
    }

    .product-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.08);
    }

    .product-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        background: var(--charcoal);
        color: var(--white);
        padding: 8px 16px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        z-index: 1;
    }

    .product-info {
        padding: 24px;
        text-align: left;
    }

    .product-category {
        font-size: 11px;
        color: var(--grey);
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 12px;
        font-weight: 600;
    }

    .product-name {
        font-size: 17px;
        font-weight: 600;
        margin-bottom: 16px;
        color: var(--charcoal);
        line-height: 1.4;
    }

    .product-price {
        font-size: 16px;
        color: var(--charcoal);
        font-weight: 700;
    }

    .product-price-discount {
        color: var(--grey);
        text-decoration: line-through;
        font-weight: 400;
        margin-right: 12px;
        font-size: 14px;
    }

    .product-stock {
        font-size: 12px;
        margin-top: 12px;
        font-weight: 600;
    }

    .product-stock.in-stock {
        color: #10B981;
    }

    .product-stock.out-stock {
        color: #EF4444;
    }

    .view-all-btn {
        display: block;
        width: fit-content;
        margin: 0 auto;
        padding: 18px 48px;
        border: 2px solid var(--charcoal);
        color: var(--charcoal);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 3px;
        text-transform: uppercase;
        transition: all 0.4s ease;
        border-radius: 2px;
    }

    .view-all-btn:hover {
        background: var(--charcoal);
        color: var(--white);
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.2);
    }

    .seo-content-section {
        padding: 120px 0;
        background: var(--white);
    }

    .content-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 80px;
        margin-top: 72px;
    }

    .content-block h3 {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        margin-bottom: 24px;
        color: var(--charcoal);
        line-height: 1.3;
    }

    .content-block p {
        font-size: 16px;
        color: var(--grey);
        line-height: 1.8;
        margin-bottom: 20px;
    }

    .content-block ul {
        list-style: none;
        padding: 0;
        margin: 24px 0;
    }

    .content-block ul li {
        font-size: 15px;
        color: var(--grey);
        padding: 12px 0;
        padding-left: 32px;
        position: relative;
        line-height: 1.6;
    }

    .content-block ul li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: var(--charcoal);
        font-weight: 700;
    }

    .features-section {
        padding: 100px 0;
        background: var(--off-white);
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 48px;
        margin-top: 72px;
    }

    .feature-card {
        text-align: center;
    }

    .feature-icon {
        font-size: 48px;
        margin-bottom: 24px;
    }

    .feature-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--charcoal);
        letter-spacing: 0.5px;
    }

    .feature-description {
        font-size: 14px;
        color: var(--grey);
        line-height: 1.6;
    }

    @media (max-width: 1024px) {
        .products-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .categories-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .features-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .content-grid {
            grid-template-columns: 1fr;
            gap: 48px;
        }
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 48px;
        }

        .hero-subtitle {
            font-size: 18px;
        }

        .section-title {
            font-size: 36px;
        }

        .products-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .categories-grid {
            grid-template-columns: 1fr;
        }

        .features-grid {
            grid-template-columns: 1fr;
            gap: 32px;
        }
    }

    @media (max-width: 480px) {
        .hero-title {
            font-size: 36px;
        }

        .hero-subtitle {
            font-size: 16px;
        }

        .section-title {
            font-size: 28px;
        }

        .products-grid {
            grid-template-columns: 1fr;
        }

        .product-info {
            padding: 16px;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Timeless Elegance</h1>
        <p class="hero-subtitle">Discover our curated collection of contemporary fashion that transcends seasons. Where quality meets style.</p>
        <a href="/pages/all-products.php" class="hero-cta">Explore Collection</a>
    </div>
</section>

<!-- Categories Showcase -->
<?php if (count($categories) > 0): ?>
<section class="categories-showcase">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Shop by Category</div>
            <h2 class="section-title">Explore Our Collections</h2>
            <p class="section-description">From timeless classics to contemporary trends, find the perfect pieces for every occasion</p>
        </div>

        <div class="categories-grid">
            <?php
            $display_categories = array_slice($categories, 0, 6);
            foreach ($display_categories as $category):
            ?>
                <a href="/pages/all-products.php?category=<?php echo $category['id']; ?>" class="category-card">
                    <?php if (!empty($category['image'])): ?>
                        <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                    <?php else: ?>
                        <img src="/public/images/image.png" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                    <?php endif; ?>
                    <div class="category-overlay">
                        <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        <div class="category-count">Shop Now</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- New Arrivals -->
<?php if (count($new_arrivals) > 0): ?>
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Latest Drops</div>
            <h2 class="section-title">New Arrivals</h2>
            <p class="section-description">Fresh styles just landed. Be the first to discover our newest collection</p>
        </div>

        <div class="products-grid">
            <?php foreach ($new_arrivals as $product):
                $is_new = isset($product['is_new']) ? $product['is_new'] : false;
            ?>
                <a href="/pages/product-detail.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-image">
                        <?php if ($is_new): ?>
                            <div class="product-badge">New</div>
                        <?php endif; ?>
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="/public/images/image.png" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <?php if ($product['category_name']): ?>
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        <?php endif; ?>
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">
                            <?php if (!empty($product['discount_price'])): ?>
                                <span class="product-price-discount"><?php echo formatPrice($product['price']); ?></span>
                                <?php echo formatPrice($product['discount_price']); ?>
                            <?php else: ?>
                                <?php echo formatPrice($product['price']); ?>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                            <div class="product-stock in-stock">In Stock</div>
                        <?php else: ?>
                            <div class="product-stock out-stock">Out of Stock</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <a href="/pages/new-collection.php" class="view-all-btn">View All New Arrivals</a>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Why Choose Dorve</div>
            <h2 class="section-title">The Dorve Experience</h2>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-description">Enjoy free shipping on orders over Rp 500,000 across Indonesia</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">Secure Payment</h3>
                <p class="feature-description">Shop with confidence using our secure payment gateway</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💎</div>
                <h3 class="feature-title">Premium Quality</h3>
                <p class="feature-description">Only the finest materials and craftsmanship in every piece</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎁</div>
                <h3 class="feature-title">Exclusive Rewards</h3>
                <p class="feature-description">Earn points and get exclusive discounts on every purchase</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (count($featured_products) > 0): ?>
<section class="featured-section" style="background: var(--white);">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Handpicked for You</div>
            <h2 class="section-title">Featured Collection</h2>
            <p class="section-description">Our team's favorite picks from this season's collection</p>
        </div>

        <div class="products-grid">
            <?php foreach ($featured_products as $product):
                $is_featured = isset($product['is_featured']) ? $product['is_featured'] : false;
            ?>
                <a href="/pages/product-detail.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-image">
                        <?php if ($is_featured): ?>
                            <div class="product-badge">Featured</div>
                        <?php endif; ?>
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="/public/images/image.png" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <?php if ($product['category_name']): ?>
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        <?php endif; ?>
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">
                            <?php if (!empty($product['discount_price'])): ?>
                                <span class="product-price-discount"><?php echo formatPrice($product['price']); ?></span>
                                <?php echo formatPrice($product['discount_price']); ?>
                            <?php else: ?>
                                <?php echo formatPrice($product['price']); ?>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                            <div class="product-stock in-stock">In Stock</div>
                        <?php else: ?>
                            <div class="product-stock out-stock">Out of Stock</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <a href="/pages/all-products.php" class="view-all-btn">View All Products</a>
    </div>
</section>
<?php endif; ?>

<!-- SEO Content Section -->
<section class="seo-content-section">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">About Dorve House</div>
            <h2 class="section-title">Fashion Yang Menginspirasi</h2>
        </div>

        <div class="content-grid">
            <div class="content-block">
                <h3>Dorve House - Fashion Online Terpercaya di Indonesia</h3>
                <p>Dorve House adalah destinasi fashion online terpercaya untuk wanita modern yang menghargai kualitas dan gaya. Kami menghadirkan koleksi baju wanita trendy, mulai dari dress elegan, tops kasual, hingga hoodies nyaman yang cocok untuk berbagai kesempatan.</p>
                <p>Setiap produk di Dorve House dipilih dengan cermat untuk memastikan kualitas terbaik. Kami percaya bahwa fashion bukan hanya tentang penampilan, tetapi juga tentang bagaimana pakaian membuat Anda merasa percaya diri.</p>
                <ul>
                    <li>Koleksi fashion wanita terlengkap dan terbaru</li>
                    <li>Material premium dengan harga terjangkau</li>
                    <li>Gratis ongkir untuk pembelian di atas Rp 500,000</li>
                    <li>COD tersedia untuk kemudahan berbelanja</li>
                </ul>
            </div>

            <div class="content-block">
                <h3>Belanja Fashion Online Mudah & Aman</h3>
                <p>Di Dorve House, kami membuat pengalaman belanja online menjadi mudah, aman, dan menyenangkan. Platform kami dirancang dengan antarmuka yang user-friendly, memudahkan Anda menemukan produk yang sesuai dengan gaya dan kebutuhan Anda.</p>
                <p>Kami menerima berbagai metode pembayaran yang aman dan terpercaya. Sistem keamanan kami melindungi setiap transaksi Anda, sehingga Anda bisa berbelanja dengan tenang.</p>
                <ul>
                    <li>Checkout cepat dan mudah dalam 3 langkah</li>
                    <li>Payment gateway terenkripsi dan aman</li>
                    <li>Customer service responsif via WhatsApp</li>
                    <li>Return policy yang customer-friendly</li>
                </ul>
            </div>

            <div class="content-block">
                <h3>Koleksi Baju Wanita untuk Setiap Gaya</h3>
                <p>Dari gaya casual everyday hingga outfit formal untuk acara spesial, Dorve House memiliki koleksi lengkap untuk melengkapi wardrobe Anda. Dress wanita kami tersedia dalam berbagai model - dari mini dress cute hingga maxi dress elegan.</p>
                <p>Tops dan blouse kami sempurna untuk mix and match dengan berbagai bottom wear. Untuk kenyamanan maksimal, koleksi hoodies dan sweater kami menawarkan gaya streetwear yang on-trend tanpa mengorbankan kualitas.</p>
                <ul>
                    <li>Dress untuk casual, party, dan formal occasions</li>
                    <li>Tops & blouse dengan design terkini</li>
                    <li>Hoodies & sweater untuk gaya casual-chic</li>
                    <li>Aksesori fashion untuk melengkapi outfit Anda</li>
                </ul>
            </div>

            <div class="content-block">
                <h3>Promo & Diskon Menarik Setiap Hari</h3>
                <p>Berbelanja fashion berkualitas tidak harus mahal! Dorve House rutin menghadirkan promo dan diskon menarik untuk produk pilihan. Daftar menjadi member dan dapatkan akses ke flash sale eksklusif, voucher diskon, dan poin reward yang bisa ditukar dengan produk gratis.</p>
                <p>Sistem referral kami memungkinkan Anda mendapatkan komisi dari setiap teman yang Anda ajak berbelanja. Semakin banyak referral, semakin besar reward yang Anda dapatkan!</p>
                <ul>
                    <li>Flash sale dan promo spesial setiap minggu</li>
                    <li>Voucher diskon untuk member baru</li>
                    <li>Reward points pada setiap pembelian</li>
                    <li>Program referral dengan komisi hingga 10%</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php
try {
    include __DIR__ . '/includes/footer.php';
} catch (Exception $e) {
    echo "Footer error: " . $e->getMessage();
}
?>
