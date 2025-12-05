<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = $_POST['order_id'] ?? 0;
$new_status = $_POST['status'] ?? '';
$courier = $_POST['courier'] ?? null;
$tracking_number = $_POST['tracking_number'] ?? null;
$estimated_days = $_POST['estimated_days'] ?? null;
$shipping_notes = $_POST['shipping_notes'] ?? null;
$cancelled_reason = $_POST['cancelled_reason'] ?? null;

// Validate required fields
if (!$order_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'processing', 'shipping', 'delivered', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// If status is shipping, require courier and tracking number
if ($new_status === 'shipping' && (empty($courier) || empty($tracking_number))) {
    echo json_encode(['success' => false, 'message' => 'Courier and tracking number are required for shipping status']);
    exit;
}

// If status is cancelled, require reason
if ($new_status === 'cancelled' && empty($cancelled_reason)) {
    echo json_encode(['success' => false, 'message' => 'Cancellation reason is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get current order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Update order
    $update_fields = ['status = ?'];
    $update_values = [$new_status];

    if ($new_status === 'shipping') {
        $update_fields[] = 'courier = ?';
        $update_fields[] = 'tracking_number = ?';
        $update_values[] = $courier;
        $update_values[] = $tracking_number;

        if ($estimated_days) {
            $update_fields[] = 'estimated_delivery_days = ?';
            $update_fields[] = 'estimated_delivery_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)';
            $update_values[] = $estimated_days;
            $update_values[] = $estimated_days;
        }

        if ($shipping_notes) {
            $update_fields[] = 'shipping_notes = ?';
            $update_values[] = $shipping_notes;
        }
    }

    if ($new_status === 'cancelled') {
        $update_fields[] = 'cancelled_reason = ?';
        $update_fields[] = 'cancelled_at = NOW()';
        $update_values[] = $cancelled_reason;
    }

    $update_values[] = $order_id;

    $sql = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($update_values);

    // Create timeline entry
    $timeline_title = match($new_status) {
        'pending' => 'Order Placed',
        'processing' => 'Order Processing',
        'shipping' => 'Order Shipped',
        'delivered' => 'Order Delivered',
        'cancelled' => 'Order Cancelled',
        default => 'Status Updated'
    };

    $timeline_description = match($new_status) {
        'pending' => 'Order has been placed and is waiting for payment confirmation',
        'processing' => 'Order is being prepared for shipment',
        'shipping' => sprintf('Package shipped via %s. Tracking: %s. Estimated delivery: %d days',
            $courier ?? 'courier',
            $tracking_number ?? 'N/A',
            $estimated_days ?? 0),
        'delivered' => 'Package has been delivered successfully',
        'cancelled' => $cancelled_reason ?? 'Order has been cancelled',
        default => 'Order status has been updated'
    };

    // Check if order_timeline table exists
    try {
        $stmt = $pdo->prepare("INSERT INTO order_timeline (order_id, status, title, description, created_by)
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $new_status, $timeline_title, $timeline_description, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Table might not exist yet, continue without timeline
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'status' => $new_status,
        'timeline_entry' => [
            'title' => $timeline_title,
            'description' => $timeline_description,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
