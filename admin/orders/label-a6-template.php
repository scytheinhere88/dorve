<?php
/**
 * A6 Shipping Label Template
 * Variables expected:
 * - $shipment (array with all shipment data)
 * - $storeInfo (array with store details)
 */

// Generate barcode for waybill (simplified - using text)
$waybillDisplay = $shipment['waybill_id'] ?? 'PENDING';
$orderNumber = $shipment['order_number'] ?? 'N/A';
$courier = ($shipment['courier_name'] ?? '') . ' - ' . ($shipment['courier_service_name'] ?? '');
$weight = number_format(($shipment['weight_kg'] ?? 1), 1, '.', '');
$shippingCost = number_format($shipment['shipping_cost'] ?? 0, 0, ',', '.');
$codAmount = number_format($shipment['cod_amount'] ?? 0, 0, ',', '.');
?>

<div class="label">
    <!-- Header -->
    <div class="label-header">
        <div class="label-logo">
            <!-- Logo placeholder - replace with actual logo -->
            <div class="label-logo-text">DORVE.ID</div>
        </div>
        <div class="label-store-info">
            <strong>DORVE.ID OFFICIAL STORE</strong>
            Website: https://dorve.id<br>
            CS/WhatsApp: +62-813-7737-8859<br>
            Instagram: @dorve.id
        </div>
    </div>
    
    <!-- Address Blocks -->
    <div class="label-addresses">
        <!-- FROM -->
        <div class="address-block from">
            <div class="address-title">PENGIRIM (FROM)</div>
            <div class="address-content">
                <strong><?php echo htmlspecialchars($storeInfo['name'] ?? 'Dorve.id Official Store'); ?></strong>
                <?php echo htmlspecialchars($storeInfo['address'] ?? ''); ?><br>
                <span class="location">
                    <?php echo htmlspecialchars($storeInfo['city'] ?? ''); ?>, 
                    <?php echo htmlspecialchars($storeInfo['province'] ?? ''); ?> 
                    <?php echo htmlspecialchars($storeInfo['postal_code'] ?? ''); ?>
                </span>
                <span class="phone">☎ <?php echo htmlspecialchars($storeInfo['phone'] ?? '+62-813-7737-8859'); ?></span>
            </div>
        </div>
        
        <!-- TO -->
        <div class="address-block to">
            <div class="address-title">PENERIMA (TO)</div>
            <div class="address-content">
                <strong><?php echo htmlspecialchars($shipment['recipient_name'] ?? 'Customer'); ?></strong>
                <?php echo htmlspecialchars($shipment['recipient_address'] ?? ''); ?><br>
                <?php if (!empty($shipment['recipient_district'])): ?>
                    <?php echo htmlspecialchars($shipment['recipient_district']); ?>,<br>
                <?php endif; ?>
                <span class="location">
                    <?php echo htmlspecialchars($shipment['recipient_city'] ?? ''); ?>, 
                    <?php echo htmlspecialchars($shipment['recipient_province'] ?? ''); ?> 
                    <?php echo htmlspecialchars($shipment['recipient_postal'] ?? ''); ?>
                </span>
                <span class="phone">☎ <?php echo htmlspecialchars($shipment['recipient_phone'] ?? ''); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Shipment Info -->
    <div class="label-shipment-info">
        <div class="info-item">
            <span class="info-label">KURIR:</span>
            <span class="info-value"><?php echo htmlspecialchars($courier); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">NO. RESI:</span>
            <span class="info-value large"><?php echo htmlspecialchars($waybillDisplay); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">ONGKIR:</span>
            <span class="info-value">Rp <?php echo $shippingCost; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">ORDER ID:</span>
            <span class="info-value"><?php echo htmlspecialchars($orderNumber); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">BERAT:</span>
            <span class="info-value"><?php echo $weight; ?> Kg</span>
        </div>
        <div class="info-item">
            <span class="info-label">COD:</span>
            <span class="info-value"><?php echo $codAmount > 0 ? 'Rp ' . $codAmount : '-'; ?></span>
        </div>
    </div>
    
    <!-- Barcode Section -->
    <?php if ($waybillDisplay !== 'PENDING'): ?>
    <div class="label-barcode">
        <!-- Simple text barcode placeholder - integrate real barcode library in production -->
        <div style="font-family: 'Libre Barcode 128', monospace; font-size: 32pt; letter-spacing: 2px;">
            <?php echo htmlspecialchars($waybillDisplay); ?>
        </div>
        <div class="barcode-text"><?php echo htmlspecialchars($waybillDisplay); ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="label-footer">
        Pengiriman dikelola oleh <strong>Dorve.id Official Store</strong> – Sistem terintegrasi Biteship
        <?php if (!empty($shipment['notes'])): ?>
            <div class="label-notes">Catatan: <?php echo htmlspecialchars($shipment['notes']); ?></div>
        <?php endif; ?>
    </div>
</div>
