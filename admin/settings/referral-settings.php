<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                $stmt = $pdo->prepare("INSERT INTO referral_settings (setting_key, setting_value) 
                                      VALUES (?, ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
        }
        $_SESSION['success'] = 'Referral settings updated successfully!';
        redirect('/admin/settings/referral-settings.php');
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

// Get current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM referral_settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
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
    'max_rewards_per_referrer' => '0',
    'require_transaction' => '1',
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

$page_title = 'Referral System Settings - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
.settings-container { max-width: 900px; }
.setting-section { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #E5E7EB; }
.setting-section h3 { margin: 0 0 20px 0; color: #111827; font-size: 18px; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 14px; }
.form-group small { display: block; margin-top: 5px; color: #6B7280; font-size: 13px; }
.radio-group { display: flex; gap: 20px; }
.radio-option { display: flex; align-items: center; gap: 8px; }
.radio-option input { width: auto; }
.btn-primary { background: #3B82F6; color: white; padding: 12px 30px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
.info-box { background: #DBEAFE; border-left: 4px solid #3B82F6; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
.alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
.alert-success { background: #D1FAE5; color: #065F46; }
</style>

<div class="header">
    <h1>⚙️ Referral System Settings</h1>
</div>

<div class="content-container settings-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="info-box">
        <strong>📖 Cara Kerja:</strong> User A dapat code → User B register dengan code → User B topup (min sesuai setting) → User A dapat reward otomatis
    </div>

    <form method="POST">
        <div class="setting-section">
            <h3>🎯 Basic Settings</h3>
            <div class="form-group">
                <label>Status</label>
                <select name="referral_enabled">
                    <option value="1" <?php echo $settings['referral_enabled'] == '1' ? 'selected' : ''; ?>>✅ Enabled</option>
                    <option value="0" <?php echo $settings['referral_enabled'] == '0' ? 'selected' : ''; ?>>❌ Disabled</option>
                </select>
            </div>
            <div class="form-group">
                <label>Min Topup (Rp)</label>
                <input type="number" name="min_topup_for_reward" value="<?php echo $settings['min_topup_for_reward']; ?>">
                <small>Minimum topup untuk trigger reward</small>
            </div>
            <div class="form-group">
                <label>Require Transaction</label>
                <select name="require_transaction">
                    <option value="1" <?php echo $settings['require_transaction'] == '1' ? 'selected' : ''; ?>>Ya - Harus topup dulu</option>
                    <option value="0" <?php echo $settings['require_transaction'] == '0' ? 'selected' : ''; ?>>Tidak - Langsung dapat</option>
                </select>
            </div>
        </div>

        <div class="setting-section">
            <h3>💰 Commission</h3>
            <div class="form-group">
                <label>Type</label>
                <select name="commission_type">
                    <option value="percentage" <?php echo $settings['commission_type'] == 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                    <option value="fixed" <?php echo $settings['commission_type'] == 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                </select>
            </div>
            <div class="form-group">
                <label>Percentage (%)</label>
                <input type="number" name="commission_percent" value="<?php echo $settings['commission_percent']; ?>" step="0.01">
                <small>3-5% recommended</small>
            </div>
            <div class="form-group">
                <label>Fixed (Rp)</label>
                <input type="number" name="commission_fixed" value="<?php echo $settings['commission_fixed']; ?>">
            </div>
        </div>

        <div class="setting-section">
            <h3>🎁 Reward Type</h3>
            <div class="form-group">
                <label>Type</label>
                <select name="reward_type">
                    <option value="wallet" <?php echo $settings['reward_type'] == 'wallet' ? 'selected' : ''; ?>>Wallet</option>
                    <option value="voucher" <?php echo $settings['reward_type'] == 'voucher' ? 'selected' : ''; ?>>Voucher</option>
                    <option value="both" <?php echo $settings['reward_type'] == 'both' ? 'selected' : ''; ?>>Both</option>
                </select>
            </div>
            <div class="form-group">
                <label>Voucher Type</label>
                <select name="voucher_type">
                    <option value="percentage" <?php echo $settings['voucher_type'] == 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                    <option value="fixed" <?php echo $settings['voucher_type'] == 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                    <option value="free_shipping" <?php echo $settings['voucher_type'] == 'free_shipping' ? 'selected' : ''; ?>>Free Shipping</option>
                </select>
            </div>
            <div class="form-group">
                <label>Voucher Value</label>
                <input type="number" name="voucher_value" value="<?php echo $settings['voucher_value']; ?>">
            </div>
            <div class="form-group">
                <label>Min Purchase (Rp)</label>
                <input type="number" name="voucher_min_purchase" value="<?php echo $settings['voucher_min_purchase']; ?>">
            </div>
            <div class="form-group">
                <label>Validity (Days)</label>
                <input type="number" name="voucher_validity_days" value="<?php echo $settings['voucher_validity_days']; ?>">
            </div>
        </div>

        <button type="submit" name="submit" class="btn-primary">💾 Save Settings</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>