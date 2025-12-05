<?php
session_start();
require_once __DIR__ . '/../../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle announcement text update
        if (isset($_POST['announcement_text'])) {
            $announcement_text = trim($_POST['announcement_text']);

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('announcement_text', ?)
                                  ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$announcement_text, $announcement_text]);
            $success = "Announcement text updated successfully!";
        }

        // Handle promotion banner settings
        if (isset($_POST['promo_banner_enabled'])) {
            $enabled = isset($_POST['promo_banner_enabled']) ? '1' : '0';
            $promo_link = trim($_POST['promo_banner_link'] ?? '/pages/all-products.php');

            // Update enabled status
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('promo_banner_enabled', ?)
                                  ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$enabled, $enabled]);

            // Update link
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('promo_banner_link', ?)
                                  ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$promo_link, $promo_link]);

            // Handle image upload
            if (isset($_FILES['promo_banner_image']) && $_FILES['promo_banner_image']['error'] === UPLOAD_ERR_OK) {
                require_once __DIR__ . '/../../includes/upload-handler.php';

                $upload_result = handleFileUpload($_FILES['promo_banner_image'], 'promotion');

                if ($upload_result['success']) {
                    // Delete old image if exists
                    $stmt = $pdo->query("SELECT value FROM settings WHERE setting_key = 'promo_banner_image' LIMIT 1");
                    $old_image = $stmt->fetch();
                    if ($old_image && file_exists(UPLOAD_PATH . $old_image['value'])) {
                        unlink(UPLOAD_PATH . $old_image['value']);
                    }

                    // Save new image path
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('promo_banner_image', ?)
                                          ON DUPLICATE KEY UPDATE value = ?");
                    $stmt->execute([$upload_result['file_path'], $upload_result['file_path']]);

                    $success = "Promotion banner updated successfully!";
                } else {
                    $error = $upload_result['error'];
                }
            } else {
                $success = "Promotion settings updated successfully!";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get current settings
$announcement_text = "🎉 Welcome to Dorve House! New Collection 2024 Available Now! FREE Shipping for orders above Rp 500.000 🎉";
$promo_enabled = false;
$promo_image = '';
$promo_link = '/pages/all-products.php';

try {
    $stmt = $pdo->query("SELECT setting_key, value FROM settings WHERE setting_key IN ('announcement_text', 'promo_banner_enabled', 'promo_banner_image', 'promo_banner_link')");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'announcement_text') {
            $announcement_text = $row['value'];
        } elseif ($row['setting_key'] === 'promo_banner_enabled') {
            $promo_enabled = ($row['value'] === '1');
        } elseif ($row['setting_key'] === 'promo_banner_image') {
            $promo_image = $row['value'];
        } elseif ($row['setting_key'] === 'promo_banner_link') {
            $promo_link = $row['value'];
        }
    }
} catch (PDOException $e) {
    // Tables might not exist yet
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>📢 Promotion Management</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Marquee Announcement Settings -->
<div class="form-container">
    <h2 style="margin-bottom: 24px; font-size: 20px;">🎯 Marquee Announcement Text</h2>
    <p style="color: #666; margin-bottom: 24px;">
        This scrolling text will appear below the main header on all pages. Keep it short and engaging!
    </p>

    <form method="POST" action="">
        <div class="form-group">
            <label for="announcement_text">Announcement Text</label>
            <textarea
                id="announcement_text"
                name="announcement_text"
                rows="3"
                placeholder="Enter your announcement text here..."
                required><?php echo htmlspecialchars($announcement_text); ?></textarea>
            <small style="color: #666; display: block; margin-top: 8px;">
                💡 Tip: Use emojis to make it more attractive! Example: 🎉 🔥 ⭐ 💝 🎁
            </small>
        </div>

        <button type="submit" class="btn btn-primary">
            💾 Update Announcement
        </button>
    </form>
</div>

<!-- Promotion Banner Settings -->
<div class="form-container" style="margin-top: 30px;">
    <h2 style="margin-bottom: 24px; font-size: 20px;">🎨 Promotion Banner (Popup)</h2>
    <p style="color: #666; margin-bottom: 24px;">
        Show a popup banner 3 seconds after users visit the website. Perfect for announcing sales, new collections, or special offers!
    </p>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <div class="checkbox-group">
                <input
                    type="checkbox"
                    id="promo_banner_enabled"
                    name="promo_banner_enabled"
                    value="1"
                    <?php echo $promo_enabled ? 'checked' : ''; ?>>
                <label for="promo_banner_enabled" style="margin: 0; cursor: pointer;">
                    ✅ Enable Promotion Banner
                </label>
            </div>
            <small style="color: #666; display: block; margin-top: 8px; margin-left: 24px;">
                Toggle ON to show the banner, toggle OFF to hide it
            </small>
        </div>

        <?php if ($promo_image): ?>
        <div class="form-group">
            <label>Current Banner Image</label>
            <div style="border: 2px solid #E8E8E8; border-radius: 8px; padding: 10px; max-width: 600px;">
                <img src="<?php echo UPLOAD_URL . htmlspecialchars($promo_image); ?>"
                     alt="Current Promotion Banner"
                     style="width: 100%; height: auto; display: block; border-radius: 6px;">
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="promo_banner_image">Upload Banner Image <?php echo $promo_image ? '(Upload new to replace)' : ''; ?></label>
            <input
                type="file"
                id="promo_banner_image"
                name="promo_banner_image"
                accept="image/jpeg,image/jpg,image/png,image/webp"
                <?php echo !$promo_image ? 'required' : ''; ?>>
            <small style="color: #666; display: block; margin-top: 8px;">
                📐 Recommended size: 1200x800px or similar landscape ratio (16:9 or 3:2)<br>
                📦 Max file size: 5MB | Formats: JPG, PNG, WebP
            </small>
        </div>

        <div class="form-group">
            <label for="promo_banner_link">Banner Link (URL)</label>
            <input
                type="text"
                id="promo_banner_link"
                name="promo_banner_link"
                value="<?php echo htmlspecialchars($promo_link); ?>"
                placeholder="/pages/all-products.php"
                required>
            <small style="color: #666; display: block; margin-top: 8px;">
                🔗 Where should users go when they click the banner?<br>
                Examples: /pages/all-products.php | /pages/new-collection.php?gender=women
            </small>
        </div>

        <button type="submit" class="btn btn-primary">
            💾 Update Promotion Banner
        </button>
    </form>
</div>

<!-- Preview Section -->
<div class="content-container" style="margin-top: 30px; background: #F8F9FA;">
    <h3 style="margin-bottom: 16px; font-size: 18px;">👀 Preview</h3>

    <div style="margin-bottom: 24px;">
        <h4 style="font-size: 14px; margin-bottom: 8px; color: #666;">Marquee Announcement:</h4>
        <div style="background: linear-gradient(90deg, #1A1A1A 0%, #333333 100%); color: white; padding: 12px; border-radius: 6px; overflow: hidden;">
            <div style="white-space: nowrap; animation: marquee 20s linear infinite;">
                <?php echo htmlspecialchars($announcement_text); ?>
            </div>
        </div>
    </div>

    <div>
        <h4 style="font-size: 14px; margin-bottom: 8px; color: #666;">Promotion Banner:</h4>
        <?php if ($promo_enabled && $promo_image): ?>
            <div style="max-width: 400px; border: 2px solid #1A1A1A; border-radius: 8px; overflow: hidden;">
                <img src="<?php echo UPLOAD_URL . htmlspecialchars($promo_image); ?>"
                     alt="Promotion Banner Preview"
                     style="width: 100%; height: auto; display: block;">
            </div>
            <p style="margin-top: 12px; color: #10B981; font-weight: 500;">
                ✅ Banner is ENABLED and will show to users after 3 seconds
            </p>
        <?php elseif (!$promo_enabled && $promo_image): ?>
            <p style="color: #F59E0B; font-weight: 500;">
                ⚠️ Banner is DISABLED - Enable it to show to users
            </p>
        <?php else: ?>
            <p style="color: #666;">
                📷 No banner image uploaded yet
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Help Section -->
<div class="content-container" style="margin-top: 30px; background: #DBEAFE; border-left: 4px solid #3B82F6;">
    <h3 style="margin-bottom: 16px; font-size: 18px; color: #1E40AF;">
        💡 Tips & Best Practices
    </h3>

    <ul style="color: #1E40AF; line-height: 2; margin-left: 20px;">
        <li><strong>Marquee Text:</strong> Keep it under 150 characters for better readability</li>
        <li><strong>Banner Design:</strong> Use high-quality images with clear, readable text</li>
        <li><strong>Banner Size:</strong> Landscape format (1200x800px) works best for all devices</li>
        <li><strong>Call to Action:</strong> Include a clear CTA like "Shop Now" or "View Collection"</li>
        <li><strong>Update Regularly:</strong> Change promotions weekly to keep content fresh</li>
        <li><strong>Test on Mobile:</strong> Always check how your banner looks on mobile devices</li>
        <li><strong>Banner Frequency:</strong> Banner shows once per session (uses sessionStorage)</li>
    </ul>
</div>

<style>
@keyframes marquee {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}
</style>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
