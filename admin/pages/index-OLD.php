<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$success = $_GET['success'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE cms_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, is_published = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $content, $meta_title, $meta_description, $is_published, $id]);
            redirect('/admin/pages/index.php?success=updated');
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT * FROM cms_pages ORDER BY title");
$pages = $stmt->fetchAll();

$editing = false;
$edit_page = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_page = $stmt->fetch();
    if ($edit_page) {
        $editing = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman CMS - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; color: #1A1A1A; }
        .admin-layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .admin-sidebar { background: #1A1A1A; color: white; padding: 30px 0; position: fixed; width: 260px; height: 100vh; overflow-y: auto; }
        .admin-logo { padding: 0 30px 30px; font-size: 24px; font-weight: 700; letter-spacing: 3px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; }
        .admin-nav { list-style: none; }
        .admin-nav a { display: block; padding: 12px 30px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.1); color: white; }
        .admin-main { margin-left: 260px; padding: 40px; max-width: 1400px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .admin-title { font-size: 32px; font-weight: 600; }
        .btn { padding: 12px 24px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.3s; display: inline-block; }
        .btn-primary { background: #1A1A1A; color: white; }
        .btn-primary:hover { background: #000; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .content-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-group { margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        input[type="text"], textarea { width: 100%; padding: 12px 16px; border: 1px solid #E8E8E8; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        textarea { min-height: 80px; resize: vertical; }
        .checkbox-wrapper { display: flex; align-items: center; gap: 8px; }
        .checkbox-wrapper input { width: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #E8E8E8; font-weight: 600; font-size: 13px; text-transform: uppercase; color: #6c757d; }
        td { padding: 16px 12px; border-bottom: 1px solid #F0F0F0; vertical-align: middle; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .actions { display: flex; gap: 8px; }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .help-text { font-size: 12px; color: #6c757d; margin-top: 4px; }
        .info-box { background: #e7f3ff; padding: 16px; border-radius: 8px; border-left: 4px solid #0066cc; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE HOUSE</div>
            <ul class="admin-nav">
                <li><a href="/admin/index.php">Dashboard</a></li>
                <li><a href="/admin/products/index.php">Produk</a></li>
                <li><a href="/admin/categories/index.php">Kategori</a></li>
                <li><a href="/admin/orders/index.php">Pesanan</a></li>
                <li><a href="/admin/users/index.php">Pengguna</a></li>
                <li><a href="/admin/vouchers/index.php">Voucher</a></li>
                <li><a href="/admin/shipping/index.php">Pengiriman</a></li>
                <li><a href="/admin/pages/index.php" class="active">Halaman CMS</a></li>
                <li><a href="/admin/settings/index.php">Pengaturan</a></li>
                <li><a href="/auth/logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title"><?php echo $editing ? 'Edit Halaman' : 'Halaman CMS'; ?></h1>
                <?php if ($editing): ?>
                    <a href="/admin/pages/index.php" class="btn btn-secondary">← Kembali</a>
                <?php endif; ?>
            </div>

            <?php if ($success === 'updated'): ?>
                <div class="alert alert-success">✓ Halaman berhasil diupdate!</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">✗ Error: <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($editing): ?>
            <div class="info-box">
                <strong>Tips Editor:</strong> Gunakan toolbar untuk format text (bold, italic, heading, list, dll). Kamu bisa juga paste dari Word/Google Docs.
            </div>

            <div class="content-card">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $edit_page['id']; ?>">

                    <div class="form-group">
                        <label for="title">Judul Halaman *</label>
                        <input type="text" id="title" name="title" required
                               value="<?php echo htmlspecialchars($edit_page['title']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="content">Konten Halaman *</label>
                        <textarea id="content" name="content"><?php echo htmlspecialchars($edit_page['content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="meta_title">Meta Title (SEO)</label>
                        <input type="text" id="meta_title" name="meta_title"
                               value="<?php echo htmlspecialchars($edit_page['meta_title'] ?? ''); ?>"
                               placeholder="<?php echo htmlspecialchars($edit_page['title']); ?>">
                        <div class="help-text">Untuk SEO Google. Kosongkan untuk otomatis pakai judul halaman</div>
                    </div>

                    <div class="form-group">
                        <label for="meta_description">Meta Description (SEO)</label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                                  placeholder="Deskripsi singkat halaman untuk SEO (max 160 karakter)"><?php echo htmlspecialchars($edit_page['meta_description'] ?? ''); ?></textarea>
                        <div class="help-text">Akan tampil di hasil pencarian Google</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="is_published" name="is_published" value="1"
                                   <?php echo $edit_page['is_published'] ? 'checked' : ''; ?>>
                            <label for="is_published" style="margin: 0;">Publish (Tampilkan di website)</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="/admin/pages/index.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
            <?php else: ?>
            <div class="content-card">
                <h3 style="font-size: 20px; margin-bottom: 24px; font-weight: 600;">Daftar Halaman</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Slug (URL)</th>
                            <th>Status</th>
                            <th>Bahasa</th>
                            <th>Terakhir Update</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                                <td><code>/pages/<?php echo htmlspecialchars($page['slug']); ?></code></td>
                                <td>
                                    <?php if ($page['is_published']): ?>
                                        <span class="badge badge-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo strtoupper($page['language']); ?></td>
                                <td><?php echo $page['updated_at'] ? date('d M Y H:i', strtotime($page['updated_at'])) : date('d M Y H:i', strtotime($page['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="/admin/pages/index.php?edit=<?php echo $page['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <a href="/pages/<?php echo $page['slug']; ?>.php" target="_blank" class="btn btn-secondary btn-sm">Lihat</a>
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

    <?php if ($editing): ?>
    <script>
        tinymce.init({
            selector: '#content',
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic forecolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family: Inter, sans-serif; font-size: 16px; line-height: 1.6; }',
            branding: false
        });
    </script>
    <?php endif; ?>
</body>
</html>
