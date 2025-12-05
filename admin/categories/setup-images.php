<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) die('Unauthorized');

echo "<h2>Setting up categories image column...</h2>\n";

try {
    // Check if image column exists in categories table
    $stmt = $pdo->query("DESCRIBE categories");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    if (!in_array('image', $columns)) {
        echo "<p>Adding image column to categories table...</p>";
        $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(500) NULL AFTER slug");
        echo "<p style='color: green;'>✓ Column added successfully!</p>";
    } else {
        echo "<p style='color: green;'>✓ image column already exists</p>";
    }
    
    // Create uploads directory
    $upload_dir = __DIR__ . '/../../uploads/categories/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "<p style='color: green;'>✓ Created uploads/categories/ directory</p>";
    } else {
        echo "<p style='color: green;'>✓ uploads/categories/ directory already exists</p>";
    }
    
    echo "<h2 style='color: green;'>✓ Setup complete!</h2>";
    echo "<p><a href='/admin/categories/index.php'>← Go to Categories</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
