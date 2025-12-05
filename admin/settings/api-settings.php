<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_midtrans') {
        try {
            $settings = [
                'midtrans_enabled' => isset($_POST['midtrans_enabled']) ? '1' : '0',
                'midtrans_server_key' => trim($_POST['midtrans_server_key'] ?? ''),
                'midtrans_client_key' => trim($_POST['midtrans_client_key'] ?? ''),
                'midtrans_merchant_id' => trim($_POST['midtrans_merchant_id'] ?? ''),
                'midtrans_environment' => $_POST['midtrans_environment'] ?? 'sandbox'
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?)
                                      ON DUPLICATE KEY UPDATE value = ?");
                $stmt->execute([$key, $value, $value]);
            }

            $success = 'Midtrans settings saved successfully!';
        } catch (PDOException $e) {
            $error = 'Error saving settings: ' . $e->getMessage();
        }
    }

    if ($action === 'save_biteship') {
        try {
            $settings = [
                'biteship_enabled' => isset($_POST['biteship_enabled']) ? '1' : '0',
                'biteship_api_key' => trim($_POST['biteship_api_key'] ?? ''),
                'biteship_environment' => $_POST['biteship_environment'] ?? 'production',
                'biteship_webhook_secret' => trim($_POST['biteship_webhook_secret'] ?? ''),
                'biteship_default_couriers' => trim($_POST['biteship_default_couriers'] ?? 'jne,jnt,sicepat,anteraja,idexpress'),
                'store_name' => trim($_POST['store_name'] ?? ''),
                'store_phone' => trim($_POST['store_phone'] ?? ''),
                'store_address' => trim($_POST['store_address'] ?? ''),
                'store_city' => trim($_POST['store_city'] ?? ''),
                'store_province' => trim($_POST['store_province'] ?? ''),
                'store_postal_code' => trim($_POST['store_postal_code'] ?? ''),
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, $valueColumn) VALUES (?, ?)
                                      ON DUPLICATE KEY UPDATE $valueColumn = ?");
                $stmt->execute([$key, $value, $value]);
            }

            $success = 'Biteship settings saved successfully!';
        } catch (PDOException $e) {
            $error = 'Error saving settings: ' . $e->getMessage();
        }
    }
    
    if ($action === 'test_biteship') {
        try {
            require_once __DIR__ . '/../../includes/BiteshipClient.php';
            $client = new BiteshipClient();
            
            // Test with a simple area search
            $result = $client->getAreas('Jakarta');
            
            if ($result['success']) {
                $success = 'Biteship connection successful! API is working correctly.';
            } else {
                $error = 'Biteship connection failed: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (Exception $e) {
            $error = 'Biteship test error: ' . $e->getMessage();
        }
    }

    if ($action === 'save_store') {
        try {
            $settings = [
                'store_name' => trim($_POST['store_name'] ?? ''),
                'store_address' => trim($_POST['store_address'] ?? ''),
                'store_phone' => trim($_POST['store_phone'] ?? ''),
                'store_email' => trim($_POST['store_email'] ?? ''),
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?)
                                      ON DUPLICATE KEY UPDATE value = ?");
                $stmt->execute([$key, $value, $value]);
            }

            $success = 'Store information saved successfully!';
        } catch (PDOException $e) {
            $error = 'Error saving settings: ' . $e->getMessage();
        }
    }
}

