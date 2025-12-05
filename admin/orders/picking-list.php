<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

// Get orders that need picking (new or waiting_print status)
$status = $_GET['status'] ?? 'new';

$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.order_number,
        o.created_at,
        oa.name as customer_name,
        oa.city,
        oa.province
    FROM orders o
    LEFT JOIN order_addresses oa ON o.id = oa.order_id AND oa.type = 'shipping'
    WHERE o.fulfillment_status = ?
    ORDER BY o.created_at ASC
");
$stmt->execute([$status]);
$orders = $stmt->fetchAll();

// Get order items for each order
$orderItems = [];
foreach ($orders as $order) {
    $stmt = $pdo->prepare("
        SELECT 
            oi.*,
            p.name as product_name,
            p.sku
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $orderItems[$order['id']] = $stmt->fetchAll();
}

include __DIR__ . '/../includes/admin-header.php';
?>

<style>
.picking-header {
    background: #3B82F6;
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 24px;
}
.picking-list {
    background: white;
    border-radius: 8px;
    padding: 24px;
}
.order-card {
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.order-card.checked {
    background: #F0FDF4;
    border-color: #10B981;
}
.item-row {
    display: flex;
    justify-content: space-between;
    padding: 12px;
    border-bottom: 1px solid #F3F4F6;
}
.checkbox-large {
    width: 24px;
    height: 24px;
}
@media print {
    .no-print { display: none !important; }
}
</style>

<div class="picking-header no-print">
    <h1>📦 Warehouse Picking List</h1>
    <p>Status: <strong><?php echo strtoupper($status); ?></strong> | Total: <?php echo count($orders); ?> orders</p>
    <button onclick="window.print()" class="btn btn-primary" style="margin-top: 12px;">🖨️ Print List</button>
</div>

<div class="picking-list">
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 40px; color: #9CA3AF;">
            <div style="font-size: 48px; margin-bottom: 16px;">✅</div>
            <p>Tidak ada pesanan yang perlu dipick untuk status ini</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $index => $order): ?>
            <div class="order-card" id="order-<?php echo $order['id']; ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0 0 8px;">
                            <input type="checkbox" class="checkbox-large" onchange="toggleOrderComplete(<?php echo $order['id']; ?>)">
                            <?php echo htmlspecialchars($order['order_number']); ?>
                        </h3>
                        <p style="margin: 0; color: #6B7280; font-size: 14px;">
                            <?php echo htmlspecialchars($order['customer_name']); ?> | 
                            <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['province']); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <span style="background: #DBEAFE; color: #1E40AF; padding: 6px 12px; border-radius: 16px; font-size: 14px; font-weight: 600;">
                            #<?php echo $index + 1; ?>
                        </span>
                    </div>
                </div>

                <div style="background: #F9FAFB; padding: 16px; border-radius: 6px;">
                    <h4 style="margin: 0 0 12px; font-size: 14px; color: #6B7280;">ITEMS TO PICK:</h4>
                    <?php foreach ($orderItems[$order['id']] as $item): ?>
                        <div class="item-row">
                            <div>
                                <input type="checkbox" class="checkbox-large">
                                <strong style="margin-left: 12px;"><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <?php if ($item['sku']): ?>
                                    <br><span style="margin-left: 36px; color: #6B7280; font-size: 13px;">SKU: <?php echo htmlspecialchars($item['sku']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <strong style="font-size: 18px;">×<?php echo $item['qty']; ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (($index + 1) % 3 === 0): ?>
                <div style="page-break-after: always;"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleOrderComplete(orderId) {
    const card = document.getElementById('order-' + orderId);
    card.classList.toggle('checked');
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>