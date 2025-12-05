<?php
require_once __DIR__ . '/config.php';

// Get banners for homepage slider
try {
    $stmt = $pdo->query("SELECT * FROM banners WHERE banner_type = 'slider' AND is_active = 1 ORDER BY display_order ASC, created_at DESC LIMIT 10");
    $slider_banners = $stmt->fetchAll();
} catch (PDOException $e) {
    $slider_banners = [];
}

// Get popup banner (only one, highest priority)
try {
    $stmt = $pdo->query("SELECT * FROM banners WHERE banner_type = 'popup' AND is_active = 1 ORDER BY display_order ASC, created_at DESC LIMIT 1");
    $popup_banner = $stmt->fetch();
} catch (PDOException $e) {
    $popup_banner = null;
}

// Get featured products
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.is_featured = 1 AND p.is_active = 1
                      ORDER BY p.created_at DESC
                      LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Get new arrivals
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.is_new = 1 AND p.is_active = 1
                      ORDER BY p.created_at DESC
                      LIMIT 8");
$stmt->execute();
$new_arrivals = $stmt->fetchAll();

// Get all products for SEO
$stmt = $pdo->query("SELECT p.*, c.name as category_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.is_active = 1
                     ORDER BY p.created_at DESC
                     LIMIT 12");
$all_products = $stmt->fetchAll();

// Get all categories for homepage
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY sequence ASC");
        $categories = $stmt->fetchAll();
    } catch (PDOException $e2) {
        $categories = [];
    }
}

