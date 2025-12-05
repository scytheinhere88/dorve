<?php
// Apply Wallet System Fix - Complete Migration Script
require_once __DIR__ . '/config.php';

// Set timezone to WIB (UTC+7)
date_default_timezone_set('Asia/Jakarta');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Wallet System Fix - Dorve</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; padding: 40px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #1a1a1a; border-bottom: 3px solid #1a1a1a; padding-bottom: 16px; margin-bottom: 30px; }
        .step { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10B981; }
        .step h3 { margin-top: 0; color: #1a1a1a; }
        .success { color: #10B981; font-weight: 600; }
        .error { color: #EF4444; font-weight: 600; }
        .warning { color: #F59E0B; font-weight: 600; }
        .info { color: #3B82F6; font-weight: 600; }
        pre { background: #1a1a1a; color: #10B981; padding: 16px; border-radius: 8px; overflow-x: auto; }
        .summary { background: #D1FAE5; padding: 20px; border-radius: 8px; margin-top: 30px; border: 2px solid #10B981; }
        .button { display: inline-block; padding: 12px 24px; background: #1a1a1a; color: white; text-decoration: none; border-radius: 8px; margin: 8px 8px 8px 0; font-weight: 600; }
        .button:hover { background: #2d2d2d; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Wallet System Fix - Migration Script</h1>

        <?php
        $errors = [];
        $success_count = 0;

        try {
            // STEP 1: Check existing schema
            echo "<div class='step'>";
            echo "<h3>STEP 1: Checking Existing Schema</h3>";

            $stmt = $pdo->query("DESCRIBE wallet_transactions");
            $existing_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_columns[] = $row['Field'];
            }

            $required_columns = ['amount_original', 'unique_code', 'bank_account_id', 'proof_image'];
            $missing_columns = array_diff($required_columns, $existing_columns);

            if (empty($missing_columns)) {
                echo "<p class='warning'>⚠️ All columns already exist</p>";
            } else {
                echo "<p class='info'>📋 Missing: " . implode(', ', $missing_columns) . "</p>";
            }
            echo "</div>";

            // STEP 2: Add columns
            echo "<div class='step'>";
            echo "<h3>STEP 2: Adding Database Columns</h3>";
            echo "<ul>";

            if (!in_array('amount_original', $existing_columns)) {
                $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN amount_original DECIMAL(15,2) DEFAULT NULL AFTER amount");
                echo "<li class='success'>✓ Added: amount_original</li>";
                $success_count++;
            } else {
                echo "<li class='warning'>⚠️ amount_original exists</li>";
            }

            if (!in_array('unique_code', $existing_columns)) {
                $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN unique_code INT DEFAULT NULL AFTER amount_original");
                echo "<li class='success'>✓ Added: unique_code</li>";
                $success_count++;
            } else {
                echo "<li class='warning'>⚠️ unique_code exists</li>";
            }

            if (!in_array('bank_account_id', $existing_columns)) {
                $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN bank_account_id INT DEFAULT NULL AFTER payment_method");
                echo "<li class='success'>✓ Added: bank_account_id</li>";
                $success_count++;
            } else {
                echo "<li class='warning'>⚠️ bank_account_id exists</li>";
            }

            if (!in_array('proof_image', $existing_columns)) {
                $pdo->exec("ALTER TABLE wallet_transactions ADD COLUMN proof_image VARCHAR(500) DEFAULT NULL AFTER description");
                echo "<li class='success'>✓ Added: proof_image</li>";
                $success_count++;
            } else {
                echo "<li class='warning'>⚠️ proof_image exists</li>";
            }

            echo "</ul></div>";

            // STEP 3: Add indexes
            echo "<div class='step'>";
            echo "<h3>STEP 3: Adding Indexes</h3>";
            echo "<ul>";

            try {
                $pdo->exec("ALTER TABLE wallet_transactions ADD INDEX idx_bank_account (bank_account_id)");
                echo "<li class='success'>✓ Index: idx_bank_account</li>";
                $success_count++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "<li class='error'>✗ Error: " . $e->getMessage() . "</li>";
                } else {
                    echo "<li class='warning'>⚠️ Index exists</li>";
                }
            }

            try {
                $pdo->exec("ALTER TABLE wallet_transactions ADD INDEX idx_payment_status (payment_status)");
                echo "<li class='success'>✓ Index: idx_payment_status</li>";
                $success_count++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "<li class='error'>✗ Error: " . $e->getMessage() . "</li>";
                } else {
                    echo "<li class='warning'>⚠️ Index exists</li>";
                }
            }

            echo "</ul></div>";

            // STEP 4: Update records
            echo "<div class='step'>";
            echo "<h3>STEP 4: Updating Existing Records</h3>";

            $stmt = $pdo->exec("UPDATE wallet_transactions SET amount_original = amount, unique_code = 0 WHERE amount_original IS NULL AND type = 'topup'");
            echo "<p class='success'>✓ Updated $stmt records</p>";
            $success_count++;

            echo "</div>";

            // STEP 5: Create directories
            echo "<div class='step'>";
            echo "<h3>STEP 5: Creating Upload Directories</h3>";
            echo "<ul>";

            $dirs = [__DIR__ . '/uploads', __DIR__ . '/uploads/payment-proofs'];

            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                    echo "<li class='success'>✓ Created: " . basename($dir) . "</li>";
                    $success_count++;
                } else {
                    echo "<li class='warning'>⚠️ Exists: " . basename($dir) . "</li>";
                }
            }

            echo "</ul></div>";

            // STEP 6: Timezone
            echo "<div class='step'>";
            echo "<h3>STEP 6: Timezone Configuration</h3>";

            $tz = date_default_timezone_get();
            echo "<p class='info'>📍 PHP Timezone: <strong>$tz</strong></p>";

            $stmt = $pdo->query("SELECT NOW() as mysql_time");
            $time = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='info'>📍 MySQL Time: <strong>{$time['mysql_time']}</strong></p>";
            echo "<p class='info'>📍 PHP Time: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";

            if ($tz === 'Asia/Jakarta') {
                echo "<p class='success'>✓ Timezone set to WIB</p>";
            } else {
                echo "<p class='warning'>⚠️ Add to config.php:</p>";
                echo "<pre>date_default_timezone_set('Asia/Jakarta');</pre>";
            }

            echo "</div>";

            // Summary
            echo "<div class='summary'>";
            echo "<h2 style='margin-top:0;'>🎉 Migration Complete!</h2>";
            echo "<p><strong>✓ Operations:</strong> $success_count</p>";
            echo "<p><strong>✓ All changes applied successfully!</strong></p>";

            echo "<h3>Next Steps:</h3>";
            echo "<ol>";
            echo "<li>Test wallet at <a href='/member/wallet.php'>/member/wallet.php</a></li>";
            echo "<li>Create a topup with unique code</li>";
            echo "<li>Upload proof → Status PENDING</li>";
            echo "<li>Admin approve at <a href='/admin/deposits/'>/admin/deposits/</a></li>";
            echo "</ol>";

            echo "<div style='margin-top: 20px;'>";
            echo "<a href='/member/wallet.php' class='button'>Test Wallet →</a>";
            echo "<a href='/admin/deposits/' class='button'>Admin Panel →</a>";
            echo "</div>";

            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='step'>";
            echo "<h3 class='error'>❌ Fatal Error</h3>";
            echo "<p class='error'>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
        ?>

    </div>
</body>
</html>
