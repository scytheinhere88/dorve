<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/upload-handler.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);

        if (empty($slug)) {
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $name)));
        }

        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = handleImageUpload($_FILES['image'], 'categories');
            if ($upload['success']) {
                $image_path = $upload['path'];
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, image_path, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $image_path, $is_featured, $sort_order]);
            redirect('/admin/categories/index.php?success=added');
        } catch (PDOException $e) {
            redirect('/admin/categories/index.php?error=' . urlencode($e->getMessage()));
        }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $sort_order = intval($_POST['sort_order'] ?? 0);

        $stmt = $pdo->prepare("SELECT image_path FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        $image_path = $category['image_path'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if ($image_path) {
                deleteImage($image_path);
            }
            $upload = handleImageUpload($_FILES['image'], 'categories');
            if ($upload['success']) {
                $image_path = $upload['path'];
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image_path = ?, is_featured = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $image_path, $is_featured, $sort_order, $id]);
            redirect('/admin/categories/index.php?success=updated');
        } catch (PDOException $e) {
            redirect('/admin/categories/index.php?error=' . urlencode($e->getMessage()));
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            redirect('/admin/categories/index.php?error=Tidak bisa hapus kategori yang masih memiliki produk!');
        }

        $stmt = $pdo->prepare("SELECT image_path FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();

        if ($category && $category['image_path']) {
            deleteImage($category['image_path']);
        }

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);

        redirect('/admin/categories/index.php?success=deleted');
    }
}

$stmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.sort_order, c.name");
$categories = $stmt->fetchAll();

$editing = false;
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
    if ($edit_category) {
        $editing = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; color: #1A1A1A; }
        .admin-layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .admin-sidebar { background: #1A1A1A; color: white; padding: 30px 0; position: fixed; width: 260px; height: 100vh; overflow-y: auto; }
        .admin-logo { padding: 0 30px 30px; font-size: 24px; font-weight: 700; letter-spacing: 3px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; }
        .admin-nav { list-style: none; }
        .admin-nav a { display: block; padding: 12px 30px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.1); color: white; }
        .admin-main { margin-left: 260px; padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .admin-title { font-size: 32px; font-weight: 600; }
        .btn { padding: 12px 24px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.3s; display: inline-block; }
        .btn-primary { background: #1A1A1A; color: white; }
        .btn-primary:hover { background: #000; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .content-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 12px 16px; border: 1px solid #E8E8E8; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        textarea { min-height: 100px; resize: vertical; }
        .image-preview { margin-top: 12px; max-width: 200px; border-radius: 8px; border: 2px solid #E8E8E8; }
        .image-preview img { width: 100%; height: auto; display: block; border-radius: 6px; }
        .checkbox-wrapper { display: flex; align-items: center; gap: 8px; }
        .checkbox-wrapper input { width: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #E8E8E8; font-weight: 600; font-size: 13px; text-transform: uppercase; color: #6c757d; }
        td { padding: 16px 12px; border-bottom: 1px solid #F0F0F0; vertical-align: middle; }
        .category-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #E8E8E8; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-featured { background: #FFF3CD; color: #856404; }
        .badge-regular { background: #E8E8E8; color: #6c757d; }
        .actions { display: flex; gap: 8px; }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE HOUSE</div>
            <ul class="admin-nav">
                <li><a href="/admin/index.php">Dashboard</a></li>
                <li><a href="/admin/products/index.php">Produk</a></li>
                <li><a href="/admin/categories/index.php" class="active">Kategori</a></li>
                <li><a href="/admin/orders/index.php">Pesanan</a></li>
                <li><a href="/admin/users/index.php">Pengguna</a></li>
                <li><a href="/admin/vouchers/index.php">Voucher</a></li>
                <li><a href="/admin/shipping/index.php">Pengiriman</a></li>
                <li><a href="/admin/pages/index.php">Halaman CMS</a></li>
                <li><a href="/admin/settings/index.php">Pengaturan</a></li>
                <li><a href="/auth/logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title"><?php echo $editing ? 'Edit Kategori' : 'Manajemen Kategori'; ?></h1>
                <?php if ($editing): ?>
                    <a href="/admin/categories/index.php" class="btn btn-secondary">← Kembali</a>
                <?php endif; ?>
            </div>

            <?php if ($success === 'added'): ?>
                <div class="alert alert-success">✓ Kategori berhasil ditambahkan!</div>
            <?php elseif ($success === 'updated'): ?>
                <div class="alert alert-success">✓ Kategori berhasil diupdate!</div>
            <?php elseif ($success === 'deleted'): ?>
                <div class="alert alert-success">✓ Kategori berhasil dihapus!</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">✗ Error: <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="content-card">
                <h3 style="font-size: 20px; margin-bottom: 24px; font-weight: 600;">
                    <?php echo $editing ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?>
                </h3>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $editing ? 'edit' : 'add'; ?>">
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nama Kategori *</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo $editing ? htmlspecialchars($edit_category['name']) : ''; ?>"
                                   placeholder="Contoh: Hoodies">
                        </div>

                        <div class="form-group">
                            <label for="slug">Slug (URL)</label>
                            <input type="text" id="slug" name="slug"
                                   value="<?php echo $editing ? htmlspecialchars($edit_category['slug']) : ''; ?>"
                                   placeholder="hoodies (kosongkan untuk auto-generate)">
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Deskripsi</label>
                            <textarea id="description" name="description"
                                      placeholder="Deskripsi singkat tentang kategori ini..."><?php echo $editing ? htmlspecialchars($edit_category['description'] ?? '') : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="image">Gambar Kategori</label>
                            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                            <div id="imagePreview" style="margin-top: 12px;">
                                <?php if ($editing && $edit_category['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($edit_category['image_path']); ?>"
                                         style="max-width: 200px; border-radius: 8px; border: 2px solid #E8E8E8;">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sort_order">Urutan Tampilan</label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   value="<?php echo $editing ? $edit_category['sort_order'] : '0'; ?>">
                        </div>

                        <div class="form-group full-width">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                       <?php echo ($editing && $edit_category['is_featured']) ? 'checked' : ''; ?>>
                                <label for="is_featured" style="margin: 0;">Tampilkan di Homepage (Featured)</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $editing ? 'Update Kategori' : 'Tambah Kategori'; ?>
                    </button>
                    <?php if ($editing): ?>
                        <a href="/admin/categories/index.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!$editing): ?>
            <div class="content-card">
                <h3 style="font-size: 20px; margin-bottom: 24px; font-weight: 600;">Daftar Kategori</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Produk</th>
                            <th>Status</th>
                            <th>Urutan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <?php if ($cat['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($cat['image_path']); ?>" class="category-image" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: #E8E8E8; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #6c757d;">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                <td><?php echo $cat['product_count']; ?> produk</td>
                                <td>
                                    <?php if ($cat['is_featured']): ?>
                                        <span class="badge badge-featured">Featured</span>
                                    <?php else: ?>
                                        <span class="badge badge-regular">Regular</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $cat['sort_order']; ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="/admin/categories/index.php?edit=<?php echo $cat['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin hapus kategori ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; border-radius: 8px; border: 2px solid #E8E8E8;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('name').addEventListener('input', function(e) {
            const slug = document.getElementById('slug');
            if (!slug.value || slug.dataset.autoGenerated) {
                const generated = e.target.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slug.value = generated;
                slug.dataset.autoGenerated = 'true';
            }
        });

        document.getElementById('slug').addEventListener('input', function() {
            this.dataset.autoGenerated = '';
        });
    </script>
</body>
</html>