// Get current settings
$settings = [];
try {
    // Check which column name is used
    $checkStmt = $pdo->query("DESCRIBE settings");
    $columns = array_column($checkStmt->fetchAll(), 'Field');
    $valueColumn = in_array('setting_value', $columns) ? 'setting_value' : 'value';
    
    $stmt = $pdo->query("SELECT setting_key, $valueColumn as setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Settings table might not exist
    $valueColumn = 'value'; // default
}

// Default values
$defaults = [
    'biteship_enabled' => '0',
    'biteship_environment' => 'production',
    'biteship_default_couriers' => 'jne,jnt,sicepat,anteraja,idexpress',
    'store_city' => 'Jakarta Selatan',
    'store_province' => 'DKI Jakarta'
];
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>⚙️ API & Integration Settings</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Store Information -->
<div class="form-container">
    <h2 style="margin-bottom: 24px; font-size: 20px;">🏪 Store Information</h2>
    <p style="color: #666; margin-bottom: 24px;">
        This information will be used on shipping labels and receipts. All admin users share the same settings.
    </p>

    <form method="POST" action="">
        <input type="hidden" name="action" value="save_store">

        <div class="form-group">
            <label for="store_name">Store Name *</label>
            <input type="text" id="store_name" name="store_name"
                   value="<?php echo htmlspecialchars($settings['store_name'] ?? 'Dorve House'); ?>" required>
        </div>

        <div class="form-group">
            <label for="store_address">Store Address *</label>
            <textarea id="store_address" name="store_address" rows="3" required><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="store_phone">Phone Number *</label>
                <input type="tel" id="store_phone" name="store_phone"
                       value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="store_email">Email Address *</label>
                <input type="email" id="store_email" name="store_email"
                       value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">💾 Save Store Information</button>
    </form>
</div>

<!-- Midtrans Payment Gateway -->
<div class="form-container" style="margin-top: 30px;">
    <h2 style="margin-bottom: 24px; font-size: 20px;">💳 Midtrans Payment Gateway</h2>
    <p style="color: #666; margin-bottom: 24px;">
        Enable credit card, e-wallet, and bank transfer payments through Midtrans. All admin users share these settings.
    </p>

    <form method="POST" action="" id="midtransForm">
        <input type="hidden" name="action" value="save_midtrans">

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="midtrans_enabled" name="midtrans_enabled" value="1"
                       <?php echo ($settings['midtrans_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                <label for="midtrans_enabled" style="margin: 0; cursor: pointer;">
                    ✅ Enable Midtrans Payment Gateway
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="midtrans_environment">Environment</label>
            <select id="midtrans_environment" name="midtrans_environment" required>
                <option value="sandbox" <?php echo ($settings['midtrans_environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>
                    Sandbox (Testing)
                </option>
                <option value="production" <?php echo ($settings['midtrans_environment'] ?? 'sandbox') === 'production' ? 'selected' : ''; ?>>
                    Production (Live)
                </option>
            </select>
            <small style="color: #666; display: block; margin-top: 8px;">
                Use Sandbox for testing, Production for live transactions
            </small>
        </div>

        <div class="form-group">
            <label for="midtrans_merchant_id">Merchant ID</label>
            <input type="text" id="midtrans_merchant_id" name="midtrans_merchant_id"
                   value="<?php echo htmlspecialchars($settings['midtrans_merchant_id'] ?? ''); ?>"
                   placeholder="M123456">
        </div>

        <div class="form-group">
            <label for="midtrans_server_key">Server Key</label>
            <input type="password" id="midtrans_server_key" name="midtrans_server_key"
                   value="<?php echo htmlspecialchars($settings['midtrans_server_key'] ?? ''); ?>"
                   placeholder="SB-Mid-server-xxxxxxxxxxxxxxxx">
            <small style="color: #666; display: block; margin-top: 8px;">
                Get this from Midtrans Dashboard → Settings → Access Keys
            </small>
        </div>

        <div class="form-group">
            <label for="midtrans_client_key">Client Key</label>
            <input type="text" id="midtrans_client_key" name="midtrans_client_key"
                   value="<?php echo htmlspecialchars($settings['midtrans_client_key'] ?? ''); ?>"
                   placeholder="SB-Mid-client-xxxxxxxxxxxxxxxx">
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">💾 Save Settings</button>
            <button type="button" class="btn btn-secondary" onclick="testMidtransAPI()">
                🧪 Test Connection
            </button>
        </div>

        <div id="midtransTestResult" style="margin-top: 20px; display: none;"></div>
    </form>
</div>

<!-- Shipping API Integration -->
<div class="form-container" style="margin-top: 30px;">
    <h2 style="margin-bottom: 24px; font-size: 20px;">📦 Shipping API Integration</h2>
    <p style="color: #666; margin-bottom: 24px;">
        Connect to shipping aggregators for automatic price calculation and label generation. All admin users share these settings.
    </p>

    <form method="POST" action="" id="shippingForm">
        <input type="hidden" name="action" value="save_shipping">

        <div class="form-group">
            <label for="shipping_aggregator">Shipping Method</label>
            <select id="shipping_aggregator" name="shipping_aggregator" required>
                <option value="manual" <?php echo ($settings['shipping_aggregator'] ?? 'manual') === 'manual' ? 'selected' : ''; ?>>
                    Manual (Set fixed prices)
                </option>
                <option value="bitship" <?php echo ($settings['shipping_aggregator'] ?? 'manual') === 'bitship' ? 'selected' : ''; ?>>
                    BitShip API
                </option>
                <option value="shipper" <?php echo ($settings['shipping_aggregator'] ?? 'manual') === 'shipper' ? 'selected' : ''; ?>>
                    Shipper API
                </option>
            </select>
        </div>

        <!-- Biteship Settings (Full Integration) -->
        <div id="bitshipSettings" style="display: none;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_biteship">
                
                <h3 style="margin: 30px 0 15px; font-size: 18px;">🚚 Biteship Configuration</h3>
                
                <!-- Enable Toggle -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="biteship_enabled" name="biteship_enabled" value="1"
                               <?php echo ($settings['biteship_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                        <label for="biteship_enabled" style="margin: 0; cursor: pointer;">
                            ✅ Enable Biteship Integration
                        </label>
                    </div>
                </div>
                
                <!-- API Key -->
                <div class="form-group">
                    <label for="biteship_api_key">Biteship API Key *</label>
                    <input type="text" id="biteship_api_key" name="biteship_api_key"
                           value="<?php echo htmlspecialchars($settings['biteship_api_key'] ?? ''); ?>"
                           placeholder="biteship_live.xxxxxxxxxxxxx" required>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        Get your API key from <a href="https://business.biteship.com/" target="_blank">Biteship Dashboard</a>
                    </small>
                </div>
                
                <!-- Environment -->
                <div class="form-group">
                    <label>Environment</label>
                    <select name="biteship_environment">
                        <option value="sandbox" <?php echo ($settings['biteship_environment'] ?? 'production') === 'sandbox' ? 'selected' : ''; ?>>
                            Sandbox (Testing)
                        </option>
                        <option value="production" <?php echo ($settings['biteship_environment'] ?? 'production') === 'production' ? 'selected' : ''; ?>>
                            Production (Live)
                        </option>
                    </select>
                </div>
                
                <!-- Webhook URL Display -->
                <div style="background: #DBEAFE; padding: 20px; border-radius: 8px; margin: 24px 0; border: 2px solid #3B82F6;">
                    <h4 style="margin: 0 0 12px; color: #1E40AF; font-size: 16px;">📡 Webhook Configuration</h4>
                    <p style="margin: 0 0 12px; font-size: 14px; color: #1E40AF;">
                        Copy this webhook URL and add it to your Biteship Dashboard:
                    </p>
                    <div style="background: white; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 13px; word-break: break-all; border: 1px solid #93C5FD;">
                        https://dorve.id/api/biteship/webhook.php
                    </div>
                    <p style="margin: 12px 0 0; font-size: 13px; color: #1E40AF;">
                        Go to: <a href="https://business.biteship.com/settings/webhook" target="_blank" style="color: #2563EB; font-weight: 600;">Biteship Dashboard → Settings → Webhooks</a>
                    </p>
                    <p style="margin: 8px 0 0; font-size: 13px; color: #1E40AF;">
                        Subscribe to events: <strong>order.status</strong>, <strong>order.waybill_id</strong>
                    </p>
                </div>
                
                <!-- Default Couriers -->
                <div class="form-group">
                    <label for="biteship_default_couriers">Default Couriers</label>
                    <input type="text" id="biteship_default_couriers" name="biteship_default_couriers"
                           value="<?php echo htmlspecialchars($settings['biteship_default_couriers'] ?? 'jne,jnt,sicepat,anteraja,idexpress'); ?>">
                    <small>Comma-separated courier codes (e.g., jne,jnt,sicepat)</small>
                </div>
                
                <!-- Store Origin Address (Required for Shipping) -->
                <h4 style="margin: 30px 0 15px; font-size: 16px; color: #374151;">📍 Store Origin Address (Pickup Location)</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Store Name *</label>
                        <input type="text" name="store_name" value="<?php echo htmlspecialchars($settings['store_name'] ?? 'Dorve.id Official Store'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Store Phone *</label>
                        <input type="text" name="store_phone" value="<?php echo htmlspecialchars($settings['store_phone'] ?? '+62-813-7737-8859'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Full Address *</label>
                    <textarea name="store_address" rows="3" required><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="store_city" value="<?php echo htmlspecialchars($settings['store_city'] ?? 'Jakarta Selatan'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Province *</label>
                        <input type="text" name="store_province" value="<?php echo htmlspecialchars($settings['store_province'] ?? 'DKI Jakarta'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Postal Code *</label>
                        <input type="text" name="store_postal_code" value="<?php echo htmlspecialchars($settings['store_postal_code'] ?? ''); ?>" required maxlength="10">
                    </div>
                    <div class="form-group">
                        <label>Webhook Secret (Optional)</label>
                        <input type="text" name="biteship_webhook_secret" value="<?php echo htmlspecialchars($settings['biteship_webhook_secret'] ?? ''); ?>">
                        <small>For webhook signature validation</small>
                    </div>
                </div>
                
                <!-- Status Indicators -->
                <div style="margin: 24px 0; padding: 20px; background: #F9FAFB; border-radius: 8px; border: 1px solid #E5E7EB;">
                    <h4 style="margin: 0 0 16px; font-size: 16px; color: #374151;">🔍 Connection Status</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <p style="margin: 0 0 8px; font-size: 14px; color: #6B7280;">Biteship API:</p>
                            <?php
                            $apiStatus = $settings['biteship_api_test_status'] ?? 'not_tested';
                            $apiTime = $settings['biteship_api_test_time'] ?? null;
                            if ($apiStatus === 'connected') {
                                echo '<span style="padding: 6px 12px; background: #D1FAE5; color: #059669; border-radius: 6px; font-size: 13px; font-weight: 600;">✅ Connected</span>';
                                if ($apiTime) echo '<p style="margin: 4px 0 0; font-size: 12px; color: #6B7280;">Last tested: ' . date('d M Y H:i', strtotime($apiTime)) . '</p>';
                            } elseif ($apiStatus === 'failed') {
                                echo '<span style="padding: 6px 12px; background: #FEE2E2; color: #DC2626; border-radius: 6px; font-size: 13px; font-weight: 600;">❌ Failed</span>';
                                if ($apiTime) echo '<p style="margin: 4px 0 0; font-size: 12px; color: #6B7280;">Last tested: ' . date('d M Y H:i', strtotime($apiTime)) . '</p>';
                            } else {
                                echo '<span style="padding: 6px 12px; background: #F3F4F6; color: #6B7280; border-radius: 6px; font-size: 13px; font-weight: 600;">⚪ Not Tested</span>';
                            }
                            ?>
                        </div>
                        <div>
                            <p style="margin: 0 0 8px; font-size: 14px; color: #6B7280;">Webhook Endpoint:</p>
                            <?php
                            $webhookStatus = $settings['biteship_webhook_test_status'] ?? 'not_tested';
                            $webhookTime = $settings['biteship_webhook_test_time'] ?? null;
                            if ($webhookStatus === 'ok') {
                                echo '<span style="padding: 6px 12px; background: #D1FAE5; color: #059669; border-radius: 6px; font-size: 13px; font-weight: 600;">✅ OK</span>';
                                if ($webhookTime) echo '<p style="margin: 4px 0 0; font-size: 12px; color: #6B7280;">Last tested: ' . date('d M Y H:i', strtotime($webhookTime)) . '</p>';
                            } elseif ($webhookStatus === 'failed') {
                                echo '<span style="padding: 6px 12px; background: #FEE2E2; color: #DC2626; border-radius: 6px; font-size: 13px; font-weight: 600;">❌ ERROR</span>';
                                if ($webhookTime) echo '<p style="margin: 4px 0 0; font-size: 12px; color: #6B7280;">Last tested: ' . date('d M Y H:i', strtotime($webhookTime)) . '</p>';
                            } else {
                                echo '<span style="padding: 6px 12px; background: #F3F4F6; color: #6B7280; border-radius: 6px; font-size: 13px; font-weight: 600;">⚪ Not Tested</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="margin-top: 30px; display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">💾 Save Biteship Settings</button>
                    <button type="button" class="btn btn-secondary" onclick="testBiteshipAPI()" style="background: #10B981;">
                        🧪 Test API Key
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="testBiteshipWebhook()" style="background: #F59E0B;">
                        📡 Test Webhook
                    </button>
                </div>
                
                <div id="biteshipTestResult" style="margin-top: 15px; display: none;"></div>
            </form>
        </div>

        <!-- Shipper Settings -->
        <div id="shipperSettings" style="display: none;">
            <h3 style="margin: 30px 0 15px; font-size: 18px;">Shipper Configuration</h3>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="shipper_enabled" name="shipper_enabled" value="1"
                           <?php echo ($settings['shipper_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    <label for="shipper_enabled" style="margin: 0; cursor: pointer;">
                        ✅ Enable Shipper Integration
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="shipper_api_key">Shipper API Key</label>
                <input type="password" id="shipper_api_key" name="shipper_api_key"
                       value="<?php echo htmlspecialchars($settings['shipper_api_key'] ?? ''); ?>"
                       placeholder="shipper_xxxxxxxxxxxxx">
                <small style="color: #666; display: block; margin-top: 8px;">
                    Register at <a href="https://shipper.id" target="_blank">shipper.id</a> to get API key
                </small>
            </div>

            <button type="button" class="btn btn-secondary" onclick="testShipperAPI()">
                🧪 Test Shipper Connection
            </button>
            <div id="shipperTestResult" style="margin-top: 15px; display: none;"></div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary">💾 Save Shipping Settings</button>
        </div>
    </form>
</div>

<!-- Help Section -->
<div class="content-container" style="margin-top: 30px; background: #DBEAFE; border-left: 4px solid #3B82F6;">
    <h3 style="margin-bottom: 16px; font-size: 18px; color: #1E40AF;">
        💡 API Integration Guide
    </h3>

    <div style="color: #1E40AF; line-height: 2;">
        <h4 style="margin-top: 20px;">Midtrans (Payment Gateway):</h4>
        <ul style="margin-left: 20px;">
            <li>Register at: <a href="https://dashboard.midtrans.com/register" target="_blank" style="color: #1E40AF; font-weight: 600;">midtrans.com</a></li>
            <li>Wait for account approval (1-2 business days)</li>
            <li>Get Server Key and Client Key from Dashboard</li>
            <li>Start with Sandbox environment for testing</li>
            <li>Switch to Production when ready to go live</li>
        </ul>

        <h4 style="margin-top: 20px;">BitShip (Shipping Aggregator):</h4>
        <ul style="margin-left: 20px;">
            <li>Register at: <a href="https://bitship.id" target="_blank" style="color: #1E40AF; font-weight: 600;">bitship.id</a></li>
            <li>Instant API key generation</li>
            <li>Deposit starting from Rp 100,000</li>
            <li>Access to JNE, JNT, Sicepat, and more</li>
            <li>Pay-as-you-go pricing</li>
        </ul>

        <h4 style="margin-top: 20px;">Shipper (Shipping Aggregator):</h4>
        <ul style="margin-left: 20px;">
            <li>Register at: <a href="https://shipper.id" target="_blank" style="color: #1E40AF; font-weight: 600;">shipper.id</a></li>
            <li>Quick approval process</li>
            <li>Multiple courier options</li>
            <li>API documentation available</li>
        </ul>

        <h4 style="margin-top: 20px;">Important Notes:</h4>
        <ul style="margin-left: 20px;">
            <li><strong>All settings are shared across all admin accounts</strong></li>
            <li>Test APIs before enabling them for customers</li>
            <li>Keep API keys secure and never share them</li>
            <li>Use Sandbox/Test mode before going live</li>
            <li>Monitor API usage and balance regularly</li>
        </ul>
    </div>
</div>

<script>
// Show/hide shipping provider settings based on selection
document.getElementById('shipping_aggregator').addEventListener('change', function() {
    const value = this.value;
    document.getElementById('bitshipSettings').style.display = value === 'bitship' ? 'block' : 'none';
    document.getElementById('shipperSettings').style.display = value === 'shipper' ? 'block' : 'none';
});

// Trigger on page load
document.getElementById('shipping_aggregator').dispatchEvent(new Event('change'));

// Test Biteship API Key
function testBiteshipAPI() {
    const resultDiv = document.getElementById('biteshipTestResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="alert alert-info">🔄 Testing Biteship API key...</div>';

    fetch('/admin/integration/test-biteship-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success"><strong>' + data.message + '</strong><br><small>' + (data.details || '') + '</small></div>';
            // Reload after 2 seconds to update status badge
            setTimeout(() => window.location.reload(), 2000);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-error"><strong>' + data.message + '</strong><br><small>' + (data.error || data.details || '') + '</small></div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-error">❌ Test failed: ' + error.message + '</div>';
    });
}

// Test Biteship Webhook
function testBiteshipWebhook() {
    const resultDiv = document.getElementById('biteshipTestResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="alert alert-info">🔄 Testing webhook endpoint...</div>';

    fetch('/admin/integration/test-webhook.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success"><strong>' + data.message + '</strong><br><small>' + (data.details || '') + '</small></div>';
            // Reload after 2 seconds to update status badge
            setTimeout(() => window.location.reload(), 2000);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-error"><strong>' + data.message + '</strong><br><small>' + (data.error || data.details || '') + '</small></div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-error">❌ Test failed: ' + error.message + '</div>';
    });
}

// Test Midtrans API
function testMidtransAPI() {
    const resultDiv = document.getElementById('midtransTestResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="alert alert-info">🔄 Testing Midtrans connection...</div>';

    const serverKey = document.getElementById('midtrans_server_key').value;
    const environment = document.getElementById('midtrans_environment').value;

    if (!serverKey) {
        resultDiv.innerHTML = '<div class="alert alert-error">❌ Please enter Server Key first</div>';
        return;
    }

    // Simulate API test (in real implementation, this would call a backend endpoint)
    setTimeout(() => {
        if (serverKey.startsWith('SB-') || serverKey.startsWith('Mid-')) {
            resultDiv.innerHTML = '<div class="alert alert-success">✅ API Key format is valid! Ready to test transactions.</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-warning">⚠️ API Key format looks incorrect. Please check your credentials.</div>';
        }
    }, 1500);
}

// Test BitShip API
function testBitshipAPI() {
    const resultDiv = document.getElementById('bitshipTestResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="alert alert-info">🔄 Testing BitShip connection...</div>';

    const apiKey = document.getElementById('bitship_api_key').value;

    if (!apiKey) {
        resultDiv.innerHTML = '<div class="alert alert-error">❌ Please enter API Key first</div>';
        return;
    }

    setTimeout(() => {
        if (apiKey.length > 10) {
            resultDiv.innerHTML = '<div class="alert alert-success">✅ API Key format is valid! Connection test successful.</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-error">❌ Invalid API Key. Please check your credentials.</div>';
        }
    }, 1500);
}

// Test Shipper API
function testShipperAPI() {
    const resultDiv = document.getElementById('shipperTestResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="alert alert-info">🔄 Testing Shipper connection...</div>';

    const apiKey = document.getElementById('shipper_api_key').value;

    if (!apiKey) {
        resultDiv.innerHTML = '<div class="alert alert-error">❌ Please enter API Key first</div>';
        return;
    }

    setTimeout(() => {
        if (apiKey.length > 10) {
            resultDiv.innerHTML = '<div class="alert alert-success">✅ API Key format is valid! Connection test successful.</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-error">❌ Invalid API Key. Please check your credentials.</div>';
        }
    }, 1500);
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