$page_title = 'Dorve House | Toko Baju Online Terpercaya - Fashion Pria & Wanita Kekinian';
$page_description = 'Belanja baju pria, baju wanita, dan fashion unisex kekinian di Dorve House. Toko baju online terpercaya dengan koleksi trendy murah berkualitas untuk sehari-hari.';
$page_keywords = 'dorve house, toko baju online, toko baju online terpercaya, baju online, baju kekinian, baju trendy, baju pria, baju wanita, fashion pria, fashion wanita, fashion unisex, baju murah, model baju terbaru, beli baju online, dress wanita, kemeja pria, kaos pria, baju couple, toko baju online kekinian, baju wanita murah berkualitas, fashion pria kekinian, hoodie pria, celana wanita, blouse wanita trendy, jaket couple, baju family gathering, baju pria terbaru, fashion wanita trendy';
include __DIR__ . '/includes/header.php';
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

    /* SEO Section 1 - Brand Story (Top) */
    .brand-story-section {
        padding: 100px 0;
        background: var(--white);
    }

    .story-content {
        max-width: 900px;
        margin: 0 auto;
        text-align: center;
    }

    .story-content h2 {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        color: var(--charcoal);
        margin-bottom: 24px;
        line-height: 1.3;
    }

    .story-content p {
        font-size: 17px;
        color: var(--grey);
        line-height: 1.9;
        margin-bottom: 20px;
    }

    .story-highlights {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 32px;
        margin-top: 64px;
    }

    .highlight-item {
        text-align: center;
        padding: 24px;
    }

    .highlight-number {
        font-family: 'Playfair Display', serif;
        font-size: 48px;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 8px;
    }

    .highlight-label {
        font-size: 13px;
        color: var(--grey);
        letter-spacing: 2px;
        text-transform: uppercase;
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

    /* SEO Section 2 - Product Categories Info (Middle) */
    .category-info-section {
        padding: 100px 0;
        background: linear-gradient(135deg, #F5F5F5 0%, #FFFFFF 100%);
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
        align-items: center;
    }

    .info-image {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .info-image img {
        width: 100%;
        height: 600px;
        object-fit: cover;
    }

    .info-content h3 {
        font-family: 'Playfair Display', serif;
        font-size: 38px;
        color: var(--charcoal);
        margin-bottom: 24px;
        line-height: 1.3;
    }

    .info-content p {
        font-size: 16px;
        color: var(--grey);
        line-height: 1.9;
        margin-bottom: 24px;
    }

    .info-features {
        margin-top: 32px;
    }

    .feature-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 24px;
        padding: 20px;
        background: var(--white);
        border-radius: 6px;
        transition: transform 0.3s ease;
    }

    .feature-item:hover {
        transform: translateX(8px);
    }

    .feature-icon-box {
        width: 48px;
        height: 48px;
        background: var(--charcoal);
        color: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .feature-text h4 {
        font-size: 16px;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 6px;
    }

    .feature-text p {
        font-size: 14px;
        color: var(--grey);
        margin: 0;
        line-height: 1.6;
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

    /* SEO Section 3 - Shopping Benefits (Middle-Bottom) */
    .benefits-section {
        padding: 100px 0;
        background: var(--charcoal);
        color: var(--white);
    }

    .benefits-header {
        text-align: center;
        margin-bottom: 64px;
    }

    .benefits-header .section-pretitle {
        color: var(--latte);
    }

    .benefits-header .section-title {
        color: var(--white);
    }

    .benefits-header .section-description {
        color: rgba(255,255,255,0.8);
    }

    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 48px;
    }

    .benefit-card {
        background: rgba(255,255,255,0.08);
        padding: 40px 32px;
        border-radius: 8px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        transition: all 0.3s ease;
    }

    .benefit-card:hover {
        background: rgba(255,255,255,0.12);
        transform: translateY(-8px);
    }

    .benefit-icon {
        font-size: 42px;
        margin-bottom: 24px;
    }

    .benefit-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--white);
    }

    .benefit-description {
        font-size: 15px;
        line-height: 1.8;
        color: rgba(255,255,255,0.85);
    }

    .benefit-list {
        list-style: none;
        padding: 0;
        margin-top: 20px;
    }

    .benefit-list li {
        padding: 8px 0;
        padding-left: 28px;
        position: relative;
        font-size: 14px;
        color: rgba(255,255,255,0.8);
    }

    .benefit-list li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: var(--latte);
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

    /* SEO Section 4 - Final Content (Bottom) */
    .final-content-section {
        padding: 100px 0;
        background: var(--white);
    }

    .final-content-wrapper {
        max-width: 1000px;
        margin: 0 auto;
    }

    .final-content-header {
        text-align: center;
        margin-bottom: 64px;
    }

    .final-content-header h2 {
        font-family: 'Playfair Display', serif;
        font-size: 44px;
        color: var(--charcoal);
        margin-bottom: 20px;
    }

    .final-content-header p {
        font-size: 18px;
        color: var(--grey);
        line-height: 1.8;
    }

    .content-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 64px;
        margin-top: 48px;
    }

    .content-col h3 {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        color: var(--charcoal);
        margin-bottom: 20px;
    }

    .content-col p {
        font-size: 15px;
        color: var(--grey);
        line-height: 1.8;
        margin-bottom: 16px;
    }

    .keyword-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 32px;
    }

    .keyword-tag {
        padding: 10px 20px;
        background: var(--off-white);
        color: var(--charcoal);
        font-size: 13px;
        border-radius: 24px;
        border: 1px solid rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .keyword-tag:hover {
        background: var(--charcoal);
        color: var(--white);
        border-color: var(--charcoal);
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

        .info-grid {
            grid-template-columns: 1fr;
            gap: 48px;
        }

        .benefits-grid {
            grid-template-columns: 1fr;
            gap: 32px;
        }

        .content-columns {
            grid-template-columns: 1fr;
            gap: 48px;
        }

        .story-highlights {
            grid-template-columns: repeat(2, 1fr);
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

        .story-content h2 {
            font-size: 32px;
        }

        .info-content h3 {
            font-size: 28px;
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

        .story-highlights {
            grid-template-columns: 1fr;
        }

        .story-content h2 {
            font-size: 28px;
        }

        .final-content-header h2 {
            font-size: 32px;
        }
    }
</style>

<!-- Hero Slider -->
<div class="hero-slider">
    <?php if (!empty($slider_banners)): ?>
        <?php foreach ($slider_banners as $index => $banner): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo htmlspecialchars($banner['image_url']); ?>') center/cover;">
                <div class="hero-content">
                    <?php if ($banner['title']): ?>
                        <h1 class="hero-title"><?php echo htmlspecialchars($banner['title']); ?></h1>
                    <?php endif; ?>
                    <?php if ($banner['subtitle']): ?>
                        <p class="hero-subtitle"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                    <?php endif; ?>
                    <?php if ($banner['link_url'] && $banner['cta_text']): ?>
                        <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" class="hero-btn"><?php echo htmlspecialchars($banner['cta_text']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Default Banner if no banners in database -->
        <div class="hero-slide active" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('/public/images/Dorve1.png') center/cover;">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to Dorve House</h1>
                <p class="hero-subtitle">Discover Latest Fashion Collection</p>
                <a href="/pages/all-products.php" class="hero-btn">Shop Now</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Popup Banner Modal -->
<?php if ($popup_banner): ?>
<div id="bannerPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 99999; align-items: center; justify-content: center; padding: 20px; animation: fadeIn 0.4s ease;">
    <div style="position: relative; max-width: 900px; width: 100%; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: slideUp 0.5s ease;">
        <button onclick="closePopup()" style="position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.7); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 24px; line-height: 1; z-index: 10; transition: all 0.3s; font-weight: bold;" onmouseover="this.style.background='rgba(0,0,0,0.9)'; this.style.transform='rotate(90deg)'" onmouseout="this.style.background='rgba(0,0,0,0.7)'; this.style.transform='rotate(0deg)'">
            ×
        </button>
        <a href="<?php echo htmlspecialchars($popup_banner['link_url']); ?>" onclick="closePopup()">
            <img src="<?php echo htmlspecialchars($popup_banner['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($popup_banner['title']); ?>" 
                 style="width: 100%; height: auto; display: block; max-height: 80vh; object-fit: contain;">
        </a>
        <?php if ($popup_banner['cta_text']): ?>
            <div style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);">
                <a href="<?php echo htmlspecialchars($popup_banner['link_url']); ?>" 
                   onclick="closePopup()" 
                   style="display: inline-block; padding: 16px 48px; background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); color: white; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 50px; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 8px 24px rgba(0,0,0,0.3); transition: all 0.3s;"
                   onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.4)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.3)'">
                    <?php echo htmlspecialchars($popup_banner['cta_text']); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<script>
