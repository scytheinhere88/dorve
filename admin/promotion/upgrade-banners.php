<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) die('Unauthorized');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Upgrade Banners</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; }";
echo ".success { color: #10B981; } .error { color: #EF4444; }</style></head><body>";
echo "<h1>🚀 Upgrading Banners Table...</h1>";

try {
    // Check current columns
    $stmt = $pdo->query("DESCRIBE banners");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    // Add banner_type column (popup or slider)
    if (!in_array('banner_type', $columns)) {
        $pdo->exec("ALTER TABLE banners ADD COLUMN banner_type ENUM('slider', 'popup') DEFAULT 'slider' AFTER subtitle");
        echo "<p class='success'>✓ Added banner_type column</p>";
    } else {
        echo "<p class='success'>✓ banner_type column already exists</p>";
    }
    
    // Add cta_text column
    if (!in_array('cta_text', $columns)) {
        $pdo->exec("ALTER TABLE banners ADD COLUMN cta_text VARCHAR(100) DEFAULT 'Shop Now' AFTER link_url");
        echo "<p class='success'>✓ Added cta_text column</p>";
    } else {
        echo "<p class='success'>✓ cta_text column already exists</p>";
    }
    
    echo "<h2 class='success'>✅ Upgrade Complete!</h2>";
    echo "<p><a href='/admin/promotion/index.php' style='padding: 10px 20px; background: #10B981; color: white; text-decoration: none; border-radius: 6px;'>← Back to Banners</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
