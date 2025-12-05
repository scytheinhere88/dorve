<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/admin/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_payment_method':
                    $stmt = $pdo->prepare("UPDATE payment_methods SET is_active = ? WHERE id = ?");
                    $stmt->execute([
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['method_id']
                    ]);
                    $_SESSION['success'] = 'Payment method updated!';
                    break;

                case 'update_gateway_settings':
                    $gateway = $_POST['gateway_name'];

                    // Check if exists
                    $stmt = $pdo->prepare("SELECT id FROM payment_gateway_settings WHERE gateway_name = ?");
                    $stmt->execute([$gateway]);

                    if ($stmt->rowCount() > 0) {
                        // Update
                        $stmt = $pdo->prepare("
                            UPDATE payment_gateway_settings
                            SET api_key = ?, api_secret = ?, merchant_id = ?, client_id = ?, client_secret = ?,
                                is_production = ?, is_active = ?
                            WHERE gateway_name = ?
                        ");
                        $stmt->execute([
                            $_POST['api_key'],
                            $_POST['api_secret'],
                            $_POST['merchant_id'],
                            $_POST['client_id'],
                            $_POST['client_secret'],
                            isset($_POST['is_production']) ? 1 : 0,
                            isset($_POST['is_active']) ? 1 : 0,
                            $gateway
                        ]);
                    } else {
                        // Insert
                        $stmt = $pdo->prepare("
                            INSERT INTO payment_gateway_settings
                            (gateway_name, api_key, api_secret, merchant_id, client_id, client_secret, is_production, is_active)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $gateway,
                            $_POST['api_key'],
                            $_POST['api_secret'],
                            $_POST['merchant_id'],
                            $_POST['client_id'],
                            $_POST['client_secret'],
                            isset($_POST['is_production']) ? 1 : 0,
                            isset($_POST['is_active']) ? 1 : 0
                        ]);
                    }

                    $_SESSION['success'] = ucfirst($gateway) . ' settings updated!';
                    break;

                case 'update_system_settings':
                    foreach ($_POST['settings'] as $key => $value) {
                        $stmt = $pdo->prepare("
                            INSERT INTO system_settings (setting_key, setting_value)
                            VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                        ");
                        $stmt->execute([$key, $value]);
                    }
                    $_SESSION['success'] = 'System settings updated!';
                    break;
            }
        }
        redirect('/admin/settings/payment-settings.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

// Get payment methods
$stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY display_order ASC");
$payment_methods = $stmt->fetchAll();

// Get gateway settings
$stmt = $pdo->query("SELECT * FROM payment_gateway_settings");
$gateways = [];
foreach ($stmt->fetchAll() as $row) {
    $gateways[$row['gateway_name']] = $row;
}

// Get system settings
$stmt = $pdo->query("SELECT * FROM system_settings");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = 'Payment Settings';
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .settings-section { background: var(--white); padding: 30px; border-radius: 12px; margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.1); }
    .settings-section h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--cream); }
    .settings-section p { color: var(--grey); font-size: 14px; margin-bottom: 24px; }

    .payment-methods-grid { display: grid; gap: 20px; }
    .payment-method-card { border: 2px solid rgba(0,0,0,0.1); padding: 24px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s; }
    .payment-method-card.active { border-color: #10B981; background: #ECFDF5; }
    .payment-method-card.inactive { opacity: 0.6; }

    .method-info { flex: 1; }
    .method-name { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
    .method-type { display: inline-block; padding: 4px 10px; background: var(--cream); border-radius: 4px; font-size: 12px; font-weight: 600; margin-top: 8px; }
    .method-status { font-size: 13px; margin-top: 8px; }
    .method-status.active { color: #10B981; font-weight: 600; }
    .method-status.inactive { color: #EF4444; font-weight: 600; }
    .method-actions { display: flex; gap: 12px; align-items: center; }

    .toggle-switch { position: relative; display: inline-block; width: 60px; height: 32px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 32px; }
    .slider:before { position: absolute; content: ""; height: 24px; width: 24px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #10B981; }
    input:checked + .slider:before { transform: translateX(28px); }

    .gateway-config { background: var(--cream); padding: 24px; border-radius: 8px; margin-top: 16px; }
    .gateway-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .gateway-header h3 { font-size: 18px; font-weight: 700; }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 1px solid rgba(0,0,0,0.15); border-radius: 6px; font-size: 14px; font-family: monospace; }
    .form-group input:focus { outline: none; border-color: var(--charcoal); }
    .form-group.full-width { grid-column: 1 / -1; }
    .checkbox-group { display: flex; align-items: center; gap: 8px; }
    .checkbox-group input { width: auto; }

    .btn { padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; text-decoration: none; display: inline-block; }
    .btn-primary { background: var(--charcoal); color: var(--white); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
    .btn-secondary { background: var(--cream); color: var(--charcoal); }
    .btn-success { background: #10B981; color: var(--white); }
    .btn-danger { background: #EF4444; color: var(--white); }

    .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #10B981; }
    .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #EF4444; }
    .alert-warning { background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; }

    .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
    .status-indicator.active { background: #10B981; }
    .status-indicator.inactive { background: #EF4444; }

    .info-box { background: #DBEAFE; border: 1px solid #3B82F6; padding: 16px; border-radius: 6px; margin-top: 12px; font-size: 13px; color: #1E40AF; }
</style>

<div class="admin-content">
    <h1>Payment Settings</h1>
    <p style="color: var(--grey); margin-top: 8px;">Configure payment methods and gateway integrations</p>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Payment Methods -->
    <div class="settings-section">
        <h2>Payment Methods</h2>
        <p>Enable or disable payment methods. Disabled methods will not be shown to customers.</p>

        <div class="payment-methods-grid">
            <?php foreach ($payment_methods as $method): ?>
                <div class="payment-method-card <?php echo $method['is_active'] ? 'active' : 'inactive'; ?>">
                    <div class="method-info">
                        <div class="method-name">
                            <span class="status-indicator <?php echo $method['is_active'] ? 'active' : 'inactive'; ?>"></span>
                            <?php echo htmlspecialchars($method['name']); ?>
                        </div>
                        <span class="method-type"><?php echo strtoupper($method['type']); ?></span>
                        <div class="method-status <?php echo $method['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $method['is_active'] ? '✓ Active - Visible to customers' : '✗ Inactive - Hidden from customers'; ?>
                        </div>
                    </div>
                    <div class="method-actions">
                        <form method="POST" onsubmit="return confirm('Toggle this payment method?');">
                            <input type="hidden" name="action" value="update_payment_method">
                            <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_active" <?php echo $method['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="info-box" style="margin-top: 24px;">
            <strong>ℹ️ Note:</strong> Bank Transfer is always available for manual verification.
            Other payment methods require API configuration below.
        </div>
    </div>

    <!-- Midtrans Settings -->
    <div class="settings-section">
        <h2>Midtrans Configuration</h2>
        <p>Configure Midtrans API for QRIS and payment gateway integration</p>

        <div class="gateway-config">
            <form method="POST">
                <input type="hidden" name="action" value="update_gateway_settings">
                <input type="hidden" name="gateway_name" value="midtrans">

                <div class="gateway-header">
                    <h3>Midtrans API Keys</h3>
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_production" id="midtrans_prod" <?php echo ($gateways['midtrans']['is_production'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="midtrans_prod">Production Mode</label>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Server Key *</label>
                        <input type="text" name="api_key" value="<?php echo htmlspecialchars($gateways['midtrans']['api_key'] ?? ''); ?>" placeholder="SB-Mid-server-..." required>
                    </div>

                    <div class="form-group">
                        <label>Client Key *</label>
                        <input type="text" name="api_secret" value="<?php echo htmlspecialchars($gateways['midtrans']['api_secret'] ?? ''); ?>" placeholder="SB-Mid-client-..." required>
                    </div>

                    <div class="form-group">
                        <label>Merchant ID</label>
                        <input type="text" name="merchant_id" value="<?php echo htmlspecialchars($gateways['midtrans']['merchant_id'] ?? ''); ?>" placeholder="M123456">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" id="midtrans_active" <?php echo ($gateways['midtrans']['is_active'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="midtrans_active">Enable Midtrans</label>
                        </div>
                    </div>
                </div>

                <div class="info-box">
                    <strong>📝 How to get Midtrans keys:</strong><br>
                    1. Sign up at <a href="https://dashboard.midtrans.com" target="_blank">dashboard.midtrans.com</a><br>
                    2. Go to Settings → Access Keys<br>
                    3. Copy Server Key and Client Key<br>
                    4. For testing: Use Sandbox keys (starts with SB-)<br>
                    5. For production: Enable Production Mode above
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                    Save Midtrans Settings
                </button>
            </form>
        </div>
    </div>

    <!-- PayPal Settings -->
    <div class="settings-section">
        <h2>PayPal Configuration</h2>
        <p>Configure PayPal for international payments</p>

        <div class="gateway-config">
            <form method="POST">
                <input type="hidden" name="action" value="update_gateway_settings">
                <input type="hidden" name="gateway_name" value="paypal">

                <div class="gateway-header">
                    <h3>PayPal API Credentials</h3>
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_production" id="paypal_prod" <?php echo ($gateways['paypal']['is_production'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="paypal_prod">Production Mode</label>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Client ID *</label>
                        <input type="text" name="client_id" value="<?php echo htmlspecialchars($gateways['paypal']['client_id'] ?? ''); ?>" placeholder="AY..." required>
                    </div>

                    <div class="form-group">
                        <label>Client Secret *</label>
                        <input type="text" name="client_secret" value="<?php echo htmlspecialchars($gateways['paypal']['client_secret'] ?? ''); ?>" placeholder="EL..." required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" id="paypal_active" <?php echo ($gateways['paypal']['is_active'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="paypal_active">Enable PayPal</label>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="api_key" value="">
                <input type="hidden" name="api_secret" value="">
                <input type="hidden" name="merchant_id" value="">

                <div class="info-box">
                    <strong>📝 How to get PayPal credentials:</strong><br>
                    1. Sign up at <a href="https://developer.paypal.com" target="_blank">developer.paypal.com</a><br>
                    2. Create a REST API app<br>
                    3. Copy Client ID and Secret<br>
                    4. For testing: Use Sandbox credentials<br>
                    5. For production: Enable Production Mode above
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                    Save PayPal Settings
                </button>
            </form>
        </div>
    </div>

    <!-- System Settings -->
    <div class="settings-section">
        <h2>General Settings</h2>
        <p>Configure WhatsApp, minimum amounts, and other system settings</p>

        <form method="POST">
            <input type="hidden" name="action" value="update_system_settings">

            <div class="form-grid">
                <div class="form-group">
                    <label>WhatsApp Admin Number *</label>
                    <input type="text" name="settings[whatsapp_admin]" value="<?php echo htmlspecialchars($settings['whatsapp_admin'] ?? '628123456789'); ?>" placeholder="628123456789" required>
                    <small style="color: var(--grey);">Format: Country code + number (no + or spaces)</small>
                </div>

                <div class="form-group">
                    <label>Minimum Topup Amount (IDR) *</label>
                    <input type="number" name="settings[min_topup_amount]" value="<?php echo htmlspecialchars($settings['min_topup_amount'] ?? '10000'); ?>" min="1000" required>
                </div>

                <div class="form-group">
                    <label>Unique Code Min *</label>
                    <input type="number" name="settings[unique_code_min]" value="<?php echo htmlspecialchars($settings['unique_code_min'] ?? '100'); ?>" min="100" max="999" required>
                </div>

                <div class="form-group">
                    <label>Unique Code Max *</label>
                    <input type="number" name="settings[unique_code_max]" value="<?php echo htmlspecialchars($settings['unique_code_max'] ?? '999'); ?>" min="100" max="999" required>
                </div>

                <div class="form-group full-width">
                    <label>WhatsApp Message Template</label>
                    <textarea name="settings[whatsapp_message]" rows="3"><?php echo htmlspecialchars($settings['whatsapp_message'] ?? 'Halo Admin, saya sudah melakukan transfer untuk topup wallet. Mohon di cek ya!'); ?></textarea>
                    <small style="color: var(--grey);">Template message for WhatsApp redirect. Use {reference}, {amount} for dynamic values.</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Save System Settings
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
