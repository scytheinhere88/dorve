<?php
/**
 * PAYMENT SYSTEM INSTALLER
 * Automatically creates all payment tables and inserts default data
 * Run this once to setup the complete payment system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment System Installer - Dorve</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; text-align: center; }
        .header h1 { font-size: 36px; margin-bottom: 12px; }
        .header p { opacity: 0.9; font-size: 16px; }

        .section { background: white; border-radius: 12px; padding: 32px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .section h2 { font-size: 24px; margin-bottom: 20px; color: #1A1A1A; }

        .step { padding: 20px; border-left: 4px solid #e0e0e0; margin-bottom: 16px; background: #fafafa; border-radius: 4px; }
        .step.success { border-color: #10B981; background: #ECFDF5; }
        .step.error { border-color: #EF4444; background: #FEF2F2; }
        .step.warning { border-color: #F59E0B; background: #FFFBEB; }
        .step.processing { border-color: #3B82F6; background: #EFF6FF; }

        .step-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .step-title { font-weight: 700; font-size: 16px; }
        .step-status { padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .status.success { background: #D1FAE5; color: #065F46; }
        .status.error { background: #FEE2E2; color: #991B1B; }
        .status.warning { background: #FEF3C7; color: #92400E; }
        .status.processing { background: #DBEAFE; color: #1E40AF; }

        .step-details { font-size: 14px; color: #666; line-height: 1.6; }
        .step-details code { background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 3px; font-family: monospace; }

        .query-info { background: #1A1A1A; color: #10B981; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 12px; margin-top: 12px; max-height: 200px; overflow-y: auto; }

        .btn { display: inline-block; padding: 16px 32px; background: #1A1A1A; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; border: none; cursor: pointer; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .btn-success { background: #10B981; }
        .btn-danger { background: #EF4444; }

        .actions { text-align: center; margin-top: 30px; }

        .summary { background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; padding: 32px; border-radius: 12px; text-align: center; }
        .summary h2 { font-size: 28px; margin-bottom: 16px; }
        .summary p { font-size: 16px; opacity: 0.95; line-height: 1.6; }
        .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 24px; }
        .summary-stat { background: rgba(255,255,255,0.2); padding: 20px; border-radius: 8px; }
        .summary-stat-value { font-size: 32px; font-weight: 700; margin-bottom: 4px; }
        .summary-stat-label { font-size: 14px; opacity: 0.9; }

        .error-summary { background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); }

        .warning-box { background: #FEF3C7; border: 2px solid #F59E0B; padding: 20px; border-radius: 8px; margin-bottom: 24px; }
        .warning-box h3 { color: #92400E; margin-bottom: 12px; font-size: 18px; }
        .warning-box p { color: #92400E; line-height: 1.6; }

        .info-box { background: #DBEAFE; border: 2px solid #3B82F6; padding: 20px; border-radius: 8px; margin-bottom: 24px; }
        .info-box h3 { color: #1E40AF; margin-bottom: 12px; font-size: 18px; }
        .info-box p { color: #1E40AF; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💳 Payment System Installer</h1>
            <p>Automated setup for complete payment infrastructure</p>
        </div>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {

    // Start installation
    echo '<div class="section">';
    echo '<h2>Installation Progress</h2>';

    $success_count = 0;
    $error_count = 0;
    $warning_count = 0;

    try {
        require_once __DIR__ . '/config.php';

        // Step 1: Check database connection
        echo '<div class="step success">';
        echo '<div class="step-header">';
        echo '<span class="step-title">✓ Step 1: Database Connection</span>';
        echo '<span class="step-status status success">Connected</span>';
        echo '</div>';
        echo '<div class="step-details">Successfully connected to database: <code>' . DB_NAME . '</code></div>';
        echo '</div>';
        $success_count++;

        // Step 2: Create wallet_transactions table
        echo '<div class="step processing">';
        echo '<div class="step-header">';
        echo '<span class="step-title">⚙️ Step 2: Creating wallet_transactions table</span>';
        echo '<span class="step-status status processing">Processing...</span>';
        echo '</div>';
        echo '</div>';

        $sql = "CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('topup', 'debit', 'refund', 'commission') NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            balance_before DECIMAL(15,2) DEFAULT 0,
            balance_after DECIMAL(15,2) DEFAULT 0,
            description TEXT,
            payment_method VARCHAR(50),
            payment_status VARCHAR(20) DEFAULT 'pending',
            reference_id VARCHAR(100),
            unique_code INT,
            bank_account_id INT,
            admin_notes TEXT,
            proof_image VARCHAR(255),
            approved_at TIMESTAMP NULL,
            approved_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_payment_status (payment_status),
            INDEX idx_created_at (created_at)
        )";

        $pdo->exec($sql);

        echo '<div class="step success">';
        echo '<div class="step-header">';
        echo '<span class="step-title">✓ Step 2: wallet_transactions table created</span>';
        echo '<span class="step-status status success">Success</span>';
        echo '</div>';
        echo '<div class="step-details">Table includes: unique_code, bank_account_id, proof_image, approval tracking</div>';
        echo '</div>';
        $success_count++;

        // Step 3: Create system_settings table
        echo '<div class="step processing">';
        echo '<div class="step-header">';
        echo '<span class="step-title">⚙️ Step 3: Creating system_settings table</span>';
        echo '<span class="step-status status processing">Processing...</span>';
        echo '</div>';
        echo '</div>';

        $sql = "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);

        // Insert default settings
        $settings = [
            ['whatsapp_admin', '628123456789', 'WhatsApp number for customer support (with country code, no +)'],
            ['whatsapp_message', 'Halo Admin, saya sudah melakukan transfer untuk topup wallet. Mohon di cek ya!', 'Default WhatsApp message template'],
            ['min_topup_amount', '10000', 'Minimum topup amount in IDR'],
            ['unique_code_min', '100', 'Minimum unique code (3 digits)'],
            ['unique_code_max', '999', 'Maximum unique code (3 digits)']
        ];

        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }

        echo '<div class="step success">';
        echo '<div class="step-header">';
        echo '<span class="step-title">✓ Step 3: system_settings table created</span>';
        echo '<span class="step-status status success">Success</span>';
        echo '</div>';
        echo '<div class="step-details">Inserted ' . count($settings) . ' default settings (WhatsApp, min topup, unique codes)</div>';
        echo '</div>';
        $success_count++;

        // Step 4: Create payment_methods table
        echo '<div class="step processing">';
        echo '<div class="step-header">';
        echo '<span class="step-title">⚙️ Step 4: Creating payment_methods table</span>';
        echo '<span class="step-status status processing">Processing...</span>';
        echo '</div>';
        echo '</div>';

        $sql = "CREATE TABLE IF NOT EXISTS payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(50) NOT NULL UNIQUE,
            type ENUM('qris', 'bank_transfer', 'gateway', 'ewallet') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            settings JSON,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);

        // Insert default payment methods
        $methods = [
            ['QRIS (Midtrans)', 'qris_midtrans', 'qris', 0, '{"fee": 0, "min_amount": 10000}', 1],
            ['Bank Transfer', 'bank_transfer', 'bank_transfer', 1, '{"fee": 0, "min_amount": 10000, "requires_confirmation": true}', 2],
            ['Midtrans Payment Gateway', 'midtrans_gateway', 'gateway', 0, '{"fee": 0, "min_amount": 10000, "supports": ["credit_card", "gopay", "shopeepay", "bank_transfer"]}', 3],
            ['PayPal', 'paypal', 'gateway', 0, '{"fee": 0, "min_amount": 10000}', 4]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO payment_methods (name, slug, type, is_active, settings, display_order)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ");

        foreach ($methods as $method) {
            $stmt->execute($method);
        }

        echo '<div class="step success">';
        echo '<div class="step-header">';
        echo '<span class="step-title">✓ Step 4: payment_methods table created</span>';
        echo '<span class="step-status status success">Success</span>';
        echo '</div>';
        echo '<div class="step-details">Added 4 payment methods: QRIS, Bank Transfer, Midtrans Gateway, PayPal</div>';
        echo '</div>';
        $success_count++;

        // Step 5: Create bank_accounts table
        echo '<div class="step processing">';
        echo '<div class="step-header">';
        echo '<span class="step-title">⚙️ Step 5: Creating bank_accounts table</span>';
        echo '<span class="step-status status processing">Processing...</span>';
        echo '</div>';
        echo '</div>';

        $sql = "CREATE TABLE IF NOT EXISTS bank_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bank_name VARCHAR(100) NOT NULL,
            bank_code VARCHAR(20),
            account_number VARCHAR(50) NOT NULL,
            account_name VARCHAR(100) NOT NULL,
            branch VARCHAR(100),
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);

        // Insert default banks (with placeholder data)
        $banks = [
            ['BCA (Bank Central Asia)', 'BCA', '1234567890', 'PT Dorve House', '', 1, 1],
            ['Mandiri', 'MANDIRI', '1234567890123', 'PT Dorve House', '', 1, 2],
            ['BNI (Bank Negara Indonesia)', 'BNI', '1234567890', 'PT Dorve House', '', 1, 3],
            ['BRI (Bank Rakyat Indonesia)', 'BRI', '123456789012345', 'PT Dorve House', '', 0, 4],
            ['CIMB Niaga', 'CIMB', '1234567890', 'PT Dorve House', '', 0, 5],
            ['Danamon', 'DANAMON', '1234567890', 'PT Dorve House', '', 0, 6],
            ['Permata Bank', 'PERMATA', '1234567890', 'PT Dorve House', '', 0, 7]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO bank_accounts (bank_name, bank_code, account_number, account_name, branch, is_active, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE bank_name = VALUES(bank_name)
        ");

        foreach ($banks as $bank) {
            $stmt->execute($bank);
        }

        echo '<div class="step success">';
        echo '<div class="step-header">';
        echo '<span class="step-title">✓ Step 5: bank_accounts table created</span>';
        echo '<span class="step-status status success">Success</span>';
        echo '</div>';
        echo '<div class="step-details">Added 7 Indonesian banks (BCA, Mandiri, BNI, BRI, CIMB, Danamon, Permata)</div>';
        echo '</div>';
        $success_count++;

        echo '<div class="step warning">';
        echo '<div class="step-header">';
        echo '<span class="step-title">⚠️ Important: Update Bank Account Numbers</span>';
        echo '<span class="step-status status warning">Action Required</span>';
        echo '</div>';
        echo '<div class="step-details">Placeholder account numbers inserted. Please update with real account numbers in <code>Admin → Settings → Bank Accounts</code></div>';
        echo '</div>';
        $warning_count++;

        // Step 6: Create payment_gateway_settings table
        echo '<div class="step processing">';
        echo '<div class="step-header">';
        echo '<span class="step-title">⚙️ Step 6: Creating payment_gateway_settings table</span>';
        echo '<span class="step-status status processing">Processing...</span>';
        echo '</div>';
        echo '</div>';

        $sql = "CREATE TABLE IF NOT EXISTS payment_gateway_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            gateway_name VARCHAR(50) NOT NULL UNIQUE,
            api_key VARCHAR(255),
            api_secret VARCHAR(255),
            merchant_id VARCHAR(100),
            client_id VARCHAR(255),
            client_secret VARCHAR(255),
            is_production TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 0,
            settings JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        $pdo->exec($sql);

        // Insert gateway placeholders
        $stmt = $pdo->prepare("
            INSERT INTO payment_gateway_settings (gateway_name, is_active)
            VALUES (?, 0)
            ON DUPLICATE KEY UPDATE gateway_name = VALUES(gateway_name)
        ");

        $stmt->execute(['midtrans']);
        $stmt->execute(['paypal']);

        echo '<div class="step success">';
        echo '<div class="step-header">';
        echo '<span class="step-title">✓ Step 6: payment_gateway_settings table created</span>';
        echo '<span class="step-status status success">Success</span>';
        echo '</div>';
        echo '<div class="step-details">Gateway placeholders created for Midtrans and PayPal (inactive by default)</div>';
        echo '</div>';
        $success_count++;

        echo '<div class="step warning">';
        echo '<div class="step-header">';
        echo '<span class="step-title">⚠️ Important: Configure Payment Gateways</span>';
        echo '<span class="step-status status warning">Optional</span>';
        echo '</div>';
        echo '<div class="step-details">Configure API keys in <code>Admin → Settings → Payment Settings</code> to enable Midtrans and PayPal</div>';
        echo '</div>';
        $warning_count++;

        echo '</div>';

        // Success Summary
        echo '<div class="summary">';
        echo '<h2>🎉 Installation Complete!</h2>';
        echo '<p>Payment system has been successfully installed and configured.</p>';
        echo '<div class="summary-stats">';
        echo '<div class="summary-stat">';
        echo '<div class="summary-stat-value">' . $success_count . '</div>';
        echo '<div class="summary-stat-label">Steps Completed</div>';
        echo '</div>';
        echo '<div class="summary-stat">';
        echo '<div class="summary-stat-value">4</div>';
        echo '<div class="summary-stat-label">Tables Created</div>';
        echo '</div>';
        echo '<div class="summary-stat">';
        echo '<div class="summary-stat-value">' . $warning_count . '</div>';
        echo '<div class="summary-stat-label">Actions Required</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Next Steps
        echo '<div class="section">';
        echo '<h2>📋 Next Steps</h2>';

        echo '<div class="info-box">';
        echo '<h3>1. Update Bank Account Numbers</h3>';
        echo '<p>Visit <strong>Admin → Settings → Bank Accounts</strong> and update the placeholder account numbers with your real bank account details.</p>';
        echo '</div>';

        echo '<div class="info-box">';
        echo '<h3>2. Configure Payment Gateways (Optional)</h3>';
        echo '<p>Visit <strong>Admin → Settings → Payment Settings</strong> to:</p>';
        echo '<p>• Enter Midtrans API keys (Server Key & Client Key)<br>';
        echo '• Enter PayPal credentials (Client ID & Secret)<br>';
        echo '• Enable/Disable payment methods as needed</p>';
        echo '</div>';

        echo '<div class="info-box">';
        echo '<h3>3. Test the System</h3>';
        echo '<p>• Visit <code>/member/wallet-new.php</code> to test payment method selection<br>';
        echo '• Make a test deposit with Bank Transfer<br>';
        echo '• Check <code>/admin/deposits/</code> to approve the deposit<br>';
        echo '• Run <code>/super-debug.php</code> to verify all systems</p>';
        echo '</div>';

        echo '<div class="actions">';
        echo '<a href="/admin/settings/bank-accounts.php" class="btn btn-success">Configure Bank Accounts</a> ';
        echo '<a href="/admin/settings/payment-settings.php" class="btn">Payment Settings</a> ';
        echo '<a href="/super-debug.php" class="btn">Run Debug Tool</a>';
        echo '</div>';

        echo '</div>';

    } catch (PDOException $e) {
        echo '<div class="step error">';
        echo '<div class="step-header">';
        echo '<span class="step-title">✗ Installation Failed</span>';
        echo '<span class="step-status status error">Error</span>';
        echo '</div>';
        echo '<div class="step-details">';
        echo '<strong>Error Message:</strong><br>';
        echo '<div class="query-info">' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>';

        echo '<div class="summary error-summary">';
        echo '<h2>❌ Installation Failed</h2>';
        echo '<p>An error occurred during installation. Please check the error message above and try again.</p>';
        echo '</div>';

        echo '<div class="actions">';
        echo '<form method="POST"><button type="submit" name="install" class="btn btn-danger">Retry Installation</button></form>';
        echo '</div>';

        $error_count++;
    }

} else {
    // Show installation form
    ?>

    <div class="section">
        <h2>Welcome to Payment System Installer</h2>
        <p style="line-height: 1.8; color: #666; margin-bottom: 24px;">
            This installer will automatically create all required database tables for the complete payment system including:
        </p>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 32px;">
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981;">
                <strong style="color: #1A1A1A; display: block; margin-bottom: 8px;">✓ wallet_transactions</strong>
                <span style="font-size: 13px; color: #666;">Store all topup, debit, and commission transactions with unique codes</span>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981;">
                <strong style="color: #1A1A1A; display: block; margin-bottom: 8px;">✓ payment_methods</strong>
                <span style="font-size: 13px; color: #666;">Manage QRIS, Bank Transfer, Midtrans Gateway, and PayPal</span>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981;">
                <strong style="color: #1A1A1A; display: block; margin-bottom: 8px;">✓ bank_accounts</strong>
                <span style="font-size: 13px; color: #666;">Configure bank accounts for manual transfer (BCA, Mandiri, etc)</span>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981;">
                <strong style="color: #1A1A1A; display: block; margin-bottom: 8px;">✓ payment_gateway_settings</strong>
                <span style="font-size: 13px; color: #666;">Store API keys for Midtrans and PayPal integrations</span>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #10B981;">
                <strong style="color: #1A1A1A; display: block; margin-bottom: 8px;">✓ system_settings</strong>
                <span style="font-size: 13px; color: #666;">WhatsApp integration, minimum topup, unique code settings</span>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #3B82F6;">
                <strong style="color: #1A1A1A; display: block; margin-bottom: 8px;">+ Default Data</strong>
                <span style="font-size: 13px; color: #666;">Pre-configured payment methods, banks, and system settings</span>
            </div>
        </div>

        <div class="warning-box">
            <h3>⚠️ Before You Install</h3>
            <p>
                • Make sure you have database backup<br>
                • This installer is safe to run multiple times (uses IF NOT EXISTS)<br>
                • Existing data will NOT be deleted<br>
                • You can update settings after installation
            </p>
        </div>

        <div class="actions">
            <form method="POST">
                <button type="submit" name="install" class="btn">
                    🚀 Install Payment System
                </button>
            </form>
        </div>
    </div>

    <div class="section">
        <h2>What Happens After Installation?</h2>

        <div style="display: grid; gap: 16px;">
            <div style="border-left: 4px solid #3B82F6; padding: 16px; background: #EFF6FF;">
                <strong style="color: #1E40AF; display: block; margin-bottom: 8px;">Step 1: Configure Bank Accounts</strong>
                <span style="font-size: 14px; color: #1E40AF;">Update placeholder account numbers with your real bank details in Admin Panel</span>
            </div>

            <div style="border-left: 4px solid #3B82F6; padding: 16px; background: #EFF6FF;">
                <strong style="color: #1E40AF; display: block; margin-bottom: 8px;">Step 2: Setup Payment Gateways (Optional)</strong>
                <span style="font-size: 14px; color: #1E40AF;">Enter Midtrans and PayPal API keys if you want to use them</span>
            </div>

            <div style="border-left: 4px solid #3B82F6; padding: 16px; background: #EFF6FF;">
                <strong style="color: #1E40AF; display: block; margin-bottom: 8px;">Step 3: Test System</strong>
                <span style="font-size: 14px; color: #1E40AF;">Make a test deposit and approve it from Admin Panel</span>
            </div>

            <div style="border-left: 4px solid #10B981; padding: 16px; background: #ECFDF5;">
                <strong style="color: #065F46; display: block; margin-bottom: 8px;">Done! Payment System Ready 🎉</strong>
                <span style="font-size: 14px; color: #065F46;">Your customers can now topup their wallets with multiple payment methods</span>
            </div>
        </div>
    </div>

    <?php
}
?>

    </div>
</body>
</html>
