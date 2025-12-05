<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel - Dorve House'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/admin-style.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" aria-label="Toggle Menu">
            ☰
        </button>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay"></div>

        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                DORVE
            </div>

            <nav class="admin-nav">
                <a href="/admin/index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && !isset($_GET['page']) ? 'active' : ''; ?>">
                    📊 Dashboard
                </a>
                <a href="/admin/products/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/products/') !== false ? 'active' : ''; ?>">
                    🛍️ Produk
                </a>
                <a href="/admin/categories/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/categories/') !== false ? 'active' : ''; ?>">
                    📁 Kategori
                </a>
                <a href="/admin/orders/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/orders/') !== false ? 'active' : ''; ?>">
                    📦 Pesanan
                </a>
                <a href="/admin/users/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>">
                    👥 Pengguna
                </a>
                <a href="/admin/deposits/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/deposits/') !== false ? 'active' : ''; ?>">
                    💰 Deposit Wallet
                </a>
                <a href="/admin/referrals/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/referrals/') !== false ? 'active' : ''; ?>">
                    🤝 Referral
                </a>
                <a href="/admin/vouchers/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/vouchers/') !== false ? 'active' : ''; ?>">
                    🎫 Voucher
                </a>
                <a href="/admin/shipping/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/shipping/') !== false ? 'active' : ''; ?>">
                    🚚 Pengiriman
                </a>
                <a href="/admin/promotion/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/promotion/') !== false ? 'active' : ''; ?>">
                    📢 Promosi & Banner
                </a>

                <!-- Settings Section -->
                <div style="margin: 12px 30px 8px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <div style="color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 8px;">Pengaturan</div>
                </div>

                <a href="/admin/settings/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/index.php') !== false ? 'active' : ''; ?>">
                    ⚙️ Pengaturan Umum
                </a>
                <a href="/admin/settings/payment-settings.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/payment-settings.php') !== false ? 'active' : ''; ?>">
                    💳 Payment Gateway
                </a>
                <a href="/admin/settings/bank-accounts.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/bank-accounts.php') !== false ? 'active' : ''; ?>">
                    🏦 Bank Accounts
                </a>
                <a href="/admin/settings/referral-settings.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/referral-settings.php') !== false ? 'active' : ''; ?>">
                    🎁 Referral Settings
                </a>
                <a href="/admin/settings/api-settings.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/api-settings.php') !== false ? 'active' : ''; ?>">
                    🔌 API & Integrasi
                </a>
                <a href="/admin/pages/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/pages/') !== false ? 'active' : ''; ?>">
                    📄 Halaman CMS
                </a>

                <hr style="margin: 20px 30px; border: none; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="/" class="nav-item" target="_blank">
                    🌐 Lihat Website
                </a>
                <a href="/auth/logout.php" class="nav-item">
                    🚪 Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
