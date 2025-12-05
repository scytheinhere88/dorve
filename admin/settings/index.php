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

    if ($action === 'save_store') {
        try {
            $settings = [
                'store_name' => trim($_POST['store_name'] ?? ''),
                'store_email' => trim($_POST['store_email'] ?? ''),
                'store_phone' => trim($_POST['store_phone'] ?? ''),
                'store_address' => trim($_POST['store_address'] ?? ''),
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

    if ($action === 'save_general') {
        try {
            $settings = [
                'currency' => trim($_POST['currency'] ?? 'IDR'),
                'currency_symbol' => trim($_POST['currency_symbol'] ?? 'Rp'),
                'whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?)
                                      ON DUPLICATE KEY UPDATE value = ?");
                $stmt->execute([$key, $value, $value]);
            }

            $success = 'General settings saved successfully!';
        } catch (PDOException $e) {
            $error = 'Error saving settings: ' . $e->getMessage();
        }
    }
}

// Get current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['value'];
    }
} catch (PDOException $e) {
    // Settings table might not exist
    $error = 'Please run database migration first!';
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>⚙️ Settings</h1>
    <p style="color: #666; margin-top: 8px;">Manage your store configuration</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Store Information -->
<div class="form-container">
    <div class="section-header">
        <h2>🏪 Store Information</h2>
        <p class="text-muted">This information will be used on shipping labels, receipts, and contact forms. All admin users share the same settings.</p>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="action" value="save_store">

        <div class="form-group">
            <label for="store_name">Store Name *</label>
            <input type="text" id="store_name" name="store_name"
                   value="<?php echo htmlspecialchars($settings['store_name'] ?? 'Dorve House'); ?>"
                   required placeholder="e.g., Dorve House">
            <small>This will appear on shipping labels and receipts</small>
        </div>

        <div class="form-group">
            <label for="store_address">Store Address *</label>
            <textarea id="store_address" name="store_address" rows="4" required
                      placeholder="Full store address including street, city, postal code"><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>
            <small>This address will be printed on shipping labels as sender address</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="store_phone">Phone Number *</label>
                <input type="tel" id="store_phone" name="store_phone"
                       value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>"
                       required placeholder="e.g., 0812-3456-7890">
                <small>Contact number for customer support</small>
            </div>

            <div class="form-group">
                <label for="store_email">Email Address *</label>
                <input type="email" id="store_email" name="store_email"
                       value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>"
                       required placeholder="e.g., info@dorvehouse.com">
                <small>Contact email for customer inquiries</small>
            </div>
        </div>

        <div class="alert alert-info" style="margin-top: 20px;">
            <strong>💡 Important:</strong> This information will be used when printing shipping labels.
            Make sure the address is complete and accurate for courier pickup.
        </div>

        <button type="submit" class="btn btn-primary btn-lg">💾 Save Store Information</button>
    </form>
</div>

<!-- General Settings -->
<div class="form-container" style="margin-top: 30px;">
    <div class="section-header">
        <h2>🌐 General Settings</h2>
        <p class="text-muted">Configure general website settings</p>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="action" value="save_general">

        <div class="form-row">
            <div class="form-group">
                <label for="currency">Currency</label>
                <input type="text" id="currency" name="currency"
                       value="<?php echo htmlspecialchars($settings['currency'] ?? 'IDR'); ?>"
                       placeholder="IDR">
            </div>

            <div class="form-group">
                <label for="currency_symbol">Currency Symbol</label>
                <input type="text" id="currency_symbol" name="currency_symbol"
                       value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'Rp'); ?>"
                       placeholder="Rp">
            </div>
        </div>

        <div class="form-group">
            <label for="whatsapp_number">WhatsApp Number</label>
            <input type="tel" id="whatsapp_number" name="whatsapp_number"
                   value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? '6281377378859'); ?>"
                   placeholder="6281377378859">
            <small>Used for WhatsApp floating button (format: 628xxxxxxxxxx)</small>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">💾 Save General Settings</button>
    </form>
</div>

<!-- Quick Links -->
<div class="form-container" style="margin-top: 30px; background: #DBEAFE; border-left: 4px solid #3B82F6;">
    <h3 style="margin-bottom: 16px; font-size: 18px; color: #1E40AF;">
        ⚡ Quick Links
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <a href="/admin/settings/api-settings.php" class="quick-link-btn">
            🔌 API & Integration Settings
        </a>
        <a href="/admin/shipping/index.php" class="quick-link-btn">
            🚚 Shipping Methods
        </a>
        <a href="/admin/promotion/index.php" class="quick-link-btn">
            📢 Promotion & Marketing
        </a>
        <a href="/admin/pages/index.php" class="quick-link-btn">
            📄 CMS Pages
        </a>
    </div>
</div>

<style>
.section-header {
    margin-bottom: 24px;
}

.section-header h2 {
    font-size: 20px;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1A1A1A;
}

.text-muted {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.quick-link-btn {
    display: block;
    padding: 16px;
    background: white;
    border: 2px solid #3B82F6;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    color: #1E40AF;
    font-weight: 600;
    transition: all 0.3s;
}

.quick-link-btn:hover {
    background: #3B82F6;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