// Auto show popup after 3 seconds
setTimeout(function() {
    if (!sessionStorage.getItem('popupShown')) {
        document.getElementById('bannerPopup').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        sessionStorage.setItem('popupShown', 'true');
    }
}, 3000);

function closePopup() {
    document.getElementById('bannerPopup').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close when clicking outside
document.getElementById('bannerPopup')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePopup();
    }
});

// Close with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePopup();
    }
});
</script>
<?php endif; ?>

<!-- SEO Section 1: Brand Story (Top Section) -->
<section class="brand-story-section">
    <div class="container">
        <div class="story-content">
            <h2>Dorve House - Toko Baju Online Terpercaya untuk Fashion Kekinian</h2>
            <p>
                Selamat datang di <strong>Dorve House</strong>, destinasi belanja <strong>baju online</strong> terpercaya yang menghadirkan koleksi <strong>fashion pria</strong>, <strong>fashion wanita</strong>, dan <strong>fashion unisex</strong> terlengkap di Indonesia. Kami memahami bahwa fashion bukan sekadar pakaian, tetapi cara Anda mengekspresikan diri dan kepercayaan diri.
            </p>
            <p>
                Sejak awal berdiri, Dorve House berkomitmen menjadi <strong>toko baju online kekinian</strong> yang menawarkan <strong>baju trendy</strong> dengan kualitas premium namun tetap terjangkau. Setiap produk dipilih dengan cermat untuk memastikan Anda mendapatkan <strong>baju murah berkualitas</strong> yang tidak mengecewakan.
            </p>
        </div>

        <div class="story-highlights">
            <div class="highlight-item">
                <div class="highlight-number">10K+</div>
                <div class="highlight-label">Produk Tersedia</div>
            </div>
            <div class="highlight-item">
                <div class="highlight-number">50K+</div>
                <div class="highlight-label">Pelanggan Puas</div>
            </div>
            <div class="highlight-item">
                <div class="highlight-number">4.8/5</div>
                <div class="highlight-label">Rating Toko</div>
            </div>
            <div class="highlight-item">
                <div class="highlight-number">24/7</div>
                <div class="highlight-label">Customer Service</div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Showcase -->
<?php if (count($categories) > 0): ?>
<section class="categories-showcase">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Belanja Berdasarkan Kategori</div>
            <h2 class="section-title">Jelajahi Koleksi Kami</h2>
            <p class="section-description">Dari klasik abadi hingga tren kontemporer, temukan produk sempurna untuk setiap kesempatan</p>
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
                        <img src="/public/images/logo.png" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                    <?php endif; ?>
                    <div class="category-overlay">
                        <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        <div class="category-count">Belanja Sekarang</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SEO Section 2: Product Categories Info (Middle Section) -->
