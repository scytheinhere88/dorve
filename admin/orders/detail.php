<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/admin/orders/index.php');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.slug as product_slug
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_payment_status') {
        $payment_status = $_POST['payment_status'];
        try {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->execute([$payment_status, $id]);
            $success = 'Status pembayaran berhasil diupdate!';

            // Refresh order
            $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }

    if ($action === 'update_shipping_status') {
        $shipping_status = $_POST['shipping_status'];
        $tracking_number = $_POST['tracking_number'] ?? null;

        try {
            $stmt = $pdo->prepare("UPDATE orders SET shipping_status = ?, tracking_number = ? WHERE id = ?");
            $stmt->execute([$shipping_status, $tracking_number, $id]);
            $success = 'Status pengiriman berhasil diupdate!';

            // Refresh order
            $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Order Detail - Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
        .btn { padding: 12px 24px; border-radius: 6px; font-size: 15px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; border: none; }
        .btn-primary { background: #1A1A1A; color: white; }
        .btn-primary:hover { background: #000000; }
        .btn-secondary { background: #E8E8E8; color: #1A1A1A; }
        .btn-small { padding: 8px 16px; font-size: 13px; }
        .content-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .section-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #E8E8E8; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .info-item { margin-bottom: 16px; }
        .info-label { font-size: 13px; color: #6c757d; margin-bottom: 4px; font-weight: 500; }
        .info-value { font-size: 15px; color: #1A1A1A; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #E8E8E8; font-weight: 600; font-size: 13px; text-transform: uppercase; color: #6c757d; }
        td { padding: 16px 12px; border-bottom: 1px solid #F0F0F0; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .badge-success { background: #D4EDDA; color: #155724; }
        .badge-warning { background: #FFF3CD; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #1A1A1A; font-size: 14px; }
        input[type="text"], select { width: 100%; padding: 10px 14px; border: 1px solid #E8E8E8; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        input:focus, select:focus { outline: none; border-color: #1A1A1A; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .status-form { background: #f8f9fa; padding: 24px; border-radius: 8px; margin-top: 20px; }
        .total-row { font-weight: 600; font-size: 16px; background: #f8f9fa; }
        .barcode-preview { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; margin-top: 16px; }
        .barcode-number { font-family: 'Courier New', monospace; font-size: 24px; font-weight: 700; letter-spacing: 2px; color: #1A1A1A; }
        .tracking-info { background: #e7f3ff; padding: 16px; border-radius: 8px; border-left: 4px solid #0066cc; margin-top: 16px; }
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
                <a href="/admin/orders/index.php" class="nav-item active">Pesanan</a>
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
                <h1>Detail Pesanan #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <a href="/admin/orders/index.php" class="btn btn-secondary">← Kembali</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Order Info -->
            <div class="content-card">
                <h2 class="section-title">Informasi Pesanan</h2>
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <div class="info-label">Nomor Pesanan</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tanggal Pesanan</div>
                            <div class="info-value"><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Metode Pembayaran</div>
                            <div class="info-value"><?php echo htmlspecialchars(strtoupper($order['payment_method'])); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <div class="info-label">Status Pembayaran</div>
                            <div class="info-value">
                                <?php
                                $payment_badges = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'failed' => 'danger',
                                    'refunded' => 'info'
                                ];
                                $payment_labels = [
                                    'pending' => 'Menunggu',
                                    'paid' => 'Lunas',
                                    'failed' => 'Gagal',
                                    'refunded' => 'Refund'
                                ];
                                ?>
                                <span class="badge badge-<?php echo $payment_badges[$order['payment_status']]; ?>">
                                    <?php echo $payment_labels[$order['payment_status']]; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status Pengiriman</div>
                            <div class="info-value">
                                <?php
                                $shipping_badges = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'shipped' => 'info',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $shipping_labels = [
                                    'pending' => 'Menunggu',
                                    'processing' => 'Diproses',
                                    'shipped' => 'Dikirim',
                                    'delivered' => 'Selesai',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                ?>
                                <span class="badge badge-<?php echo $shipping_badges[$order['shipping_status']]; ?>">
                                    <?php echo $shipping_labels[$order['shipping_status']]; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($order['tracking_number']): ?>
                        <div class="info-item">
                            <div class="info-label">Nomor Resi</div>
                            <div class="info-value">
                                <strong style="font-family: 'Courier New', monospace; font-size: 16px;">
                                    <?php echo htmlspecialchars($order['tracking_number']); ?>
                                </strong>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="content-card">
                <h2 class="section-title">Informasi Pelanggan</h2>
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <div class="info-label">Nama</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <div class="info-label">Telepon</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Alamat Pengiriman</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="content-card">
                <h2 class="section-title">Detail Produk</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['variant_info'] ?? '-'); ?></td>
                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: 500;">Subtotal:</td>
                            <td>Rp <?php echo number_format($order['total_amount'] - $order['shipping_cost'] + $order['discount_amount'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php if ($order['discount_amount'] > 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: 500;">Diskon <?php echo $order['voucher_code'] ? '(' . $order['voucher_code'] . ')' : ''; ?>:</td>
                            <td style="color: #28a745;">- Rp <?php echo number_format($order['discount_amount'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: 500;">Ongkir:</td>
                            <td>Rp <?php echo number_format($order['shipping_cost'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right;">TOTAL:</td>
                            <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Update Payment Status -->
            <div class="content-card">
                <h2 class="section-title">Update Status Pembayaran</h2>
                <form method="POST" class="status-form">
                    <input type="hidden" name="action" value="update_payment_status">
                    <div class="form-group">
                        <label for="payment_status">Status Pembayaran</label>
                        <select id="payment_status" name="payment_status">
                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Lunas</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Gagal</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refund</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status Pembayaran</button>
                </form>
            </div>

            <!-- Update Shipping Status -->
            <div class="content-card">
                <h2 class="section-title">Update Status Pengiriman & Tracking</h2>
                <form method="POST" class="status-form">
                    <input type="hidden" name="action" value="update_shipping_status">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_status">Status Pengiriman</label>
                            <select id="shipping_status" name="shipping_status">
                                <option value="pending" <?php echo $order['shipping_status'] === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                <option value="processing" <?php echo $order['shipping_status'] === 'processing' ? 'selected' : ''; ?>>Sedang Diproses</option>
                                <option value="shipped" <?php echo $order['shipping_status'] === 'shipped' ? 'selected' : ''; ?>>Sudah Dikirim</option>
                                <option value="delivered" <?php echo $order['shipping_status'] === 'delivered' ? 'selected' : ''; ?>>Sudah Diterima</option>
                                <option value="cancelled" <?php echo $order['shipping_status'] === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tracking_number">Nomor Resi (Tracking Number)</label>
                            <input type="text" id="tracking_number" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" placeholder="Contoh: JP123456789ID">
                        </div>
                    </div>

                    <div class="tracking-info">
                        <strong>📦 Cara Update Tracking:</strong>
                        <ol style="margin-top: 8px; padding-left: 20px;">
                            <li>Proses pesanan, siapkan produk untuk dikirim</li>
                            <li>Cetak label & barcode pengiriman dari JNE/JNT</li>
                            <li>Kirim paket ke ekspedisi</li>
                            <li>Update status menjadi "Sudah Dikirim"</li>
                            <li>Masukkan nomor resi dari JNE/JNT ke field di atas</li>
                            <li>Customer bisa tracking otomatis dari halaman orders mereka</li>
                        </ol>
                    </div>

                    <?php if ($order['tracking_number']): ?>
                    <div class="barcode-preview">
                        <div style="font-size: 14px; color: #6c757d; margin-bottom: 8px;">NOMOR RESI:</div>
                        <div class="barcode-number"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
                        <div style="margin-top: 12px; font-size: 13px; color: #6c757d;">
                            Customer dapat tracking dengan nomor ini
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Update Status & Tracking</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
