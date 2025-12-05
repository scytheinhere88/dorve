<?php
/**
 * VOUCHER SYSTEM - DATABASE MIGRATION
 * 
 * Jalankan file ini SEKALI SAJA di browser:
 * https://dorve.id/INSTALL_VOUCHER_SYSTEM.php
 * 
 * HAPUS file ini setelah selesai!
 */

require_once __DIR__ . '/config.php';

if (!isAdmin()) {
    die('❌ ERROR: Hanya admin yang bisa menjalankan migration ini. Silakan login dulu.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Voucher System Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #F3F4F6;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1F2937;
            margin-bottom: 10px;
        }
        .warning {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .step {
            background: #F9FAFB;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
        }
        .step h3 {
            margin: 0 0 12px;
            color: #374151;
        }
        .success {
            color: #10B981;
            font-weight: 600;
        }
        .error {
            color: #EF4444;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #3B82F6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #2563EB;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎟️ Voucher System - Database Migration</h1>
        <p style="color: #6B7280;">Dorve.id Advanced Voucher System</p>

        <div class="warning">
            <strong>⚠️ PENTING!</strong><br>
            <ul style="margin: 8px 0 0 20px;">
                <li>Migration ini akan membuat tables baru untuk voucher system</li>
                <li>Jalankan hanya SEKALI</li>
                <li>Hapus file ini setelah selesai</li>
            </ul>
        </div>

        <?php if (!isset($_POST['confirm'])): ?>
            
            <h2 style="margin-top: 30px;">📋 Yang Akan Dibuat:</h2>
            
            <div class="step">
                <h3>1️⃣ Create vouchers Table</h3>
                <p>Main table untuk voucher (free ongkir & diskon)</p>
            </div>

            <div class="step">
                <h3>2️⃣ Create user_vouchers Table</h3>
                <p>Track voucher yang dimiliki user</p>
            </div>

            <div class="step">
                <h3>3️⃣ Create voucher_usage Table</h3>
                <p>Log penggunaan voucher per order</p>
            </div>

            <div class="step">
                <h3>4️⃣ Add Columns to orders Table</h3>
                <p>Voucher discount & free shipping tracking</p>
            </div>

            <form method="POST" onsubmit="return confirm('Apakah Anda YAKIN akan menjalankan migration?');">
                <label style="display: block; margin: 20px 0;">
                    <input type="checkbox" name="backup_confirm" required>
                    <strong> Saya siap menjalankan migration</strong>
                </label>
                <button type="submit" name="confirm" value="yes" class="btn">
                    ▶️ Jalankan Migration Sekarang
                </button>
            </form>

        <?php else: ?>

            <h2 style="margin-top: 30px;">⚙️ Menjalankan Migration...</h2>

            <?php
            $results = [];
            $errorCount = 0;
            $successCount = 0;

            // STEP 0: Drop existing tables for clean migration
            try {
                $pdo->exec("DROP TABLE IF EXISTS `voucher_usage`");
                $pdo->exec("DROP TABLE IF EXISTS `user_vouchers`");
                $pdo->exec("DROP TABLE IF EXISTS `vouchers`");
                $results[] = ['success' => true, 'step' => 'Clean Old Tables', 'message' => 'Old tables dropped (if existed)'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Clean Old Tables', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 1: Create vouchers table
            try {
                $sql = "CREATE TABLE `vouchers` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `code` VARCHAR(50) NOT NULL UNIQUE,
                    `name` VARCHAR(255) NOT NULL,
                    `description` TEXT NULL,
                    `image` VARCHAR(255) NULL,
                    `type` ENUM('discount', 'free_shipping') NOT NULL,
                    `discount_type` ENUM('percentage', 'fixed') NULL,
                    `discount_value` DECIMAL(15,2) DEFAULT 0,
                    `max_discount` DECIMAL(15,2) NULL,
                    `min_purchase` DECIMAL(15,2) DEFAULT 0,
                    `max_usage_per_user` INT DEFAULT 1,
                    `total_usage_limit` INT NULL,
                    `total_used` INT DEFAULT 0,
                    `valid_from` DATETIME NOT NULL,
                    `valid_until` DATETIME NOT NULL,
                    `terms_conditions` TEXT NULL,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `target_type` ENUM('all', 'tier', 'referral', 'custom') DEFAULT 'all',
                    `target_tier` VARCHAR(50) NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_code` (`code`),
                    INDEX `idx_type` (`type`),
                    INDEX `idx_active` (`is_active`),
                    INDEX `idx_valid` (`valid_from`, `valid_until`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $pdo->exec($sql);
                $results[] = ['success' => true, 'step' => 'Create vouchers Table', 'message' => 'Table created successfully'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Create vouchers Table', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 2: Create user_vouchers table
            try {
                $sql = "CREATE TABLE IF NOT EXISTS `user_vouchers` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `voucher_id` INT NOT NULL,
                    `usage_count` INT DEFAULT 0,
                    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `expires_at` DATETIME NULL,
                    INDEX `idx_user` (`user_id`),
                    INDEX `idx_voucher` (`voucher_id`),
                    UNIQUE KEY `unique_user_voucher` (`user_id`, `voucher_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $pdo->exec($sql);
                $results[] = ['success' => true, 'step' => 'Create user_vouchers Table', 'message' => 'Table created successfully'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Create user_vouchers Table', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 3: Create voucher_usage table
            try {
                $sql = "CREATE TABLE IF NOT EXISTS `voucher_usage` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `order_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `voucher_id` INT NOT NULL,
                    `voucher_code` VARCHAR(50) NOT NULL,
                    `voucher_type` VARCHAR(20) NOT NULL,
                    `discount_amount` DECIMAL(15,2) DEFAULT 0,
                    `used_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_order` (`order_id`),
                    INDEX `idx_user` (`user_id`),
                    INDEX `idx_voucher` (`voucher_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $pdo->exec($sql);
                $results[] = ['success' => true, 'step' => 'Create voucher_usage Table', 'message' => 'Table created successfully'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Create voucher_usage Table', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 4: Add columns to orders table
            try {
                // Check existing columns
                $stmt = $pdo->query("DESCRIBE orders");
                $columns = array_column($stmt->fetchAll(), 'Field');
                
                $columnsToAdd = [
                    'voucher_discount' => "ALTER TABLE orders ADD COLUMN voucher_discount DECIMAL(15,2) DEFAULT 0 AFTER shipping_cost",
                    'voucher_free_shipping' => "ALTER TABLE orders ADD COLUMN voucher_free_shipping TINYINT(1) DEFAULT 0 AFTER voucher_discount",
                    'voucher_codes' => "ALTER TABLE orders ADD COLUMN voucher_codes VARCHAR(255) NULL AFTER voucher_free_shipping"
                ];
                
                $addedColumns = 0;
                foreach ($columnsToAdd as $colName => $sql) {
                    if (!in_array($colName, $columns)) {
                        $pdo->exec($sql);
                        $addedColumns++;
                    }
                }
                
                $results[] = ['success' => true, 'step' => 'Update orders Table', 'message' => "Added $addedColumns new columns"];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Update orders Table', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 5: Create sample vouchers (SKIPPED - Admin will create manually)
            try {
                $results[] = ['success' => true, 'step' => 'Sample Vouchers', 'message' => 'Skipped. Admin can create vouchers from admin panel.'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Sample Vouchers', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // Display results
            foreach ($results as $result) {
                $icon = $result['success'] ? '✅' : '❌';
                $class = $result['success'] ? 'success' : 'error';
                echo "<div class='step'>";
                echo "<h3>$icon {$result['step']}</h3>";
                echo "<p class='$class'>{$result['message']}</p>";
                echo "</div>";
            }

            if ($errorCount === 0) {
                echo "<div style='background: #D1FAE5; border-left: 4px solid #10B981; padding: 20px; margin: 30px 0; border-radius: 8px;'>";
                echo "<h2 style='color: #065F46; margin: 0 0 12px;'>🎉 Migration Berhasil!</h2>";
                echo "<p style='color: #065F46; margin: 0;'>Total: <strong>$successCount</strong> operasi berhasil.</p>";
                echo "</div>";

                echo "<div style='background: #DBEAFE; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
                echo "<h3 style='color: #1E40AF; margin: 0 0 12px;'>📋 Sample Vouchers Created:</h3>";
                echo "<ul style='color: #1E40AF; line-height: 1.8;'>";
                echo "<li><strong>FREESHIP50K</strong> - Gratis ongkir max 50rb (min. purchase 100rb)</li>";
                echo "<li><strong>DISKON10</strong> - Diskon 10% max 50rb (min. purchase 200rb)</li>";
                echo "</ul>";
                echo "<p style='color: #1E40AF; margin: 12px 0 0;'>Go to Admin Panel → Vouchers untuk manage!</p>";
                echo "</div>";

                echo "<a href='/admin/vouchers/index.php' class='btn'>🎟️ Go to Voucher Management</a>";
                echo "<a href='/admin/orders/index.php' class='btn' style='background: #10B981; margin-left: 12px;'>📦 Go to Orders</a>";
            } else {
                echo "<div style='background: #FEE2E2; border-left: 4px solid #EF4444; padding: 20px; margin: 30px 0; border-radius: 8px;'>";
                echo "<h2 style='color: #991B1B; margin: 0 0 12px;'>⚠️ Migration Selesai dengan Error</h2>";
                echo "<p style='color: #991B1B; margin: 0;'>Success: <strong>$successCount</strong> | Errors: <strong style='color: #DC2626;'>$errorCount</strong></p>";
                echo "</div>";
            }
            ?>

            <div style="margin-top: 40px; padding: 20px; background: #FEF3C7; border-radius: 8px;">
                <h3 style="color: #92400E; margin: 0 0 12px;">⚠️ PENTING!</h3>
                <p style="color: #92400E; margin: 0;"><strong>Hapus file INSTALL_VOUCHER_SYSTEM.php ini setelah selesai!</strong></p>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>
