<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Products Table</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        pre { background: #f3f4f6; padding: 16px; border-radius: 8px; line-height: 1.6; overflow-x: auto; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .btn { display: inline-block; margin: 20px 10px 0 0; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn:hover { background: #2563eb; }
        .section { margin: 20px 0; padding: 20px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Fix Products Table - Add discount_price Column</h1>

    <pre><?php

    try {
        echo "=== STEP 1: Check Current Structure ===\n";
        $stmt = $pdo->query("DESCRIBE products");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existing_columns = array_column($columns, 'Field');

        $has_discount = in_array('discount_price', $existing_columns);

        if ($has_discount) {
            echo "<span class='warning'>⚠ discount_price already exists!</span>\n";
            echo "\nCurrent structure:\n";
            foreach ($columns as $col) {
                if ($col['Field'] === 'discount_price' || $col['Field'] === 'price') {
                    echo "  ✓ {$col['Field']} | {$col['Type']}\n";
                }
            }
        } else {
            echo "<span class='error'>✗ discount_price is MISSING</span>\n";

            echo "\n=== STEP 2: Adding discount_price Column ===\n";

            // Add the column
            $pdo->exec("
                ALTER TABLE products
                ADD COLUMN discount_price DECIMAL(15,2) NULL AFTER price
            ");

            echo "<span class='success'>✓ discount_price column added successfully!</span>\n";
            echo "\nColumn details:\n";
            echo "  - Name: discount_price\n";
            echo "  - Type: DECIMAL(15,2)\n";
            echo "  - Nullable: YES (NULL for products without discount)\n";
            echo "  - Position: After 'price' column\n";
        }

        echo "\n=== STEP 3: Verify Structure ===\n";
        $stmt = $pdo->query("DESCRIBE products");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Price-related columns:\n";
        foreach ($columns as $col) {
            if (in_array($col['Field'], ['price', 'discount_price'])) {
                $nullable = $col['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
                echo "  ✓ {$col['Field']} | {$col['Type']} | $nullable\n";
            }
        }

        echo "\n=== STEP 4: Test Query ===\n";

        // Test that we can now use discount_price in queries
        $test_query = $pdo->prepare("
            SELECT id, name, price, discount_price
            FROM products
            LIMIT 1
        ");
        $test_query->execute();

        echo "<span class='success'>✓ Query with discount_price works!</span>\n";

        $test_product = $test_query->fetch(PDO::FETCH_ASSOC);
        if ($test_product) {
            echo "\nSample product:\n";
            echo "  ID: {$test_product['id']}\n";
            echo "  Name: {$test_product['name']}\n";
            echo "  Price: Rp " . number_format($test_product['price'], 0, ',', '.') . "\n";
            echo "  Discount: " . ($test_product['discount_price'] ? 'Rp ' . number_format($test_product['discount_price'], 0, ',', '.') : 'None') . "\n";
        } else {
            echo "\nNo products in database yet (this is OK)\n";
        }

        echo "\n=== STEP 5: Update add.php Check ===\n";

        // Check if add.php file uses discount_price correctly
        $add_php_path = __DIR__ . '/admin/products/add.php';
        if (file_exists($add_php_path)) {
            $add_content = file_get_contents($add_php_path);
            if (strpos($add_content, 'discount_price') !== false) {
                echo "<span class='success'>✓ add.php uses discount_price</span>\n";
            } else {
                echo "<span class='warning'>⚠ add.php doesn't use discount_price</span>\n";
            }
        }

        echo "\n";
        echo "═══════════════════════════════════════════════════\n";
        echo "<span class='success'>✅ SUCCESS! Products table is now fixed!</span>\n";
        echo "═══════════════════════════════════════════════════\n";
        echo "\nYou can now:\n";
        echo "  ✓ Add products with discount prices\n";
        echo "  ✓ Leave discount_price empty (NULL) for no discount\n";
        echo "  ✓ Update existing products with discounts\n";

    } catch (Exception $e) {
        echo "\n<span class='error'>✗ ERROR: " . $e->getMessage() . "</span>\n";
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }

    ?></pre>

    <div class="section">
        <h3>What Was Fixed:</h3>
        <ul>
            <li><strong>Added Column:</strong> <code>discount_price DECIMAL(15,2) NULL</code></li>
            <li><strong>Position:</strong> After the <code>price</code> column</li>
            <li><strong>Allows NULL:</strong> Products without discount will have NULL value</li>
            <li><strong>Now Works:</strong> Adding products with or without discount</li>
        </ul>
    </div>

    <div class="section">
        <h3>How to Use in Admin:</h3>
        <ol>
            <li>Go to <strong>Add New Product</strong></li>
            <li>Fill in product details</li>
            <li>Set <strong>Price</strong> (required)</li>
            <li>Set <strong>Discount Price</strong> (optional)
                <ul>
                    <li>If empty: Product has no discount</li>
                    <li>If filled: Product shows original price crossed out + discount price</li>
                </ul>
            </li>
            <li>Submit - Should work now!</li>
        </ol>
    </div>

    <a href="/admin/products/add.php" class="btn">→ Try Adding Product Now</a>
    <a href="/admin/products/" class="btn">→ View All Products</a>
    <a href="/check-products-table.php" class="btn">→ Check Table Again</a>

</div>
</body>
</html>
