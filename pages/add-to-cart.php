<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/all-products.php');
}

$product_id = $_POST['product_id'] ?? 0;
$variant_id = $_POST['variant_id'] ?? null;
$qty = intval($_POST['qty'] ?? 1);

if ($qty < 1 || $qty > 10) {
    $qty = 1;
}

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? AND variant_id <=> ?");
    $stmt->execute([$_SESSION['user_id'], $product_id, $variant_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $new_qty = min($existing['qty'] + $qty, 10);
        $stmt = $pdo->prepare("UPDATE cart_items SET qty = ? WHERE id = ?");
        $stmt->execute([$new_qty, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, variant_id, qty) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $product_id, $variant_id, $qty]);
    }
} else {
    $session_id = session_id();

    $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE session_id = ? AND product_id = ? AND variant_id <=> ?");
    $stmt->execute([$session_id, $product_id, $variant_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $new_qty = min($existing['qty'] + $qty, 10);
        $stmt = $pdo->prepare("UPDATE cart_items SET qty = ? WHERE id = ?");
        $stmt->execute([$new_qty, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart_items (session_id, product_id, variant_id, qty) VALUES (?, ?, ?, ?)");
        $stmt->execute([$session_id, $product_id, $variant_id, $qty]);
    }
}

redirect('/pages/cart.php');
