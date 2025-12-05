<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'percentage';
    $category = $_POST['category'] ?? 'general';
    $value = $_POST['value'] ?? 0;
    $max_discount = $_POST['max_discount'] ?? null;
    $min_order_amount = $_POST['min_order_amount'] ?? 0;
    $min_purchase = $_POST['min_purchase'] ?? 0;
    $target_tier = $_POST['target_tier'] ?? 'all';
    $max_uses = $_POST['max_uses'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($code && $value >= 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO vouchers (code, name, type, category, value, max_discount, min_order_amount, min_purchase, target_tier, max_uses, start_date, end_date, is_active, valid_from, valid_until)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, CURDATE()), COALESCE(?, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)))
            ");
            $stmt->execute([$code, $name, $type, $category, $value, $max_discount, $min_order_amount, $min_purchase, $target_tier, $max_uses, $start_date, $end_date, $is_active, $start_date, $end_date]);

            redirect('/admin/vouchers/index.php?success=created');
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = 'Kode voucher sudah digunakan!';
            } else {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Kode voucher dan nilai wajib diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Voucher - Admin Dorve</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; color: #1A1A1A; }
        .admin-layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .admin-sidebar { background: #1A1A1A; color: white; padding: 30px 0; position: fixed; width: 260px; height: 100vh; overflow-y: auto; }
        .admin-logo { font-size: 24px; font-weight: 700; letter-spacing: 3px; padding: 0 30px 30px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-nav { padding: 20px 0; }
        .nav-item { padding: 12px 30px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; transition: all 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.1); color: white; }
        .admin-content { margin-left: 260px; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { font-size: 32px; font-weight: 600; }
        .form-container { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 800px; }
        .form-group { margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #1A1A1A; }
        .help-text { font-size: 13px; color: #6c757d; margin-top: 4px; }
        input[type="text"], input[type="number"], input[type="date"], select, textarea {
            width: 100%; padding: 12px 16px; border: 1px solid #E8E8E8; border-radius: 6px;
            font-size: 15px; font-family: 'Inter', sans-serif; transition: all 0.3s;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #1A1A1A; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .btn { padding: 12px 24px; border-radius: 6px; font-size: 15px; font-weight: 500;
            cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; border: none; }
        .btn-primary { background: #1A1A1A; color: white; }
        .btn-primary:hover { background: #000000; }
        .btn-secondary { background: #E8E8E8; color: #1A1A1A; }
        .btn-secondary:hover { background: #D0D0D0; }
        .button-group { display: flex; gap: 12px; margin-top: 32px; }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE</div>
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
                <h1>Tambah Voucher Baru</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="code">Kode Voucher *</label>
                        <input type="text" id="code" name="code" required placeholder="DISKON50" style="text-transform: uppercase;">
                        <div class="help-text">Kode unik untuk voucher (akan otomatis huruf besar)</div>
                    </div>

                    <div class="form-group">
                        <label for="name">Nama Voucher</label>
                        <input type="text" id="name" name="name" placeholder="Diskon Akhir Tahun">
                        <div class="help-text">Nama deskriptif untuk internal</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">Tipe Diskon *</label>
                            <select id="type" name="type" onchange="updateValueLabel()">
                                <option value="percentage">Persentase (%)</option>
                                <option value="fixed">Nominal (Rp)</option>
                                <option value="free_shipping">Gratis Ongkir</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="category">Kategori Voucher *</label>
                            <select id="category" name="category">
                                <option value="general">General - Semua</option>
                                <option value="discount">Discount - Potongan Harga</option>
                                <option value="free_shipping">Free Shipping - Gratis Ongkir</option>
                                <option value="new_customer">New Customer - Member Baru</option>
                                <option value="loyalty">Loyalty - Member Setia</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="value" id="value_label">Nilai Diskon (%) *</label>
                            <input type="number" id="value" name="value" min="0" step="0.01" required>
                            <div class="help-text" id="value_help">Contoh: 10 = diskon 10%, atau 0 untuk gratis ongkir</div>
                        </div>

                        <div class="form-group">
                            <label for="target_tier">Target Tier 🎯</label>
                            <select id="target_tier" name="target_tier">
                                <option value="all">🌟 Semua Tier</option>
                                <option value="bronze">🥉 Bronze</option>
                                <option value="silver">🥈 Silver (1M+ topup)</option>
                                <option value="gold">🥇 Gold (3M+ topup)</option>
                                <option value="platinum">💎 Platinum (10M+ topup)</option>
                                <option value="vvip">👑 VVIP (20M+ topup)</option>
                            </select>
                            <div class="help-text">Hanya user dengan tier ini yang bisa pakai voucher</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_discount">Maksimal Diskon (Rp)</label>
                            <input type="number" id="max_discount" name="max_discount" min="0" step="1000" placeholder="100000">
                            <div class="help-text">Untuk tipe persentase, batas maksimal potongan</div>
                        </div>

                        <div class="form-group">
                            <label for="min_order_amount">Min. Total Order (Rp)</label>
                            <input type="number" id="min_order_amount" name="min_order_amount" min="0" step="1000" value="0">
                            <div class="help-text">Minimum total order untuk pakai voucher</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="min_purchase">Min. Purchase untuk Gratis Ongkir (Rp)</label>
                            <input type="number" id="min_purchase" name="min_purchase" min="0" step="1000" value="0" placeholder="50000">
                            <div class="help-text">Khusus free shipping: min belanja untuk gratis ongkir (50K, 100K, dll)</div>
                        </div>

                        <div class="form-group">
                            <label for="max_uses">Batas Penggunaan</label>
                            <input type="number" id="max_uses" name="max_uses" min="1" placeholder="100">
                            <div class="help-text">Kosongkan untuk unlimited</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date">
                            <div class="help-text">Kosongkan untuk mulai sekarang</div>
                        </div>

                        <div class="form-group">
                            <label for="end_date">Tanggal Berakhir</label>
                            <input type="date" id="end_date" name="end_date">
                            <div class="help-text">Kosongkan untuk tidak ada batas</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Aktifkan Voucher Sekarang</span>
                        </label>
                        <div class="help-text">Voucher langsung bisa digunakan setelah disimpan</div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Simpan Voucher</button>
                        <a href="/admin/vouchers/index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function updateValueLabel() {
            const type = document.getElementById('type').value;
            const label = document.getElementById('value_label');
            const help = document.getElementById('value_help');
            const valueInput = document.getElementById('value');
            const maxDiscountInput = document.getElementById('max_discount');

            if (type === 'percentage') {
                label.textContent = 'Nilai Diskon (%) *';
                help.textContent = 'Contoh: 10 = diskon 10%';
                valueInput.max = '100';
                maxDiscountInput.disabled = false;
            } else if (type === 'fixed') {
                label.textContent = 'Nilai Diskon (Rp) *';
                help.textContent = 'Contoh: 50000 = diskon Rp 50.000';
                valueInput.max = '';
                valueInput.step = '1000';
                maxDiscountInput.disabled = true;
                maxDiscountInput.value = '';
            } else {
                label.textContent = 'Nilai';
                help.textContent = 'Tidak perlu diisi untuk gratis ongkir';
                valueInput.value = '0';
                valueInput.disabled = true;
                maxDiscountInput.disabled = true;
                maxDiscountInput.value = '';
            }
        }
    </script>
</body>
</html>
