<?php
require_once __DIR__ . '/config.php';

echo "<h1>Wallet Debug</h1>";
echo "<pre>";

// Check if user is logged in
echo "=== SESSION CHECK ===\n";
echo "Logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "\n";
if (isLoggedIn()) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
}

// Check wallet_transactions table structure
echo "\n=== WALLET_TRANSACTIONS STRUCTURE ===\n";
try {
    $stmt = $pdo->query("DESCRIBE wallet_transactions");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Check bank_accounts
echo "\n=== BANK ACCOUNTS ===\n";
try {
    $stmt = $pdo->query("SELECT id, bank_name, is_active FROM bank_accounts ORDER BY display_order");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | {$row['bank_name']} | Active: {$row['is_active']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Test INSERT
echo "\n=== TEST TRANSACTION INSERT ===\n";
if (isLoggedIn()) {
    try {
        $pdo->beginTransaction();

        $test_data = [
            'user_id' => $_SESSION['user_id'],
            'type' => 'topup',
            'amount' => 100541,
            'amount_original' => 100000,
            'unique_code' => 541,
            'balance_before' => 0,
            'balance_after' => 0,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'reference_id' => 'TEST-' . time(),
            'bank_account_id' => 1,
            'description' => 'Test transaction'
        ];

        $sql = "INSERT INTO wallet_transactions
                (user_id, type, amount, amount_original, unique_code, balance_before, balance_after,
                 payment_method, payment_status, reference_id, bank_account_id, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $test_data['user_id'],
            $test_data['type'],
            $test_data['amount'],
            $test_data['amount_original'],
            $test_data['unique_code'],
            $test_data['balance_before'],
            $test_data['balance_after'],
            $test_data['payment_method'],
            $test_data['payment_status'],
            $test_data['reference_id'],
            $test_data['bank_account_id'],
            $test_data['description']
        ]);

        $test_id = $pdo->lastInsertId();

        // Rollback (don't actually create test transaction)
        $pdo->rollBack();

        echo "✓ SUCCESS! Test transaction would have ID: $test_id\n";
        echo "✓ All columns are working correctly!\n";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "\nSQL State: " . $e->getCode() . "\n";
        echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "Not logged in - cannot test\n";
}

// Check current transactions
echo "\n=== RECENT TRANSACTIONS ===\n";
if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT id, amount, amount_original, unique_code, payment_status, created_at
                               FROM wallet_transactions
                               WHERE user_id = ?
                               ORDER BY created_at DESC
                               LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: {$row['id']} | Amount: {$row['amount']} | Original: {$row['amount_original']} | Code: {$row['unique_code']} | Status: {$row['payment_status']} | Date: {$row['created_at']}\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "</pre>";

echo "<br><a href='/member/wallet.php'>← Back to Wallet</a>";
?>
