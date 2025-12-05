<?php
require_once __DIR__ . '/../../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/admin/login.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO bank_accounts (bank_name, bank_code, account_number, account_name, branch, is_active, display_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['bank_name'],
                        $_POST['bank_code'],
                        $_POST['account_number'],
                        $_POST['account_name'],
                        $_POST['branch'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['display_order']
                    ]);
                    $_SESSION['success'] = 'Bank account added successfully!';
                    break;

                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE bank_accounts
                        SET bank_name = ?, bank_code = ?, account_number = ?, account_name = ?, branch = ?, is_active = ?, display_order = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['bank_name'],
                        $_POST['bank_code'],
                        $_POST['account_number'],
                        $_POST['account_name'],
                        $_POST['branch'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['display_order'],
                        $_POST['id']
                    ]);
                    $_SESSION['success'] = 'Bank account updated successfully!';
                    break;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = 'Bank account deleted successfully!';
                    break;

                case 'toggle':
                    $stmt = $pdo->prepare("UPDATE bank_accounts SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = 'Bank account status updated!';
                    break;
            }
        }
        redirect('/admin/settings/bank-accounts.php');
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

// Get all bank accounts
$stmt = $pdo->query("SELECT * FROM bank_accounts ORDER BY display_order ASC, id ASC");
$banks = $stmt->fetchAll();

