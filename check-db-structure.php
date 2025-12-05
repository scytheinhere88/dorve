<?php
require_once __DIR__ . '/config.php';

// Get products table structure
echo "=== PRODUCTS TABLE STRUCTURE ===\n";
$stmt = $pdo->query("DESCRIBE products");
$columns = $stmt->fetchAll();
foreach ($columns as $col) {
    echo sprintf("%-20s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
}

echo "\n=== CATEGORIES TABLE STRUCTURE ===\n";
$stmt = $pdo->query("DESCRIBE categories");
$columns = $stmt->fetchAll();
foreach ($columns as $col) {
    echo sprintf("%-20s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
}

echo "\n=== PRODUCT_IMAGES TABLE (if exists) ===\n";
try {
    $stmt = $pdo->query("DESCRIBE product_images");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo sprintf("%-20s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
    }
} catch (PDOException $e) {
    echo "Table product_images does not exist\n";
}

echo "\n=== REFERRAL_SETTINGS TABLE STRUCTURE ===\n";
try {
    $stmt = $pdo->query("DESCRIBE referral_settings");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo sprintf("%-20s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
    }
} catch (PDOException $e) {
    echo "Table referral_settings does not exist\n";
}
