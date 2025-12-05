<?php
/**
 * MIDTRANS PAYMENT GATEWAY - DATABASE MIGRATION
 * 
 * Jalankan file ini SEKALI SAJA di browser:
 * https://dorve.id/INSTALL_PAYMENT_GATEWAY.php
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
    <title>Payment Gateway Migration</title>
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
        <h1>💳 Midtrans Payment Gateway - Database Migration</h1>
        <p style="color: #6B7280;">Dorve.id Professional Payment System</p>

        <div class="warning">
            <strong>⚠️ PENTING!</strong><br>
            <ul style="margin: 8px 0 0 20px;">
                <li>Migration ini akan membuat/update tables untuk payment gateway</li>
                <li>Jalankan hanya SEKALI</li>
                <li>Hapus file ini setelah selesai</li>
            </ul>
        </div>

        <?php if (!isset($_POST['confirm'])): ?>
            
            <h2 style="margin-top: 30px;">📋 Yang Akan Dibuat/Update:</h2>
            
            <div class="step">
                <h3>1️⃣ Create wallet_topups Table</h3>
                <p>Track semua topup wallet user (via Midtrans atau manual)</p>
            </div>

            <div class="step">
                <h3>2️⃣ Update orders Table</h3>
                <p>Tambah kolom: payment_method, payment_status, midtrans_*, voucher_*, unique_code</p>
            </div>

            <div class="step">
                <h3>3️⃣ Create payment_methods Table</h3>
                <p>Manage payment methods (Midtrans, Direct Bank Transfer, dll)</p>
            </div>

            <div class="step">
                <h3>4️⃣ Create payment_banks Table</h3>
                <p>Manage available banks untuk direct transfer</p>
            </div>

            <div class="step">
                <h3>5️⃣ Create payment_settings Table</h3>
                <p>Global payment settings (Midtrans keys, toggle enable/disable)</p>
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

            // STEP 1: Create wallet_topups table
            try {
                $sql = "CREATE TABLE IF NOT EXISTS `wallet_topups` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `amount` DECIMAL(15,2) NOT NULL,
                    `payment_method` VARCHAR(50) DEFAULT 'midtrans',
                    `payment_status` ENUM('pending', 'paid', 'failed', 'expired') DEFAULT 'pending',
                    `midtrans_order_id` VARCHAR(255) NULL,
                    `midtrans_transaction_id` VARCHAR(255) NULL,
                    `midtrans_snap_token` TEXT NULL,
                    `unique_code` INT NULL,
                    `bank_name` VARCHAR(100) NULL,
                    `account_number` VARCHAR(50) NULL,
                    `account_name` VARCHAR(255) NULL,
                    `paid_at` DATETIME NULL,
                    `expired_at` DATETIME NULL,
                    `notes` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_user` (`user_id`),
                    INDEX `idx_status` (`payment_status`),
                    INDEX `idx_midtrans_order` (`midtrans_order_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $pdo->exec($sql);
                $results[] = ['success' => true, 'step' => 'Create wallet_topups Table', 'message' => 'Table created successfully'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Create wallet_topups Table', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 2: Update orders table
            try {
                // Check existing columns
                $stmt = $pdo->query("DESCRIBE orders");
                $columns = array_column($stmt->fetchAll(), 'Field');
                
                $columnsToAdd = [
                    'payment_method' => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'wallet' AFTER payment_status",
                    'midtrans_order_id' => "ALTER TABLE orders ADD COLUMN midtrans_order_id VARCHAR(255) NULL AFTER payment_method",
                    'midtrans_transaction_id' => "ALTER TABLE orders ADD COLUMN midtrans_transaction_id VARCHAR(255) NULL AFTER midtrans_order_id",
                    'midtrans_snap_token' => "ALTER TABLE orders ADD COLUMN midtrans_snap_token TEXT NULL AFTER midtrans_transaction_id",
                    'unique_code' => "ALTER TABLE orders ADD COLUMN unique_code INT NULL AFTER midtrans_snap_token",
                    'paid_at' => "ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL AFTER unique_code"
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

            // STEP 3: Create payment_methods table
            try {
                $sql = "CREATE TABLE IF NOT EXISTS `payment_methods` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `code` VARCHAR(50) NOT NULL UNIQUE,
                    `name` VARCHAR(255) NOT NULL,
                    `description` TEXT NULL,
                    `type` ENUM('wallet', 'midtrans', 'bank_transfer') NOT NULL,
                    `icon` VARCHAR(255) NULL,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `sort_order` INT DEFAULT 0,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_type` (`type`),
                    INDEX `idx_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $pdo->exec($sql);
                
                // Insert default payment methods
                $stmt = $pdo->prepare("
                    INSERT INTO payment_methods (code, name, description, type, is_active, sort_order) VALUES
                    ('wallet', 'Dorve Wallet', 'Bayar dengan saldo wallet Dorve', 'wallet', 1, 1),
                    ('midtrans', 'Payment Gateway', 'Bayar via berbagai metode (Bank Transfer, E-Wallet, Kartu Kredit)', 'midtrans', 1, 2),
                    ('bank_transfer', 'Direct Bank Transfer', 'Transfer langsung ke rekening bank', 'bank_transfer', 1, 3)
                    ON DUPLICATE KEY UPDATE name=VALUES(name)
                ");
                $stmt->execute();
                
                $results[] = ['success' => true, 'step' => 'Create payment_methods Table', 'message' => 'Table created with default methods'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Create payment_methods Table', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 4: Create payment_banks table
            try {
                $sql = "CREATE TABLE IF NOT EXISTS `payment_banks` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `bank_code` VARCHAR(50) NOT NULL,
                    `bank_name` VARCHAR(255) NOT NULL,
                    `account_number` VARCHAR(50) NOT NULL,
                    `account_name` VARCHAR(255) NOT NULL,
                    `icon` VARCHAR(255) NULL,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `sort_order` INT DEFAULT 0,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $pdo->exec($sql);
                
                // Insert sample banks
                $stmt = $pdo->prepare("
                    INSERT INTO payment_banks (bank_code, bank_name, account_number, account_name, is_active, sort_order) VALUES
                    ('BCA', 'Bank Central Asia (BCA)', '1234567890', 'PT Dorve Indonesia', 1, 1),
                    ('MANDIRI', 'Bank Mandiri', '9876543210', 'PT Dorve Indonesia', 1, 2),
                    ('BNI', 'Bank Negara Indonesia (BNI)', '5555666677', 'PT Dorve Indonesia', 1, 3),
                    ('BRI', 'Bank Rakyat Indonesia (BRI)', '8888999900', 'PT Dorve Indonesia', 1, 4)
                    ON DUPLICATE KEY UPDATE bank_name=VALUES(bank_name)
                ");
                $stmt->execute();
                
                $results[] = ['success' => true, 'step' => 'Create payment_banks Table', 'message' => 'Table created with sample banks'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Create payment_banks Table', 'message' => $e->getMessage()];
                $errorCount++;
            }

            // STEP 5: Create payment_settings table
            try {
                $sql = "CREATE TABLE IF NOT EXISTS `payment_settings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                    `setting_value` TEXT NULL,
                    `setting_type` VARCHAR(50) DEFAULT 'text',
                    `description` TEXT NULL,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $pdo->exec($sql);
                
                // Insert Midtrans settings
                $stmt = $pdo->prepare("
                    INSERT INTO payment_settings (setting_key, setting_value, setting_type, description) VALUES
                    ('midtrans_merchant_id', 'MIDTRANS_MERCHANT_ID', 'text', 'Midtrans Merchant ID'),
                    ('midtrans_client_key', 'MIDTRANS_CLIENT_KEY', 'text', 'Midtrans Client Key'),
                    ('midtrans_server_key', 'MIDTRANS_SERVER_KEY', 'password', 'Midtrans Server Key'),
                    ('midtrans_is_production', '0', 'boolean', 'Midtrans Mode (0=Sandbox, 1=Production)'),
                    ('midtrans_enabled', '1', 'boolean', 'Enable Midtrans Payment Gateway'),
                    ('bank_transfer_enabled', '1', 'boolean', 'Enable Direct Bank Transfer'),
                    ('unique_code_min', '1', 'number', 'Minimum unique code'),
                    ('unique_code_max', '999', 'number', 'Maximum unique code')
                    ON DUPLICATE KEY UPDATE description=VALUES(description)
                ");
                $stmt->execute();
                
                $results[] = ['success' => true, 'step' => 'Create payment_settings Table', 'message' => 'Table created with default settings'];
                $successCount++;
            } catch (Exception $e) {
                $results[] = ['success' => false, 'step' => 'Create payment_settings Table', 'message' => $e->getMessage()];
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
                echo "<h3 style='color: #1E40AF; margin: 0 0 12px;'>📋 Next Steps:</h3>";
                echo "<ol style='color: #1E40AF; line-height: 1.8; margin: 0;'>";
                echo "<li>Update <strong>Midtrans credentials</strong> di Admin → Payment Settings</li>";
                echo "<li>Update <strong>bank accounts</strong> untuk direct transfer</li>";
                echo "<li>Test <strong>topup wallet</strong> via Midtrans</li>";
                echo "<li>Test <strong>checkout order</strong> dengan semua payment methods</li>";
                echo "</ol>";
                echo "</div>";

                echo "<a href='/admin/payment/settings.php' class='btn'>⚙️ Go to Payment Settings</a>";
                echo "<a href='/admin/payment/banks.php' class='btn' style='background: #10B981; margin-left: 12px;'>🏦 Manage Banks</a>";
            } else {
                echo "<div style='background: #FEE2E2; border-left: 4px solid #EF4444; padding: 20px; margin: 30px 0; border-radius: 8px;'>";
                echo "<h2 style='color: #991B1B; margin: 0 0 12px;'>⚠️ Migration Selesai dengan Error</h2>";
                echo "<p style='color: #991B1B; margin: 0;'>Success: <strong>$successCount</strong> | Errors: <strong style='color: #DC2626;'>$errorCount</strong></p>";
                echo "</div>";
            }
            ?>

            <div style="margin-top: 40px; padding: 20px; background: #FEF3C7; border-radius: 8px;">
                <h3 style="color: #92400E; margin: 0 0 12px;">⚠️ PENTING!</h3>
                <p style="color: #92400E; margin: 0;"><strong>Hapus file INSTALL_PAYMENT_GATEWAY.php ini setelah selesai!</strong></p>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>
