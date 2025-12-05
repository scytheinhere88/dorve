<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$success = $_GET['success'] ?? '';
$error = '';

// Handle delete
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'deleted';
    } catch (PDOException $e) {
        $error = 'Error menghapus voucher: ' . $e->getMessage();
    }
}

// Handle toggle active
if (isset($_POST['toggle_id'])) {
    $id = $_POST['toggle_id'];
    $is_active = $_POST['is_active'];
    try {
        $stmt = $pdo->prepare("UPDATE vouchers SET is_active = ? WHERE id = ?");
        $stmt->execute([!$is_active, $id]);
        $success = 'updated';
    } catch (PDOException $e) {
        $error = 'Error mengupdate voucher: ' . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC");
$vouchers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Management - Admin Dorve</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; color: #1A1A1A; }
        .admin-layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .admin-sidebar { background: #1A1A1A; color: white; padding: 30px 0; position: fixed; width: 260px; height: 100vh; overflow-y: auto; }
        .admin-logo { font-size: 24px; font-weight: 700; letter-spacing: 3px; padding: 0 30px 30px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; }
        .admin-nav { list-style: none; }
        .nav-item { padding: 12px 30px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; transition: all 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .admin-content { margin-left: 260px; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 32px; font-weight: 600; }
        .btn { padding: 12px 24px; border-radius: 6px; font-size: 15px; font-weight: 500; cursor: pointer;
            text-decoration: none; display: inline-block; transition: all 0.3s; border: none; }
        .btn-primary { background: #1A1A1A; color: white; }
        .btn-primary:hover { background: #000000; }
        .btn-small { padding: 6px 12px; font-size: 13px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-warning:hover { background: #e0a800; }
        .content-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #E8E8E8; font-weight: 600; font-size: 13px; text-transform: uppercase; color: #6c757d; }
        td { padding: 16px 12px; border-bottom: 1px solid #F0F0F0; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .badge-success { background: #D4EDDA; color: #155724; }
        .badge-warning { background: #FFF3CD; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .action-buttons { display: flex; gap: 8px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
        .empty-state h3 { font-size: 20px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE HOUSE</div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="nav-item">Dashboard</a>
                <a href="/admin/products/index.php" class="nav-item">Produk</a>
                <a href="/admin/categories/index.php" class="nav-item">Kategori</a>
                <a href="/admin/orders/index.php" class="nav-item">Pesanan</a>
                <a href="/admin/users/index.php" class="nav-item">Pengguna</a>
                <a href="/admin/vouchers/index.php" class="nav-item active">Voucher</a>
                <a href="/admin/shipping/index.php" class="nav-item">Pengiriman</a>
                <a href="/admin/pages/index.php" class="nav-item">Halaman CMS</a>
                <a href="/admin/settings/index.php" class="nav-item">Pengaturan</a>
                <a href="/auth/logout.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="header">
                <h1>Manajemen Voucher</h1>
                <a href="/admin/vouchers/add.php" class="btn btn-primary">+ Tambah Voucher</a>
            </div>

                <?php if ($success === 'created'): ?>
                <div class="alert alert-success">✓ Voucher berhasil ditambahkan!</div>
            <?php elseif ($success === 'updated'): ?>
                <div class="alert alert-success">✓ Voucher berhasil diupdate!</div>
            <?php elseif ($success === 'deleted'): ?>
                <div class="alert alert-success">✓ Voucher berhasil dihapus!</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">✗ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="content-card">
                <?php if (count($vouchers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Tipe</th>
                                <th>Nilai Diskon</th>
                                <th>Max Diskon</th>
                                <th>Min. Belanja</th>
                                <th>Penggunaan</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vouchers as $v): ?>
                                <?php
                                $type_label = [
                                    'percentage' => 'Persentase',
                                    'fixed' => 'Nominal',
                                    'free_shipping' => 'Gratis Ongkir'
                                ][$v['type']];

                                if ($v['type'] === 'percentage') {
                                    $value_display = $v['value'] . '%';
                                } elseif ($v['type'] === 'fixed') {
                                    $value_display = 'Rp ' . number_format($v['value'], 0, ',', '.');
                                } else {
                                    $value_display = '-';
                                }

                                $max_discount_display = $v['max_discount'] ? 'Rp ' . number_format($v['max_discount'], 0, ',', '.') : '-';
                                $min_order_display = 'Rp ' . number_format($v['min_order_amount'], 0, ',', '.');

                                $usage_display = $v['used_count'];
                                if ($v['max_uses']) {
                                    $usage_display .= ' / ' . $v['max_uses'];
                                    $usage_percent = ($v['used_count'] / $v['max_uses']) * 100;
                                    if ($usage_percent >= 100) {
                                        $usage_badge = 'danger';
                                    } elseif ($usage_percent >= 80) {
                                        $usage_badge = 'warning';
                                    } else {
                                        $usage_badge = 'success';
                                    }
                                } else {
                                    $usage_display .= ' / ∞';
                                    $usage_badge = 'info';
                                }

                                $period = '';
                                if ($v['start_date']) {
                                    $period .= date('d/m/Y', strtotime($v['start_date']));
                                } else {
                                    $period .= 'Sekarang';
                                }
                                $period .= ' - ';
                                if ($v['end_date']) {
                                    $period .= date('d/m/Y', strtotime($v['end_date']));
                                    // Check if expired
                                    if (strtotime($v['end_date']) < time()) {
                                        $is_expired = true;
                                    } else {
                                        $is_expired = false;
                                    }
                                } else {
                                    $period .= 'Unlimited';
                                    $is_expired = false;
                                }
                                ?>
                                <tr>
                                    <td><strong style="font-family: 'Courier New', monospace; font-size: 14px;"><?php echo htmlspecialchars($v['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['name'] ?? '-'); ?></td>
                                    <td><span class="badge badge-info"><?php echo $type_label; ?></span></td>
                                    <td><?php echo $value_display; ?></td>
                                    <td><?php echo $max_discount_display; ?></td>
                                    <td><?php echo $min_order_display; ?></td>
                                    <td><span class="badge badge-<?php echo $usage_badge; ?>"><?php echo $usage_display; ?></span></td>
                                    <td style="font-size: 13px;"><?php echo $period; ?></td>
                                    <td>
                                        <?php if ($is_expired): ?>
                                            <span class="badge badge-danger">Kadaluarsa</span>
                                        <?php else: ?>
                                            <span class="badge badge-<?php echo $v['is_active'] ? 'success' : 'warning'; ?>">
                                                <?php echo $v['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="toggle_id" value="<?php echo $v['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $v['is_active']; ?>">
                                                <button type="submit" class="btn btn-small <?php echo $v['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                    <?php echo $v['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus voucher ini?')">
                                                <input type="hidden" name="delete_id" value="<?php echo $v['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Belum Ada Voucher</h3>
                        <p>Mulai tambahkan voucher untuk menarik pelanggan!</p>
                        <a href="/admin/vouchers/add.php" class="btn btn-primary" style="margin-top: 20px;">+ Tambah Voucher Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
