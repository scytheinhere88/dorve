<?php
require_once __DIR__ . '/../config.php';

if (!isAdmin()) die('Unauthorized');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Setup Biteship Integration</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #F5F7FA; }";
echo "h1 { color: #1F2937; } h2 { color: #374151; margin-top: 30px; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px; }";
echo ".success { color: #10B981; } .error { color: #EF4444; } .warning { color: #F59E0B; }";
echo ".box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #E5E7EB; }";
echo "code { background: #F3F4F6; padding: 2px 6px; border-radius: 4px; font-size: 13px; }</style></head><body>";

echo "<h1>🚚 Setting up Biteship Shipping Integration...</h1>";

$success_count = 0;
$error_count = 0;

try {
    // 1. Check and update orders table
    echo "<div class='box'><h2>1. Updating Orders Table</h2>";
    
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    if (!in_array('fulfillment_status', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN fulfillment_status ENUM('new', 'waiting_print', 'waiting_pickup', 'in_transit', 'delivered', 'cancelled', 'returned') DEFAULT 'new' AFTER payment_status");
        echo "<p class='success'>✓ Added fulfillment_status column</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ fulfillment_status column exists</p>";
    }
    
    if (!in_array('shipping_courier', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_courier VARCHAR(100) NULL AFTER fulfillment_status");
        echo "<p class='success'>✓ Added shipping_courier column</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ shipping_courier column exists</p>";
    }
    
    if (!in_array('shipping_service', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_service VARCHAR(100) NULL AFTER shipping_courier");
        echo "<p class='success'>✓ Added shipping_service column</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ shipping_service column exists</p>";
    }
    
    if (!in_array('shipping_cost', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_cost DECIMAL(15,2) DEFAULT 0 AFTER shipping_service");
        echo "<p class='success'>✓ Added shipping_cost column</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ shipping_cost column exists</p>";
    }
    
    if (!in_array('tracking_number', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(255) NULL AFTER shipping_cost");
        $pdo->exec("ALTER TABLE orders ADD INDEX idx_tracking (tracking_number)");
        echo "<p class='success'>✓ Added tracking_number column</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ tracking_number column exists</p>";
    }
    
    if (!in_array('notes', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER tracking_number");
        echo "<p class='success'>✓ Added notes column</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ notes column exists</p>";
    }
    
    echo "</div>";
    
    // 2. Create order_addresses table
    echo "<div class='box'><h2>2. Order Addresses Table</h2>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'order_addresses'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE order_addresses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                type ENUM('billing', 'shipping') NOT NULL,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                address_line TEXT NOT NULL,
                district VARCHAR(255),
                city VARCHAR(255) NOT NULL,
                province VARCHAR(255) NOT NULL,
                postal_code VARCHAR(20) NOT NULL,
                country VARCHAR(5) DEFAULT 'ID',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order (order_id),
                INDEX idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p class='success'>✓ order_addresses table created</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ order_addresses table exists</p>";
    }
    echo "</div>";
    
    // 3. Create biteship_shipments table
    echo "<div class='box'><h2>3. Biteship Shipments Table</h2>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'biteship_shipments'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE biteship_shipments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                biteship_order_id VARCHAR(255) NOT NULL UNIQUE,
                courier_company VARCHAR(100) NOT NULL,
                courier_name VARCHAR(100) NOT NULL,
                courier_service_name VARCHAR(100) NOT NULL,
                courier_service_code VARCHAR(100) NULL,
                rate_id VARCHAR(255) NULL,
                shipping_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
                insurance_cost DECIMAL(15,2) DEFAULT 0,
                status VARCHAR(50) DEFAULT 'pending',
                waybill_id VARCHAR(255) NULL,
                pickup_code VARCHAR(50) NULL,
                delivery_date DATETIME NULL,
                pickup_time VARCHAR(100) NULL,
                destination_province VARCHAR(255),
                destination_city VARCHAR(255),
                destination_postal_code VARCHAR(20),
                origin_province VARCHAR(255),
                origin_city VARCHAR(255),
                origin_postal_code VARCHAR(20),
                weight_kg DECIMAL(10,2) DEFAULT 0,
                raw_response TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_order (order_id),
                INDEX idx_biteship_id (biteship_order_id),
                INDEX idx_waybill (waybill_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p class='success'>✓ biteship_shipments table created</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ biteship_shipments table exists</p>";
    }
    echo "</div>";
    
    // 4. Create biteship_webhook_logs table
    echo "<div class='box'><h2>4. Biteship Webhook Logs Table</h2>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'biteship_webhook_logs'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE biteship_webhook_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event VARCHAR(100) NOT NULL,
                biteship_order_id VARCHAR(255) NULL,
                payload TEXT NOT NULL,
                processed TINYINT(1) DEFAULT 0,
                error_message TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_event (event),
                INDEX idx_biteship_id (biteship_order_id),
                INDEX idx_processed (processed)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p class='success'>✓ biteship_webhook_logs table created</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ biteship_webhook_logs table exists</p>";
    }
    echo "</div>";
    
    // 5. Insert default Biteship settings
    echo "<div class='box'><h2>5. Biteship Configuration Settings</h2>";
    
    $settings = [
        'biteship_enabled' => '1',
        'biteship_api_key' => 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiRG9ydmUuaWQiLCJ1c2VySWQiOiI2OTI4NDVhNDM4MzQ5ZjAyZjdhM2VhNDgiLCJpYXQiOjE3NjQ2NTYwMjV9.xmkeeT2ghfHPe7PItX5HJ0KptlC5xbIhL1TlHWn6S1U',
        'biteship_environment' => 'production',
        'biteship_webhook_secret' => '',
        'biteship_default_couriers' => 'jne,jnt,sicepat,anteraja,idexpress',
        'store_name' => 'Dorve.id Official Store',
        'store_phone' => '+62-813-7737-8859',
        'store_address' => 'Jakarta, Indonesia',
        'store_city' => 'Jakarta Selatan',
        'store_province' => 'DKI Jakarta',
        'store_postal_code' => '12345',
        'store_country' => 'ID'
    ];
    
    // Check column name first
    $stmt = $pdo->query("DESCRIBE settings");
    $settingsColumns = array_column($stmt->fetchAll(), 'Field');
    $valueColumn = in_array('setting_value', $settingsColumns) ? 'setting_value' : 'value';
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, $valueColumn) VALUES (?, ?) ON DUPLICATE KEY UPDATE $valueColumn = VALUES($valueColumn)");
        $stmt->execute([$key, $value]);
    }
    echo "<p class='success'>✓ Biteship settings configured</p>";
    $success_count++;
    
    echo "<div style='background: #DBEAFE; padding: 16px; border-radius: 6px; margin-top: 16px;'>";
    echo "<p style='margin: 0; font-size: 14px; color: #1E40AF;'><strong>📋 Webhook URL:</strong></p>";
    echo "<code style='font-size: 14px; display: block; margin-top: 8px; padding: 8px; background: white;'>https://dorve.id/api/biteship/webhook.php</code>";
    echo "<p style='margin: 12px 0 0; font-size: 13px; color: #1E40AF;'>Copy URL ini ke Biteship Dashboard → Settings → Webhooks</p>";
    echo "</div>";
    
    echo "</div>";
    
    // 6. Create print_batches table
    echo "<div class='box'><h2>6. Print Batches Table</h2>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'print_batches'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE print_batches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                batch_code VARCHAR(50) NOT NULL UNIQUE,
                printed_by_admin_id INT NOT NULL,
                printed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                total_orders INT DEFAULT 0,
                notes TEXT NULL,
                INDEX idx_batch_code (batch_code),
                INDEX idx_admin (printed_by_admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p class='success'>✓ print_batches table created</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ print_batches table exists</p>";
    }
    echo "</div>";
    
    // 7. Add label_print_batch_id to shipments
    echo "<div class='box'><h2>7. Update Shipments for Batch Print</h2>";
    
    $stmt = $pdo->query("DESCRIBE biteship_shipments");
    $shipmentColumns = array_column($stmt->fetchAll(), 'Field');
    
    if (!in_array('label_print_batch_id', $shipmentColumns)) {
        $pdo->exec("ALTER TABLE biteship_shipments ADD COLUMN label_print_batch_id INT NULL AFTER waybill_id");
        $pdo->exec("ALTER TABLE biteship_shipments ADD INDEX idx_batch (label_print_batch_id)");
        echo "<p class='success'>✓ Added label_print_batch_id to shipments</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ label_print_batch_id column exists</p>";
    }
    echo "</div>";
    
    // Summary
    echo "<div class='box' style='background: #F0FDF4; border-color: #10B981;'>";
    echo "<h2 style='color: #10B981;'>✅ Setup Complete!</h2>";
    echo "<p><strong>$success_count</strong> operations completed successfully</p>";
    
    if ($error_count > 0) {
        echo "<p class='error'><strong>Errors:</strong> $error_count</p>";
    }
    
    echo "<div style='margin-top: 24px;'>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol style='line-height: 2;'>";
    echo "<li>Go to <a href='/admin/settings/api-settings.php' style='color: #3B82F6; text-decoration: none; font-weight: 600;'>API Settings</a> to configure Biteship</li>";
    echo "<li>Test connection dengan Biteship API</li>";
    echo "<li>Configure webhook di <a href='https://business.biteship.com/' target='_blank' style='color: #3B82F6;'>Biteship Dashboard</a></li>";
    echo "<li>Test shipping rates di checkout page</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box' style='background: #FEE2E2; border-color: #EF4444;'>";
    echo "<h2 style='color: #EF4444;'>❌ Setup Error</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    $error_count++;
}

echo "</body></html>";
?>
