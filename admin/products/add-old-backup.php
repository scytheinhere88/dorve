<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/upload-handler.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$error = '';
$success = '';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $long_description = trim($_POST['long_description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $discount_percent = floatval($_POST['discount_percent'] ?? 0);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $gender = $_POST['gender'] ?? 'women';
    $is_new_collection = isset($_POST['is_new_collection']) ? 1 : 0;
    $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';

    if ($name && $price > 0) {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

            $stmt = $pdo->prepare("
                INSERT INTO products (name, slug, short_description, long_description, price, discount_percent, category_id, gender, is_new_collection, is_best_seller, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $short_description, $long_description, $price, $discount_percent, $category_id, $gender, $is_new_collection, $is_best_seller, $status]);

            $product_id = $pdo->lastInsertId();

            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $fileCount = count($_FILES['images']['name']);
                $uploadedCount = 0;

                for ($i = 0; $i < min($fileCount, 9); $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$i],
                            'type' => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error' => $_FILES['images']['error'][$i],
                            'size' => $_FILES['images']['size'][$i]
                        ];

                        $upload = handleImageUpload($file, 'products');

                        if ($upload['success']) {
                            $is_primary = ($uploadedCount === 0) ? 1 : 0;
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$product_id, $upload['path'], $is_primary, $uploadedCount]);
                            $uploadedCount++;
                        }
                    }
                }
            }

            redirect("/admin/products/edit.php?id=$product_id&success=created");
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } else {
        $error = 'Nama produk dan harga wajib diisi!';
    }
}

$page_title = 'Tambah Produk Baru - Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #F8F9FA;
            color: #1A1A1A;
        }

        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .admin-sidebar {
            background: #1A1A1A;
            color: white;
            padding: 30px 0;
            position: fixed;
            width: 260px;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 3px;
            padding: 0 30px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-nav {
            padding: 20px 0;
        }

        .nav-item {
            padding: 12px 30px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .admin-content {
            margin-left: 260px;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
        }

        .form-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1A1A1A;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E8E8E8;
            border-radius: 6px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #1A1A1A;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border: none;
        }

        .btn-primary {
            background: #1A1A1A;
            color: white;
        }

        .btn-primary:hover {
            background: #000000;
        }

        .btn-secondary {
            background: #E8E8E8;
            color: #1A1A1A;
        }

        .btn-secondary:hover {
            background: #D0D0D0;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .alert {
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #E8E8E8;
        }
        .preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE</div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="nav-item">Dashboard</a>
                <a href="/admin/products/index.php" class="nav-item active">Produk</a>
                <a href="/admin/categories/index.php" class="nav-item">Kategori</a>
                <a href="/admin/orders/index.php" class="nav-item">Pesanan</a>
                <a href="/admin/users/index.php" class="nav-item">Pengguna</a>
                <a href="/admin/vouchers/index.php" class="nav-item">Voucher</a>
                <a href="/admin/shipping/index.php" class="nav-item">Pengiriman</a>
                <a href="/admin/pages/index.php" class="nav-item">Halaman CMS</a>
                <a href="/admin/settings/index.php" class="nav-item">Pengaturan</a>
                <a href="/auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="header">
                <h1>Tambah Produk Baru</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Nama Produk *</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Harga (Rp) *</label>
                            <input type="number" id="price" name="price" min="0" step="1000" required>
                        </div>

                        <div class="form-group">
                            <label for="discount_percent">Diskon (%)</label>
                            <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" value="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Kategori</label>
                        <select id="category_id" name="category_id">
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender <span style="color: #e74c3c;">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="women">Women (Wanita)</option>
                            <option value="men">Men (Pria)</option>
                            <option value="unisex">Unisex (Pria & Wanita)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="short_description">Deskripsi Singkat</label>
                        <textarea id="short_description" name="short_description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="long_description">Deskripsi Lengkap</label>
                        <textarea id="long_description" name="long_description" style="min-height: 200px;"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-group">
                                <input type="checkbox" name="is_new_collection" value="1">
                                <span>Koleksi Baru</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-group">
                                <input type="checkbox" name="is_best_seller" value="1">
                                <span>Best Seller</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft">Draft</option>
                            <option value="published" selected>Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Gambar Produk</label>
                        <div style="border: 2px dashed #E8E8E8; border-radius: 8px; padding: 30px; text-align: center; background: #FAFAFA; cursor: pointer;" onclick="document.getElementById('images').click()">
                            <div style="font-size: 48px; margin-bottom: 12px; color: #6c757d;">📷</div>
                            <div style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">Upload Gambar Produk</div>
                            <div style="font-size: 12px; color: #6c757d;">Klik untuk pilih gambar (Max 9 gambar, JPG/PNG/WEBP, Max 5MB per file)</div>
                            <input type="file" id="images" name="images[]" accept="image/*" multiple style="display: none;" onchange="previewImages(this)">
                        </div>
                        <div id="imagePreview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; margin-top: 20px;"></div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Simpan Produk</button>
                        <a href="/admin/products/index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            if (input.files) {
                const files = Array.from(input.files).slice(0, 9);

                files.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Preview ${index + 1}">
                            ${index === 0 ? '<div style="position: absolute; top: 8px; left: 8px; background: #1A1A1A; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">PRIMARY</div>' : ''}
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
</body>
</html>