$page_title = 'Bank Accounts Management';
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .banks-grid { display: grid; gap: 20px; margin-top: 30px; }
    .bank-card { background: var(--white); padding: 24px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); position: relative; }
    .bank-card.inactive { opacity: 0.6; background: #f9f9f9; }
    .bank-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px; }
    .bank-name { font-size: 20px; font-weight: 700; color: var(--charcoal); }
    .bank-code { display: inline-block; padding: 4px 12px; background: var(--cream); border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 12px; }
    .bank-details { margin-top: 12px; }
    .bank-detail-row { display: flex; margin-bottom: 8px; font-size: 14px; }
    .bank-detail-label { font-weight: 600; width: 140px; color: var(--grey); }
    .bank-detail-value { flex: 1; font-family: monospace; }
    .bank-actions { display: flex; gap: 8px; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.06); }
    .status-badge { position: absolute; top: 24px; right: 24px; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    .status-badge.active { background: #D1FAE5; color: #065F46; }
    .status-badge.inactive { background: #FEE2E2; color: #991B1B; }

    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
    .modal.show { display: flex; }
    .modal-content { background: var(--white); padding: 32px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .modal-header h2 { font-size: 24px; font-weight: 700; }
    .close-modal { font-size: 28px; cursor: pointer; color: var(--grey); line-height: 1; }
    .close-modal:hover { color: var(--charcoal); }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
    .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid rgba(0,0,0,0.15); border-radius: 6px; font-size: 14px; }
    .form-group input:focus { outline: none; border-color: var(--charcoal); }
    .form-group-inline { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .checkbox-group { display: flex; align-items: center; }
    .checkbox-group input { width: auto; margin-right: 8px; }

    .btn { padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; text-decoration: none; display: inline-block; }
    .btn-primary { background: var(--charcoal); color: var(--white); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
    .btn-secondary { background: var(--cream); color: var(--charcoal); }
    .btn-secondary:hover { background: #E8DCC4; }
    .btn-danger { background: #EF4444; color: var(--white); }
    .btn-danger:hover { background: #DC2626; }
    .btn-success { background: #10B981; color: var(--white); }
    .btn-success:hover { background: #059669; }
    .btn-sm { padding: 8px 16px; font-size: 13px; }

    .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #10B981; }
    .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #EF4444; }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--grey); }
    .empty-state-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
</style>

<div class="admin-content">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1>Bank Accounts Management</h1>
            <p style="color: var(--grey); margin-top: 8px;">Manage bank accounts for customer deposits</p>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">
            + Add Bank Account
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (count($banks) == 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🏦</div>
            <h3>No Bank Accounts</h3>
            <p>Add your first bank account to start accepting deposits</p>
            <button class="btn btn-primary" onclick="openAddModal()" style="margin-top: 20px;">
                + Add Bank Account
            </button>
        </div>
    <?php else: ?>
        <div class="banks-grid">
            <?php foreach ($banks as $bank): ?>
                <div class="bank-card <?php echo !$bank['is_active'] ? 'inactive' : ''; ?>">
                    <span class="status-badge <?php echo $bank['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $bank['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>

                    <div class="bank-header">
                        <div>
                            <span class="bank-name"><?php echo htmlspecialchars($bank['bank_name']); ?></span>
                            <span class="bank-code"><?php echo htmlspecialchars($bank['bank_code']); ?></span>
                        </div>
                    </div>

                    <div class="bank-details">
                        <div class="bank-detail-row">
                            <span class="bank-detail-label">Account Number:</span>
                            <span class="bank-detail-value"><?php echo htmlspecialchars($bank['account_number']); ?></span>
                        </div>
                        <div class="bank-detail-row">
                            <span class="bank-detail-label">Account Name:</span>
                            <span class="bank-detail-value"><?php echo htmlspecialchars($bank['account_name']); ?></span>
                        </div>
                        <?php if ($bank['branch']): ?>
                        <div class="bank-detail-row">
                            <span class="bank-detail-label">Branch:</span>
                            <span class="bank-detail-value"><?php echo htmlspecialchars($bank['branch']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="bank-detail-row">
                            <span class="bank-detail-label">Display Order:</span>
                            <span class="bank-detail-value"><?php echo $bank['display_order']; ?></span>
                        </div>
                    </div>

                    <div class="bank-actions">
                        <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?php echo json_encode($bank); ?>)'>
                            Edit
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $bank['id']; ?>">
                            <button type="submit" class="btn <?php echo $bank['is_active'] ? 'btn-secondary' : 'btn-success'; ?> btn-sm">
                                <?php echo $bank['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this bank account?');" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $bank['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="bankModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Bank Account</h2>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>

        <form method="POST" id="bankForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="bankId">

            <div class="form-group">
                <label>Bank Name *</label>
                <input type="text" name="bank_name" id="bankName" required placeholder="e.g., Bank Central Asia">
            </div>

            <div class="form-group-inline">
                <div class="form-group">
                    <label>Bank Code</label>
                    <input type="text" name="bank_code" id="bankCode" placeholder="e.g., BCA">
                </div>

                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="displayOrder" value="0" min="0">
                </div>
            </div>

            <div class="form-group">
                <label>Account Number *</label>
                <input type="text" name="account_number" id="accountNumber" required placeholder="e.g., 1234567890">
            </div>

            <div class="form-group">
                <label>Account Name *</label>
                <input type="text" name="account_name" id="accountName" required placeholder="e.g., PT Dorve House">
            </div>

            <div class="form-group">
                <label>Branch (Optional)</label>
                <input type="text" name="branch" id="branch" placeholder="e.g., Jakarta Pusat">
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="isActive" checked>
                    <label for="isActive" style="margin: 0;">Active (visible to customers)</label>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Bank Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Bank Account';
    document.getElementById('formAction').value = 'add';
    document.getElementById('bankForm').reset();
    document.getElementById('bankId').value = '';
    document.getElementById('isActive').checked = true;
    document.getElementById('bankModal').classList.add('show');
}

function openEditModal(bank) {
    document.getElementById('modalTitle').textContent = 'Edit Bank Account';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('bankId').value = bank.id;
    document.getElementById('bankName').value = bank.bank_name;
    document.getElementById('bankCode').value = bank.bank_code || '';
    document.getElementById('accountNumber').value = bank.account_number;
    document.getElementById('accountName').value = bank.account_name;
    document.getElementById('branch').value = bank.branch || '';
    document.getElementById('displayOrder').value = bank.display_order;
    document.getElementById('isActive').checked = bank.is_active == 1;
    document.getElementById('bankModal').classList.add('show');
}

function closeModal() {
    document.getElementById('bankModal').classList.remove('show');
}

// Close modal on background click
document.getElementById('bankModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
