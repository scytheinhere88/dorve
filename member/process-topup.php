<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/upload-handler.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/member/wallet.php');
}

$action = $_POST['action'] ?? 'create';

// STEP 1: Create pending transaction with unique code
if ($action === 'create' || !isset($_POST['action'])) {
    $amount_original = floatval($_POST['amount'] ?? 0);
    $bank_account_id = intval($_POST['bank_account_id'] ?? 0);

    if ($amount_original < 10000) {
        $_SESSION['error'] = 'Minimum top up amount is Rp 10,000';
        redirect('/member/wallet.php');
    }

    if ($bank_account_id <= 0) {
        $_SESSION['error'] = 'Please select a bank account';
        redirect('/member/wallet.php');
    }

    try {
        $pdo->beginTransaction();

        // Generate unique code (1-999)
        $unique_code = rand(100, 999);
        $amount_with_code = $amount_original + $unique_code;

        $user = getCurrentUser();
        $reference = 'TOP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Create PENDING transaction (balance_after = balance_before for now, updated on approval)
        $current_balance = $user['wallet_balance'] ?? 0;

        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions
            (user_id, type, amount, amount_original, unique_code, balance_before, balance_after, payment_method, payment_status, reference_id, bank_account_id, description)
            VALUES (?, 'topup', ?, ?, ?, ?, ?, 'bank_transfer', 'pending', ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $amount_with_code,
            $amount_original,
            $unique_code,
            $current_balance,
            $current_balance, // balance_after stays same until approved
            $reference,
            $bank_account_id,
            'Wallet top up via Bank Transfer - Waiting for payment proof'
        ]);

        $txn_id = $pdo->lastInsertId();
        $pdo->commit();

        // Redirect to confirmation page
        redirect('/member/wallet.php?step=confirm&txn_id=' . $txn_id);

    } catch (Exception $e) {
        $pdo->rollBack();

        // Log error for debugging
        error_log("Topup Error: " . $e->getMessage());
        error_log("User ID: " . $_SESSION['user_id']);
        error_log("Amount: $amount_original, Bank: $bank_account_id");

        // Show detailed error in development (comment out in production)
        $_SESSION['error'] = 'Failed to create transaction: ' . $e->getMessage();
        redirect('/member/wallet.php');
    }
}

// STEP 2: Upload proof and mark as pending review
elseif ($action === 'upload_proof') {
    $txn_id = intval($_POST['txn_id'] ?? 0);

    if ($txn_id <= 0) {
        $_SESSION['error'] = 'Invalid transaction';
        redirect('/member/wallet.php');
    }

    // Verify transaction belongs to user
    $stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE id = ? AND user_id = ? AND payment_status = 'pending'");
    $stmt->execute([$txn_id, $_SESSION['user_id']]);
    $txn = $stmt->fetch();

    if (!$txn) {
        $_SESSION['error'] = 'Transaction not found or already processed';
        redirect('/member/wallet.php');
    }

    // Handle file upload
    if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'Please upload payment proof';
        redirect('/member/wallet.php?step=confirm&txn_id=' . $txn_id);
    }

    try {
        $pdo->beginTransaction();

        // Upload proof image
        $upload = handleImageUpload($_FILES['proof'], 'payment-proofs');

        if (!$upload['success']) {
            throw new Exception($upload['error'] ?? 'Failed to upload image');
        }

        // Update transaction with proof path and keep status as pending
        $stmt = $pdo->prepare("
            UPDATE wallet_transactions
            SET proof_image = ?,
                description = 'Wallet top up via Bank Transfer - Pending admin approval'
            WHERE id = ?
        ");

        $stmt->execute([$upload['path'], $txn_id]);

        $pdo->commit();

        $_SESSION['success'] = '✓ Bukti transfer berhasil dikirim! Transaksi Anda sedang diproses oleh admin. Saldo akan ditambahkan setelah verifikasi.';
        redirect('/member/wallet.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Failed to upload proof: ' . $e->getMessage();
        redirect('/member/wallet.php?step=confirm&txn_id=' . $txn_id);
    }
}

else {
    $_SESSION['error'] = 'Invalid action';
    redirect('/member/wallet.php');
}
