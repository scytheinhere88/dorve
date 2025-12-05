<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'];
            $slug = $_POST['slug'] ?: strtolower(str_replace(' ', '-', $name));
            $image = null;
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../uploads/categories/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $filename = 'cat_' . time() . '.' . $ext;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image = '/uploads/categories/' . $filename;
                    }
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, image) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $image]);
        } elseif ($_POST['action'] === 'update') {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $slug = $_POST['slug'] ?: strtolower(str_replace(' ', '-', $name));
            
            // Get current category
            $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch();
            $image = $current['image'];
            
            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../uploads/categories/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    // Delete old image if exists
                    if ($image && file_exists(__DIR__ . '/../../' . $image)) {
                        unlink(__DIR__ . '/../../' . $image);
                    }
                    
                    $filename = 'cat_' . time() . '.' . $ext;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image = '/uploads/categories/' . $filename;
                    }
                }
            }
            
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $image, $id]);
        } elseif ($_POST['action'] === 'delete') {
            // Get image path before deleting
            $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $cat = $stmt->fetch();
            
            // Delete image file if exists
            if ($cat && $cat['image'] && file_exists(__DIR__ . '/../../' . $cat['image'])) {
                unlink(__DIR__ . '/../../' . $cat['image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    }
    redirect('/admin/categories/');
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$page_title = 'Kelola Kategori - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Kategori</h1>
</div>

<div class="form-container">
    <h2 style="margin-bottom: 20px;">Tambah Kategori</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="name" required placeholder="Contoh: Dresses">
            </div>
            <div class="form-group">
                <label>Slug (optional)</label>
                <input type="text" name="slug" placeholder="dresses">
            </div>
        </div>
        <div class="form-group">
            <label>Category Image</label>
            <input type="file" name="image" accept="image/*">
            <small>Upload an image for this category. Recommended size: 400x400px</small>
        </div>
        <button type="submit" class="btn btn-primary">Tambah Kategori</button>
    </form>
</div>

<div class="content-container">
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Image</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <?php if (!empty($cat['image'])): ?>
                            <img src="<?php echo htmlspecialchars($cat['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($cat['name']); ?>" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #E5E7EB;">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; background: #F3F4F6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: 24px;">
                                📁
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                    <td><code style="background: #F3F4F6; padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                    <td>
                        <button class="btn btn-secondary" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>', '<?php echo htmlspecialchars($cat['slug']); ?>', '<?php echo htmlspecialchars($cat['image'] ?? ''); ?>')">Edit</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus kategori ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Category Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 32px; border-radius: 12px; max-width: 600px; width: 90%;">
        <h2 style="margin-bottom: 24px;">Edit Category</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" id="edit_slug" required>
            </div>
            
            <div class="form-group">
                <label>Category Image</label>
                <div id="current_image_preview" style="margin-bottom: 12px;"></div>
                <input type="file" name="image" accept="image/*">
                <small>Upload new image to replace existing one</small>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, slug, image) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_slug').value = slug;
    
    const previewDiv = document.getElementById('current_image_preview');
    if (image) {
        previewDiv.innerHTML = '<img src="' + image + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #E5E7EB;">';
    } else {
        previewDiv.innerHTML = '<p style="color: #9CA3AF; font-size: 14px;">No image uploaded</p>';
    }
    
    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
