<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();

// Handle step parameter
$step = $_GET['step'] ?? 'list';
$txn_id = $_GET['txn_id'] ?? null;

// Bank accounts (hardcoded for now - no bank_accounts table)
$all_banks = [
    ['id' => 1, 'bank_name' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'Dorve House'],
    ['id' => 2, 'bank_name' => 'Mandiri', 'account_number' => '0987654321', 'account_name' => 'Dorve House'],
];

// Get wallet transactions
$stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();

// If step is 'confirm', get transaction details
$pending_txn = null;
if ($step === 'confirm' && $txn_id) {
    $stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$txn_id, $_SESSION['user_id']]);
    $pending_txn = $stmt->fetch();
}

$page_title = 'My Wallet - Dorve';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .member-layout { max-width: 1400px; margin: 80px auto; padding: 0 40px; display: grid; grid-template-columns: 280px 1fr; gap: 60px; }
    .member-sidebar { position: sticky; top: 120px; height: fit-content; }
    .sidebar-header { padding: 30px; background: var(--cream); margin-bottom: 24px; border-radius: 8px; }
    .sidebar-header h3 { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 8px; }
    .sidebar-header p { font-size: 14px; color: var(--grey); }
    .sidebar-nav { list-style: none; }
    .sidebar-nav li { margin-bottom: 8px; }
    .sidebar-nav a { display: block; padding: 14px 20px; color: var(--charcoal); text-decoration: none; transition: all 0.3s; border-radius: 4px; font-size: 14px; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--cream); padding-left: 28px; }
    .logout-btn { margin-top: 24px; display: block; width: 100%; padding: 14px 20px; background: var(--white); border: 1px solid rgba(0,0,0,0.15); color: #C41E3A; text-decoration: none; text-align: center; border-radius: 4px; font-size: 14px; transition: all 0.3s; }
    .logout-btn:hover { background: #C41E3A; color: var(--white); }
    .member-content h1 { font-family: 'Playfair Display', serif; font-size: 36px; margin-bottom: 40px; }

    .wallet-balance-card { background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); color: var(--white); padding: 40px; border-radius: 16px; margin-bottom: 40px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
    .balance-label { font-size: 14px; opacity: 0.8; margin-bottom: 12px; letter-spacing: 1px; text-transform: uppercase; }
    .balance-amount { font-family: 'Playfair Display', serif; font-size: 48px; font-weight: 700; margin-bottom: 30px; }
    .wallet-actions { display: flex; gap: 16px; }
    .btn { padding: 14px 32px; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 600; transition: all 0.3s; display: inline-block; text-align: center; border: none; cursor: pointer; }
    .btn-topup { background: var(--white); color: var(--charcoal); }
    .btn-topup:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(255,255,255,0.3); }

    .quick-topup { background: var(--white); padding: 30px; border-radius: 12px; margin-bottom: 40px; border: 1px solid rgba(0,0,0,0.08); }
    .quick-topup h3 { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 24px; }
    .topup-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .topup-option { padding: 20px; border: 2px solid rgba(0,0,0,0.1); border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s; }
    .topup-option:hover { border-color: var(--charcoal); background: var(--cream); }
    .topup-option.selected { border-color: var(--charcoal); background: var(--charcoal); color: var(--white); }
    .topup-amount { font-size: 20px; font-weight: 700; }
    .custom-amount { margin-bottom: 24px; }
    .custom-amount label { display: block; margin-bottom: 8px; font-weight: 600; }
    .custom-amount input { width: 100%; padding: 14px 16px; border: 1px solid rgba(0,0,0,0.15); border-radius: 8px; font-size: 15px; }

    .payment-section h4 { margin-bottom: 16px; font-weight: 600; }
    .bank-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .bank-card { padding: 20px; border: 2px solid rgba(0,0,0,0.1); border-radius: 8px; cursor: pointer; transition: all 0.3s; background: var(--white); }
    .bank-card:hover { border-color: var(--charcoal); background: var(--cream); }
    .bank-card.selected { border-color: var(--charcoal); background: var(--charcoal); color: var(--white); }
    .bank-card.disabled { opacity: 0.4; cursor: not-allowed; background: #f5f5f5; }
    .bank-card.disabled:hover { border-color: rgba(0,0,0,0.1); background: #f5f5f5; }
    .bank-name { font-weight: 700; margin-bottom: 4px; font-size: 16px; }
    .bank-number { font-family: monospace; font-size: 14px; opacity: 0.8; }

    .confirmation-card { background: var(--white); padding: 40px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); margin-bottom: 30px; }
    .amount-display { text-align: center; padding: 40px; background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); border-radius: 12px; margin-bottom: 30px; }
    .amount-label { color: rgba(255,255,255,0.8); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
    .amount-value { color: var(--white); font-family: 'Playfair Display', serif; font-size: 48px; font-weight: 700; }
    .unique-code { color: #FCD34D; font-size: 24px; margin-top: 8px; }

    .transfer-details { background: var(--cream); padding: 24px; border-radius: 8px; margin-bottom: 24px; }
    .transfer-details h4 { margin-bottom: 16px; }
    .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.08); }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: var(--grey); }
    .detail-value { font-weight: 600; }

    .instructions { background: #FEF3C7; padding: 20px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #F59E0B; }
    .instructions h4 { margin-bottom: 12px; color: #92400E; }
    .instructions ol { margin-left: 20px; color: #78350F; }
    .instructions li { margin-bottom: 8px; }

    .upload-section { margin-top: 30px; }
    .upload-box { border: 2px dashed rgba(0,0,0,0.2); padding: 30px; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s; }
    .upload-box:hover { border-color: var(--charcoal); background: var(--cream); }
    .upload-box input { display: none; }
    .upload-label { font-weight: 600; margin-bottom: 8px; }
    .upload-hint { font-size: 14px; color: var(--grey); }

    .transactions-card { background: var(--white); padding: 30px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); }
    .transactions-card h3 { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 24px; }
    .transaction-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .transaction-item:last-child { border-bottom: none; }
    .transaction-info { flex: 1; margin-right: 20px; }
    .transaction-title { font-weight: 600; margin-bottom: 4px; }
    .transaction-date { font-size: 13px; color: var(--grey); }
    .transaction-amount { font-weight: 700; font-size: 18px; }
    .transaction-amount.credit { color: #10B981; }
    .transaction-status { padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-left: 12px; }
    .transaction-status.success { background: #D1FAE5; color: #065F46; }
    .transaction-status.pending { background: #FEF3C7; color: #92400E; }
    .transaction-status.rejected { background: #FEE2E2; color: #991B1B; }

    .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; }
    .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #10B981; }
    .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #EF4444; }
    .alert-info { background: #DBEAFE; color: #1E40AF; border: 1px solid #3B82F6; }

    .empty-state { text-align: center; padding: 60px 40px; color: var(--grey); }

    /* Modal */
    .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
    .modal-content { background-color: var(--white); margin: 10% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-header { font-size: 24px; font-weight: 700; margin-bottom: 16px; }
    .modal-body { margin-bottom: 24px; line-height: 1.6; }
    .modal-buttons { display: flex; gap: 12px; }
    .modal-buttons button { flex: 1; padding: 12px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: all 0.3s; }
    .btn-modal-primary { background: var(--charcoal); color: var(--white); }
    .btn-modal-secondary { background: var(--cream); color: var(--charcoal); }

    @media (max-width: 1024px) {
        .member-layout { grid-template-columns: 1fr; gap: 40px; }
        .member-sidebar { position: static; }
    }
</style>

<div class="member-layout">
    <aside class="member-sidebar">
        <div class="sidebar-header">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <ul class="sidebar-nav">
            <li><a href="/member/dashboard.php">Dashboard</a></li>
            <li><a href="/member/wallet.php" class="active">My Wallet</a></li>
            <li><a href="/member/orders.php">My Orders</a></li>
            <li><a href="/member/referral.php">🎁 My Referrals</a></li>
            <li><a href="/member/reviews.php">My Reviews</a></li>
            <li><a href="/member/profile.php">Edit Profile</a></li>
            <li><a href="/member/address.php">Address Book</a></li>
            <li><a href="/member/password.php">Change Password</a></li>
        </ul>

        <a href="/auth/logout.php" class="logout-btn">Logout</a>
    </aside>

    <div class="member-content">
        <h1>My Wallet</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="wallet-balance-card">
            <div class="balance-label">Available Balance</div>
            <div class="balance-amount"><?php echo formatPrice($user['wallet_balance'] ?? 0); ?></div>
            <div class="wallet-actions">
                <button class="btn btn-topup" onclick="toggleTopup()">Top Up Wallet</button>
            </div>
        </div>

        <?php if ($step === 'list'): ?>
        <!-- STEP 1: Select Amount & Payment Method -->
        <div class="quick-topup" id="topupForm" style="display: none;">
            <h3>Top Up Your Wallet</h3>

            <form action="/member/process-topup.php" method="POST" id="topupFormElement">
                <div class="topup-options">
                    <div class="topup-option" onclick="selectAmount(50000)">
                        <div class="topup-amount">Rp 50K</div>
                    </div>
                    <div class="topup-option" onclick="selectAmount(100000)">
                        <div class="topup-amount">Rp 100K</div>
                    </div>
                    <div class="topup-option" onclick="selectAmount(250000)">
                        <div class="topup-amount">Rp 250K</div>
                    </div>
                    <div class="topup-option" onclick="selectAmount(500000)">
                        <div class="topup-amount">Rp 500K</div>
                    </div>
                    <div class="topup-option" onclick="selectAmount(1000000)">
                        <div class="topup-amount">Rp 1M</div>
                    </div>
                </div>

                <div class="custom-amount">
                    <label>Or Enter Custom Amount</label>
                    <input type="number" name="amount" id="customAmount" placeholder="Minimum Rp 10,000" min="10000" required>
                </div>

                <div class="payment-section">
                    <h4>Select Destination Bank</h4>
                    <div class="bank-grid">
                        <?php foreach ($all_banks as $bank): ?>
                        <div class="bank-card <?php echo $bank['is_active'] ? '' : 'disabled'; ?>"
                             data-bank-id="<?php echo $bank['id']; ?>"
                             data-active="<?php echo $bank['is_active']; ?>"
                             onclick="selectBank(this)">
                            <div class="bank-name"><?php echo htmlspecialchars($bank['bank_name']); ?></div>
                            <div class="bank-number"><?php echo htmlspecialchars($bank['account_number']); ?></div>
                            <div style="font-size: 12px; opacity: 0.7; margin-top: 4px;">
                                a.n. <?php echo htmlspecialchars($bank['account_name']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="bank_account_id" id="selectedBankId" required>
                </div>

                <button type="submit" class="btn" style="background: var(--charcoal); color: var(--white); width: 100%;">
                    Continue to Payment
                </button>
            </form>
        </div>

        <?php elseif ($step === 'confirm' && $pending_txn): ?>
        <!-- STEP 2: Show Transfer Details & Upload Proof -->
        <div class="confirmation-card">
            <div class="amount-display">
                <div class="amount-label">Transfer Amount</div>
                <div class="amount-value"><?php echo formatPrice($pending_txn['amount_original']); ?></div>
                <div class="unique-code">+ Kode Unik: Rp <?php echo number_format($pending_txn['unique_code'], 0, ',', '.'); ?></div>
                <div class="amount-value" style="font-size: 36px; margin-top: 12px;">
                    = <?php echo formatPrice($pending_txn['amount']); ?>
                </div>
            </div>

            <div class="instructions">
                <h4>⚠️ Instruksi Transfer</h4>
                <ol>
                    <li>Transfer <strong>TEPAT</strong> sejumlah <strong><?php echo formatPrice($pending_txn['amount']); ?></strong></li>
                    <li>Ke rekening bank yang tertera di bawah</li>
                    <li>Kode unik digunakan untuk verifikasi otomatis</li>
                    <li>Upload bukti transfer setelah melakukan pembayaran</li>
                    <li>Saldo akan ditambahkan setelah admin approve</li>
                </ol>
            </div>

            <div class="transfer-details">
                <h4>Detail Transfer</h4>
                <div class="detail-row">
                    <span class="detail-label">Bank</span>
                    <span class="detail-value"><?php echo htmlspecialchars($pending_txn['bank_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor Rekening</span>
                    <span class="detail-value"><?php echo htmlspecialchars($pending_txn['account_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Atas Nama</span>
                    <span class="detail-value"><?php echo htmlspecialchars($pending_txn['account_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Jumlah Transfer</span>
                    <span class="detail-value" style="color: #EF4444; font-size: 18px;">
                        <?php echo formatPrice($pending_txn['amount']); ?>
                    </span>
                </div>
            </div>

            <form action="/member/process-topup.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_proof">
                <input type="hidden" name="txn_id" value="<?php echo $pending_txn['id']; ?>">

                <div class="upload-section">
                    <label class="upload-label">Upload Bukti Transfer (Required)</label>
                    <div class="upload-box" onclick="document.getElementById('proofFile').click()">
                        <input type="file" id="proofFile" name="proof" accept="image/*" required onchange="showFileName(this)">
                        <div class="upload-label">📤 Klik untuk upload gambar</div>
                        <div class="upload-hint" id="fileNameDisplay">Format: JPG, PNG (Max 5MB)</div>
                    </div>
                </div>

                <button type="submit" class="btn" style="background: var(--charcoal); color: var(--white); width: 100%; margin-top: 24px;">
                    Sudah Bayar & Submit
                </button>
            </form>

            <div style="text-align: center; margin-top: 16px;">
                <a href="/member/wallet.php" style="color: var(--grey); text-decoration: underline;">
                    Batal & Kembali
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transaction History -->
        <div class="transactions-card">
            <h3>Transaction History</h3>
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <p>No transactions yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $txn): ?>
                    <div class="transaction-item">
                        <div class="transaction-info">
                            <div class="transaction-title">Wallet Top Up</div>
                            <div class="transaction-date">
                                <?php echo date('d M Y, H:i', strtotime($txn['created_at'])); ?>
                                <?php if ($txn['description']): ?>
                                    - <?php echo htmlspecialchars($txn['description']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="transaction-amount credit">
                                + <?php echo formatPrice($txn['amount_original'] ?? $txn['amount']); ?>
                            </div>
                            <span class="transaction-status <?php echo $txn['payment_status']; ?>">
                                <?php echo strtoupper($txn['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for Unavailable Banks -->
<div id="bankModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">⚠️ Bank Tidak Tersedia</div>
        <div class="modal-body">
            Bank yang Anda pilih saat ini tidak tersedia. Silakan gunakan metode pembayaran lain yang tersedia atau pilih bank yang aktif.
        </div>
        <div class="modal-buttons">
            <button class="btn-modal-primary" onclick="closeModal()">OK, Mengerti</button>
        </div>
    </div>
</div>

<script>
function toggleTopup() {
    const form = document.getElementById('topupForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function selectAmount(amount) {
    document.getElementById('customAmount').value = amount;
    document.querySelectorAll('.topup-option').forEach(opt => opt.classList.remove('selected'));
    event.target.closest('.topup-option').classList.add('selected');
}

function selectBank(element) {
    const isActive = element.getAttribute('data-active') === '1';

    if (!isActive) {
        document.getElementById('bankModal').style.display = 'block';
        return;
    }

    document.querySelectorAll('.bank-card').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedBankId').value = element.getAttribute('data-bank-id');
}

function closeModal() {
    document.getElementById('bankModal').style.display = 'none';
}

function showFileName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('fileNameDisplay').textContent = '✓ ' + input.files[0].name;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bankModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
