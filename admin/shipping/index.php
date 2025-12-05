<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO shipping_methods (name, description, cost, estimated_days) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['cost'], $_POST['estimated_days']]);
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM shipping_methods WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    }
    redirect('/admin/shipping/');
}

$stmt = $pdo->query("SELECT * FROM shipping_methods ORDER BY cost ASC");
$methods = $stmt->fetchAll();

$page_title = 'Kelola Pengiriman - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Metode Pengiriman</h1>
</div>

<div class="form-container">
    <h2 style="margin-bottom: 20px;">Tambah Metode Pengiriman</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>Nama (contoh: JNE REG)</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Biaya (Rp)</label>
                <input type="number" name="cost" required>
            </div>
            <div class="form-group">
                <label>Estimasi (hari)</label>
                <input type="number" name="estimated_days" required>
            </div>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="description"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Tambah Metode</button>
    </form>
</div>

<div class="content-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Cost</th>
                <th>Estimated Days</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($methods as $method): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($method['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($method['description']); ?></td>
                    <td><strong>Rp <?php echo number_format($method['cost'], 0, ',', '.'); ?></strong></td>
                    <td><?php echo $method['estimated_days']; ?> hari</td>
                    <td>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus metode ini?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
