<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) die('Unauthorized');

echo "<h2>Checking Referral Schema...</h2>\n";

try {
    // Check referral_rewards table structure
    echo "<h3>Referral Rewards Table:</h3>";
    $stmt = $pdo->query("DESCRIBE referral_rewards");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Add topup_amount column if not exists
    if (!in_array('topup_amount', $columns)) {
        echo "<p>Adding topup_amount column...</p>";
        $pdo->exec("ALTER TABLE referral_rewards ADD COLUMN topup_amount DECIMAL(15,2) DEFAULT 0 AFTER reward_value");
        echo "<p style='color: green;'>✓ Column added successfully!</p>";
    } else {
        echo "<p style='color: green;'>✓ topup_amount column already exists</p>";
    }
    
    // Check if user_vouchers table exists
    echo "<h3>User Vouchers Table:</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'user_vouchers'")->fetchAll();
    
    if (empty($tables)) {
        echo "<p>Creating user_vouchers table...</p>";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_vouchers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                voucher_id INT NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                used_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
                INDEX idx_user_voucher (user_id, voucher_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p style='color: green;'>✓ Table created successfully!</p>";
    } else {
        echo "<p style='color: green;'>✓ user_vouchers table already exists</p>";
    }
    
    // Check if referral_settings table exists
    echo "<h3>Referral Settings Table:</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'referral_settings'")->fetchAll();
    
    if (empty($tables)) {
        echo "<p>Creating referral_settings table...</p>";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS referral_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p style='color: green;'>✓ Table created successfully!</p>";
        
        // Insert default settings
        echo "<p>Inserting default settings...</p>";
        $defaults = [
            'referral_enabled' => '1',
            'commission_type' => 'percentage',
            'commission_percent' => '5.00',
            'commission_fixed' => '50000',
            'min_topup_for_reward' => '100000',
            'reward_type' => 'wallet',
            'voucher_type' => 'percentage',
            'voucher_value' => '10',
            'voucher_min_purchase' => '50000',
            'voucher_validity_days' => '30',
            'require_transaction' => '1',
        ];
        
        foreach ($defaults as $key => $value) {
            $pdo->prepare("INSERT INTO referral_settings (setting_key, setting_value) VALUES (?, ?)")
                ->execute([$key, $value]);
        }
        echo "<p style='color: green;'>✓ Default settings inserted!</p>";
    } else {
        echo "<p style='color: green;'>✓ referral_settings table already exists</p>";
    }
    
    echo "<h2 style='color: green;'>✓ All schema checks complete!</h2>";
    echo "<p><a href='/admin/deposits/index.php'>← Back to Deposits</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
