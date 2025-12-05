<?php
require_once __DIR__ . '/config.php';

echo "Testing Referral Setup...\n\n";

// Test 1: Check referral_settings table
echo "1. Checking referral_settings table...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM referral_settings");
    $count = $stmt->fetchColumn();
    echo "✓ Table exists with $count settings\n\n";
} catch (PDOException $e) {
    echo "✗ Table does not exist, creating...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS referral_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Insert defaults
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
    echo "✓ Table created and populated\n\n";
}

// Test 2: Check referral_rewards has topup_amount column
echo "2. Checking referral_rewards.topup_amount column...\n";
try {
    $stmt = $pdo->query("DESCRIBE referral_rewards");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    if (!in_array('topup_amount', $columns)) {
        echo "Adding topup_amount column...\n";
        $pdo->exec("ALTER TABLE referral_rewards ADD COLUMN topup_amount DECIMAL(15,2) DEFAULT 0");
        echo "✓ Column added\n\n";
    } else {
        echo "✓ Column already exists\n\n";
    }
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Check user_vouchers table
echo "3. Checking user_vouchers table...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_vouchers");
    echo "✓ Table exists\n\n";
} catch (PDOException $e) {
    echo "Creating user_vouchers table...\n";
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
    echo "✓ Table created\n\n";
}

echo "✓ All setup complete!\n";
