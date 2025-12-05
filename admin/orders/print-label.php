<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$id = $_GET['id'] ?? 0;

// Get order details with customer info
$stmt = $pdo->prepare("
    SELECT o.*,
           u.name as customer_name,
           u.email as customer_email,
           u.phone as customer_phone,
           u.address as customer_address
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Get store settings
$store_name = "Dorve House";
$store_address = "Jl. Fashion Street No. 123, Jakarta 12345";
$store_phone = "0812-3456-7890";

try {
    $stmt = $pdo->query("SELECT setting_key, value FROM settings WHERE setting_key IN ('store_name', 'store_address', 'store_phone')");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'store_name') $store_name = $row['value'];
        if ($row['setting_key'] === 'store_address') $store_address = $row['value'];
        if ($row['setting_key'] === 'store_phone') $store_phone = $row['value'];
    }
} catch (PDOException $e) {
    // Use defaults
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - Order #<?php echo $order['id']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12pt;
            line-height: 1.4;
            padding: 20px;
            background: #f5f5f5;
        }

        .label-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 2px solid #000;
        }

        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header .order-info {
            font-size: 10pt;
            margin-top: 10px;
        }

        .section {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #000;
        }

        .section-title {
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        .info-line {
            margin: 5px 0;
            font-size: 11pt;
        }

        .info-label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .items-table th {
            background: #000;
            color: white;
            font-weight: bold;
        }

        .tracking-box {
            text-align: center;
            padding: 20px;
            border: 3px solid #000;
            margin: 20px 0;
            background: #fff;
        }

        .tracking-box .courier {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .tracking-box .tracking-number {
            font-size: 24pt;
            font-weight: bold;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }

        .barcode-placeholder {
            width: 100%;
            height: 80px;
            background: repeating-linear-gradient(
                90deg,
                #000 0px,
                #000 2px,
                #fff 2px,
                #fff 4px
            );
            margin: 15px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #000;
            font-size: 10pt;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .no-print button {
            padding: 12px 30px;
            font-size: 14pt;
            background: #000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }

        .no-print button:hover {
            background: #333;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .label-container {
                border: none;
                padding: 0;
                max-width: 100%;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print Label</button>
        <button onclick="window.close()">❌ Close</button>
    </div>

    <div class="label-container">
        <!-- Header -->
        <div class="header">
            <h1>SHIPPING LABEL</h1>
            <div class="order-info">
                Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?> |
                Date: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
            </div>
        </div>

        <!-- From (Store) Section -->
        <div class="section">
            <div class="section-title">FROM (Pengirim):</div>
            <div class="info-line">
                <span class="info-label">Name:</span>
                <strong><?php echo htmlspecialchars($store_name); ?></strong>
            </div>
            <div class="info-line">
                <span class="info-label">Address:</span>
                <?php echo htmlspecialchars($store_address); ?>
            </div>
            <div class="info-line">
                <span class="info-label">Phone:</span>
                <?php echo htmlspecialchars($store_phone); ?>
            </div>
        </div>

        <!-- To (Customer) Section -->
        <div class="section">
            <div class="section-title">TO (Penerima):</div>
            <div class="info-line">
                <span class="info-label">Name:</span>
                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
            </div>
            <div class="info-line">
                <span class="info-label">Address:</span>
                <?php echo htmlspecialchars($order['customer_address']); ?>
            </div>
            <div class="info-line">
                <span class="info-label">Phone:</span>
                <strong><?php echo htmlspecialchars($order['customer_phone'] ?? '-'); ?></strong>
            </div>
            <div class="info-line">
                <span class="info-label">Email:</span>
                <?php echo htmlspecialchars($order['customer_email']); ?>
            </div>
        </div>

        <!-- Courier & Tracking -->
        <?php if ($order['courier'] && $order['tracking_number']): ?>
        <div class="tracking-box">
            <div class="courier"><?php echo htmlspecialchars(strtoupper($order['courier'])); ?></div>
            <div class="tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
            <div class="barcode-placeholder"></div>
            <div>Scan barcode for tracking</div>
        </div>
        <?php endif; ?>

        <!-- Items Section -->
        <div class="section">
            <div class="section-title">ITEMS (Barang):</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Variant</th>
                        <th>Qty</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td>
                            <?php
                            $variants = [];
                            if ($item['size']) $variants[] = 'Size: ' . $item['size'];
                            if ($item['color']) $variants[] = 'Color: ' . $item['color'];
                            echo $variants ? implode(', ', $variants) : '-';
                            ?>
                        </td>
                        <td><?php echo $item['qty']; ?></td>
                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="section">
            <div class="section-title">ORDER SUMMARY:</div>
            <div class="info-line">
                <span class="info-label">Total Items:</span>
                <strong><?php echo count($items); ?> item(s)</strong>
            </div>
            <div class="info-line">
                <span class="info-label">Total Value:</span>
                <strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong>
            </div>
            <div class="info-line">
                <span class="info-label">Payment:</span>
                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))); ?>
            </div>
            <?php if ($order['estimated_delivery_days']): ?>
            <div class="info-line">
                <span class="info-label">Est. Delivery:</span>
                <?php echo $order['estimated_delivery_days']; ?> days
                (<?php echo date('d/m/Y', strtotime($order['estimated_delivery_date'])); ?>)
            </div>
            <?php endif; ?>
            <?php if ($order['shipping_notes']): ?>
            <div class="info-line">
                <span class="info-label">Notes:</span>
                <?php echo htmlspecialchars($order['shipping_notes']); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Handle with care | Fragile items inside</strong></p>
            <p>For inquiries: <?php echo htmlspecialchars($store_phone); ?> | <?php echo htmlspecialchars($store_name); ?></p>
            <p style="margin-top: 10px; font-size: 9pt;">
                This is a computer-generated shipping label. No signature required.
            </p>
        </div>
    </div>

    <script>
        // Auto-print on load (optional)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>
