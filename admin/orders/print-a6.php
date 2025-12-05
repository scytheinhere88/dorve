<?php
/**
 * ADMIN - Print A6 Shipping Label
 * Professional 105x148mm shipping label
 */
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$orderId = (int)($_GET['id'] ?? 0);

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email, u.phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

// Get shipping address
$stmt = $pdo->prepare("SELECT * FROM order_addresses WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$address = $stmt->fetch();

// Get first product image for quick identification
$stmt = $pdo->prepare("
    SELECT p.image, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    LIMIT 1
");
$stmt->execute([$orderId]);
$product = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Shipping Label - Order #<?= $order['order_number'] ?></title>
    <style>
        @page {
            size: 105mm 148mm; /* A6 */
            margin: 0;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            width: 105mm; height: 148mm;
            padding: 8mm; background: white;
        }
        .label-container {
            border: 2px solid #000;
            padding: 10px; height: 132mm;
            display: flex; flex-direction: column;
        }
        .header {
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 2px dashed #000;
            margin-bottom: 8px;
        }
        .logo {
            font-size: 24px; font-weight: bold;
            letter-spacing: 3px; margin-bottom: 4px;
        }
        .order-number {
            font-size: 16px; font-weight: bold;
            background: #000; color: white;
            padding: 4px 8px; display: inline-block;
            margin-top: 4px;
        }
        .section {
            margin-bottom: 10px;
        }
        .section-title {
            font-size: 11px; font-weight: bold;
            background: #000; color: white;
            padding: 3px 6px; margin-bottom: 5px;
        }
        .section-content {
            font-size: 10px; line-height: 1.4;
        }
        .big-text {
            font-size: 12px; font-weight: bold;
        }
        .barcode {
            text-align: center;
            padding: 8px 0;
            border: 1px dashed #000;
            margin: 8px 0;
        }
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 14px; font-weight: bold;
            letter-spacing: 2px;
        }
        .product-preview {
            display: flex; gap: 8px; align-items: center;
            padding: 6px; border: 1px solid #ddd;
            border-radius: 4px; margin-top: 6px;
        }
        .product-image {
            width: 40px; height: 40px;
            object-fit: cover; border-radius: 4px;
        }
        .footer {
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 8px;
        }
        .print-btn {
            padding: 12px 24px; background: #667EEA;
            color: white; border: none; border-radius: 8px;
            font-weight: bold; cursor: pointer;
            margin: 10px;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 10px;">
        <button onclick="window.print()" class="print-btn">🖨️ Print A6 Label</button>
        <button onclick="window.close()" class="print-btn" style="background: #6B7280;">❌ Close</button>
    </div>

    <div class="label-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">DORVE</div>
            <div style="font-size: 9px;">Jl. Contoh No. 123, Jakarta</div>
            <div class="order-number">#<?= htmlspecialchars($order['order_number']) ?></div>
        </div>

        <!-- From -->
        <div class="section">
            <div class="section-title">📤 FROM (Pengirim)</div>
            <div class="section-content">
                <div class="big-text">DORVE Store</div>
                <div>Phone: +62 812-3456-7890</div>
                <div>Jl. Contoh No. 123, Jakarta 12345</div>
            </div>
        </div>

        <!-- To -->
        <div class="section">
            <div class="section-title">📥 TO (Penerima)</div>
            <div class="section-content">
                <div class="big-text">
                    <?= htmlspecialchars($address['recipient_name'] ?? $order['customer_name']) ?>
                </div>
                <div>Phone: <?= htmlspecialchars($address['phone'] ?? $order['phone']) ?></div>
                <div style="margin-top: 3px;">
                    <?= htmlspecialchars($address['address'] ?? 'No address provided') ?>
                </div>
            </div>
        </div>

        <!-- Courier Info -->
        <?php if ($order['shipping_courier']): ?>
        <div class="section">
            <div class="section-title">🚚 Kurir: <?= strtoupper($order['shipping_courier']) ?></div>
            <div class="section-content">
                Service: <strong><?= htmlspecialchars($order['shipping_service']) ?></strong>
                <?php if ($order['tracking_number']): ?>
                <div class="barcode">
                    <div class="barcode-number"><?= htmlspecialchars($order['tracking_number']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Product Info -->
        <?php if ($product): ?>
        <div class="product-preview">
            <?php if ($product['image']): ?>
                <img src="/uploads/products/<?= htmlspecialchars($product['image']) ?>" class="product-image" alt="Product">
            <?php endif; ?>
            <div style="flex: 1; font-size: 9px;">
                <div style="font-weight: bold;"><?= htmlspecialchars($product['name']) ?></div>
                <div>COD: Rp <?= number_format($order['total_payable_amount'], 0, ',', '.') ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div><strong>Thank you for shopping at DORVE!</strong></div>
            <div>Date: <?= date('d M Y H:i', strtotime($order['created_at'])) ?></div>
        </div>
    </div>

    <script>
    // Optional: Auto print on load
    // window.onload = () => window.print();
    </script>
</body>
</html>
