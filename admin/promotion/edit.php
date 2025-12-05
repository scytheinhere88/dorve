<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Get banner
$stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
$stmt->execute([$id]);
$banner = $stmt->fetch();

if (!$banner) {
    $_SESSION['error'] = 'Banner not found!';
    redirect('/admin/promotion/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $banner_type = $_POST['banner_type'] ?? 'slider';
    $link_url = trim($_POST['link_url'] ?? '');
    $cta_text = trim($_POST['cta_text'] ?? 'Shop Now');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Title is required!';
    } else {
        try {
            $image_url = $banner['image_url'];
            
            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../uploads/banners/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($ext, $allowed)) {
                    throw new Exception('Invalid image format. Allowed: JPG, PNG, WEBP');
                }
                
                // Delete old image
                $old_image = __DIR__ . '/../../' . $banner['image_url'];
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
                
                $filename = 'banner_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                    $image_url = '/uploads/banners/' . $filename;
                } else {
                    throw new Exception('Failed to upload image');
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE banners 
                SET title = ?, subtitle = ?, banner_type = ?, image_url = ?, link_url = ?, cta_text = ?, display_order = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $subtitle, $banner_type, $image_url, $link_url, $cta_text, $display_order, $is_active, $id]);
            
            $_SESSION['success'] = 'Banner updated successfully!';
            redirect('/admin/promotion/index.php');
            
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit Banner - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Edit Banner</h1>
    <a href="/admin/promotion/index.php" class="btn btn-secondary">← Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-container">
    <div class="form-group">
        <label>Banner Title *</label>
        <input type="text" name="title" required placeholder="e.g., Summer Sale 2024" value="<?php echo htmlspecialchars($banner['title']); ?>">
    </div>
    
    <div class="form-group">
        <label>Subtitle</label>
        <input type="text" name="subtitle" placeholder="e.g., Up to 50% Off" value="<?php echo htmlspecialchars($banner['subtitle'] ?? ''); ?>">
        <small>Optional - Leave empty for cleaner design</small>
    </div>
    
    <div class="form-group">
        <label>Banner Type *</label>
        <select name="banner_type" required>
            <option value="slider" <?php echo ($banner['banner_type'] ?? 'slider') === 'slider' ? 'selected' : ''; ?>>Homepage Slider Banner</option>
            <option value="popup" <?php echo ($banner['banner_type'] ?? 'slider') === 'popup' ? 'selected' : ''; ?>>Popup Banner (Auto show after 3 seconds)</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>Current Banner Image</label>
        <div style="margin-bottom: 12px;">
            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                 alt="Banner" 
                 style="max-width: 400px; height: auto; border-radius: 8px; border: 1px solid #E5E7EB;">
        </div>
        <label>Upload New Image (Optional)</label>
        <input type="file" name="image" accept="image/*">
        <small>Leave empty to keep current image. <strong>Recommended: 2944x1440px</strong>, Max: 128MB</small>
    </div>
    
    <div class="form-group">
        <label>Link URL *</label>
        <input type="text" name="link_url" placeholder="/pages/all-products.php" value="<?php echo htmlspecialchars($banner['link_url'] ?? ''); ?>" required>
        <small>Where banner leads to</small>
    </div>
    
    <div class="form-group">
        <label>CTA Button Text</label>
        <input type="text" name="cta_text" placeholder="Shop Now" value="<?php echo htmlspecialchars($banner['cta_text'] ?? 'Shop Now'); ?>" maxlength="50">
        <small>Button text on banner</small>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="display_order" value="<?php echo $banner['display_order']; ?>" min="0">
            <small>Lower numbers appear first</small>
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $banner['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active">Active (Display on website)</label>
            </div>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary">Update Banner</button>
</form>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