<section class="category-info-section">
    <div class="container">
        <div class="info-grid">
            <div class="info-image">
                <img src="/public/images/Dorve2.png" alt="Koleksi Baju Wanita Kekinian di Dorve House">
            </div>
            <div class="info-content">
                <h3>Koleksi Baju Wanita Lengkap untuk Setiap Gaya</h3>
                <p>
                    Temukan <strong>baju wanita</strong> terlengkap di Dorve House! Dari <strong>dress wanita</strong> elegan untuk acara formal, <strong>blouse wanita trendy</strong> untuk kantor, hingga <strong>celana wanita murah</strong> untuk aktivitas sehari-hari. Kami menghadirkan <strong>model baju terbaru</strong> yang selalu update mengikuti tren fashion terkini.
                </p>
                <p>
                    Koleksi <strong>baju wanita murah berkualitas</strong> kami tersedia dalam berbagai ukuran, termasuk <strong>baju wanita big size murah</strong> yang tetap stylish dan nyaman. Setiap produk dirancang untuk memberikan kenyamanan maksimal tanpa mengorbankan penampilan.
                </p>

                <div class="info-features">
                    <div class="feature-item">
                        <div class="feature-icon-box">👗</div>
                        <div class="feature-text">
                            <h4>Dress Wanita untuk Semua Acara</h4>
                            <p>Mini dress, midi dress, hingga maxi dress dengan desain terkini</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon-box">👚</div>
                        <div class="feature-text">
                            <h4>Tops & Blouse Trendy</h4>
                            <p>Koleksi atasan wanita untuk mix and match outfit sempurna</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon-box">👖</div>
                        <div class="feature-text">
                            <h4>Celana & Rok Kekinian</h4>
                            <p>Dari jeans hingga kulot, temukan bottom wear favorit Anda</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- New Arrivals -->
<?php if (count($new_arrivals) > 0): ?>
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Koleksi Terkini</div>
            <h2 class="section-title">Produk Baru</h2>
            <p class="section-description">Gaya fresh yang baru tiba. Jadilah yang pertama menemukan koleksi terbaru kami</p>
        </div>

        <div class="products-grid">
            <?php foreach ($new_arrivals as $product): ?>
                <a href="/pages/product-detail.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-image">
                        <?php if ($product['is_new']): ?>
                            <div class="product-badge">Baru</div>
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
                            <?php if ($product['discount_price']): ?>
                                <span class="product-price-discount"><?php echo formatPrice($product['price']); ?></span>
                                <?php echo formatPrice($product['discount_price']); ?>
                            <?php else: ?>
                                <?php echo formatPrice($product['price']); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['stock'] > 0): ?>
                            <div class="product-stock in-stock">Tersedia</div>
                        <?php else: ?>
                            <div class="product-stock out-stock">Stok Habis</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <a href="/pages/new-collection.php" class="view-all-btn">Lihat Semua Produk Baru</a>
    </div>
</section>
<?php endif; ?>

<!-- SEO Section 3: Men's Fashion & Couple Collection (Middle Section) -->
<section class="category-info-section" style="background: var(--white);">
    <div class="container">
        <div class="info-grid">
            <div class="info-content">
                <h3>Fashion Pria Kekinian & Baju Couple Terlengkap</h3>
                <p>
                    Dorve House juga menawarkan koleksi <strong>baju pria</strong> lengkap untuk pria modern! Dari <strong>kemeja pria lengan panjang murah</strong> untuk acara formal, <strong>kaos pria keren</strong> untuk casual look, hingga <strong>hoodie pria keren</strong> untuk gaya streetwear yang nyaman. Setiap produk <strong>baju pria terbaru</strong> kami dirancang dengan memperhatikan detail dan kualitas material terbaik.
                </p>
                <p>
                    Tak lupa, kami menghadirkan koleksi <strong>baju couple</strong> eksklusif untuk Anda dan pasangan! Temukan <strong>kaos couple keren</strong>, <strong>hoodie couple matching</strong>, hingga <strong>jaket couple keren</strong> yang sempurna untuk menunjukkan chemistry Anda. Cocok juga untuk <strong>baju family gathering</strong> dan acara bersama keluarga.
                </p>

                <div class="info-features">
                    <div class="feature-item">
                        <div class="feature-icon-box">👔</div>
                        <div class="feature-text">
                            <h4>Kemeja & Formal Wear</h4>
                            <p>Koleksi kemeja pria untuk tampilan profesional dan elegan</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon-box">👕</div>
                        <div class="feature-text">
                            <h4>Kaos & Casual Wear</h4>
                            <p>T-shirt pria dengan desain unik dan material premium</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon-box">🧥</div>
                        <div class="feature-text">
                            <h4>Jaket & Hoodie</h4>
                            <p>Outerwear stylish untuk melengkapi penampilan Anda</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon-box">👫</div>
                        <div class="feature-text">
                            <h4>Koleksi Couple & Family</h4>
                            <p>Matching outfit untuk pasangan dan keluarga</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="info-image">
                <img src="/public/images/Dorve3.png" alt="Koleksi Baju Pria dan Couple di Dorve House">
            </div>
        </div>
    </div>
