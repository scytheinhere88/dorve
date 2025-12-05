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
    try {
        $settings = [
            'referral_enabled' => isset($_POST['referral_enabled']) ? '1' : '0',
            'commission_percent' => floatval($_POST['commission_percent'] ?? 5),
            'min_topup_for_reward' => intval($_POST['min_topup_for_reward'] ?? 100000),
            'email_verification_required' => isset($_POST['email_verification_required']) ? '1' : '0',
        ];

        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO referral_settings (setting_key, setting_value) VALUES (?, ?)
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        $success = 'Referral settings saved successfully!';
    } catch (PDOException $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
    }
}

// Load current settings
$settings = [
    'referral_enabled' => '1',
    'commission_percent' => '5.00',
    'min_topup_for_reward' => '100000',
    'email_verification_required' => '1',
];

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM referral_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get statistics
$stats = [
    'total_referrals' => 0,
    'total_commission_paid' => 0,
    'pending_commission' => 0,
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM referral_rewards");
    $stats['total_referrals'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT SUM(reward_value) as total FROM referral_rewards WHERE status = 'completed'");
    $stats['total_commission_paid'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT SUM(reward_value) as total FROM referral_rewards WHERE status = 'pending'");
    $stats['pending_commission'] = $stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    // Tables might not exist yet
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Settings - Admin Dorve</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 32px; font-weight: 600; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-label { font-size: 14px; color: #6c757d; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1A1A1A; }
        .form-container { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 800px; }
        .form-group { margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #1A1A1A; }
        .help-text { font-size: 13px; color: #6c757d; margin-top: 4px; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 12px 16px; border: 1px solid #E8E8E8; border-radius: 6px; font-size: 15px; font-family: 'Inter', sans-serif; }
        input:focus, select:focus { outline: none; border-color: #1A1A1A; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .btn { padding: 12px 24px; border-radius: 6px; font-size: 15px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; border: none; }
        .btn-primary { background: #1A1A1A; color: white; }
        .btn-primary:hover { background: #000000; }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE</div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="nav-item">Dashboard</a>
                <a href="/admin/products/index.php" class="nav-item">Produk</a>
                <a href="/admin/categories/index.php" class="nav-item">Kategori</a>
                <a href="/admin/orders/index.php" class="nav-item">Pesanan</a>
                <a href="/admin/users/index.php" class="nav-item">Pengguna</a>
                <a href="/admin/vouchers/index.php" class="nav-item">Voucher</a>
                <a href="/admin/referrals/index.php" class="nav-item">Referrals</a>
                <a href="/admin/settings/index.php" class="nav-item">Pengaturan</a>
                <a href="/admin/settings/referral-settings.php" class="nav-item active">🎁 Referral Settings</a>
                <a href="/auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="header">
                <h1>🎁 Referral System Settings</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Referrals</div>
                    <div class="stat-value"><?php echo number_format($stats['total_referrals']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Commission Paid</div>
                    <div class="stat-value">Rp <?php echo number_format($stats['total_commission_paid'], 0, ',', '.'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Commission</div>
                    <div class="stat-value">Rp <?php echo number_format($stats['pending_commission'], 0, ',', '.'); ?></div>
                </div>
            </div>

            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="referral_enabled" value="1" <?php echo $settings['referral_enabled'] == '1' ? 'checked' : ''; ?>>
                            <span>Enable Referral System</span>
                        </label>
                        <div class="help-text">Turn on/off the entire referral system</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="commission_percent">Commission Percentage (%)</label>
                            <input type="number" id="commission_percent" name="commission_percent" 
                                   value="<?php echo htmlspecialchars($settings['commission_percent']); ?>" 
                                   min="0" max="100" step="0.01" required>
                            <div class="help-text">Percentage of first topup given as commission (default: 5%)</div>
                        </div>

                        <div class="form-group">
                            <label for="min_topup_for_reward">Min Topup for Reward (Rp)</label>
                            <input type="number" id="min_topup_for_reward" name="min_topup_for_reward" 
                                   value="<?php echo htmlspecialchars($settings['min_topup_for_reward']); ?>" 
                                   min="0" step="10000" required>
                            <div class="help-text">Minimum topup amount to trigger referral reward (default: Rp 100,000)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="email_verification_required" value="1" <?php echo $settings['email_verification_required'] == '1' ? 'checked' : ''; ?>>
                            <span>Require Email Verification</span>
                        </label>
                        <div class="help-text">Users must verify email before they can login</div>
                    </div>

                    <div style="margin-top: 32px;">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>

                <div style="margin-top: 40px; padding-top: 40px; border-top: 1px solid #E8E8E8;">
                    <h3 style="margin-bottom: 16px;">How It Works:</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0;">1. User A shares referral link with code</li>
                        <li style="padding: 8px 0;">2. User B registers with User A's code</li>
                        <li style="padding: 8px 0;">3. Referral reward created with status <strong>PENDING</strong> and <strong>Rp 0</strong></li>
                        <li style="padding: 8px 0;">4. User B completes FIRST topup (≥ min topup)</li>
                        <li style="padding: 8px 0;">5. Commission auto-calculated (topup × commission %)</li>
                        <li style="padding: 8px 0;">6. User A wallet credited with commission</li>
                        <li style="padding: 8px 0;">7. Reward status changed to <strong>COMPLETED</strong></li>
                        <li style="padding: 8px 0; margin-top: 16px; color: #dc3545;"><strong>Note:</strong> Commission only paid ONCE on first topup!</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
