<?php
// Direct DB test and setup
require_once __DIR__ . '/config.php';

echo "=== DORVE.ID DATABASE SETUP ===\n\n";

try {
    // Step 1: Check settings table
    echo "1. Checking settings table structure...\n";
    $stmt = $pdo->query("DESCRIBE settings");
    $columns = array_column($stmt->fetchAll(), 'Field');
    echo "   Current columns: " . implode(', ', $columns) . "\n";
    
    // Fix column if needed
    if (in_array('value', $columns) && !in_array('setting_value', $columns)) {
        echo "   Renaming 'value' to 'setting_value'...\n";
        $pdo->exec("ALTER TABLE settings CHANGE COLUMN `value` `setting_value` TEXT");
        echo "   ✓ Done\n";
    } elseif (in_array('setting_value', $columns) && in_array('value', $columns)) {
        echo "   Merging duplicate columns...\n";
        $pdo->exec("UPDATE settings SET setting_value = `value` WHERE setting_value IS NULL OR setting_value = ''");
        $pdo->exec("ALTER TABLE settings DROP COLUMN `value`");
        echo "   ✓ Done\n";
    } else {
        echo "   ✓ Already correct\n";
    }
    
    // Step 2: Update Biteship settings
    echo "\n2. Updating Biteship API key...\n";
    $newApiKey = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiRG9ydmUuaWQiLCJ1c2VySWQiOiI2OTI4NDVhNDM4MzQ5ZjAyZjdhM2VhNDgiLCJpYXQiOjE3NjQ2NTYwMjV9.xmkeeT2ghfHPe7PItX5HJ0KptlC5xbIhL1TlHWn6S1U';
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('biteship_api_key', ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$newApiKey, $newApiKey]);
    echo "   ✓ API key updated\n";
    
    // Step 3: Verify
    echo "\n3. Verifying Biteship configuration...\n";
    $stmt = $pdo->query("SELECT setting_key, LEFT(setting_value, 30) as val FROM settings WHERE setting_key LIKE 'biteship%' OR setting_key LIKE 'store_%'");
    while ($row = $stmt->fetch()) {
        echo "   - {$row['setting_key']}: {$row['val']}...\n";
    }
    
    // Step 4: Check tables
    echo "\n4. Checking Biteship tables...\n";
    $requiredTables = ['biteship_shipments', 'biteship_webhook_logs', 'print_batches', 'order_addresses'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ $table exists\n";
        } else {
            echo "   ✗ $table MISSING\n";
        }
    }
    
    echo "\n✅ SETUP COMPLETE!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
?>