</section>

<!-- SEO Section 4: Shopping Benefits (Dark Section) -->
<section class="benefits-section">
    <div class="container">
        <div class="benefits-header">
            <div class="section-pretitle">Keunggulan Dorve House</div>
            <h2 class="section-title">Kenapa Belanja Baju Online di Dorve House?</h2>
            <p class="section-description">Pengalaman berbelanja fashion online yang mudah, aman, dan menyenangkan</p>
        </div>

        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">🛒</div>
                <h3 class="benefit-title">Belanja Online Mudah & Cepat</h3>
                <p class="benefit-description">
                    <strong>Toko baju online terpercaya</strong> dengan sistem checkout yang simpel. Cukup 3 langkah untuk menyelesaikan pembelian Anda. Interface user-friendly membuat Anda mudah menemukan <strong>baju kekinian</strong> yang sesuai dengan gaya.
                </p>
                <ul class="benefit-list">
                    <li>Pencarian produk cepat dan akurat</li>
                    <li>Filter kategori lengkap</li>
                    <li>Checkout dalam 3 langkah mudah</li>
                    <li>Multiple payment options</li>
                </ul>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">💳</div>
                <h3 class="benefit-title">Pembayaran Aman & Fleksibel</h3>
                <p class="benefit-description">
                    Nikmati berbagai metode pembayaran yang aman di <strong>toko baju online</strong> kami. Dari transfer bank, e-wallet, hingga COD (Cash on Delivery) tersedia untuk kemudahan Anda <strong>beli baju online</strong>.
                </p>
                <ul class="benefit-list">
                    <li>Transfer bank semua bank major</li>
                    <li>E-wallet (GoPay, OVO, DANA, ShopeePay)</li>
                    <li>COD untuk area tertentu</li>
                    <li>Cicilan 0% tersedia</li>
                </ul>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">🎁</div>
                <h3 class="benefit-title">Promo & Diskon Setiap Hari</h3>
                <p class="benefit-description">
                    Dapatkan <strong>baju murah</strong> dengan promo menarik! Flash sale, voucher diskon, hingga program referral dengan komisi menguntungkan. <strong>Jual baju</strong> berkualitas dengan harga terjangkau adalah misi kami.
                </p>
                <ul class="benefit-list">
                    <li>Flash sale setiap minggu</li>
                    <li>Voucher diskon untuk member baru</li>
                    <li>Reward points setiap pembelian</li>
                    <li>Program referral komisi hingga 10%</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Layanan Terbaik</div>
            <h2 class="section-title">Pengalaman Berbelanja Dorve House</h2>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3 class="feature-title">Gratis Ongkir</h3>
                <p class="feature-description">Nikmati gratis ongkir untuk pembelian di atas Rp 500.000 ke seluruh Indonesia</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">Pembayaran Aman</h3>
                <p class="feature-description">Belanja dengan tenang menggunakan payment gateway terenkripsi kami</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💎</div>
                <h3 class="feature-title">Kualitas Premium</h3>
                <p class="feature-description">Hanya material terbaik dan craftsmanship sempurna di setiap produk</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3 class="feature-title">Customer Service 24/7</h3>
                <p class="feature-description">Tim support kami siap membantu Anda kapan saja via WhatsApp</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (count($featured_products) > 0): ?>
<section class="featured-section" style="background: var(--white);">
    <div class="container">
        <div class="section-header">
            <div class="section-pretitle">Pilihan Terbaik Kami</div>
            <h2 class="section-title">Koleksi Unggulan</h2>
            <p class="section-description">Produk favorit pilihan tim kami dari koleksi musim ini</p>
        </div>

        <div class="products-grid">
            <?php foreach ($featured_products as $product): ?>
                <a href="/pages/product-detail.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-image">
                        <?php if ($product['is_featured']): ?>
                            <div class="product-badge">Unggulan</div>
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
                            <?php if ($product['discount_price']): ?>
                                <span class="product-price-discount"><?php echo formatPrice($product['price']); ?></span>
                                <?php echo formatPrice($product['discount_price']); ?>
                            <?php else: ?>
                                <?php echo formatPrice($product['price']); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['stock'] > 0): ?>
                            <div class="product-stock in-stock">Tersedia</div>
                        <?php else: ?>
                            <div class="product-stock out-stock">Stok Habis</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <a href="/pages/all-products.php" class="view-all-btn">Lihat Semua Produk</a>
    </div>
