<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$order_count = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

$page_title = 'Akun Saya - Dashboard Member Dorve House | Kelola Pesanan & Profil';
$page_description = 'Dashboard akun member Dorve. Lihat riwayat pesanan, status pengiriman, wallet, dan kelola profil Anda. Belanja baju wanita online jadi lebih mudah.';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .member-layout {
        max-width: 1400px;
        margin: 80px auto;
        padding: 0 40px;
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 60px;
    }

    .member-sidebar {
        position: sticky;
        top: 120px;
        height: fit-content;
    }

    .sidebar-header {
        padding: 30px;
        background: var(--cream);
        margin-bottom: 24px;
        border-radius: 8px;
    }

    .sidebar-header h3 {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        margin-bottom: 8px;
    }

    .sidebar-header p {
        font-size: 14px;
        color: var(--grey);
    }

    .sidebar-nav {
        list-style: none;
    }

    .sidebar-nav li {
        margin-bottom: 8px;
    }

    .sidebar-nav a {
        display: block;
        padding: 14px 20px;
        color: var(--charcoal);
        text-decoration: none;
        transition: all 0.3s;
        border-radius: 4px;
        font-size: 14px;
    }

    .sidebar-nav a:hover,
    .sidebar-nav a.active {
        background: var(--cream);
        padding-left: 28px;
    }

    .logout-btn {
        margin-top: 24px;
        display: block;
        width: 100%;
        padding: 14px 20px;
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.15);
        color: #C41E3A;
        text-decoration: none;
        text-align: center;
        border-radius: 4px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .logout-btn:hover {
        background: #C41E3A;
        color: var(--white);
    }

    .member-content h1 {
        font-family: 'Playfair Display', serif;
        font-size: 42px;
        margin-bottom: 40px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
        margin-bottom: 60px;
    }

    .stat-card {
        padding: 30px;
        background: var(--cream);
        border-radius: 8px;
        text-align: center;
    }

    .stat-value {
        font-size: 36px;
        font-weight: 600;
        color: var(--charcoal);
        margin-bottom: 8px;
    }

    .stat-label {
        font-size: 14px;
        color: var(--grey);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        margin-bottom: 30px;
    }

    .order-list {
        border-top: 1px solid rgba(0,0,0,0.08);
    }

    .order-item {
        padding: 24px 0;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 24px;
    }

    .order-number {
        font-weight: 600;
        margin-bottom: 8px;
    }

    .order-date {
        font-size: 13px;
        color: var(--grey);
        margin-bottom: 8px;
    }

    .order-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-pending {
        background: #FFF3CD;
        color: #856404;
    }

    .status-paid {
        background: #D4EDDA;
        color: #155724;
    }

    .status-shipped {
        background: #D1ECF1;
        color: #0C5460;
    }

    .order-total {
        text-align: right;
    }

    .order-price {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 12px;
    }

    .view-order-btn {
        padding: 8px 20px;
        background: var(--charcoal);
        color: var(--white);
        text-decoration: none;
        font-size: 12px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (max-width: 968px) {
        .member-layout {
            grid-template-columns: 1fr;
            gap: 40px;
            padding: 40px 24px;
        }

        .member-sidebar {
            position: static;
        }

        .order-item {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="member-layout">
    <aside class="member-sidebar">
        <div class="sidebar-header">
            <h3>Welcome back!</h3>
            <p><?php echo htmlspecialchars($user['name']); ?></p>
        </div>

        <ul class="sidebar-nav">
            <li><a href="/member/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="/member/wallet.php">My Wallet</a></li>
            <li><a href="/member/orders.php">My Orders</a></li>
            <li><a href="/member/referral.php">🎁 My Referrals</a></li>
            <li><a href="/member/reviews.php">My Reviews</a></li>
            <li><a href="/member/profile.php">Edit Profile</a></li>
            <li><a href="/member/address.php">Address Book</a></li>
            <li><a href="/member/password.php">Change Password</a></li>
        </ul>

        <a href="/auth/logout.php" class="logout-btn">Logout</a>
    </aside>

    <div class="member-content">
        <h1>My Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $order_count; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-value">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND payment_status = 'paid'");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo $stmt->fetch()['count'];
                    ?>
                </div>
                <div class="stat-label">Completed</div>
            </div>

            <div class="stat-card">
                <div class="stat-value">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo $stmt->fetch()['count'];
                    ?>
                </div>
                <div class="stat-label">Reviews Written</div>
            </div>
        </div>

        <!-- Tier Status Section -->
        <?php
        $current_tier = $user['current_tier'] ?? 'bronze';
        $total_topup = floatval($user['total_topup'] ?? 0);

        // Tier requirements
        $tiers = [
            'bronze' => ['name' => 'Bronze', 'min' => 0, 'max' => 999999, 'next' => 'silver', 'next_min' => 1000000, 'discount' => '0%', 'benefits' => ['Akses ke semua produk', 'Customer support standard']],
            'silver' => ['name' => 'Silver', 'min' => 1000000, 'max' => 4999999, 'next' => 'gold', 'next_min' => 5000000, 'discount' => '5%', 'benefits' => ['Diskon 5% setiap pembelian', 'Priority customer support', 'Akses flash sale eksklusif']],
            'gold' => ['name' => 'Gold', 'min' => 5000000, 'max' => 9999999, 'next' => 'platinum', 'next_min' => 10000000, 'discount' => '10%', 'benefits' => ['Diskon 10% setiap pembelian', 'Free shipping semua pesanan', 'Priority customer support', 'Akses early bird sale']],
            'platinum' => ['name' => 'Platinum', 'min' => 10000000, 'max' => PHP_INT_MAX, 'next' => null, 'next_min' => 0, 'discount' => '15%', 'benefits' => ['Diskon 15% setiap pembelian', 'Free shipping & packing premium', 'Dedicated account manager', 'Akses koleksi eksklusif', 'Birthday special gift']]
        ];

        $tier_info = $tiers[$current_tier];
        $next_tier = $tier_info['next'];
        $progress_percent = 0;

        if ($next_tier) {
            $current_min = $tier_info['min'];
            $next_min = $tier_info['next_min'];
            $progress = $total_topup - $current_min;
            $range = $next_min - $current_min;
            $progress_percent = min(100, ($progress / $range) * 100);
        } else {
            $progress_percent = 100;
        }
        ?>

        <div style="background: linear-gradient(135deg, #1A1A1A 0%, #3A3A3A 100%); padding: 40px; border-radius: 12px; margin: 48px 0; color: white;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px; align-items: center;">
                <div>
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                        <div style="font-size: 48px;">
                            <?php
                            $tier_icons = ['bronze' => '🥉', 'silver' => '🥈', 'gold' => '🥇', 'platinum' => '💎'];
                            echo $tier_icons[$current_tier];
                            ?>
                        </div>
                        <div>
                            <h2 style="font-family: 'Playfair Display', serif; font-size: 36px; margin-bottom: 4px; text-transform: capitalize;"><?php echo $tier_info['name']; ?> Member</h2>
                            <p style="opacity: 0.8; font-size: 14px;">Total Topup: Rp <?php echo number_format($total_topup, 0, ',', '.'); ?></p>
                        </div>
                    </div>

                    <?php if ($next_tier): ?>
                        <div style="margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span>Progress ke <?php echo $tiers[$next_tier]['name']; ?></span>
                                <span><?php echo number_format($progress_percent, 1); ?>%</span>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); height: 12px; border-radius: 20px; overflow: hidden;">
                                <div style="background: linear-gradient(90deg, #D4C5B9 0%, #F5E6D3 100%); height: 100%; width: <?php echo $progress_percent; ?>%; transition: width 0.5s;"></div>
                            </div>
                            <p style="font-size: 13px; opacity: 0.8; margin-top: 8px;">
                                Topup Rp <?php echo number_format($tier_info['next_min'] - $total_topup, 0, ',', '.'); ?> lagi untuk naik ke <?php echo $tiers[$next_tier]['name']; ?>!
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(212, 197, 185, 0.2); padding: 16px; border-radius: 8px; border-left: 4px solid #D4C5B9;">
                            <p style="font-size: 14px; margin: 0;">🎉 Selamat! Anda sudah mencapai tier tertinggi!</p>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 24px;">
                        <a href="/member/wallet.php" style="display: inline-block; padding: 14px 32px; background: white; color: #1A1A1A; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; letter-spacing: 0.5px; text-transform: uppercase;">Topup Sekarang</a>
                    </div>
                </div>

                <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 8px;">
                    <h3 style="font-size: 18px; margin-bottom: 20px; font-weight: 600;">Benefit Tier Anda</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($tier_info['benefits'] as $benefit): ?>
                            <li style="padding: 10px 0; padding-left: 28px; position: relative; font-size: 14px; line-height: 1.5;">
                                <span style="position: absolute; left: 0; top: 10px;">✓</span>
                                <?php echo htmlspecialchars($benefit); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($tier_info['discount'] != '0%'): ?>
                        <div style="margin-top: 20px; padding: 16px; background: rgba(212, 197, 185, 0.3); border-radius: 6px; text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; font-family: 'Playfair Display', serif;"><?php echo $tier_info['discount']; ?></div>
                            <div style="font-size: 12px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">Auto Discount</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- All Tiers Info -->
        <div style="background: #F8F8F8; padding: 48px; border-radius: 12px; margin-bottom: 48px;">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; text-align: center; margin-bottom: 16px;">Membership Tiers</h2>
            <p style="text-align: center; color: #6B6B6B; margin-bottom: 48px;">Semakin banyak topup, semakin besar benefit yang Anda dapatkan</p>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;">
                <?php foreach ($tiers as $tier_key => $tier_data): ?>
                    <div style="background: white; padding: 32px; border-radius: 8px; border: 2px solid <?php echo $tier_key === $current_tier ? '#1A1A1A' : 'transparent'; ?>; position: relative;">
                        <?php if ($tier_key === $current_tier): ?>
                            <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #1A1A1A; color: white; padding: 4px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Your Tier</div>
                        <?php endif; ?>

                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="font-size: 48px; margin-bottom: 12px;">
                                <?php echo $tier_icons[$tier_key]; ?>
                            </div>
                            <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 8px; text-transform: capitalize;"><?php echo $tier_data['name']; ?></h3>
                            <p style="font-size: 13px; color: #6B6B6B;">
                                <?php if ($tier_key === 'platinum'): ?>
                                    ≥ Rp 10,000,000
                                <?php else: ?>
                                    Rp <?php echo number_format($tier_data['min'], 0, ',', '.'); ?> - <?php echo number_format($tier_data['max'], 0, ',', '.'); ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if ($tier_data['discount'] != '0%'): ?>
                            <div style="text-align: center; padding: 16px; background: #F5F5F5; border-radius: 6px; margin-bottom: 20px;">
                                <div style="font-size: 28px; font-weight: 700; color: #1A1A1A;"><?php echo $tier_data['discount']; ?></div>
                                <div style="font-size: 11px; color: #6B6B6B; text-transform: uppercase; letter-spacing: 1px;">Discount</div>
                            </div>
                        <?php endif; ?>

                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($tier_data['benefits'] as $benefit): ?>
                                <li style="padding: 8px 0; padding-left: 24px; position: relative; font-size: 12px; color: #4A4A4A; line-height: 1.4;">
                                    <span style="position: absolute; left: 0; top: 8px; color: #1A1A1A;">✓</span>
                                    <?php echo htmlspecialchars($benefit); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h2 class="section-title">Recent Orders</h2>

        <?php if (empty($recent_orders)): ?>
            <div style="text-align: center; padding: 60px 20px; background: var(--cream); border-radius: 8px;">
                <p style="color: var(--grey); margin-bottom: 24px;">You haven't placed any orders yet.</p>
                <a href="/pages/all-products.php" style="display: inline-block; padding: 14px 32px; background: var(--charcoal); color: var(--white); text-decoration: none; border-radius: 4px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div>
                            <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                            <span class="order-status status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                        <div class="order-total">
                            <div class="order-price"><?php echo formatPrice($order['total_amount']); ?></div>
                            <a href="/member/order-detail.php?id=<?php echo $order['id']; ?>" class="view-order-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="text-align: center; margin-top: 40px;">
                <a href="/member/orders.php" style="color: var(--charcoal); text-decoration: none; font-weight: 500;">View All Orders →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
