<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) die('Unauthorized');

echo "<h2>Setting up product_images table...</h2>\n";

try {
    // Check if product_images table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'product_images'")->fetchAll();
    
    if (empty($tables)) {
        echo "<p>Creating product_images table...</p>";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS product_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                image_path VARCHAR(500) NOT NULL,
                is_primary TINYINT(1) DEFAULT 0,
                sort_order INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product (product_id),
                INDEX idx_primary (product_id, is_primary)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p style='color: green;'>✓ Table created successfully!</p>";
        
        // Migrate existing product images to product_images table
        echo "<p>Migrating existing product images...</p>";
        $stmt = $pdo->query("SELECT id, image FROM products WHERE image IS NOT NULL AND image != ''");
        $products = $stmt->fetchAll();
        
        $migrated = 0;
        foreach ($products as $product) {
            $pdo->prepare("
                INSERT INTO product_images (product_id, image_path, is_primary, sort_order)
                VALUES (?, ?, 1, 1)
            ")->execute([$product['id'], $product['image']]);
            $migrated++;
        }
        
        echo "<p style='color: green;'>✓ Migrated $migrated product images!</p>";
    } else {
        echo "<p style='color: green;'>✓ product_images table already exists</p>";
    }
    
    echo "<h2 style='color: green;'>✓ Setup complete!</h2>";
    echo "<p><a href='/admin/products/add.php'>← Go to Add Product</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