</section>
<?php endif; ?>

<!-- SEO Section 5: Final Content (Bottom Section) -->
<section class="final-content-section">
    <div class="container">
        <div class="final-content-wrapper">
            <div class="final-content-header">
                <h2>Belanja Fashion Online Terlengkap di Indonesia</h2>
                <p>Dorve House hadir sebagai solusi lengkap untuk kebutuhan fashion Anda. Dari baju casual hingga formal, dari pria hingga wanita, semuanya ada di sini dengan harga terjangkau dan kualitas terjamin.</p>
            </div>

            <div class="content-columns">
                <div class="content-col">
                    <h3>Komitmen Kualitas & Kepuasan Pelanggan</h3>
                    <p>
                        Sebagai <strong>toko baju online terpercaya</strong>, kami memastikan setiap produk yang sampai ke tangan Anda adalah yang terbaik. Proses quality control ketat dilakukan untuk menjaga standar kualitas <strong>baju murah berkualitas</strong> kami.
                    </p>
                    <p>
                        Kepuasan pelanggan adalah prioritas utama. Oleh karena itu, kami menyediakan return policy yang customer-friendly dan customer service yang responsif 24/7 untuk menjawab semua pertanyaan Anda tentang produk <strong>fashion pria kekinian</strong> dan <strong>fashion wanita trendy</strong> kami.
                    </p>
                    <p>
                        Bergabunglah dengan ribuan pelanggan puas yang telah mempercayai Dorve House sebagai destinasi <strong>belanja baju online</strong> favorit mereka. Dapatkan update produk terbaru dan promo eksklusif dengan follow akun media sosial kami!
                    </p>
                </div>

                <div class="content-col">
                    <h3>Pengiriman Cepat & Aman ke Seluruh Indonesia</h3>
                    <p>
                        Kami bekerjasama dengan ekspedisi terpercaya untuk memastikan <strong>baju online</strong> pesanan Anda sampai dengan cepat dan aman. Gratis ongkir tersedia untuk pembelian di atas Rp 500.000 ke seluruh Indonesia, termasuk Jakarta, Surabaya, Bandung, Medan, dan kota-kota besar lainnya.
                    </p>
                    <p>
                        Sistem tracking real-time memungkinkan Anda memantau perjalanan paket dari gudang hingga ke rumah. Packaging premium kami memastikan produk <strong>baju kekinian</strong> Anda tiba dalam kondisi sempurna.
                    </p>
                    <p>
                        Untuk area tertentu, kami juga menyediakan layanan COD (Cash on Delivery) sehingga Anda bisa bayar langsung saat barang sampai. Keamanan dan kenyamanan berbelanja adalah jaminan kami untuk Anda.
                    </p>
                </div>
            </div>

            <div class="keyword-tags">
                <span class="keyword-tag">Dorve</span>
                <span class="keyword-tag">Dorve House</span>
                <span class="keyword-tag">Dorve House Official</span>
                <span class="keyword-tag">Baju Wanita</span>
                <span class="keyword-tag">Baju Pria</span>
                <span class="keyword-tag">Fashion Wanita</span>
                <span class="keyword-tag">Baju Online</span>
                <span class="keyword-tag">Toko Baju Online</span>
                <span class="keyword-tag">Baju Kekinian</span>
                <span class="keyword-tag">Baju Trendy</span>
                <span class="keyword-tag">Fashion Pria</span>
                <span class="keyword-tag">Dress Wanita</span>
                <span class="keyword-tag">Kemeja Pria</span>
                <span class="keyword-tag">Baju Couple</span>
                <span class="keyword-tag">Fashion Unisex</span>
                <span class="keyword-tag">Baju Murah</span>
                <span class="keyword-tag">Model Baju Terbaru</span>
                <span class="keyword-tag">Kaos Pria</span>
                <span class="keyword-tag">Blouse Wanita</span>
                <span class="keyword-tag">Hoodie Pria</span>
                <span class="keyword-tag">Celana Wanita</span>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Hero Slider
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const totalSlides = slides.length;

function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    slides[index].classList.add('active');
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    showSlide(currentSlide);
}

// Auto slide every 5 seconds
setInterval(nextSlide, 5000);
</script>