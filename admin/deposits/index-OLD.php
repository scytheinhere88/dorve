<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/admin/login.php');
}

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo->beginTransaction();

        $tx_id = $_POST['transaction_id'];
        $action = $_POST['action'];

        // Get transaction details
        $stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE id = ?");
        $stmt->execute([$tx_id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new Exception('Transaction not found');
        }

        if ($action === 'approve') {
            // Update user wallet balance
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ?, total_topup = total_topup + ? WHERE id = ?");
            $stmt->execute([$transaction['amount'], $transaction['amount'], $transaction['user_id']]);

            // Update transaction status
            $stmt = $pdo->prepare("
                UPDATE wallet_transactions
                SET payment_status = 'success',
                    admin_notes = ?,
                    approved_by = ?,
                    approved_at = NOW(),
                    balance_after = (SELECT wallet_balance FROM users WHERE id = ?)
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['admin_notes'] ?? 'Approved by admin',
                $_SESSION['user_id'],
                $transaction['user_id'],
                $tx_id
            ]);

            // Update tier
            require_once __DIR__ . '/../../includes/tier-helper.php';
            updateUserTier($pdo, $transaction['user_id']);

            $_SESSION['success'] = 'Deposit approved! Wallet balance updated and tier recalculated.';

        } elseif ($action === 'reject') {
            // Update transaction status
            $stmt = $pdo->prepare("
                UPDATE wallet_transactions
                SET payment_status = 'rejected',
                    admin_notes = ?,
                    approved_by = ?,
                    approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['admin_notes'] ?? 'Rejected by admin',
                $_SESSION['user_id'],
                $tx_id
            ]);

            $_SESSION['success'] = 'Deposit rejected.';
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }

    redirect('/admin/deposits/');
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["wt.type = 'topup'"];
$params = [];

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "wt.payment_status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR wt.reference_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = implode(' AND ', $where_conditions);

