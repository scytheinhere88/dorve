<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

// Handle suspend/activate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    if ($_POST['action'] === 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE users SET is_suspended = NOT is_suspended WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User status updated!';
        redirect('/admin/users/index.php');
    }
}

// Get users with wallet balance
$stmt = $pdo->query("SELECT id, name, email, phone, role, tier, wallet_balance, is_suspended, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$page_title = 'Kelola Pengguna - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
.wallet-badge {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.suspended-badge {
    background: #FEE2E2;
    color: #DC2626;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.active-badge {
    background: #D1FAE5;
    color: #059669;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<div class="header">
    <h1>👥 Kelola Pengguna</h1>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="content-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Wallet Balance</th>
                <th>Status</th>
                <th>Tier</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr style="<?php echo $user['is_suspended'] ? 'opacity: 0.6; background: #FEF2F2;' : ''; ?>">
                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                    <td>
                        <span class="wallet-badge">
                            Rp <?php echo number_format($user['wallet_balance'] ?? 0, 0, ',', '.'); ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" style="border: none; background: none; cursor: pointer; padding: 0;">
                                <?php if ($user['is_suspended']): ?>
                                    <span class="suspended-badge">🚫 SUSPENDED</span>
                                <?php else: ?>
                                    <span class="active-badge">✓ ACTIVE</span>
                                <?php endif; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <span style="padding: 4px 10px; background: <?php echo $user['role'] === 'admin' ? '#DBEAFE' : '#F3F4F6'; ?>; color: <?php echo $user['role'] === 'admin' ? '#1E40AF' : '#374151'; ?>; border-radius: 6px; font-size: 12px; font-weight: 600;">
                            <?php echo strtoupper($user['tier'] ?? 'bronze'); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <a href="/admin/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">✏️ Edit</a>
                        <a href="/admin/users/manage-balance.php?id=<?php echo $user['id']; ?>" class="btn btn-primary" style="background: #10B981;">💰 Balance</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
