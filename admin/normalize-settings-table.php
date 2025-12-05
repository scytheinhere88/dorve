<?php
require_once __DIR__ . '/../config.php';

if (!isAdmin()) die('Unauthorized');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Normalize Settings Table</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #F5F7FA; }";
echo "h1 { color: #1F2937; } .success { color: #10B981; } .error { color: #EF4444; }";
echo ".box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #E5E7EB; }</style></head><body>";

echo "<h1>🔧 Normalizing Settings Table...</h1>";

try {
    // Check current structure
    $stmt = $pdo->query("DESCRIBE settings");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    echo "<div class='box'>";
    echo "<h2>Current Columns:</h2>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
    // Check if we need to migrate
    if (in_array('value', $columns) && !in_array('setting_value', $columns)) {
        echo "<h3>Migration Needed: 'value' → 'setting_value'</h3>";
        
        // Rename column
        $pdo->exec("ALTER TABLE settings CHANGE COLUMN `value` `setting_value` TEXT");
        echo "<p class='success'>✓ Renamed column 'value' to 'setting_value'</p>";
        
    } elseif (in_array('setting_value', $columns) && in_array('value', $columns)) {
        echo "<h3>Both columns exist! Merging...</h3>";
        
        // Copy data from 'value' to 'setting_value' if null
        $pdo->exec("UPDATE settings SET setting_value = `value` WHERE setting_value IS NULL OR setting_value = ''");
        echo "<p class='success'>✓ Merged data from 'value' to 'setting_value'</p>";
        
        // Drop old column
        $pdo->exec("ALTER TABLE settings DROP COLUMN `value`");
        echo "<p class='success'>✓ Dropped old 'value' column</p>";
        
    } elseif (in_array('setting_value', $columns)) {
        echo "<p class='success'>✓ Already using 'setting_value' column. No migration needed.</p>";
    }
    
    echo "</div>";
    
    echo "<div class='box' style='background: #F0FDF4; border-color: #10B981;'>";
    echo "<h2 style='color: #10B981;'>✅ Normalization Complete!</h2>";
    echo "<p>Settings table is now using standard 'setting_value' column.</p>";
    echo "<p><a href='/admin/setup-biteship.php' style='padding: 10px 20px; background: #3B82F6; color: white; text-decoration: none; border-radius: 6px;'>→ Continue to Biteship Setup</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box' style='background: #FEE2E2; border-color: #EF4444;'>";
    echo "<h2 style='color: #EF4444;'>❌ Error</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
