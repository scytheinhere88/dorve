<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/cart.php');
}

$cart_id = $_POST['cart_id'] ?? 0;
$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
    $stmt->execute([$cart_id]);
} elseif ($action === 'increase') {
    $stmt = $pdo->prepare("UPDATE cart_items SET qty = LEAST(qty + 1, 10) WHERE id = ?");
    $stmt->execute([$cart_id]);
} elseif ($action === 'decrease') {
    $stmt = $pdo->prepare("UPDATE cart_items SET qty = GREATEST(qty - 1, 1) WHERE id = ?");
    $stmt->execute([$cart_id]);
}

redirect('/pages/cart.php');
