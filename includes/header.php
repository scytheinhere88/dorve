<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/seo-helper.php';

$current_lang = $_SESSION['lang'] ?? 'id';
$cart_count = getCartCount();
$canonical_url = getCanonicalUrl();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Dorve House Indonesia">
    <meta name="publisher" content="Dorve House">
    <meta property="og:site_name" content="Dorve House - Pusat Fashion Pria Wanita Indonesia">
    <meta property="og:type" content="<?php echo $og_type ?? 'website'; ?>">
    <meta property="og:title" content="<?php echo $page_title ?? 'Dorve House | Pusat Fashion Pria & Wanita – Toko Baju Online Kekinian'; ?>">
    <meta property="og:description" content="<?php echo $page_description ?? 'Belanja baju pria, baju wanita, dan fashion unisex kekinian di Dorve House. Toko baju online terpercaya dengan koleksi murah berkualitas untuk sehari-hari.'; ?>">
    <meta property="og:url" content="<?php echo $canonical_url; ?>">
    <meta property="og:image" content="<?php echo getOgImage($og_image ?? null); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@dorvehouse">
    <meta name="twitter:title" content="<?php echo $page_title ?? 'Dorve House - Pusat Fashion Pria Wanita'; ?>">
    <meta name="twitter:description" content="<?php echo $page_description ?? 'Belanja fashion pria wanita trendy & kekinian di Dorve House'; ?>">
    <meta name="twitter:image" content="<?php echo getOgImage($og_image ?? null); ?>">
    <link rel="canonical" href="<?php echo $canonical_url; ?>">
    <?php // generateAlternativeLanguageTags(); ?>
    <?php // generateAdvancedMetaTags($page_type ?? 'website'); ?>
    <title><?php echo $page_title ?? 'Dorve House - Pusat Fashion Pria Wanita Terbaru Termurah | Baju Trendy & Kekinian 2024'; ?></title>
    <meta name="description" content="<?php echo $page_description ?? 'Belanja fashion pria wanita trendy & kekinian di Dorve House Indonesia. Koleksi lengkap: dress, blouse, rok, celana. Gratis ongkir, COD, harga terjangkau. Toko baju online terpercaya Indonesia.'; ?>">
    <meta name="keywords" content="<?php echo $page_keywords ?? 'baju pria, baju wanita, fashion pria, fashion wanita, baju trendy, baju kekinian, model baju terbaru, dress wanita, blouse wanita, rok wanita, celana wanita, baju online, toko baju wanita, fashion indonesia, baju murah, dorve house'; ?>">
    <link rel="icon" type="image/png" href="/public/images/fav.ico">
    <link rel="shortcut icon" type="image/png" href="/public/images/fav.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --cream: #F5EFE7;
            --blush: #FFEDE8;
            --latte: #E8C5A6;
            --charcoal: #1A1A1A;
            --grey: #6F6F6F;
            --white: #FFFFFF;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--charcoal);
            background: var(--white);
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 500;
            line-height: 1.2;
        }

        /* Header Styles */
        .header-top {
            background: var(--charcoal);
            color: var(--white);
            padding: 8px 0;
            font-size: 12px;
            text-align: center;
        }

        .main-header {
            background: var(--white);
            border-bottom: 1px solid rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo img {
            height: 100px;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 600;
            color: var(--charcoal);
            letter-spacing: 2px;
        }

        .main-nav {
            display: flex;
            gap: 40px;
            flex: 1;
            justify-content: center;
        }

        .main-nav a {
            color: var(--charcoal);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
            text-transform: uppercase;
        }

        .main-nav a:hover {
            color: var(--latte);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .search-toggle,
        .cart-icon,
        .lang-switcher {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: var(--charcoal);
            transition: color 0.3s ease;
            position: relative;
        }

        .search-toggle:hover,
        .cart-icon:hover {
            color: var(--latte);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--charcoal);
            color: var(--white);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
        }

        .lang-switcher {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            padding: 6px 12px;
            border: 1px solid rgba(0,0,0,0.15);
            border-radius: 20px;
        }

        .search-bar {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 30px 40px;
            animation: slideDown 0.3s ease;
        }

        .search-bar.active {
            display: block;
        }

        .search-bar form {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 16px 50px 16px 20px;
            border: 1px solid rgba(0,0,0,0.15);
            border-radius: 30px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }

        .search-bar button {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        @media (max-width: 968px) {
            .header-container {
                padding: 16px 24px;
            }

            .main-nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--white);
                flex-direction: column;
                padding: 30px 24px;
                border-bottom: 1px solid rgba(0,0,0,0.08);
                gap: 20px;
            }

            .main-nav.active {
                display: flex;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .logo img {
                height: 45px;
            }

            .logo-text {
                font-size: 24px;
            }

            .search-bar {
                padding: 20px 24px;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 24px;
            }
        }

        /* Marquee Announcement */
        .announcement-bar {
            background: linear-gradient(90deg, #1A1A1A 0%, #333333 100%);
            color: white;
            padding: 12px 0;
            overflow: hidden;
            position: relative;
        }

        .announcement-content {
            display: inline-block;
            white-space: nowrap;
            animation: marquee 30s linear infinite;
            padding-left: 100%;
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        .announcement-bar:hover .announcement-content {
            animation-play-state: paused;
        }

        /* Floating WhatsApp Button */
        .whatsapp-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            background: #25D366;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            text-decoration: none;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        .whatsapp-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
        }

        .whatsapp-float svg {
            width: 32px;
            height: 32px;
            fill: white;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            }
            50% {
                box-shadow: 0 4px 20px rgba(37, 211, 102, 0.7);
            }
        }

        @media (max-width: 768px) {
            .whatsapp-float {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
            }

            .whatsapp-float svg {
                width: 26px;
                height: 26px;
            }
        }

        /* Promotion Banner Modal */
        .promo-banner-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .promo-banner-overlay.active {
            display: flex;
        }

        .promo-banner-content {
            position: relative;
            max-width: 600px;
            width: 90%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            animation: slideUp 0.4s ease;
        }

        .promo-banner-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .promo-banner-close:hover {
            background: #1A1A1A;
            transform: rotate(90deg);
        }

        .promo-banner-image {
            width: 100%;
            cursor: pointer;
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <?php
    // Output JSON-LD structured data
    // SEO JSON-LD DISABLED
    // if (isset($json_schemas) && is_array($json_schemas)) {
    //     outputSchemas($json_schemas);
    // } else {
    //     $default_schemas = [
    //         generateOrganizationSchema(),
    //         generateWebsiteSchema()
    //     ];
    //     outputSchemas($default_schemas);
    // }
    ?>
</head>
<body>
    <div class="header-top">
        Free shipping on orders over Rp 500.000 | Express delivery available
    </div>

    <header class="main-header">
        <div class="header-container">
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>

            <a href="/" class="logo">
                <img src="/public/images/logo.png" alt="Dorve Logo">
            </a>

            <nav class="main-nav" id="mainNav">
                <a href="/"><?php echo t('home'); ?></a>
                <a href="/pages/new-collection.php"><?php echo t('new_collection'); ?></a>
                <a href="/pages/all-products.php"><?php echo t('all_products'); ?></a>
                <a href="/pages/our-story.php"><?php echo t('our_story'); ?></a>
                <a href="/pages/faq.php"><?php echo t('faq'); ?></a>
            </nav>

            <div class="header-actions">
                <button class="search-toggle" onclick="toggleSearch()">🔍</button>

                <form action="/pages/lang-switch.php" method="POST" style="display: inline;">
                    <button type="submit" name="lang" value="<?php echo $current_lang === 'id' ? 'en' : 'id'; ?>" class="lang-switcher">
                        <?php echo $current_lang === 'id' ? 'EN' : 'ID'; ?>
                    </button>
                </form>

                <a href="/pages/cart.php" class="cart-icon" style="text-decoration: none;">
                    🛍️
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isLoggedIn()): ?>
                    <a href="/member/dashboard.php" style="text-decoration: none; color: var(--charcoal); font-size: 14px;">Account</a>
                <?php else: ?>
                    <a href="/auth/login.php" style="text-decoration: none; color: var(--charcoal); font-size: 14px;">Login</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="search-bar" id="searchBar">
            <form action="/pages/search.php" method="GET">
                <input type="text" name="q" placeholder="Search for products..." required>
                <button type="submit">→</button>
            </form>
        </div>
    </header>

    <?php
    // Get announcement text from database
    $announcement_text = "🎉 Welcome to Dorve House! New Collection 2024 Available Now! FREE Shipping for orders above Rp 500.000 🎉";
    try {
        $stmt = $pdo->query("SELECT value FROM settings WHERE setting_key = 'announcement_text' LIMIT 1");
        $result = $stmt->fetch();
        if ($result) {
            $announcement_text = $result['value'];
        }
    } catch (PDOException $e) {
        // Use default if table doesn't exist
    }

    // Get promotion banner settings
    $promo_enabled = false;
    $promo_image = '';
    $promo_link = '/pages/all-products.php';
    try {
        $stmt = $pdo->query("SELECT setting_key, value FROM settings WHERE setting_key IN ('promo_banner_enabled', 'promo_banner_image', 'promo_banner_link')");
        while ($row = $stmt->fetch()) {
            if ($row['setting_key'] === 'promo_banner_enabled') {
                $promo_enabled = ($row['value'] === '1' || $row['value'] === 'on');
            } elseif ($row['setting_key'] === 'promo_banner_image') {
                $promo_image = $row['value'];
            } elseif ($row['setting_key'] === 'promo_banner_link') {
                $promo_link = $row['value'] ?: '/pages/all-products.php';
            }
        }
    } catch (PDOException $e) {
        // Use defaults if table doesn't exist
    }
    ?>

    <!-- Marquee Announcement Bar -->
    <div class="announcement-bar">
        <div class="announcement-content">
            <?php echo htmlspecialchars($announcement_text); ?>
        </div>
    </div>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/6281377378859" target="_blank" class="whatsapp-float" aria-label="Chat on WhatsApp">
        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 0c-8.837 0-16 7.163-16 16 0 2.825 0.737 5.607 2.137 8.048l-2.137 7.952 7.933-2.127c2.42 1.37 5.173 2.127 8.067 2.127 8.837 0 16-7.163 16-16s-7.163-16-16-16zM16 29.467c-2.482 0-4.908-0.646-7.07-1.87l-0.507-0.292-4.713 1.262 1.262-4.669-0.292-0.508c-1.207-2.100-1.847-4.507-1.847-6.923 0-7.435 6.049-13.483 13.483-13.483s13.483 6.049 13.483 13.483c0 7.435-6.049 13.483-13.483 13.483zM21.883 18.908c-0.305-0.152-1.807-0.892-2.087-0.994-0.28-0.102-0.483-0.152-0.686 0.152s-0.788 0.994-0.967 1.198c-0.178 0.203-0.356 0.229-0.661 0.076-0.305-0.152-1.288-0.475-2.454-1.514-0.908-0.809-1.521-1.809-1.699-2.114s-0.019-0.467 0.134-0.619c0.137-0.137 0.305-0.356 0.458-0.534 0.152-0.178 0.203-0.305 0.305-0.508 0.102-0.203 0.051-0.381-0.025-0.534-0.076-0.152-0.686-1.653-0.940-2.261-0.248-0.593-0.499-0.513-0.686-0.522-0.178-0.008-0.381-0.010-0.584-0.010s-0.534 0.076-0.813 0.381c-0.28 0.305-1.067 1.043-1.067 2.543s1.093 2.951 1.245 3.155c0.152 0.203 2.146 3.279 5.201 4.598 0.727 0.313 1.295 0.500 1.738 0.640 0.731 0.232 1.397 0.199 1.923 0.121 0.587-0.088 1.807-0.739 2.063-1.453 0.255-0.714 0.255-1.326 0.178-1.453-0.076-0.127-0.28-0.203-0.584-0.356z"/>
        </svg>
    </a>

    <!-- Promotion Banner Modal -->
    <?php if ($promo_enabled && $promo_image): ?>
    <div class="promo-banner-overlay" id="promoBanner">
        <div class="promo-banner-content">
            <button class="promo-banner-close" onclick="closePromoBanner()">×</button>
            <a href="<?php echo htmlspecialchars($promo_link); ?>">
                <img src="<?php echo UPLOAD_URL . htmlspecialchars($promo_image); ?>"
                     alt="Promotion Banner"
                     class="promo-banner-image">
            </a>
        </div>
    </div>

    <script>
        // Show promotion banner after 3 seconds
        setTimeout(function() {
            const promoBanner = document.getElementById('promoBanner');
            if (promoBanner && !sessionStorage.getItem('promoBannerShown')) {
                promoBanner.classList.add('active');
                sessionStorage.setItem('promoBannerShown', 'true');
            }
        }, 3000);

        function closePromoBanner() {
            document.getElementById('promoBanner').classList.remove('active');
        }

        // Close on overlay click
        document.getElementById('promoBanner')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closePromoBanner();
            }
        });
    </script>
    <?php endif; ?>

    <script>
        function toggleSearch() {
            document.getElementById('searchBar').classList.toggle('active');
        }

        function toggleMobileMenu() {
            document.getElementById('mainNav').classList.toggle('active');
        }

        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            if (window.scrollY > 100) {
                header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.08)';
            } else {
                header.style.boxShadow = 'none';
            }
        });
    </script>
