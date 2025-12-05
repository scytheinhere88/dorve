<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$page_title = 'Pesanan Saya - Cek Status & Riwayat Belanja | Dorve House';
$page_description = 'Lihat semua pesanan baju wanita Anda di Dorve House. Cek status pengiriman, detail pesanan, dan riwayat transaksi. Track pesanan dengan mudah.';
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
        font-size: 36px;
        margin-bottom: 40px;
    }

    .order-card {
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 24px;
        transition: all 0.3s;
    }

    .order-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .order-number {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .order-date {
        font-size: 14px;
        color: var(--grey);
    }

    .order-status {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .status-badge {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-paid {
        background: #D4EDDA;
        color: #155724;
    }

    .status-pending {
        background: #FFF3CD;
        color: #856404;
    }

    .status-delivered {
        background: #D4EDDA;
        color: #155724;
    }

    .status-processing {
        background: #D1ECF1;
        color: #0C5460;
    }

    .order-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .detail-item {
        font-size: 14px;
    }

    .detail-label {
        color: var(--grey);
        margin-bottom: 4px;
    }

    .detail-value {
        font-weight: 600;
        color: var(--charcoal);
    }

    .order-total {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        font-weight: 600;
        color: var(--charcoal);
    }

    .order-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-block;
        text-align: center;
    }

    .btn-primary {
        background: var(--charcoal);
        color: var(--white);
    }

    .btn-secondary {
        background: var(--white);
        color: var(--charcoal);
        border: 1px solid rgba(0,0,0,0.15);
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .empty-state {
        text-align: center;
        padding: 80px 40px;
    }

    .empty-state h3 {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        margin-bottom: 16px;
    }

    .empty-state p {
        color: var(--grey);
        margin-bottom: 30px;
    }
</style>

<div class="member-layout">
    <aside class="member-sidebar">
        <div class="sidebar-header">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <ul class="sidebar-nav">
            <li><a href="/member/dashboard.php">Dashboard</a></li>
            <li><a href="/member/wallet.php">My Wallet</a></li>
            <li><a href="/member/orders.php" class="active">My Orders</a></li>
            <li><a href="/member/reviews.php">Reviews</a></li>
            <li><a href="/member/profile.php">Edit Profile</a></li>
            <li><a href="/member/address.php">Address Book</a></li>
            <li><a href="/member/password.php">Change Password</a></li>
        </ul>

        <a href="/auth/logout.php" class="logout-btn">Logout</a>
    </aside>

    <div class="member-content">
        <h1>My Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders. Start shopping to see your orders here.</p>
                <a href="/pages/all-products.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                <button onclick="copyOrderId('<?php echo htmlspecialchars($order['order_number']); ?>')" 
                                        style="padding: 4px 12px; background: #E5E7EB; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; color: #374151;"
                                        title="Copy Order ID">
                                    📋 Copy ID
                                </button>
                            </div>
                            <div class="order-date">Ordered on <?php echo date('F d, Y', strtotime($order['created_at'])); ?></div>
                            <div style="font-size: 12px; color: #9CA3AF; margin-top: 4px;">
                                Order ID: <code style="background: #F3F4F6; padding: 2px 6px; border-radius: 4px; font-weight: 600;">#<?php echo $order['id']; ?></code>
                            </div>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                            <span class="status-badge status-<?php echo $order['shipping_status']; ?>">
                                <?php echo ucfirst($order['shipping_status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo ucfirst($order['payment_method']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Shipping Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars(explode(',', $order['shipping_address'])[0]); ?></div>
                        </div>
                        <?php if ($order['tracking_number']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Tracking Number</div>
                            <div class="detail-value" style="font-family: 'Courier New', monospace; color: #0066cc;">
                                <?php echo htmlspecialchars($order['tracking_number']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="order-total"><?php echo formatPrice($order['total_amount']); ?></div>
                        </div>
                    </div>

                    <div class="order-actions">
                        <a href="/member/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">View Details</a>
                        <?php if ($order['shipping_status'] === 'delivered'): ?>
                            <a href="/member/reviews.php?order=<?php echo $order['id']; ?>" class="btn btn-secondary">Write Review</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
