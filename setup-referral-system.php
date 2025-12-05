<?php
/*
 * REFERRAL + TIER SYSTEM SETUP
 *
 * Upload file ini ke root directory
 * Akses via browser: http://yourdomain.com/setup-referral-system.php
 *
 * Script ini akan setup:
 * - Referral system
 * - Customer tier system
 * - Commission settings
 * - Stored procedures
 */

require_once __DIR__ . '/config.php';

// Security: Only allow admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    die('ERROR: Only admin can run this setup!');
}

$results = [];
$errors = [];

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Setup Referral System</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
        pre { background: #000; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
<h1>🚀 SETUP REFERRAL + TIER SYSTEM</h1>
<pre>";

// Step 1: Update users table
echo "\n=== STEP 1: UPDATE USERS TABLE ===\n";
try {
    $pdo->exec("
        ALTER TABLE `users`
        ADD COLUMN IF NOT EXISTS `referral_code` VARCHAR(20) UNIQUE DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `referred_by` INT(11) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `total_referrals` INT(11) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'bronze',
        ADD COLUMN IF NOT EXISTS `total_topup_amount` DECIMAL(15,2) DEFAULT 0.00
    ");
    echo "✓ Users table updated\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Users table: " . $e->getMessage();
}

// Add indexes
try {
    $pdo->exec("ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_referral_code` (`referral_code`)");
    $pdo->exec("ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_referred_by` (`referred_by`)");
    $pdo->exec("ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_tier` (`tier`)");
    echo "✓ Indexes created\n";
} catch (PDOException $e) {
    echo "⚠ Index warning: " . $e->getMessage() . "\n";
}

// Step 2: Create topups table
echo "\n=== STEP 2: CREATE TOPUPS TABLE ===\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `topups` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `user_id` INT(11) NOT NULL,
          `amount` DECIMAL(15,2) NOT NULL,
          `payment_method` VARCHAR(50) DEFAULT NULL,
          `transaction_id` VARCHAR(255) DEFAULT NULL,
          `status` ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
          `notes` TEXT DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `completed_at` TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_user` (`user_id`),
          KEY `idx_status` (`status`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Topups table created\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Topups table: " . $e->getMessage();
}

// Step 3: Update vouchers table
echo "\n=== STEP 3: UPDATE VOUCHERS TABLE ===\n";
try {
    $pdo->exec("ALTER TABLE `vouchers` MODIFY COLUMN `type` ENUM('percentage', 'fixed', 'free_shipping') DEFAULT 'percentage'");
    echo "✓ Vouchers type updated\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("
        ALTER TABLE `vouchers`
        ADD COLUMN IF NOT EXISTS `category` ENUM('discount', 'free_shipping') DEFAULT 'discount',
        ADD COLUMN IF NOT EXISTS `target_tier` ENUM('all', 'bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'all',
        ADD COLUMN IF NOT EXISTS `is_referral_reward` TINYINT(1) DEFAULT 0
    ");
    echo "✓ Vouchers columns added\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

// Update existing vouchers
try {
    $pdo->exec("
        UPDATE `vouchers`
        SET `category` = CASE
            WHEN `type` = 'free_shipping' THEN 'free_shipping'
            ELSE 'discount'
        END
        WHERE `category` IS NULL OR `category` = ''
    ");
    echo "✓ Existing vouchers updated\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

// Step 4: Create referral_rewards table
echo "\n=== STEP 4: CREATE REFERRAL REWARDS TABLE ===\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `referral_rewards` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `referrer_id` INT(11) NOT NULL,
          `referred_id` INT(11) NOT NULL,
          `topup_id` INT(11) DEFAULT NULL,
          `topup_amount` DECIMAL(15,2) DEFAULT 0.00,
          `commission_percent` DECIMAL(5,2) DEFAULT 5.00,
          `reward_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
          `completed_at` TIMESTAMP NULL DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_referrer` (`referrer_id`),
          KEY `idx_referred` (`referred_id`),
          KEY `idx_topup` (`topup_id`),
          KEY `idx_status` (`status`),
          FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`referred_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Referral rewards table created\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Referral rewards: " . $e->getMessage();
}

// Step 5: Create referral_settings table
echo "\n=== STEP 5: CREATE REFERRAL SETTINGS TABLE ===\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `referral_settings` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `setting_key` VARCHAR(100) NOT NULL UNIQUE,
          `setting_value` TEXT NOT NULL,
          `description` TEXT DEFAULT NULL,
          `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Referral settings table created\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Referral settings: " . $e->getMessage();
}

// Insert default settings
try {
    $pdo->exec("
        INSERT INTO `referral_settings` (`setting_key`, `setting_value`, `description`) VALUES
        ('referral_enabled', '1', 'Enable/disable referral system'),
        ('commission_percent', '5.00', 'Commission percentage for referrals'),
        ('min_topup_for_reward', '100000', 'Minimum topup amount to trigger referral reward'),
        ('referral_code_prefix', 'DRV', 'Prefix for referral codes')
        ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)
    ");
    echo "✓ Default settings inserted\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

// Step 6: Create other tables
echo "\n=== STEP 6: CREATE SUPPORTING TABLES ===\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `order_vouchers` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `order_id` INT(11) NOT NULL,
          `voucher_id` INT(11) NOT NULL,
          `voucher_code` VARCHAR(50) NOT NULL,
          `voucher_type` ENUM('percentage', 'fixed', 'free_shipping') NOT NULL,
          `voucher_category` ENUM('discount', 'free_shipping') NOT NULL,
          `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_order` (`order_id`),
          KEY `idx_voucher` (`voucher_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Order vouchers table created\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `tier_upgrades` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `user_id` INT(11) NOT NULL,
          `from_tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') NOT NULL,
          `to_tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') NOT NULL,
          `total_topup_at_upgrade` DECIMAL(15,2) NOT NULL,
          `upgraded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_user` (`user_id`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tier upgrades table created\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

// Step 7: Generate referral codes
echo "\n=== STEP 7: GENERATE REFERRAL CODES ===\n";
try {
    $pdo->exec("
        UPDATE `users`
        SET `referral_code` = CONCAT('DRV', UPPER(SUBSTRING(MD5(CONCAT(id, email, UNIX_TIMESTAMP())), 1, 6)))
        WHERE `referral_code` IS NULL AND `role` = 'customer'
    ");
    $count = $pdo->exec("SELECT COUNT(*) FROM users WHERE referral_code IS NOT NULL");
    echo "✓ Referral codes generated for existing users\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

// Step 8: Insert sample vouchers
echo "\n=== STEP 8: INSERT SAMPLE VOUCHERS ===\n";
try {
    $pdo->exec("
        INSERT INTO `vouchers` (`code`, `type`, `category`, `value`, `min_purchase`, `target_tier`, `valid_from`, `valid_until`, `is_active`) VALUES
        ('FREESHIP50K', 'free_shipping', 'free_shipping', 0, 50000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
        ('FREESHIP100K', 'free_shipping', 'free_shipping', 0, 100000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
        ('SILVER50K', 'free_shipping', 'free_shipping', 0, 50000.00, 'silver', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
        ('GOLD30K', 'free_shipping', 'free_shipping', 0, 30000.00, 'gold', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
        ('PLATINUM20K', 'free_shipping', 'free_shipping', 0, 20000.00, 'platinum', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
        ('VVIPFREE', 'free_shipping', 'free_shipping', 0, 0.00, 'vvip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1)
        ON DUPLICATE KEY UPDATE `code` = VALUES(`code`)
    ");
    echo "✓ Sample vouchers inserted\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

// Step 9: Create stored procedures
echo "\n=== STEP 9: CREATE STORED PROCEDURES ===\n";
try {
    $pdo->exec("DROP PROCEDURE IF EXISTS `update_user_tier`");
    $pdo->exec("
        CREATE PROCEDURE `update_user_tier`(IN user_id_param INT)
        BEGIN
            DECLARE total_topup DECIMAL(15,2);
            DECLARE current_tier VARCHAR(20);
            DECLARE new_tier VARCHAR(20);

            SELECT COALESCE(SUM(amount), 0) INTO total_topup
            FROM topups
            WHERE user_id = user_id_param AND status = 'completed';

            SELECT tier INTO current_tier FROM users WHERE id = user_id_param;

            SET new_tier = CASE
                WHEN total_topup >= 20000000 THEN 'vvip'
                WHEN total_topup >= 10000000 THEN 'platinum'
                WHEN total_topup >= 3000000 THEN 'gold'
                WHEN total_topup >= 1000000 THEN 'silver'
                ELSE 'bronze'
            END;

            UPDATE users
            SET tier = new_tier, total_topup_amount = total_topup
            WHERE id = user_id_param;

            IF current_tier != new_tier THEN
                INSERT INTO tier_upgrades (user_id, from_tier, to_tier, total_topup_at_upgrade)
                VALUES (user_id_param, current_tier, new_tier, total_topup);
            END IF;
        END
    ");
    echo "✓ Stored procedure 'update_user_tier' created\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Stored procedure 1: " . $e->getMessage();
}

try {
    $pdo->exec("DROP PROCEDURE IF EXISTS `process_referral_reward`");
    $pdo->exec("
        CREATE PROCEDURE `process_referral_reward`(IN topup_id_param INT)
        BEGIN
            DECLARE user_id_param INT;
            DECLARE topup_amount_param DECIMAL(15,2);
            DECLARE referrer_id_param INT;
            DECLARE commission_percent_val DECIMAL(5,2);
            DECLARE reward_amount DECIMAL(15,2);
            DECLARE first_topup_count INT;
            DECLARE min_topup_required DECIMAL(15,2);

            SELECT user_id, amount INTO user_id_param, topup_amount_param
            FROM topups
            WHERE id = topup_id_param AND status = 'completed';

            SELECT COUNT(*) INTO first_topup_count
            FROM topups
            WHERE user_id = user_id_param AND status = 'completed';

            IF first_topup_count = 1 THEN
                SELECT referred_by INTO referrer_id_param
                FROM users
                WHERE id = user_id_param;

                IF referrer_id_param IS NOT NULL THEN
                    SELECT CAST(setting_value AS DECIMAL(5,2)) INTO commission_percent_val
                    FROM referral_settings
                    WHERE setting_key = 'commission_percent';

                    SELECT CAST(setting_value AS DECIMAL(15,2)) INTO min_topup_required
                    FROM referral_settings
                    WHERE setting_key = 'min_topup_for_reward';

                    IF topup_amount_param >= min_topup_required THEN
                        SET reward_amount = (topup_amount_param * commission_percent_val / 100);

                        UPDATE referral_rewards
                        SET status = 'completed',
                            topup_id = topup_id_param,
                            topup_amount = topup_amount_param,
                            commission_percent = commission_percent_val,
                            reward_value = reward_amount,
                            completed_at = NOW()
                        WHERE referred_id = user_id_param AND status = 'pending';

                        UPDATE users
                        SET wallet_balance = wallet_balance + reward_amount
                        WHERE id = referrer_id_param;
                    END IF;
                END IF;
            END IF;

            CALL update_user_tier(user_id_param);
        END
    ");
    echo "✓ Stored procedure 'process_referral_reward' created\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Stored procedure 2: " . $e->getMessage();
}

// Summary
echo "\n\n=== SETUP COMPLETE! ===\n";
if (empty($errors)) {
    echo "<span class='success'>✓ ALL STEPS COMPLETED SUCCESSFULLY!</span>\n\n";
    echo "✅ Referral system ready\n";
    echo "✅ Customer tier system ready\n";
    echo "✅ Commission: 5% (changeable)\n";
    echo "✅ Stored procedures created\n";
    echo "✅ Sample vouchers added\n\n";
    echo "<span class='info'>Next steps:</span>\n";
    echo "1. Run setup-email-verification.php\n";
    echo "2. Upload admin/referrals/index.php\n";
    echo "3. Upload member/referral.php\n";
    echo "4. Test the system!\n\n";
    echo "<span class='error'>⚠ IMPORTANT: DELETE THIS FILE AFTER SETUP!</span>\n";
} else {
    echo "<span class='error'>✗ COMPLETED WITH SOME ERRORS:</span>\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\nMost errors can be ignored if tables already exist.\n";
}

echo "</pre>
</body>
</html>";
?>
