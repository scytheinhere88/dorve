<?php
/**
 * ADMIN - Orders Dashboard dengan Sound Notification
 * Professional order management system
 */
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base query
$where = ['1=1'];
$params = [];

if ($filter === 'new_unpaid') {
    $where[] = "payment_status = 'pending'";
} elseif ($filter === 'paid_unprinted') {
    $where[] = "payment_status = 'paid' AND (printed_at IS NULL OR printed_at = '')";
} elseif ($filter === 'printed') {
    $where[] = "printed_at IS NOT NULL AND printed_at != ''";
} elseif ($filter === 'processing') {
    $where[] = "fulfillment_status = 'processing'";
} elseif ($filter === 'shipped') {
    $where[] = "shipping_status = 'shipped'";
}

if ($search) {
    $where[] = "(order_number LIKE ? OR id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "
    SELECT o.*, u.name as customer_name, u.email as customer_email,
           COUNT(DISTINCT oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 100
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get counts for badges
$counts = [];
$counts['all'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$counts['new_unpaid'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn();
$counts['paid_unprinted'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND (printed_at IS NULL OR printed_at = '')")->fetchColumn();
$counts['printed'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE printed_at IS NOT NULL AND printed_at != ''")->fetchColumn();
$counts['processing'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE fulfillment_status = 'processing'")->fetchColumn();
$counts['shipped'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE shipping_status = 'shipped'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Dashboard - Admin Dorve</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            color: white; padding: 32px 40px;
        }
        .dashboard-header h1 {
            font-size: 32px; font-weight: 700; margin-bottom: 8px;
        }
        .dashboard-header .subtitle {
            opacity: 0.9; font-size: 16px;
        }
        
        .container { max-width: 1600px; margin: 0 auto; padding: 32px 40px; }
        
        .filter-tabs {
            display: flex; gap: 8px; margin-bottom: 32px;
            overflow-x: auto; padding-bottom: 8px;
        }
        .filter-tab {
            padding: 12px 24px; background: white; border: 2px solid #E5E7EB;
            border-radius: 10px; cursor: pointer; transition: all 0.3s;
            white-space: nowrap; display: flex; align-items: center; gap: 8px;
            text-decoration: none; color: #374151; font-weight: 600;
        }
        .filter-tab:hover { border-color: #667EEA; background: #EEF2FF; }
        .filter-tab.active {
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            color: white; border-color: #667EEA;
        }
        .filter-badge {
            background: rgba(0,0,0,0.2); color: white;
            padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700;
        }
        .filter-tab.active .filter-badge { background: rgba(255,255,255,0.3); }
        
        .search-bar {
            margin-bottom: 24px; display: flex; gap: 12px;
        }
        .search-bar input {
            flex: 1; padding: 14px 20px; border: 2px solid #E5E7EB;
            border-radius: 10px; font-size: 15px;
        }
        .search-bar input:focus {
            outline: none; border-color: #667EEA;
        }
        .search-bar button {
            padding: 14px 28px; background: #667EEA; color: white;
            border: none; border-radius: 10px; font-weight: 600; cursor: pointer;
        }
        
        .order-card {
            background: white; border-radius: 16px; padding: 24px;
            margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 2px solid #E5E7EB; transition: all 0.3s;
        }
        .order-card:hover {
            border-color: #667EEA; box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .order-card.new-order {
            border-color: #10B981; background: linear-gradient(to right, #ECFDF5 0%, white 20%);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 4px 16px rgba(16, 185, 129, 0.2); }
            50% { box-shadow: 0 4px 24px rgba(16, 185, 129, 0.4); }
        }
        
        .order-header {
            display: grid; grid-template-columns: 1fr auto; gap: 24px;
            margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #E5E7EB;
        }
        .order-number {
            font-size: 20px; font-weight: 700; color: #1F2937; margin-bottom: 8px;
        }
        .order-meta {
            display: flex; gap: 16px; flex-wrap: wrap; font-size: 14px; color: #6B7280;
        }
        .order-meta-item {
            display: flex; align-items: center; gap: 6px;
        }
        
        .order-items {
            display: grid; gap: 12px; margin-bottom: 20px;
        }
        .order-item {
            display: grid; grid-template-columns: 80px 1fr auto;
            gap: 16px; align-items: center; padding: 12px;
            background: #F9FAFB; border-radius: 8px;
        }
        .item-image {
            width: 80px; height: 80px; border-radius: 8px;
            object-fit: cover; background: #E5E7EB;
        }
        .item-info h4 {
            font-size: 15px; font-weight: 600; margin-bottom: 4px;
        }
        .item-sku {
            font-size: 12px; color: #6B7280; font-family: 'Courier New', monospace;
            background: #E5E7EB; padding: 2px 8px; border-radius: 4px;
            display: inline-block; margin-bottom: 4px;
        }
        .item-price {
            font-size: 16px; font-weight: 700; color: #1F2937;
        }
        
        .order-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 20px; border-top: 1px solid #E5E7EB;
        }
        .order-total {
            font-size: 24px; font-weight: 700; color: #667EEA;
        }
        .order-actions {
            display: flex; gap: 8px;
        }
        .btn {
            padding: 10px 20px; border-radius: 8px; font-weight: 600;
            font-size: 14px; cursor: pointer; border: none;
            transition: all 0.3s; text-decoration: none; display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-secondary {
            background: white; color: #374151; border: 2px solid #E5E7EB;
        }
        .btn-secondary:hover { border-color: #667EEA; }
        
        .status-badge {
            display: inline-block; padding: 6px 12px; border-radius: 6px;
            font-size: 12px; font-weight: 600; text-transform: uppercase;
        }
        .status-paid { background: #D1FAE5; color: #065F46; }
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-processing { background: #DBEAFE; color: #1E40AF; }
        
        .notification-indicator {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: #10B981; color: white; padding: 16px 24px;
            border-radius: 12px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
            display: none; animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .empty-state {
            text-align: center; padding: 80px 20px;
            background: white; border-radius: 16px;
        }
        .empty-icon { font-size: 64px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>📦 Orders Dashboard</h1>
        <p class="subtitle">Manage all orders with real-time notifications</p>
    </div>

    <div class="container">
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                🌐 All Orders
                <span class="filter-badge"><?= $counts['all'] ?></span>
            </a>
            <a href="?filter=new_unpaid" class="filter-tab <?= $filter === 'new_unpaid' ? 'active' : '' ?>">
                ⏰ Unpaid
                <span class="filter-badge"><?= $counts['new_unpaid'] ?></span>
            </a>
            <a href="?filter=paid_unprinted" class="filter-tab <?= $filter === 'paid_unprinted' ? 'active' : '' ?>">
                🔔 Paid (Unprinted)
                <span class="filter-badge"><?= $counts['paid_unprinted'] ?></span>
            </a>
            <a href="?filter=printed" class="filter-tab <?= $filter === 'printed' ? 'active' : '' ?>">
                🖨️ Printed
                <span class="filter-badge"><?= $counts['printed'] ?></span>
            </a>
            <a href="?filter=processing" class="filter-tab <?= $filter === 'processing' ? 'active' : '' ?>">
                ⚙️ Processing
                <span class="filter-badge"><?= $counts['processing'] ?></span>
            </a>
            <a href="?filter=shipped" class="filter-tab <?= $filter === 'shipped' ? 'active' : '' ?>">
                🚚 Shipped
                <span class="filter-badge"><?= $counts['shipped'] ?></span>
            </a>
        </div>

        <!-- Search Bar -->
        <form class="search-bar" method="GET">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="text" name="search" placeholder="Search by Order Number or ID..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">🔍 Search</button>
        </form>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>No Orders Found</h3>
                <p style="color: #6B7280; margin-top: 8px;">No orders match your filter criteria</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                // Get order items dengan product details
                $stmt = $pdo->prepare("
                    SELECT oi.*, p.name, p.sku, p.image
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order['id']]);
                $items = $stmt->fetchAll();
                
                $isNew = $order['payment_status'] === 'paid' && empty($order['printed_at']);
            ?>
            <div class="order-card <?= $isNew ? 'new-order' : '' ?>" data-order-id="<?= $order['id'] ?>" data-is-new="<?= $isNew ? '1' : '0' ?>">
                <div class="order-header">
                    <div>
                        <div class="order-number">
                            Order #<?= htmlspecialchars($order['order_number']) ?>
                        </div>
                        <div class="order-meta">
                            <div class="order-meta-item">
                                <span>👤</span> <?= htmlspecialchars($order['customer_name']) ?>
                            </div>
                            <div class="order-meta-item">
                                <span>📅</span> <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                            </div>
                            <div class="order-meta-item">
                                <span>💳</span> <?= ucfirst($order['payment_method']) ?>
                            </div>
                            <div class="order-meta-item">
                                <?= getPaymentStatusBadge($order['payment_status']) ?>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <?php if ($isNew): ?>
                            <div style="background: #10B981; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 700; margin-bottom: 8px;">
                                🔔 NEW ORDER!
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 14px; color: #6B7280;">
                            <?= $order['item_count'] ?> item(s)
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <img src="/uploads/products/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>" 
                             class="item-image" alt="Product">
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <span class="item-sku">SKU: <?= htmlspecialchars($item['sku'] ?? 'N/A') ?></span>
                            <div style="font-size: 13px; color: #6B7280;">
                                Qty: <?= $item['quantity'] ?> × Rp <?= number_format($item['price'], 0, ',', '.') ?>
                            </div>
                        </div>
                        <div class="item-price">
                            Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-footer">
                    <div class="order-total">
                        Total: Rp <?= number_format($order['total_payable_amount'], 0, ',', '.') ?>
                    </div>
                    <div class="order-actions">
                        <a href="/admin/orders/detail.php?id=<?= $order['id'] ?>" class="btn btn-secondary">
                            👁️ View Details
                        </a>
                        <?php if ($order['payment_status'] === 'paid' && empty($order['printed_at'])): ?>
                            <button onclick="printOrder(<?= $order['id'] ?>)" class="btn btn-primary">
                                🖨️ Print Order
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Notification Indicator -->
    <div class="notification-indicator" id="notification">
        <strong>🔔 New Order Received!</strong>
    </div>

    <!-- Sound Files -->
    <audio id="newOrderSound" preload="auto">
        <source src="/sounds/new-order.mp3" type="audio/mpeg">
    </audio>
    <audio id="newDepositSound" preload="auto">
        <source src="/sounds/new-deposit.mp3" type="audio/mpeg">
    </audio>

    <script>
    // Check for new orders every 10 seconds
    let lastOrderId = <?= !empty($orders) ? $orders[0]['id'] : 0 ?>;
    let soundInterval = null;

    function checkNewOrders() {
        fetch('/api/admin/check-new-orders.php?last_id=' + lastOrderId)
            .then(r => r.json())
            .then(data => {
                if (data.has_new) {
                    lastOrderId = data.latest_id;
                    showNotification('🔔 New Order Received!');
                    
                    // Start sound loop (every 10 seconds until user clicks)
                    if (!soundInterval) {
                        playOrderSound();
                        soundInterval = setInterval(playOrderSound, 10000);
                    }
                }
            });
    }

    function playOrderSound() {
        const sound = document.getElementById('newOrderSound');
        sound.play().catch(e => console.log('Sound play failed:', e));
    }

    function showNotification(message) {
        const notif = document.getElementById('notification');
        notif.textContent = message;
        notif.style.display = 'block';
        
        setTimeout(() => {
            notif.style.display = 'none';
        }, 5000);
    }

    // Stop sound when user interacts
    document.addEventListener('click', () => {
        if (soundInterval) {
            clearInterval(soundInterval);
            soundInterval = null;
        }
    });

    // Start checking
    setInterval(checkNewOrders, 10000); // Check every 10 seconds

    function printOrder(orderId) {
        if (confirm('Mark this order as printed and open print page?')) {
            fetch('/api/admin/mark-printed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.open(`/admin/orders/print.php?id=${orderId}`, '_blank');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
    }
    </script>
</body>
</html>
