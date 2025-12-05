<?php
// A6 Shipping Label Template (105mm x 148mm)
// This file expects $order variable with shipment data
?>
<div class="label-a6">
    <!-- Header with Logo -->
    <div class="label-header">
        <div class="logo">
            <h1>DORVE.ID</h1>
            <p>Official Store</p>
        </div>
        <div class="batch-info">
            <small>Batch: <?php echo htmlspecialchars($batchCode ?? ''); ?></small><br>
            <small><?php echo date('d M Y H:i'); ?></small>
        </div>
    </div>

    <!-- From (Store) Address -->
    <div class="address-block from-address">
        <div class="label-text">DARI:</div>
        <div class="address-content">
            <strong><?php echo htmlspecialchars($order['store_name'] ?? 'Dorve.id Official Store'); ?></strong><br>
            <?php echo htmlspecialchars($order['store_phone'] ?? '+62-813-7737-8859'); ?><br>
            <?php echo htmlspecialchars($order['store_address'] ?? ''); ?><br>
            <?php echo htmlspecialchars($order['store_city'] ?? 'Jakarta Selatan'); ?>, 
            <?php echo htmlspecialchars($order['store_province'] ?? 'DKI Jakarta'); ?> 
            <?php echo htmlspecialchars($order['store_postal'] ?? '12345'); ?>
        </div>
    </div>

    <!-- To (Customer) Address -->
    <div class="address-block to-address">
        <div class="label-text">KEPADA:</div>
        <div class="address-content">
            <strong style="font-size: 16px;"><?php echo strtoupper(htmlspecialchars($order['ship_name'])); ?></strong><br>
            <strong><?php echo htmlspecialchars($order['ship_phone']); ?></strong><br>
            <?php echo htmlspecialchars($order['ship_address']); ?><br>
            <?php if ($order['ship_district']): ?>
                <?php echo htmlspecialchars($order['ship_district']); ?>, 
            <?php endif; ?>
            <?php echo htmlspecialchars($order['ship_city']); ?>, 
            <?php echo htmlspecialchars($order['ship_province']); ?> 
            <strong><?php echo htmlspecialchars($order['ship_postal']); ?></strong>
        </div>
    </div>

    <!-- Courier Info -->
    <div class="courier-info">
        <div class="courier-logo">
            <strong style="font-size: 24px;"><?php echo strtoupper($order['courier_company']); ?></strong>
        </div>
        <div class="courier-service">
            <?php echo htmlspecialchars($order['courier_service_name']); ?>
            <?php if ($order['weight_kg']): ?>
                <br><small><?php echo $order['weight_kg']; ?> kg</small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Waybill Barcode -->
    <div class="waybill-section">
        <div class="barcode-placeholder">
            <!-- In production, generate actual barcode image here -->
            <svg width="100%" height="60" viewBox="0 0 200 60">
                <rect x="5" y="5" width="3" height="50" fill="#000"/>
                <rect x="10" y="5" width="2" height="50" fill="#000"/>
                <rect x="15" y="5" width="4" height="50" fill="#000"/>
                <rect x="22" y="5" width="2" height="50" fill="#000"/>
                <rect x="27" y="5" width="5" height="50" fill="#000"/>
                <rect x="35" y="5" width="3" height="50" fill="#000"/>
                <rect x="40" y="5" width="2" height="50" fill="#000"/>
                <rect x="45" y="5" width="4" height="50" fill="#000"/>
                <rect x="52" y="5" width="3" height="50" fill="#000"/>
                <rect x="58" y="5" width="2" height="50" fill="#000"/>
                <rect x="63" y="5" width="5" height="50" fill="#000"/>
                <rect x="71" y="5" width="2" height="50" fill="#000"/>
                <rect x="76" y="5" width="4" height="50" fill="#000"/>
                <rect x="83" y="5" width="3" height="50" fill="#000"/>
                <rect x="89" y="5" width="2" height="50" fill="#000"/>
                <rect x="94" y="5" width="5" height="50" fill="#000"/>
            </svg>
        </div>
        <div class="waybill-number">
            <strong><?php echo htmlspecialchars($order['waybill_id']); ?></strong>
        </div>
    </div>

    <!-- Footer -->
    <div class="label-footer">
        <div>Order: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong></div>
        <div>Tanggal: <?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
    </div>
</div>