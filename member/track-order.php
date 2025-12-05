<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$order_id = $_GET['id'] ?? 0;
$user = getCurrentUser();

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error_message'] = 'Order not found!';
    header('Location: /member/orders.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.slug
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Get order timeline
$timeline = [];
try {
    $stmt = $pdo->prepare("
        SELECT ot.*, u.name as updated_by_name
        FROM order_timeline ot
        LEFT JOIN users u ON ot.created_by = u.id
        WHERE ot.order_id = ?
        ORDER BY ot.created_at ASC
    ");
    $stmt->execute([$order_id]);
    $timeline = $stmt->fetchAll();
} catch (PDOException $e) {
    // Timeline table might not exist
}

// If no timeline, create basic one from order status
if (empty($timeline)) {
    $timeline = [
        [
            'status' => $order['status'],
            'title' => match($order['status']) {
                'pending' => 'Order Placed',
                'processing' => 'Order Processing',
                'shipping' => 'Order Shipped',
                'delivered' => 'Order Delivered',
                'cancelled' => 'Order Cancelled',
                default => 'Order Status'
            },
            'description' => 'Order has been placed',
            'created_at' => $order['created_at']
        ]
    ];
}

// Status to progress map
$status_progress = [
    'pending' => 25,
    'processing' => 50,
    'shipping' => 75,
    'delivered' => 100,
    'cancelled' => 0
];

$progress = $status_progress[$order['status']] ?? 25;

$page_title = 'Track Order #' . $order['id'];
include __DIR__ . '/../includes/header.php';
?>

<style>
    .track-container {
        max-width: 1000px;
        margin: 100px auto;
        padding: 0 40px;
    }

    .track-header {
        background: var(--cream);
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .track-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        margin-bottom: 15px;
    }

    .order-status {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .status-pending { background: #FEF3C7; color: #92400E; }
    .status-processing { background: #DBEAFE; color: #1E40AF; }
    .status-shipping { background: #D1FAE5; color: #065F46; }
    .status-delivered { background: #D1FAE5; color: #065F46; }
    .status-cancelled { background: #FEE2E2; color: #991B1B; }

    .tracking-info {
        background: white;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .tracking-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .tracking-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .tracking-label {
        font-weight: 600;
        color: var(--grey);
        font-size: 14px;
    }

    .tracking-value {
        font-weight: 500;
        font-size: 15px;
    }

    .tracking-link {
        color: var(--charcoal);
        text-decoration: none;
        padding: 8px 16px;
        border: 2px solid var(--charcoal);
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .tracking-link:hover {
        background: var(--charcoal);
        color: white;
    }

    .progress-bar-container {
        background: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .progress-bar {
        width: 100%;
        height: 8px;
        background: #E8E8E8;
        border-radius: 10px;
        overflow: hidden;
        margin: 20px 0;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #10B981 0%, #059669 100%);
        transition: width 0.5s ease;
    }

    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
    }

    .progress-step {
        text-align: center;
        font-size: 12px;
        color: var(--grey);
        flex: 1;
    }

    .progress-step.active {
        color: var(--charcoal);
        font-weight: 600;
    }

    .timeline {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .timeline h2 {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        margin-bottom: 30px;
    }

    .timeline-item {
        position: relative;
        padding-left: 40px;
        padding-bottom: 30px;
        border-left: 2px solid #E8E8E8;
    }

    .timeline-item:last-child {
        border-left: 2px solid transparent;
        padding-bottom: 0;
    }

    .timeline-dot {
        position: absolute;
        left: -9px;
        top: 0;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: white;
        border: 3px solid #10B981;
    }

    .timeline-item.pending .timeline-dot {
        background: white;
        border-color: #E8E8E8;
    }

    .timeline-date {
        font-size: 12px;
        color: var(--grey);
        margin-bottom: 5px;
    }

    .timeline-title {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 5px;
    }

    .timeline-description {
        color: var(--grey);
        font-size: 14px;
        line-height: 1.6;
    }

    .items-section {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .items-section h3 {
        font-size: 20px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .item-row {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 15px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-name {
        flex: 1;
        font-weight: 500;
    }

    .item-variant {
        font-size: 13px;
        color: var(--grey);
    }

    .item-qty {
        font-weight: 600;
    }

    .item-price {
        font-weight: 600;
        font-size: 15px;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: var(--charcoal);
        color: white;
        border: none;
    }

    .btn-primary:hover {
        background: #000;
    }

    .btn-secondary {
        background: white;
        color: var(--charcoal);
        border: 2px solid var(--charcoal);
    }

    .btn-secondary:hover {
        background: var(--charcoal);
        color: white;
    }

    @media (max-width: 768px) {
        .track-container {
            padding: 0 20px;
            margin: 60px auto;
        }

        .tracking-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="track-container">
    <!-- Header -->
    <div class="track-header">
        <h1>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h1>
        <span class="order-status status-<?php echo $order['status']; ?>">
            <?php echo strtoupper($order['status']); ?>
        </span>
    </div>

    <!-- Tracking Info -->
    <?php if ($order['courier'] || $order['tracking_number']): ?>
    <div class="tracking-info">
        <?php if ($order['courier']): ?>
        <div class="tracking-row">
            <span class="tracking-label">Courier</span>
            <span class="tracking-value"><?php echo htmlspecialchars(strtoupper($order['courier'])); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($order['tracking_number']): ?>
        <div class="tracking-row">
            <span class="tracking-label">Tracking Number</span>
            <span class="tracking-value"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
        </div>
        <div class="tracking-row">
            <span class="tracking-label">Track Package</span>
            <a href="<?php
                $tracking_url = '#';
                if ($order['courier'] === 'JNT') {
                    $tracking_url = 'https://www.jet.co.id/track?waybill=' . $order['tracking_number'];
                } elseif ($order['courier'] === 'JNE') {
                    $tracking_url = 'https://www.jne.co.id/id/tracking/trace';
                } elseif ($order['courier'] === 'Sicepat') {
                    $tracking_url = 'https://www.sicepat.com/checkAwb/' . $order['tracking_number'];
                }
                echo $tracking_url;
            ?>" target="_blank" class="tracking-link">
                Track on <?php echo htmlspecialchars(ucfirst($order['courier'])); ?> →
            </a>
        </div>
        <?php endif; ?>

        <?php if ($order['estimated_delivery_date']): ?>
        <div class="tracking-row">
            <span class="tracking-label">Estimated Delivery</span>
            <span class="tracking-value">
                <?php echo date('d F Y', strtotime($order['estimated_delivery_date'])); ?>
                (<?php echo $order['estimated_delivery_days']; ?> days)
            </span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Progress Bar -->
    <div class="progress-bar-container">
        <h3 style="margin-bottom: 20px;">Order Progress</h3>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
        </div>
        <div class="progress-steps">
            <div class="progress-step <?php echo in_array($order['status'], ['pending', 'processing', 'shipping', 'delivered']) ? 'active' : ''; ?>">
                Placed
            </div>
            <div class="progress-step <?php echo in_array($order['status'], ['processing', 'shipping', 'delivered']) ? 'active' : ''; ?>">
                Processing
            </div>
            <div class="progress-step <?php echo in_array($order['status'], ['shipping', 'delivered']) ? 'active' : ''; ?>">
                Shipped
            </div>
            <div class="progress-step <?php echo $order['status'] === 'delivered' ? 'active' : ''; ?>">
                Delivered
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="timeline">
        <h2>Order Timeline</h2>
        <?php foreach ($timeline as $index => $event): ?>
        <div class="timeline-item <?php echo $index === count($timeline) - 1 ? '' : 'completed'; ?>">
            <div class="timeline-dot"></div>
            <div class="timeline-date"><?php echo date('d F Y, H:i', strtotime($event['created_at'])); ?></div>
            <div class="timeline-title"><?php echo htmlspecialchars($event['title']); ?></div>
            <div class="timeline-description"><?php echo htmlspecialchars($event['description']); ?></div>
        </div>
        <?php endforeach; ?>

        <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
        <div class="timeline-item pending">
            <div class="timeline-dot"></div>
            <div class="timeline-title">Pending - Next Update</div>
            <div class="timeline-description">Waiting for status update...</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Items -->
    <div class="items-section">
        <h3>Order Items</h3>
        <?php foreach ($items as $item): ?>
        <div class="item-row">
            <div class="item-name">
                <?php echo htmlspecialchars($item['product_name']); ?>
                <?php if ($item['size'] || $item['color']): ?>
                <div class="item-variant">
                    <?php
                    $variants = [];
                    if ($item['size']) $variants[] = 'Size: ' . $item['size'];
                    if ($item['color']) $variants[] = 'Color: ' . $item['color'];
                    echo implode(', ', $variants);
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="item-qty">Qty: <?php echo $item['qty']; ?></div>
            <div class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
        </div>
        <?php endforeach; ?>
        <div class="item-row" style="border-top: 2px solid rgba(0,0,0,0.1); margin-top: 15px; padding-top: 15px;">
            <div class="item-name" style="font-weight: 600; font-size: 16px;">Total</div>
            <div></div>
            <div class="item-price" style="font-size: 18px; font-weight: 700;">
                Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="/member/orders.php" class="btn btn-secondary">
            ← Back to Orders
        </a>
        <a href="https://wa.me/6281377378859?text=Hi, I have a question about order %23<?php echo $order['id']; ?>" target="_blank" class="btn btn-primary">
            💬 Contact Support
        </a>
        <?php if ($order['status'] === 'delivered'): ?>
        <a href="/pages/product-detail.php?slug=<?php echo $items[0]['slug']; ?>#reviews" class="btn btn-primary">
            ⭐ Leave a Review
        </a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
