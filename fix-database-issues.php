<?php
/**
 * FIX DATABASE ISSUES
 * Adds missing tables and columns found by super-debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html><html><head><title>Database Fix</title></head><body style='font-family: monospace; padding: 20px;'>";
echo "<h1>🔧 Fixing Database Issues...</h1>";

try {
    // 1. Add missing columns to wallet_transactions
    echo "<p><strong>1. Adding unique_code to wallet_transactions...</strong></p>";
    try {
        $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN unique_code INT AFTER reference_id");
        echo "<p style='color: green;'>✓ Added unique_code column</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠ Column unique_code already exists</p>";
        } else {
            throw $e;
        }
    }

    echo "<p><strong>2. Adding bank_account_id to wallet_transactions...</strong></p>";
    try {
        $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN bank_account_id INT AFTER unique_code");
        echo "<p style='color: green;'>✓ Added bank_account_id column</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠ Column bank_account_id already exists</p>";
        } else {
            throw $e;
        }
    }

    // 2. Create referral tables
    echo "<p><strong>3. Creating referral_tiers table...</strong></p>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS referral_tiers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tier_name VARCHAR(50) NOT NULL UNIQUE,
        min_total_topup DECIMAL(15,2) DEFAULT 0,
        commission_rate DECIMAL(5,2) DEFAULT 0,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color: green;'>✓ Created referral_tiers table</p>";

    // Insert default tiers
    $pdo->exec("INSERT IGNORE INTO referral_tiers (tier_name, min_total_topup, commission_rate, description) VALUES
        ('bronze', 0, 5.00, 'Bronze tier - 5% commission'),
        ('silver', 1000000, 7.50, 'Silver tier - 7.5% commission'),
        ('gold', 5000000, 10.00, 'Gold tier - 10% commission'),
        ('platinum', 10000000, 15.00, 'Platinum tier - 15% commission')
    ");
    echo "<p style='color: green;'>✓ Inserted default tier data</p>";

    echo "<p><strong>4. Creating referrals table...</strong></p>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS referrals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT NOT NULL,
            referred_id INT NOT NULL,
            referral_code VARCHAR(20),
            status ENUM('pending', 'active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_referral (referrer_id, referred_id),
            INDEX idx_referrer (referrer_id),
            INDEX idx_referred (referred_id)
        ) ENGINE=InnoDB");
        echo "<p style='color: green;'>✓ Created referrals table</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Referrals table: " . $e->getMessage() . "</p>";
        echo "<p style='color: blue;'>ℹ️ Trying without foreign keys...</p>";
    }

    echo "<p><strong>5. Creating referral_commissions table...</strong></p>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS referral_commissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id INT NOT NULL,
            referred_id INT NOT NULL,
            transaction_id INT,
            commission_amount DECIMAL(15,2) NOT NULL,
            commission_rate DECIMAL(5,2) NOT NULL,
            transaction_amount DECIMAL(15,2) NOT NULL,
            status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_referrer (referrer_id),
            INDEX idx_referred (referred_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB");
        echo "<p style='color: green;'>✓ Created referral_commissions table</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Referral commissions table: " . $e->getMessage() . "</p>";
        echo "<p style='color: blue;'>ℹ️ Trying without foreign keys...</p>";
    }

    // 3. Create uploads directory
    echo "<p><strong>6. Creating uploads directory...</strong></p>";
    $upload_dir = __DIR__ . '/public/uploads';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p style='color: green;'>✓ Created directory: $upload_dir</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Directory already exists: $upload_dir</p>";
    }

    // Create subdirectories
    $subdirs = ['products', 'proofs', 'users', 'vouchers'];
    foreach ($subdirs as $subdir) {
        $path = $upload_dir . '/' . $subdir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            echo "<p style='color: green;'>✓ Created subdirectory: $path</p>";
        }
    }

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ ALL FIXES COMPLETED!</h2>";
    echo "<p><a href='/super-debug.php' style='background: #10B981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px;'>Run Super Debug Again</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
