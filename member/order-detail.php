<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();
$id = $_GET['id'] ?? 0;

// Get order details - make sure it belongs to current user
$stmt = $pdo->prepare("
    SELECT * FROM orders
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/member/orders.php');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.slug as product_slug
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$page_title = 'Order Detail - Dorve';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .order-detail-container {
        max-width: 1200px;
        margin: 120px auto 80px;
        padding: 0 40px;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--charcoal);
        text-decoration: none;
        margin-bottom: 30px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .back-link:hover {
        gap: 12px;
    }

    .page-header {
        margin-bottom: 40px;
    }

    .page-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 40px;
        margin-bottom: 12px;
    }

    .order-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }

    .info-card {
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        padding: 24px;
    }

    .info-card h3 {
        font-size: 14px;
        color: var(--grey);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-card .value {
        font-size: 18px;
        font-weight: 600;
        color: var(--charcoal);
    }

    .tracking-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 32px;
        border-radius: 12px;
        margin-bottom: 40px;
    }

    .tracking-card h3 {
        font-size: 16px;
        margin-bottom: 16px;
        opacity: 0.9;
    }

    .tracking-number {
        font-family: 'Courier New', monospace;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: 3px;
        margin-bottom: 20px;
    }

    .tracking-links {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .tracking-link {
        padding: 10px 20px;
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .tracking-link:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
    }

    .status-timeline {
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        padding: 32px;
        margin-bottom: 40px;
    }

    .timeline-title {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        margin-bottom: 30px;
    }

    .timeline {
        position: relative;
        padding-left: 40px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #E8E8E8;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 30px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -36px;
        top: 4px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--white);
        border: 3px solid #E8E8E8;
    }

    .timeline-item.active::before {
        border-color: #28a745;
        background: #28a745;
    }

    .timeline-item.active {
        color: var(--charcoal);
    }

    .timeline-item:not(.active) {
        color: var(--grey);
        opacity: 0.6;
    }

    .timeline-status {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 4px;
    }

    .timeline-date {
        font-size: 13px;
        color: var(--grey);
    }

    .items-card {
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        padding: 32px;
        margin-bottom: 40px;
    }

    .items-title {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        margin-bottom: 24px;
    }

    .item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-info {
        flex: 1;
    }

    .item-name {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 4px;
    }

    .item-variant {
        font-size: 14px;
        color: var(--grey);
    }

    .item-price {
        font-size: 16px;
        color: var(--charcoal);
        margin: 0 20px;
    }

    .item-quantity {
        font-size: 14px;
        color: var(--grey);
        margin: 0 20px;
    }

    .item-total {
        font-weight: 600;
        font-size: 18px;
        color: var(--charcoal);
        min-width: 120px;
        text-align: right;
    }

    .order-summary {
        background: var(--cream);
        border-radius: 8px;
        padding: 24px;
        margin-top: 24px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        font-size: 15px;
    }

    .summary-row.total {
        border-top: 2px solid rgba(0,0,0,0.1);
        margin-top: 12px;
        padding-top: 16px;
        font-size: 20px;
        font-weight: 700;
        font-family: 'Playfair Display', serif;
    }

    .shipping-address-card {
        background: var(--white);
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
        padding: 32px;
    }

    .address-title {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        margin-bottom: 16px;
    }

    .address-text {
        line-height: 1.8;
        color: var(--charcoal);
    }

    .status-badge {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }

    .status-paid { background: #D4EDDA; color: #155724; }
    .status-pending { background: #FFF3CD; color: #856404; }
    .status-delivered { background: #D4EDDA; color: #155724; }
    .status-shipped { background: #D1ECF1; color: #0C5460; }
    .status-processing { background: #D1ECF1; color: #0C5460; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    .status-failed { background: #f8d7da; color: #721c24; }
</style>

<div class="order-detail-container">
    <a href="/member/orders.php" class="back-link">
        ← Kembali ke Pesanan Saya
    </a>

    <div class="page-header">
        <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
        <p style="color: var(--grey);">Dipesan pada <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB</p>
    </div>

    <!-- Order Info Grid -->
    <div class="order-info-grid">
        <div class="info-card">
            <h3>Status Pembayaran</h3>
            <div class="value">
                <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                    <?php
                    $payment_labels = [
                        'pending' => 'Menunggu',
                        'paid' => 'Lunas',
                        'failed' => 'Gagal',
                        'refunded' => 'Refund'
                    ];
                    echo $payment_labels[$order['payment_status']];
                    ?>
                </span>
            </div>
        </div>

        <div class="info-card">
            <h3>Status Pengiriman</h3>
            <div class="value">
                <span class="status-badge status-<?php echo $order['shipping_status']; ?>">
                    <?php
                    $shipping_labels = [
                        'pending' => 'Menunggu',
                        'processing' => 'Diproses',
                        'shipped' => 'Dikirim',
                        'delivered' => 'Selesai',
                        'cancelled' => 'Dibatalkan'
                    ];
                    echo $shipping_labels[$order['shipping_status']];
                    ?>
                </span>
            </div>
        </div>

        <div class="info-card">
            <h3>Metode Pembayaran</h3>
            <div class="value"><?php echo htmlspecialchars(strtoupper($order['payment_method'])); ?></div>
        </div>

        <div class="info-card">
            <h3>Total Pembayaran</h3>
            <div class="value" style="color: #28a745;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></div>
        </div>
    </div>

    <!-- Tracking Card -->
    <?php if ($order['tracking_number']): ?>
    <div class="tracking-card">
        <h3>📦 NOMOR RESI PENGIRIMAN</h3>
        <div class="tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
        <p style="opacity: 0.9; margin-bottom: 20px;">Gunakan nomor resi di atas untuk melacak paket Anda</p>
        <div class="tracking-links">
            <a href="https://www.jne.co.id/id/tracking/trace" target="_blank" class="tracking-link">🚚 Lacak di JNE</a>
            <a href="https://www.jet.co.id/track" target="_blank" class="tracking-link">🚚 Lacak di J&T</a>
            <a href="https://sicepat.com/checkAwb" target="_blank" class="tracking-link">🚚 Lacak di SiCepat</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Timeline -->
    <div class="status-timeline">
        <h2 class="timeline-title">Status Pesanan</h2>
        <div class="timeline">
            <div class="timeline-item <?php echo in_array($order['shipping_status'], ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                <div class="timeline-status">Pesanan Dibuat</div>
                <div class="timeline-date"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></div>
            </div>

            <?php if ($order['payment_status'] === 'paid'): ?>
            <div class="timeline-item active">
                <div class="timeline-status">Pembayaran Diterima</div>
                <div class="timeline-date">Verified</div>
            </div>
            <?php endif; ?>

            <div class="timeline-item <?php echo in_array($order['shipping_status'], ['processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                <div class="timeline-status">Pesanan Diproses</div>
                <div class="timeline-date"><?php echo $order['shipping_status'] === 'processing' ? 'Sedang dikemas' : ''; ?></div>
            </div>

            <div class="timeline-item <?php echo in_array($order['shipping_status'], ['shipped', 'delivered']) ? 'active' : ''; ?>">
                <div class="timeline-status">Pesanan Dikirim</div>
                <?php if ($order['tracking_number']): ?>
                    <div class="timeline-date">Resi: <?php echo htmlspecialchars($order['tracking_number']); ?></div>
                <?php endif; ?>
            </div>

            <div class="timeline-item <?php echo $order['shipping_status'] === 'delivered' ? 'active' : ''; ?>">
                <div class="timeline-status">Pesanan Diterima</div>
                <div class="timeline-date"><?php echo $order['shipping_status'] === 'delivered' ? 'Selesai' : 'Menunggu konfirmasi'; ?></div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="items-card">
        <h2 class="items-title">Detail Produk</h2>
        <?php foreach ($items as $item): ?>
        <div class="item-row">
            <div class="item-info">
                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                <?php if ($item['variant_info']): ?>
                    <div class="item-variant"><?php echo htmlspecialchars($item['variant_info']); ?></div>
                <?php endif; ?>
            </div>
            <div class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
            <div class="item-quantity">x <?php echo $item['quantity']; ?></div>
            <div class="item-total">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></div>
        </div>
        <?php endforeach; ?>

        <div class="order-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>Rp <?php echo number_format($order['total_amount'] - $order['shipping_cost'] + $order['discount_amount'], 0, ',', '.'); ?></span>
            </div>
            <?php if ($order['discount_amount'] > 0): ?>
            <div class="summary-row" style="color: #28a745;">
                <span>Diskon <?php echo $order['voucher_code'] ? '(' . $order['voucher_code'] . ')' : ''; ?>:</span>
                <span>- Rp <?php echo number_format($order['discount_amount'], 0, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Ongkos Kirim:</span>
                <span>Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></span>
            </div>
            <div class="summary-row total">
                <span>TOTAL:</span>
                <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <!-- Shipping Address -->
    <div class="shipping-address-card">
        <h2 class="address-title">Alamat Pengiriman</h2>
        <div class="address-text">
            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
