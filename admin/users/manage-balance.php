<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$user_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'User not found!';
    redirect('/admin/users/index.php');
}

// Handle balance update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $amount = floatval($_POST['amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if ($amount <= 0) {
        $error = 'Amount must be greater than 0!';
    } elseif (empty($notes)) {
        $error = 'Notes/reason is required!';
    } else {
        try {
            $pdo->beginTransaction();
            
            $current_balance = floatval($user['wallet_balance']);
            $new_balance = $current_balance;
            $transaction_type = '';
            $description = '';
            
            if ($action === 'add') {
                $new_balance = $current_balance + $amount;
                $transaction_type = 'admin_credit';
                $description = 'Balance added by admin: ' . $notes;
            } elseif ($action === 'deduct') {
                if ($amount > $current_balance) {
                    throw new Exception('Deduct amount cannot exceed current balance!');
                }
                $new_balance = $current_balance - $amount;
                $transaction_type = 'admin_debit';
                $description = 'Balance deducted by admin: ' . $notes;
                $amount = -$amount; // Negative for deduction
            }
            
            // Update user balance
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $user_id]);
            
            // Create transaction record
            $stmt = $pdo->prepare("
                INSERT INTO wallet_transactions 
                (user_id, type, amount, balance_before, balance_after, description, payment_status, reference_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'completed', ?, NOW())
            ");
            
            $reference = 'ADMIN-' . strtoupper(substr(md5(time() . $user_id), 0, 8));
            $stmt->execute([
                $user_id,
                $transaction_type,
                $amount,
                $current_balance,
                $new_balance,
                $description,
                $reference
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Balance updated successfully!';
            redirect('/admin/users/manage-balance.php?id=' . $user_id);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get transaction history
$stmt = $pdo->prepare("
    SELECT * FROM wallet_transactions 
    WHERE user_id = ? AND (type = 'admin_credit' OR type = 'admin_debit')
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

$page_title = 'Manage Balance - ' . $user['name'];
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
.balance-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 32px;
    border-radius: 16px;
    color: white;
    margin-bottom: 32px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}
.balance-amount {
    font-size: 48px;
    font-weight: 800;
    margin: 16px 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.action-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
}
.action-card {
    background: white;
    padding: 32px;
    border-radius: 12px;
    border: 2px solid #E5E7EB;
}
.action-card h3 {
    margin: 0 0 24px 0;
    font-size: 20px;
    color: #1F2937;
}
</style>

<div class="header">
    <h1>💰 Manage Balance</h1>
    <a href="/admin/users/index.php" class="btn btn-secondary">← Back to Users</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="balance-card">
    <div style="font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">Current Balance</div>
    <div style="font-size: 20px; opacity: 0.9; margin-top: 8px;"><?php echo htmlspecialchars($user['name']); ?></div>
    <div class="balance-amount">Rp <?php echo number_format($user['wallet_balance'], 0, ',', '.'); ?></div>
    <div style="font-size: 14px; opacity: 0.85;">Email: <?php echo htmlspecialchars($user['email']); ?></div>
</div>

<div class="action-cards">
    <!-- Add Balance -->
    <div class="action-card">
        <h3 style="color: #10B981;">➕ Add Balance</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label>Amount (Rp)</label>
                <input type="number" name="amount" min="1" step="1" required placeholder="50000">
            </div>
            
            <div class="form-group">
                <label>Reason/Notes *</label>
                <textarea name="notes" rows="3" required placeholder="e.g., Customer service adjustment, Compensation, etc."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="background: #10B981; width: 100%;">Add Balance</button>
        </form>
    </div>
    
    <!-- Deduct Balance -->
    <div class="action-card">
        <h3 style="color: #EF4444;">➖ Deduct Balance</h3>
        <form method="POST" onsubmit="return confirm('Are you sure you want to deduct balance from this user?');">
            <input type="hidden" name="action" value="deduct">
            
            <div class="form-group">
                <label>Amount (Rp)</label>
                <input type="number" name="amount" min="1" step="1" max="<?php echo $user['wallet_balance']; ?>" required placeholder="50000">
                <small>Max: Rp <?php echo number_format($user['wallet_balance'], 0, ',', '.'); ?></small>
            </div>
            
            <div class="form-group">
                <label>Reason/Notes *</label>
                <textarea name="notes" rows="3" required placeholder="e.g., Wrong deposit amount, Refund correction, etc."></textarea>
            </div>
            
            <button type="submit" class="btn btn-danger" style="width: 100%;">Deduct Balance</button>
        </form>
    </div>
</div>

<!-- Transaction History -->
<div class="content-container">
    <h2 style="margin-bottom: 24px;">📊 Admin Transaction History</h2>
    
    <?php if (empty($transactions)): ?>
        <p style="text-align: center; color: #6B7280; padding: 40px;">No admin transactions yet</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance Before</th>
                    <th>Balance After</th>
                    <th>Notes</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?php echo date('d M Y H:i', strtotime($tx['created_at'])); ?></td>
                        <td>
                            <?php if ($tx['type'] === 'admin_credit'): ?>
                                <span style="color: #10B981; font-weight: 600;">➕ ADD</span>
                            <?php else: ?>
                                <span style="color: #EF4444; font-weight: 600;">➖ DEDUCT</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color: <?php echo $tx['amount'] >= 0 ? '#10B981' : '#EF4444'; ?>;">
                                <?php echo $tx['amount'] >= 0 ? '+' : ''; ?>Rp <?php echo number_format($tx['amount'], 0, ',', '.'); ?>
                            </strong>
                        </td>
                        <td>Rp <?php echo number_format($tx['balance_before'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($tx['balance_after'], 0, ',', '.'); ?></td>
                        <td><small><?php echo htmlspecialchars($tx['description']); ?></small></td>
                        <td><code style="background: #F3F4F6; padding: 4px 8px; border-radius: 4px; font-size: 11px;"><?php echo $tx['reference_id']; ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
