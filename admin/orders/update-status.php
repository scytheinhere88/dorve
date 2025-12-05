<?php
session_start();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderIds = $input['order_ids'] ?? [];
    $newStatus = $input['status'] ?? '';
    
    if (empty($orderIds) || empty($newStatus)) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate status
    $validStatuses = ['new', 'waiting_print', 'waiting_pickup', 'in_transit', 'delivered', 'cancelled', 'returned'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('Invalid status');
    }
    
    // Update orders
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $pdo->prepare("UPDATE orders SET fulfillment_status = ? WHERE id IN ($placeholders)");
    $stmt->execute(array_merge([$newStatus], $orderIds));
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'updated_count' => $stmt->rowCount()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}