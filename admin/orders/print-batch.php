<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

// Get order IDs from query string
$orderIds = isset($_GET['order_ids']) ? explode(',', $_GET['order_ids']) : [];
$orderIds = array_map('intval', array_filter($orderIds));

if (empty($orderIds)) {
    die('No orders selected');
}

// Create print batch record
$batchCode = 'PRINT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
$stmt = $pdo->prepare("INSERT INTO print_batches (batch_code, printed_by_admin_id, total_orders) VALUES (?, ?, ?)");
$stmt->execute([$batchCode, $_SESSION['user_id'], count($orderIds)]);
$batchId = $pdo->lastInsertId();

// Update shipments with batch ID
$placeholders = implode(',', array_fill(0, count($orderIds), '?'));
$stmt = $pdo->prepare("UPDATE biteship_shipments SET label_print_batch_id = ? WHERE order_id IN ($placeholders)");
$stmt->execute(array_merge([$batchId], $orderIds));

// Update order status to waiting_pickup
$stmt = $pdo->prepare("UPDATE orders SET fulfillment_status = 'waiting_pickup' WHERE id IN ($placeholders)");
$stmt->execute($orderIds);

// Get orders with shipment data
$stmt = $pdo->prepare("
    SELECT 
        o.order_number,
        o.created_at,
        bs.waybill_id,
        bs.courier_company,
        bs.courier_service_name,
        bs.weight_kg,
        oa_ship.name as ship_name,
        oa_ship.phone as ship_phone,
        oa_ship.address_line as ship_address,
        oa_ship.district as ship_district,
        oa_ship.city as ship_city,
        oa_ship.province as ship_province,
        oa_ship.postal_code as ship_postal,
        s.setting_value as store_name,
        s2.setting_value as store_phone,
        s3.setting_value as store_address,
        s4.setting_value as store_city,
        s5.setting_value as store_province,
        s6.setting_value as store_postal
    FROM orders o
    JOIN biteship_shipments bs ON o.id = bs.order_id
    JOIN order_addresses oa_ship ON o.id = oa_ship.order_id AND oa_ship.type = 'shipping'
    LEFT JOIN settings s ON s.setting_key = 'store_name'
    LEFT JOIN settings s2 ON s2.setting_key = 'store_phone'
    LEFT JOIN settings s3 ON s3.setting_key = 'store_address'
    LEFT JOIN settings s4 ON s4.setting_key = 'store_city'
    LEFT JOIN settings s5 ON s5.setting_key = 'store_province'
    LEFT JOIN settings s6 ON s6.setting_key = 'store_postal_code'
    WHERE o.id IN ($placeholders)
    ORDER BY o.created_at DESC
");
$stmt->execute($orderIds);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Batch - <?php echo $batchCode; ?></title>
    <link rel="stylesheet" href="/admin/assets/label-a6.css">
</head>
<body>
    <div class="print-info no-print">
        <h2>📄 Print Batch: <?php echo $batchCode; ?></h2>
        <p>Total: <?php echo count($orders); ?> labels</p>
        <button onclick="window.print()" class="btn-print">🖨️ Print Sekarang</button>
        <button onclick="window.close()" class="btn-close">✕ Tutup</button>
    </div>

    <?php foreach ($orders as $index => $order): ?>
        <div class="label-page">
            <?php include __DIR__ . '/templates/label-a6.php'; ?>
        </div>
        <?php if ($index < count($orders) - 1): ?>
            <div class="page-break"></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script>
    // Auto print dialog on load (optional)
    // window.onload = function() { window.print(); };
    </script>
</body>
</html>