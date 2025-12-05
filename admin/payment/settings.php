<?php
/**
 * ADMIN - Payment Settings
 * Manage Midtrans credentials & payment method toggles
 */
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = str_replace('setting_', '', $key);
                $stmt = $pdo->prepare("
                    UPDATE payment_settings 
                    SET setting_value = ? 
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $settingKey]);
            }
        }
        $success = 'Payment settings updated successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all settings
$stmt = $pdo->query("SELECT * FROM payment_settings ORDER BY setting_key");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row;
}

// Get payment methods
$stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY sort_order");
$paymentMethods = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - Admin Dorve</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; color: #1A1A1A; }
        .admin-layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .admin-sidebar { background: #1A1A1A; color: white; padding: 30px 0; position: fixed; width: 260px; height: 100vh; overflow-y: auto; }
        .admin-logo { font-size: 24px; font-weight: 700; letter-spacing: 3px; padding: 0 30px 30px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-nav { padding: 20px 0; }
        .nav-item { padding: 12px 30px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; transition: all 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .admin-content { margin-left: 260px; padding: 40px; }
        .header { margin-bottom: 40px; }
        .header h1 { font-size: 32px; font-weight: 600; margin-bottom: 8px; }
        .header .subtitle { color: #6B7280; }
        
        .settings-container { max-width: 900px; }
        .settings-card {
            background: white; border-radius: 12px; padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 24px;
        }
        .settings-card h2 {
            font-size: 20px; font-weight: 700; margin-bottom: 24px;
            padding-bottom: 16px; border-bottom: 2px solid #E5E7EB;
        }
        
        .form-group { margin-bottom: 24px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 600;
            font-size: 14px; color: #374151;
        }
        .form-group .help-text {
            font-size: 13px; color: #6B7280; margin-top: 4px;
        }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="number"] {
            width: 100%; padding: 12px 16px;
            border: 2px solid #E5E7EB; border-radius: 8px;
            font-size: 15px; transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none; border-color: #667EEA;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative; display: inline-block;
            width: 56px; height: 28px;
        }
        .toggle-switch input {
            opacity: 0; width: 0; height: 0;
        }
        .toggle-slider {
            position: absolute; cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s;
            border-radius: 28px;
        }
        .toggle-slider:before {
            position: absolute; content: "";
            height: 20px; width: 20px; left: 4px; bottom: 4px;
            background-color: white; transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #10B981;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(28px);
        }
        
        .toggle-item {
            display: flex; justify-content: space-between;
            align-items: center; padding: 16px;
            border: 2px solid #E5E7EB; border-radius: 12px;
            margin-bottom: 12px; transition: all 0.3s;
        }
        .toggle-item:hover {
            border-color: #667EEA; background: #F9FAFB;
        }
        .toggle-item-info h3 {
            font-size: 16px; font-weight: 600; margin-bottom: 4px;
        }
        .toggle-item-info p {
            font-size: 13px; color: #6B7280;
        }
        
        .btn {
            padding: 12px 24px; border-radius: 8px; font-size: 15px;
            font-weight: 600; cursor: pointer; border: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 16px; border-radius: 8px; margin-bottom: 24px;
        }
        .alert-success {
            background: #D1FAE5; border: 1px solid #10B981; color: #065F46;
        }
        .alert-error {
            background: #FEE2E2; border: 1px solid #EF4444; color: #991B1B;
        }
        
        .credential-item {
            background: #F9FAFB; padding: 12px 16px; border-radius: 8px;
            margin-bottom: 12px; display: flex; justify-content: space-between;
            align-items: center; font-family: 'Courier New', monospace;
        }
        .credential-value {
            color: #667EEA; font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE</div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="nav-item">Dashboard</a>
                <a href="/admin/products/index.php" class="nav-item">Produk</a>
                <a href="/admin/orders/index.php" class="nav-item">Pesanan</a>
                <a href="/admin/users/index.php" class="nav-item">Pengguna</a>
                <a href="/admin/vouchers/index.php" class="nav-item">Voucher</a>
                <a href="/admin/payment/settings.php" class="nav-item active">Payment Settings</a>
                <a href="/admin/payment/banks.php" class="nav-item">Manage Banks</a>
                <a href="/auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="header">
                <h1>💳 Payment Settings</h1>
                <p class="subtitle">Manage payment gateway & methods</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="settings-container">
                <form method="POST">
                    <!-- Midtrans Configuration -->
                    <div class="settings-card">
                        <h2>🔐 Midtrans Configuration</h2>
                        
                        <div class="form-group">
                            <label>Merchant ID</label>
                            <input type="text" name="setting_midtrans_merchant_id" 
                                   value="<?= htmlspecialchars($settings['midtrans_merchant_id']['setting_value'] ?? '') ?>" 
                                   required>
                            <div class="help-text">Get from Midtrans Dashboard</div>
                        </div>

                        <div class="form-group">
                            <label>Client Key</label>
                            <input type="text" name="setting_midtrans_client_key" 
                                   value="<?= htmlspecialchars($settings['midtrans_client_key']['setting_value'] ?? '') ?>" 
                                   required>
                            <div class="help-text">Client Key for frontend integration</div>
                        </div>

                        <div class="form-group">
                            <label>Server Key</label>
                            <input type="password" name="setting_midtrans_server_key" 
                                   value="<?= htmlspecialchars($settings['midtrans_server_key']['setting_value'] ?? '') ?>" 
                                   required>
                            <div class="help-text">Server Key for backend API calls (kept secret)</div>
                        </div>

                        <div class="form-group">
                            <label>Environment Mode</label>
                            <select name="setting_midtrans_is_production" class="form-group" style="width: 100%; padding: 12px 16px; border: 2px solid #E5E7EB; border-radius: 8px;">
                                <option value="0" <?= ($settings['midtrans_is_production']['setting_value'] ?? '0') == '0' ? 'selected' : '' ?>>
                                    Sandbox (Testing)
                                </option>
                                <option value="1" <?= ($settings['midtrans_is_production']['setting_value'] ?? '0') == '1' ? 'selected' : '' ?>>
                                    Production (Live)
                                </option>
                            </select>
                            <div class="help-text">Use Sandbox for testing, Production for live transactions</div>
                        </div>
                    </div>

                    <!-- Payment Methods Toggle -->
                    <div class="settings-card">
                        <h2>💰 Payment Methods</h2>
                        
                        <div class="toggle-item">
                            <div class="toggle-item-info">
                                <h3>🎯 Midtrans Payment Gateway</h3>
                                <p>Enable all payment methods via Midtrans (Bank Transfer, E-Wallet, Cards)</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="setting_midtrans_enabled" value="1" 
                                       <?= ($settings['midtrans_enabled']['setting_value'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="toggle-item">
                            <div class="toggle-item-info">
                                <h3>🏦 Direct Bank Transfer</h3>
                                <p>Enable manual bank transfer with unique code</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="setting_bank_transfer_enabled" value="1" 
                                       <?= ($settings['bank_transfer_enabled']['setting_value'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Unique Code Settings -->
                    <div class="settings-card">
                        <h2>🔢 Unique Code Settings (for Bank Transfer)</h2>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Minimum Code</label>
                                <input type="number" name="setting_unique_code_min" 
                                       value="<?= htmlspecialchars($settings['unique_code_min']['setting_value'] ?? '1') ?>" 
                                       min="1" max="999">
                                <div class="help-text">Minimum unique code (e.g., 1)</div>
                            </div>

                            <div class="form-group">
                                <label>Maximum Code</label>
                                <input type="number" name="setting_unique_code_max" 
                                       value="<?= htmlspecialchars($settings['unique_code_max']['setting_value'] ?? '999') ?>" 
                                       min="1" max="999">
                                <div class="help-text">Maximum unique code (e.g., 999)</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        💾 Save Settings
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
    // Handle toggle checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                this.parentElement.parentElement.insertAdjacentHTML('beforeend', 
                    '<input type="hidden" name="' + this.name + '" value="0">');
            } else {
                const hidden = this.parentElement.parentElement.querySelector('input[type="hidden"]');
                if (hidden) hidden.remove();
            }
        });
    });
    </script>
</body>
</html>
