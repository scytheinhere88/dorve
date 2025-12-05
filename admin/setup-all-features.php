<?php
/**
 * Master Setup Script
 * Run this to setup all new features at once
 */

require_once __DIR__ . '/../config.php';

if (!isAdmin()) die('Unauthorized - Admin access required');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Setup All Features</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #F5F7FA; }";
echo "h1 { color: #1F2937; } h2 { color: #374151; margin-top: 30px; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px; }";
echo ".success { color: #10B981; } .error { color: #EF4444; } .warning { color: #F59E0B; }";
echo ".box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #E5E7EB; }";
echo "</style></head><body>";

echo "<h1>🛠️ Setup All Features</h1>";
echo "<p>This script will setup all database tables and configurations for new features.</p>";

$success_count = 0;
$error_count = 0;

// 1. Setup Referral System
echo "<div class='box'><h2>1. Referral System</h2>";
try {
    // referral_settings table
    $tables = $pdo->query("SHOW TABLES LIKE 'referral_settings'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS referral_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $defaults = [
            'referral_enabled' => '1', 'commission_type' => 'percentage', 'commission_percent' => '5.00',
            'commission_fixed' => '50000', 'min_topup_for_reward' => '100000', 'reward_type' => 'wallet',
            'voucher_type' => 'percentage', 'voucher_value' => '10', 'voucher_min_purchase' => '50000',
            'voucher_validity_days' => '30', 'require_transaction' => '1',
        ];
        foreach ($defaults as $key => $value) {
            $pdo->prepare("INSERT INTO referral_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $value]);
        }
        echo "<p class='success'>✓ referral_settings table created with default values</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ referral_settings table exists</p>";
    }
    
    // Add topup_amount column to referral_rewards
    $stmt = $pdo->query("DESCRIBE referral_rewards");
    $columns = array_column($stmt->fetchAll(), 'Field');
    if (!in_array('topup_amount', $columns)) {
        $pdo->exec("ALTER TABLE referral_rewards ADD COLUMN topup_amount DECIMAL(15,2) DEFAULT 0");
        echo "<p class='success'>✓ topup_amount column added to referral_rewards</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ topup_amount column exists</p>";
    }
    
    // user_vouchers table
    $tables = $pdo->query("SHOW TABLES LIKE 'user_vouchers'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_vouchers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                voucher_id INT NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                used_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_voucher (user_id, voucher_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p class='success'>✓ user_vouchers table created</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ user_vouchers table exists</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    $error_count++;
}
echo "</div>";

// 2. Setup Product Images
echo "<div class='box'><h2>2. Product Multiple Images</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'product_images'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS product_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                image_path VARCHAR(500) NOT NULL,
                is_primary TINYINT(1) DEFAULT 0,
                sort_order INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product (product_id),
                INDEX idx_primary (product_id, is_primary)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p class='success'>✓ product_images table created</p>";
        
        // Migrate existing images
        $stmt = $pdo->query("SELECT id, image FROM products WHERE image IS NOT NULL AND image != ''");
        $products = $stmt->fetchAll();
        $migrated = 0;
        foreach ($products as $product) {
            $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 1, 1)")->execute([$product['id'], $product['image']]);
            $migrated++;
        }
        echo "<p class='success'>✓ Migrated $migrated existing product images</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ product_images table exists</p>";
    }
    
    $upload_dir = __DIR__ . '/../uploads/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p class='success'>✓ Created uploads/products/ directory</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    $error_count++;
}
echo "</div>";

// 3. Setup Category Images & Size Guides
echo "<div class='box'><h2>3. Category Images & Size Guides</h2>";
try {
    $stmt = $pdo->query("DESCRIBE categories");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    if (!in_array('image', $columns)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(500) NULL AFTER slug");
        echo "<p class='success'>✓ image column added to categories</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ image column exists in categories</p>";
    }
    
    if (!in_array('size_guide', $columns)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN size_guide VARCHAR(500) NULL AFTER image");
        echo "<p class='success'>✓ size_guide column added to categories</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ size_guide column exists in categories</p>";
    }
    
    $upload_dir = __DIR__ . '/../uploads/categories/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p class='success'>✓ Created uploads/categories/ directory</p>";
    }
    
    $upload_dir = __DIR__ . '/../uploads/size-guides/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p class='success'>✓ Created uploads/size-guides/ directory</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    $error_count++;
}
echo "</div>";

// 4. Setup Banners
echo "<div class='box'><h2>4. Promotion Banners</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'banners'")->fetchAll();
    if (empty($tables)) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS banners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                subtitle VARCHAR(255) NULL,
                image_url VARCHAR(500) NOT NULL,
                link_url VARCHAR(500) NULL,
                display_order INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_order (display_order),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p class='success'>✓ banners table created</p>";
        $success_count++;
    } else {
        echo "<p class='success'>✓ banners table exists</p>";
    }
    
    $upload_dir = __DIR__ . '/../uploads/banners/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p class='success'>✓ Created uploads/banners/ directory</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    $error_count++;
}
echo "</div>";

// Summary
echo "<div class='box' style='background: #F0FDF4; border-color: #10B981;'>";
echo "<h2 style='color: #10B981;'>✓ Setup Complete!</h2>";
echo "<p><strong>Success:</strong> $success_count operations completed</p>";
if ($error_count > 0) {
    echo "<p class='error'><strong>Errors:</strong> $error_count</p>";
}
echo "<p style='margin-top: 20px;'>";
echo "<a href='/admin/index.php' style='padding: 10px 20px; background: #10B981; color: white; text-decoration: none; border-radius: 6px; margin-right: 10px;'>Go to Dashboard</a>";
echo "<a href='/admin/settings/referral-settings.php' style='padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 6px;'>Configure Referral System</a>";
echo "</p>";
echo "</div>";

echo "</body></html>";
?>
