<?php
require_once __DIR__ . '/config.php';

echo "<h1>🔧 Fix Wallet Admin Columns</h1>";
echo "<pre>";

try {
    echo "=== STEP 1: Checking Current Structure ===\n";
    $stmt = $pdo->query("DESCRIBE wallet_transactions");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
        echo "✓ {$row['Field']}\n";
    }

    echo "\n=== STEP 2: Adding Missing Columns ===\n";

    // Add admin_notes
    if (!in_array('admin_notes', $existing_columns)) {
        $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN admin_notes TEXT NULL AFTER description");
        echo "✓ Added: admin_notes\n";
    } else {
        echo "⚠ Exists: admin_notes\n";
    }

    // Add approved_by
    if (!in_array('approved_by', $existing_columns)) {
        $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN approved_by INT(10) UNSIGNED NULL AFTER admin_notes");
        echo "✓ Added: approved_by\n";
    } else {
        echo "⚠ Exists: approved_by\n";
    }

    // Add approved_at
    if (!in_array('approved_at', $existing_columns)) {
        $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by");
        echo "✓ Added: approved_at\n";
    } else {
        echo "⚠ Exists: approved_at\n";
    }

    echo "\n=== STEP 3: Fixing ENUM Values ===\n";

    // Update ENUM to include 'rejected'
    $pdo->exec("ALTER TABLE wallet_transactions
                MODIFY COLUMN payment_status ENUM('pending','success','failed','rejected') NULL");
    echo "✓ Updated: payment_status ENUM (added 'rejected')\n";

    echo "\n=== STEP 4: Adding Foreign Key ===\n";

    // Add FK for approved_by (if doesn't exist)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'wallet_transactions'
        AND CONSTRAINT_NAME = 'fk_wallet_approved_by'
    ");
    $fk_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if (!$fk_exists) {
        try {
            $pdo->exec("ALTER TABLE wallet_transactions
                        ADD CONSTRAINT fk_wallet_approved_by
                        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL");
            echo "✓ Added: FK for approved_by\n";
        } catch (PDOException $e) {
            echo "⚠ FK already exists or error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠ Exists: FK for approved_by\n";
    }

    echo "\n=== STEP 5: Verification ===\n";
    $stmt = $pdo->query("DESCRIBE wallet_transactions");
    echo "Current structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nullable = $row['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
        echo "  {$row['Field']} | {$row['Type']} | $nullable\n";
    }

    echo "\n✅ SUCCESS! All columns added!\n";
    echo "\n=== TEST QUERY ===\n";

    $stmt = $pdo->query("
        SELECT id, amount, payment_status, admin_notes, approved_by, approved_at
        FROM wallet_transactions
        LIMIT 3
    ");

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Test query returned " . count($results) . " rows\n";
    foreach ($results as $row) {
        echo "  ID: {$row['id']}, Amount: {$row['amount']}, Status: {$row['payment_status']}\n";
    }

    echo "\n🎉 DONE! Admin deposits page should work now!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nTrace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";

echo "<br><a href='/admin/deposits/'>→ Go to Admin Deposits</a>";
echo " | <a href='/debug-wallet.php'>→ Debug Wallet</a>";
?>
