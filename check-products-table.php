<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products Table Check</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        pre { background: white; padding: 20px; border-radius: 8px; line-height: 1.6; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .fix-btn { display: inline-block; margin: 20px 0; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .fix-btn:hover { background: #2563eb; }
    </style>
</head>
<body>
<h1>🔍 Products Table Structure Check</h1>
<pre><?php

try {
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== EXISTING COLUMNS ===\n";
    foreach ($columns as $col) {
        $nullable = $col['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
        echo "{$col['Field']} | {$col['Type']} | $nullable\n";
    }

    echo "\n=== CHECKING FOR discount_price ===\n";
    $has_discount = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'discount_price') {
            $has_discount = true;
            echo "<span class='success'>✓ discount_price EXISTS</span>\n";
            break;
        }
    }

    if (!$has_discount) {
        echo "<span class='error'>✗ discount_price MISSING!</span>\n";
        echo "\n<span class='error'>This is why add product fails!</span>\n";
        echo "\n=== SOLUTION ===\n";
        echo "Need to add discount_price column to products table\n";
    }

    echo "\n=== SAMPLE PRODUCT ===\n";
    $stmt = $pdo->query("SELECT * FROM products LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        echo "Columns in actual data:\n";
        foreach (array_keys($sample) as $key) {
            echo "  - $key\n";
        }
    } else {
        echo "No products in database yet\n";
    }

} catch (Exception $e) {
    echo "<span class='error'>ERROR: " . $e->getMessage() . "</span>\n";
}

?></pre>

<a href="fix-products-table.php" class="fix-btn">→ Fix Products Table Now</a>
<br><a href="/admin/products/add.php">← Back to Add Product</a>

</body>
</html>
