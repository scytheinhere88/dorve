<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$stmt = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC");
$vouchers = $stmt->fetchAll();

$page_title = 'Kelola Voucher - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Voucher</h1>
    <a href="/admin/vouchers/add.php" class="btn btn-primary">+ Tambah Voucher</a>
</div>

<div class="content-container">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Discount</th>
                <th>Valid Until</th>
                <th>Usage</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vouchers)): ?>
                <tr><td colspan="7" style="text-align: center; padding: 40px; color: #6B7280;">No vouchers yet</td></tr>
            <?php else: ?>
                <?php foreach ($vouchers as $voucher): ?>
                    <tr>
                        <td><code style="background: #F3F4F6; padding: 4px 8px; border-radius: 4px; font-weight: 600;"><?php echo htmlspecialchars($voucher['code']); ?></code></td>
                        <td><?php echo ucfirst($voucher['discount_type']); ?></td>
                        <td><strong><?php echo $voucher['discount_type'] === 'percentage' ? $voucher['discount_value'] . '%' : 'Rp ' . number_format($voucher['discount_value'], 0, ',', '.'); ?></strong></td>
                        <td><?php echo $voucher['valid_until'] ? date('d M Y', strtotime($voucher['valid_until'])) : 'No expiry'; ?></td>
                        <td><?php echo $voucher['used_count'] ?? 0; ?> / <?php echo $voucher['max_uses'] ?: '∞'; ?></td>
                        <td><span style="padding: 6px 12px; background: <?php echo $voucher['is_active'] ? '#ECFDF5' : '#F3F4F6'; ?>; color: <?php echo $voucher['is_active'] ? '#059669' : '#6B7280'; ?>; border-radius: 6px; font-size: 12px; font-weight: 600;"><?php echo $voucher['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                        <td>
                            <a href="/admin/vouchers/edit.php?id=<?php echo $voucher['id']; ?>" class="btn btn-secondary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
