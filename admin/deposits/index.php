<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['deposit_id'])) {
    $deposit_id = $_POST['deposit_id'];
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if ($action === 'approve') {
        // Redirect to approval handler with referral logic
        $_POST['admin_notes'] = $admin_notes;
        require_once __DIR__ . '/approve-deposit.php';
        exit;
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE topups SET status = 'failed', admin_notes = ?, completed_at = NOW() WHERE id = ?");
        $stmt->execute([$admin_notes, $deposit_id]);
        $_SESSION['success'] = 'Deposit rejected successfully!';
    }
    redirect('/admin/deposits/');
}

$stmt = $pdo->query("SELECT t.*, u.name as user_name, u.email as user_email FROM topups t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
$deposits = $stmt->fetchAll();

$pending = array_filter($deposits, fn($d) => $d['status'] === 'pending');
$approved = array_filter($deposits, fn($d) => $d['status'] === 'completed');
$rejected = array_filter($deposits, fn($d) => $d['status'] === 'failed');

$page_title = 'Kelola Deposit Wallet - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Deposit Wallet</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo count($pending); ?></div>
        <div class="stat-label">Pending Deposits</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count($approved); ?></div>
        <div class="stat-label">Approved Deposits</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo count($rejected); ?></div>
        <div class="stat-label">Rejected Deposits</div>
    </div>
</div>

<div class="content-container">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Proof</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($deposits as $deposit): ?>
                <tr>
                    <td><?php echo date('d M Y H:i', strtotime($deposit['created_at'])); ?></td>
                    <td>
                        <div><strong><?php echo htmlspecialchars($deposit['user_name']); ?></strong></div>
                        <small style="color: #6B7280;"><?php echo htmlspecialchars($deposit['user_email']); ?></small>
                    </td>
                    <td><strong>Rp <?php echo number_format($deposit['amount'], 0, ',', '.'); ?></strong></td>
                    <td><?php echo strtoupper($deposit['payment_method']); ?></td>
                    <td>
                        <?php if ($deposit['payment_proof']): ?>
                            <a href="<?php echo htmlspecialchars($deposit['payment_proof']); ?>" target="_blank" class="btn btn-secondary" style="font-size: 12px;">View Proof</a>
                        <?php else: ?>
                            <span style="color: #9CA3AF;">No proof</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $badge_class = $deposit['status'] === 'completed' ? '#ECFDF5;color:#059669' : ($deposit['status'] === 'failed' ? '#FEF2F2;color:#DC2626' : '#FEF3C7;color:#92400E');
                        echo '<span style="padding: 6px 12px; background: ' . $badge_class . '; border-radius: 6px; font-size: 12px; font-weight: 600;">' . ucfirst($deposit['status']) . '</span>';
                        ?>
                    </td>
                    <td>
                        <?php if ($deposit['status'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <input type="text" name="admin_notes" placeholder="Notes (optional)" style="width: 150px; margin-right: 5px;">
                                <button type="submit" class="btn btn-success" style="font-size: 12px;">Approve</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="text" name="admin_notes" placeholder="Reason" style="width: 150px; margin-right: 5px;">
                                <button type="submit" class="btn btn-danger" style="font-size: 12px;">Reject</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #6B7280; font-size: 13px;"><?php echo $deposit['admin_notes'] ?: 'No notes'; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
