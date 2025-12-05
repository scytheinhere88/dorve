<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/referral-helper.php';

if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$deposit_id = intval($_POST['deposit_id'] ?? 0);
$admin_notes = trim($_POST['admin_notes'] ?? '');

if ($deposit_id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Invalid deposit ID']));
}

try {
    $pdo->beginTransaction();
    
    // Get deposit info
    $stmt = $pdo->prepare("SELECT * FROM topups WHERE id = ? AND status = 'pending'");
    $stmt->execute([$deposit_id]);
    $deposit = $stmt->fetch();
    
    if (!$deposit) {
        throw new Exception('Deposit not found or already processed');
    }
    
    // Update deposit status
    $stmt = $pdo->prepare("
        UPDATE topups 
        SET status = 'completed', 
            admin_notes = ?, 
            completed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$admin_notes, $deposit_id]);
    
    // Add balance to user wallet
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->execute([$deposit['amount'], $deposit['user_id']]);
    
    // Create wallet transaction record
    $stmt = $pdo->prepare("
        INSERT INTO wallet_transactions 
        (user_id, type, amount, balance_before, balance_after, description, payment_status, reference_id, created_at)
        VALUES (?, 'topup', ?, 
            (SELECT wallet_balance - ? FROM users WHERE id = ?),
            (SELECT wallet_balance FROM users WHERE id = ?),
            ?, 'completed', ?, NOW())
    ");
    
    $description = 'Wallet topup approved by admin';
    if ($admin_notes) {
        $description .= ' - ' . $admin_notes;
    }
    
    $stmt->execute([
        $deposit['user_id'],
        $deposit['amount'],
        $deposit['amount'],
        $deposit['user_id'],
        $deposit['user_id'],
        $description,
        'TOP-' . $deposit_id
    ]);
    
    $pdo->commit();
    
    // Check if this is first topup and process referral reward
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM topups 
        WHERE user_id = ? AND status = 'completed'
    ");
    $stmt->execute([$deposit['user_id']]);
    $topup_count = $stmt->fetchColumn();
    
    if ($topup_count == 1) {
        // This is first topup, process referral reward
        $reward_result = processReferralReward($deposit['user_id'], $deposit['amount']);
        
        if ($reward_result['success']) {
            $_SESSION['success'] = 'Deposit approved successfully! Referral reward of Rp ' . number_format($reward_result['commission'], 0, ',', '.') . ' has been awarded.';
        } else {
            $_SESSION['success'] = 'Deposit approved successfully!';
        }
    } else {
        $_SESSION['success'] = 'Deposit approved successfully!';
    }
    
    redirect('/admin/deposits/index.php');
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Deposit approval error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to approve deposit: ' . $e->getMessage();
    redirect('/admin/deposits/index.php');
}
