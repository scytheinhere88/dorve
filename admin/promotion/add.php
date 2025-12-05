<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$error = '';
$success = '';

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
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Banner image is required!';
    } else {
        try {
            // Handle image upload
            $upload_dir = __DIR__ . '/../../uploads/banners/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($ext, $allowed)) {
                throw new Exception('Invalid image format. Allowed: JPG, PNG, WEBP');
            }
            
            $filename = 'banner_' . time() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image_url = '/uploads/banners/' . $filename;
                
                $stmt = $pdo->prepare("
                    INSERT INTO banners (title, subtitle, banner_type, image_url, link_url, cta_text, display_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $subtitle, $banner_type, $image_url, $link_url, $cta_text, $display_order, $is_active]);
                
                $_SESSION['success'] = 'Banner added successfully!';
                redirect('/admin/promotion/index.php');
            } else {
                throw new Exception('Failed to upload image');
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Add Banner - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Add New Banner</h1>
    <a href="/admin/promotion/index.php" class="btn btn-secondary">← Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-container">
    <div class="form-group">
        <label>Banner Title *</label>
        <input type="text" name="title" required placeholder="e.g., Summer Sale 2024" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label>Subtitle</label>
        <input type="text" name="subtitle" placeholder="e.g., Up to 50% Off" value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
        <small>Optional - Leave empty for cleaner design</small>
    </div>
    
    <div class="form-group">
        <label>Banner Type *</label>
        <select name="banner_type" required>
            <option value="slider">Homepage Slider Banner</option>
            <option value="popup">Popup Banner (Auto show after 3 seconds)</option>
        </select>
        <small>Choose where this banner will be displayed</small>
    </div>
    
    <div class="form-group">
        <label>Banner Image *</label>
        <input type="file" name="image" accept="image/*" required>
        <small><strong>Recommended size: 2944x1440px</strong> for best quality. Max: 128MB</small>
    </div>
    
    <div class="form-group">
        <label>Link URL</label>
        <input type="url" name="link_url" placeholder="https://..." value="<?php echo htmlspecialchars($_POST['link_url'] ?? ''); ?>">
        <small>Where should the banner link to? (Optional)</small>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="display_order" value="<?php echo htmlspecialchars($_POST['display_order'] ?? '0'); ?>" min="0">
            <small>Lower numbers appear first</small>
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                <label for="is_active">Active (Display on website)</label>
            </div>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary">Add Banner</button>
</form>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>