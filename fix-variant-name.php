<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix variant_name Column</title>
    <style>
        body { font-family: -apple-system, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; }
        pre { background: #f3f4f6; padding: 16px; border-radius: 8px; line-height: 1.6; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Fix variant_name Column</h1>
    <pre><?php

    try {
        echo "=== Checking product_variants table ===\n";
        
        $stmt = $pdo->query("DESCRIBE product_variants");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Current columns:\n";
        foreach ($columns as $col) {
            echo "  {$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Default']}\n";
        }
        
        echo "\n=== Fixing variant_name column ===\n";
        
        // Option 1: Make it nullable with default
        $pdo->exec("ALTER TABLE product_variants MODIFY COLUMN variant_name VARCHAR(255) NULL DEFAULT 'Default'");
        
        echo "<span class='success'>✓ variant_name now has default value 'Default'</span>\n";
        
        // Also check if we need to update existing NULL values
        $pdo->exec("UPDATE product_variants SET variant_name = 'Default' WHERE variant_name IS NULL OR variant_name = ''");
        
        echo "<span class='success'>✓ Updated existing NULL values</span>\n";
        
        echo "\n=== Verification ===\n";
        $stmt = $pdo->query("DESCRIBE product_variants");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'variant_name') {
                echo "variant_name: {$col['Type']} | Default: {$col['Default']}\n";
            }
        }
        
        echo "\n<span class='success'>✅ DONE! Can add products now!</span>\n";
        
    } catch (Exception $e) {
        echo "<span class='error'>✗ ERROR: " . $e->getMessage() . "</span>\n";
    }

    ?></pre>
    <a href="/admin/products/add.php" style="display:inline-block;margin-top:20px;padding:12px 24px;background:#3b82f6;color:white;text-decoration:none;border-radius:6px;">Try Adding Product</a>
</div>
</body>
</html>
