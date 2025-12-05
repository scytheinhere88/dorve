<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$success = $_GET['success'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);

        try {
            $stmt = $pdo->prepare("UPDATE shipping_methods SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $id]);
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $base_cost = floatval($_POST['base_cost'] ?? 0);
        $cost_per_kg = floatval($_POST['cost_per_kg'] ?? 0);
        $estimated_days = trim($_POST['estimated_days'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO shipping_methods (name, base_cost, cost_per_kg, estimated_days, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $base_cost, $cost_per_kg, $estimated_days, $is_active]);
                redirect('/admin/shipping/index.php?success=added');
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $base_cost = floatval($_POST['base_cost'] ?? 0);
        $cost_per_kg = floatval($_POST['cost_per_kg'] ?? 0);
        $estimated_days = trim($_POST['estimated_days'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE shipping_methods SET name = ?, base_cost = ?, cost_per_kg = ?, estimated_days = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $base_cost, $cost_per_kg, $estimated_days, $is_active, $id]);
            redirect('/admin/shipping/index.php?success=updated');
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        try {
            $stmt = $pdo->prepare("DELETE FROM shipping_methods WHERE id = ?");
            $stmt->execute([$id]);
            redirect('/admin/shipping/index.php?success=deleted');
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT * FROM shipping_methods ORDER BY name");
$shipping_methods = $stmt->fetchAll();

$editing = false;
$edit_method = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM shipping_methods WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_method = $stmt->fetch();
    if ($edit_method) {
        $editing = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pengiriman - Admin</title>
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
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 12px 16px; border: 1px solid #E8E8E8; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        .checkbox-wrapper { display: flex; align-items: center; gap: 8px; }
        .checkbox-wrapper input { width: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #E8E8E8; font-weight: 600; font-size: 13px; text-transform: uppercase; color: #6c757d; }
        td { padding: 16px 12px; border-bottom: 1px solid #F0F0F0; vertical-align: middle; }
        .actions { display: flex; gap: 8px; align-items: center; }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .help-text { font-size: 12px; color: #6c757d; margin-top: 4px; }

        .toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 28px; }
        .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .toggle-slider { background-color: #28a745; }
        input:checked + .toggle-slider:before { transform: translateX(24px); }
        .toggle-slider:hover { background-color: #999; }
        input:checked + .toggle-slider:hover { background-color: #218838; }
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
                <li><a href="/admin/shipping/index.php" class="active">Pengiriman</a></li>
                <li><a href="/admin/pages/index.php">Halaman CMS</a></li>
                <li><a href="/admin/settings/index.php">Pengaturan</a></li>
                <li><a href="/auth/logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title"><?php echo $editing ? 'Edit Metode Pengiriman' : 'Metode Pengiriman'; ?></h1>
                <?php if ($editing): ?>
                    <a href="/admin/shipping/index.php" class="btn btn-secondary">← Kembali</a>
                <?php endif; ?>
            </div>

            <?php if ($success === 'added'): ?>
                <div class="alert alert-success">✓ Metode pengiriman berhasil ditambahkan!</div>
            <?php elseif ($success === 'updated'): ?>
                <div class="alert alert-success">✓ Metode pengiriman berhasil diupdate!</div>
            <?php elseif ($success === 'deleted'): ?>
                <div class="alert alert-success">✓ Metode pengiriman berhasil dihapus!</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">✗ Error: <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="content-card">
                <h3 style="font-size: 20px; margin-bottom: 24px; font-weight: 600;">
                    <?php echo $editing ? 'Edit Metode Pengiriman' : 'Tambah Metode Pengiriman Baru'; ?>
                </h3>

                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editing ? 'edit' : 'add'; ?>">
                    <?php if ($editing): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_method['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="name">Nama Metode Pengiriman *</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo $editing ? htmlspecialchars($edit_method['name']) : ''; ?>"
                                   placeholder="Contoh: JNE Regular">
                        </div>

                        <div class="form-group">
                            <label for="base_cost">Biaya Dasar (Rp)</label>
                            <input type="number" id="base_cost" name="base_cost" min="0" step="1000"
                                   value="<?php echo $editing ? $edit_method['base_cost'] : '0'; ?>"
                                   placeholder="25000">
                            <div class="help-text">Biaya tetap untuk pengiriman</div>
                        </div>

                        <div class="form-group">
                            <label for="cost_per_kg">Biaya per KG (Rp)</label>
                            <input type="number" id="cost_per_kg" name="cost_per_kg" min="0" step="1000"
                                   value="<?php echo $editing ? $edit_method['cost_per_kg'] : '0'; ?>"
                                   placeholder="5000">
                            <div class="help-text">Biaya tambahan per kilogram</div>
                        </div>

                        <div class="form-group full-width">
                            <label for="estimated_days">Estimasi Pengiriman</label>
                            <input type="text" id="estimated_days" name="estimated_days"
                                   value="<?php echo $editing ? htmlspecialchars($edit_method['estimated_days']) : ''; ?>"
                                   placeholder="3-5 hari">
                            <div class="help-text">Contoh: "3-5 hari", "1-2 hari", "7-14 hari"</div>
                        </div>

                        <div class="form-group full-width">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="is_active" name="is_active" value="1"
                                       <?php echo ($editing && $edit_method['is_active']) || !$editing ? 'checked' : ''; ?>>
                                <label for="is_active" style="margin: 0;">Aktif (Tampilkan di checkout)</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $editing ? 'Update Metode' : 'Tambah Metode'; ?>
                    </button>
                    <?php if ($editing): ?>
                        <a href="/admin/shipping/index.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!$editing): ?>
            <div class="content-card">
                <h3 style="font-size: 20px; margin-bottom: 24px; font-weight: 600;">Daftar Metode Pengiriman</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Metode</th>
                            <th>Biaya Dasar</th>
                            <th>Per KG</th>
                            <th>Estimasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shipping_methods as $method): ?>
                            <tr id="method-<?php echo $method['id']; ?>">
                                <td><strong><?php echo htmlspecialchars($method['name']); ?></strong></td>
                                <td>Rp <?php echo number_format($method['base_cost'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($method['cost_per_kg'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($method['estimated_days']); ?></td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox"
                                               data-id="<?php echo $method['id']; ?>"
                                               <?php echo $method['is_active'] ? 'checked' : ''; ?>
                                               onchange="toggleShipping(this)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="/admin/shipping/index.php?edit=<?php echo $method['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin hapus metode pengiriman ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
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
        function toggleShipping(checkbox) {
            const id = checkbox.dataset.id;
            const isActive = checkbox.checked ? 1 : 0;

            fetch('/admin/shipping/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle&id=${id}&is_active=${isActive}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Status updated successfully');
                } else {
                    alert('Error: ' + data.error);
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat update status');
                checkbox.checked = !checkbox.checked;
            });
        }
    </script>
</body>
</html>
