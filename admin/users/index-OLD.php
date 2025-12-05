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
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $role = isset($_POST['is_admin']) && $_POST['is_admin'] ? 'admin' : 'customer';

        if ($id && $name && $email) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $address, $role, $id]);
                redirect('/admin/users/index.php?success=updated');
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user && $user['is_admin']) {
            redirect('/admin/users/index.php?error=cannot_delete_admin');
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("DELETE FROM wallet_transactions WHERE user_id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("UPDATE orders SET user_id = NULL WHERE user_id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                redirect('/admin/users/index.php?success=deleted');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get users with tier and total spend
try {
    $stmt = $pdo->query("
        SELECT
            u.*,
            COALESCE(u.current_tier, 'bronze') as tier,
            COALESCE(u.total_topup, 0) as total_spend,
            COALESCE((SELECT SUM(total_price) FROM orders WHERE user_id = u.id AND status IN ('processing', 'shipped', 'completed')), 0) as total_orders_value,
            (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders_count
        FROM users u
        ORDER BY u.created_at DESC
    ");
} catch (PDOException $e) {
    // Fallback if tier columns don't exist
    $stmt = $pdo->query("
        SELECT
            u.*,
            'bronze' as tier,
            0 as total_spend,
            COALESCE((SELECT SUM(total_price) FROM orders WHERE user_id = u.id AND status IN ('processing', 'shipped', 'completed')), 0) as total_orders_value,
            (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders_count
        FROM users u
        ORDER BY u.created_at DESC
    ");
}
$users = $stmt->fetchAll();

$editing = false;
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
    if ($edit_user) {
        $editing = true;
    }
}

$error_msg = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Admin</title>
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
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        input[type="text"], input[type="email"], textarea { width: 100%; padding: 12px 16px; border: 1px solid #E8E8E8; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
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
        .warning-box { background: #fff3cd; padding: 16px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 24px; }
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
                <li><a href="/admin/users/index.php" class="active">Pengguna</a></li>
                <li><a href="/admin/vouchers/index.php">Voucher</a></li>
                <li><a href="/admin/shipping/index.php">Pengiriman</a></li>
                <li><a href="/admin/pages/index.php">Halaman CMS</a></li>
                <li><a href="/admin/settings/index.php">Pengaturan</a></li>
                <li><a href="/auth/logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title"><?php echo $editing ? 'Edit Pengguna' : 'Manajemen Pengguna'; ?></h1>
                <?php if ($editing): ?>
                    <a href="/admin/users/index.php" class="btn btn-secondary">← Kembali</a>
                <?php endif; ?>
            </div>

            <?php if ($success === 'updated'): ?>
                <div class="alert alert-success">✓ Pengguna berhasil diupdate!</div>
            <?php elseif ($success === 'deleted'): ?>
                <div class="alert alert-success">✓ Pengguna berhasil dihapus!</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">✗ Error: <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($error_msg === 'cannot_delete_admin'): ?>
                <div class="alert alert-error">✗ Error: Tidak bisa hapus user admin!</div>
            <?php endif; ?>

            <?php if ($editing): ?>
            <div class="warning-box">
                <strong>⚠️ Perhatian:</strong> Jika mengubah role menjadi Admin, user akan memiliki akses penuh ke admin panel!
            </div>

            <div class="content-card">
                <h3 style="font-size: 20px; margin-bottom: 24px; font-weight: 600;">Edit Data Pengguna</h3>

                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">

                    <div class="form-group">
                        <label for="name">Nama Lengkap *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($edit_user['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($edit_user['email']); ?>">
                        <div class="help-text">Email digunakan untuk login</div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Nomor HP</label>
                        <input type="text" id="phone" name="phone"
                               value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>"
                               placeholder="08123456789">
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat Lengkap</label>
                        <textarea id="address" name="address"
                                  placeholder="Alamat lengkap untuk pengiriman..."><?php echo htmlspecialchars($edit_user['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="is_admin" name="is_admin" value="1"
                                   <?php echo $edit_user['is_admin'] ? 'checked' : ''; ?>>
                            <label for="is_admin" style="margin: 0;">Admin (Akses penuh ke admin panel)</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="/admin/users/index.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
            <?php else: ?>
            <div class="content-card">
                <h3 style="font-size: 20px; margin-bottom: 24px; font-weight: 600;">Daftar Pengguna</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Tier</th>
                            <th>Total Topup</th>
                            <th>Total Belanja</th>
                            <th>Role</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $tier = strtoupper($user['tier']);
                                    $tierColors = [
                                        'BRONZE' => 'background: #CD7F32; color: white;',
                                        'SILVER' => 'background: #C0C0C0; color: #333;',
                                        'GOLD' => 'background: #FFD700; color: #333;',
                                        'PLATINUM' => 'background: #E5E4E2; color: #333;',
                                        'VVIP' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'
                                    ];
                                    $style = $tierColors[$tier] ?? 'background: #6c757d; color: white;';
                                    ?>
                                    <span class="badge" style="<?php echo $style; ?>">
                                        <?php echo $tier; ?>
                                    </span>
                                </td>
                                <td>Rp <?php echo number_format($user['total_spend'], 0, ',', '.'); ?></td>
                                <td>
                                    <strong>Rp <?php echo number_format($user['total_orders_value'], 0, ',', '.'); ?></strong>
                                    <div style="font-size: 11px; color: #6c757d;"><?php echo $user['total_orders_count']; ?> pesanan</div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['is_admin'] ? 'warning' : 'success'; ?>">
                                        <?php echo $user['is_admin'] ? 'Admin' : 'Customer'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="/admin/users/index.php?edit=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <?php if (!$user['is_admin']): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin hapus user ini? Data cart, review, dan wallet akan ikut terhapus!');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled title="Admin tidak bisa dihapus">Hapus</button>
                                        <?php endif; ?>
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
</body>
</html>