// Get transactions
$stmt = $pdo->prepare("
    SELECT
        wt.*,
        u.name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        ba.bank_name,
        ba.account_number as bank_account_number,
        admin.name as approved_by_name
    FROM wallet_transactions wt
    JOIN users u ON wt.user_id = u.id
    LEFT JOIN bank_accounts ba ON wt.bank_account_id = ba.id
    LEFT JOIN users admin ON wt.approved_by = admin.id
    WHERE $where_sql
    ORDER BY
        CASE wt.payment_status
            WHEN 'pending' THEN 1
            WHEN 'success' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END,
        wt.created_at DESC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get counts
$stmt = $pdo->query("SELECT payment_status, COUNT(*) as count FROM wallet_transactions WHERE type = 'topup' GROUP BY payment_status");
$status_counts = [];
foreach ($stmt->fetchAll() as $row) {
    $status_counts[$row['payment_status']] = $row['count'];
}

$page_title = 'Deposit Requests';
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--white); padding: 24px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); }
    .stat-card.pending { border-left: 4px solid #F59E0B; }
    .stat-card.success { border-left: 4px solid #10B981; }
    .stat-card.rejected { border-left: 4px solid #EF4444; }
    .stat-label { font-size: 14px; color: var(--grey); margin-bottom: 8px; }
    .stat-value { font-size: 32px; font-weight: 700; }

    .filters { background: var(--white); padding: 20px; border-radius: 8px; margin-bottom: 24px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    .filter-group { display: flex; gap: 8px; align-items: center; }
    .filter-group label { font-size: 14px; font-weight: 600; }
    .filter-group select, .filter-group input { padding: 8px 12px; border: 1px solid rgba(0,0,0,0.15); border-radius: 6px; font-size: 14px; }

    .transactions-table { background: var(--white); border-radius: 8px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    thead { background: var(--cream); }
    th { padding: 16px; text-align: left; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 16px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: var(--cream); }

    .status-badge { padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; display: inline-block; }
    .status-badge.pending { background: #FEF3C7; color: #92400E; }
    .status-badge.success { background: #D1FAE5; color: #065F46; }
    .status-badge.rejected { background: #FEE2E2; color: #991B1B; }

    .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; text-decoration: none; display: inline-block; }
    .btn-success { background: #10B981; color: var(--white); }
    .btn-success:hover { background: #059669; }
    .btn-danger { background: #EF4444; color: var(--white); }
    .btn-danger:hover { background: #DC2626; }
    .btn-secondary { background: var(--cream); color: var(--charcoal); }
    .btn-secondary:hover { background: #E8DCC4; }

    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
    .modal.show { display: flex; }
    .modal-content { background: var(--white); padding: 32px; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--cream); }
    .modal-header h2 { font-size: 24px; font-weight: 700; }
    .close-modal { font-size: 28px; cursor: pointer; color: var(--grey); line-height: 1; }
    .close-modal:hover { color: var(--charcoal); }

    .detail-row { display: flex; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { font-weight: 600; width: 180px; color: var(--grey); font-size: 14px; }
    .detail-value { flex: 1; font-size: 14px; }

    .proof-image { max-width: 100%; border-radius: 8px; margin-top: 16px; cursor: pointer; }
    .proof-image:hover { opacity: 0.9; }

    .unique-code { display: inline-block; padding: 8px 16px; background: #FEF3C7; border: 2px dashed #F59E0B; border-radius: 6px; font-family: monospace; font-size: 20px; font-weight: 700; color: #92400E; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
    .form-group textarea { width: 100%; padding: 12px 16px; border: 1px solid rgba(0,0,0,0.15); border-radius: 6px; font-size: 14px; min-height: 100px; font-family: inherit; }

    .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #10B981; }
    .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #EF4444; }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--grey); }
</style>

<div class="admin-content">
    <h1>Deposit Requests</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card pending">
            <div class="stat-label">Pending Deposits</div>
            <div class="stat-value"><?php echo $status_counts['pending'] ?? 0; ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Approved Deposits</div>
            <div class="stat-value"><?php echo $status_counts['success'] ?? 0; ?></div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-label">Rejected Deposits</div>
            <div class="stat-value"><?php echo $status_counts['rejected'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="filters">
        <div class="filter-group">
            <label>Status:</label>
            <select name="status" onchange="this.form.submit()">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="success" <?php echo $status_filter === 'success' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Search:</label>
            <input type="text" name="search" placeholder="Name, email, or reference..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($search || $status_filter !== 'pending'): ?>
            <a href="/admin/deposits/" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Transactions Table -->
    <div class="transactions-table">
        <?php if (count($transactions) == 0): ?>
            <div class="empty-state">
                <h3>No deposit requests found</h3>
                <p>Transactions will appear here when customers make deposits</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Unique Code</th>
                        <th>Bank</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo date('d M Y', strtotime($tx['created_at'])); ?></div>
                                <div style="font-size: 12px; color: var(--grey);"><?php echo date('H:i', strtotime($tx['created_at'])); ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($tx['user_name']); ?></div>
                                <div style="font-size: 12px; color: var(--grey);"><?php echo htmlspecialchars($tx['user_email']); ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 700; font-size: 16px;"><?php echo formatPrice($tx['amount']); ?></div>
                                <div style="font-size: 12px; color: var(--grey);">Base Amount</div>
                            </td>
                            <td>
                                <?php if ($tx['unique_code']): ?>
                                    <span style="font-family: monospace; font-weight: 700; font-size: 16px; color: #F59E0B;">
                                        +<?php echo $tx['unique_code']; ?>
                                    </span>
                                    <div style="font-size: 11px; color: var(--grey); margin-top: 2px;">
                                        Total: <?php echo formatPrice($tx['amount'] + $tx['unique_code']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--grey);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tx['bank_name']): ?>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($tx['bank_name']); ?></div>
                                    <div style="font-size: 11px; color: var(--grey); font-family: monospace;">
                                        <?php echo htmlspecialchars($tx['bank_account_number']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--grey);"><?php echo htmlspecialchars($tx['payment_method']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $tx['payment_status']; ?>">
                                    <?php echo ucfirst($tx['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-secondary" onclick='viewTransaction(<?php echo json_encode($tx); ?>)'>
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal" id="transactionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Deposit Request Details</h2>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>

        <div id="transactionDetails"></div>
    </div>
</div>

<script>
function viewTransaction(tx) {
    const totalWithCode = tx.amount + (tx.unique_code || 0);

    let detailsHTML = `
        <div class="detail-row">
            <div class="detail-label">Reference ID:</div>
            <div class="detail-value" style="font-family: monospace; font-weight: 700;">${tx.reference_id || '-'}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Customer:</div>
            <div class="detail-value">
                <strong>${tx.user_name}</strong><br>
                ${tx.user_email}<br>
                ${tx.user_phone || '-'}
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Amount:</div>
            <div class="detail-value">
                <div style="font-size: 18px; font-weight: 700;">${formatPrice(tx.amount)}</div>
                ${tx.unique_code ? `
                    <div style="margin-top: 8px;">
                        <span style="font-size: 13px; color: var(--grey);">Unique Code:</span>
                        <span class="unique-code">+${tx.unique_code}</span>
                    </div>
                    <div style="margin-top: 8px; font-size: 16px;">
                        <strong>Total to Transfer: ${formatPrice(totalWithCode)}</strong>
                    </div>
                ` : ''}
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Bank Account:</div>
            <div class="detail-value">
                ${tx.bank_name ? `
                    <strong>${tx.bank_name}</strong><br>
                    <span style="font-family: monospace;">${tx.bank_account_number}</span>
                ` : '-'}
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Date:</div>
            <div class="detail-value">${new Date(tx.created_at).toLocaleString('id-ID')}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Status:</div>
            <div class="detail-value">
                <span class="status-badge ${tx.payment_status}">${tx.payment_status.toUpperCase()}</span>
            </div>
        </div>
    `;

    if (tx.proof_image) {
        detailsHTML += `
            <div class="detail-row">
                <div class="detail-label">Transfer Proof:</div>
                <div class="detail-value">
                    <a href="${tx.proof_image}" target="_blank">
                        <img src="${tx.proof_image}" class="proof-image" alt="Transfer Proof">
                    </a>
                    <div style="font-size: 12px; color: var(--grey); margin-top: 8px;">Click to view full size</div>
                </div>
            </div>
        `;
    }

    if (tx.admin_notes) {
        detailsHTML += `
            <div class="detail-row">
                <div class="detail-label">Admin Notes:</div>
                <div class="detail-value">${tx.admin_notes}</div>
            </div>
        `;
    }

    if (tx.approved_by_name && tx.approved_at) {
        detailsHTML += `
            <div class="detail-row">
                <div class="detail-label">Processed By:</div>
                <div class="detail-value">
                    ${tx.approved_by_name}<br>
                    <span style="font-size: 12px; color: var(--grey);">${new Date(tx.approved_at).toLocaleString('id-ID')}</span>
                </div>
            </div>
        `;
    }

    if (tx.payment_status === 'pending') {
        detailsHTML += `
            <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid var(--cream);">
                <form method="POST" id="approveForm">
                    <input type="hidden" name="transaction_id" value="${tx.id}">
                    <input type="hidden" name="action" value="approve">
                    <div class="form-group">
                        <label>Admin Notes (Optional):</label>
                        <textarea name="admin_notes" placeholder="Add notes about this approval..."></textarea>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-success" style="flex: 1;">
                            ✓ Approve Deposit
                        </button>
                        <button type="button" class="btn btn-danger" style="flex: 1;" onclick="rejectDeposit(${tx.id})">
                            ✗ Reject Deposit
                        </button>
                    </div>
                </form>
            </div>
        `;
    }

    document.getElementById('transactionDetails').innerHTML = detailsHTML;
    document.getElementById('transactionModal').classList.add('show');
}

function rejectDeposit(txId) {
    const notes = prompt('Reason for rejection (optional):');
    if (notes === null) return; // User cancelled

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="transaction_id" value="${txId}">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="admin_notes" value="${notes}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function closeModal() {
    document.getElementById('transactionModal').classList.remove('show');
}

function formatPrice(amount) {
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
}

// Close modal on background click
document.getElementById('transactionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
