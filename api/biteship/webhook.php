<?php
/**
 * Biteship Webhook Handler
 * POST /api/biteship/webhook.php
 * 
 * Configure this URL in Biteship Dashboard:
 * https://dorve.id/api/biteship/webhook.php
 */

require_once __DIR__ . '/../../config.php';

// Get raw POST body
$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true);

// Log webhook
try {
    $stmt = $pdo->prepare("
        INSERT INTO biteship_webhook_logs (event, biteship_order_id, payload, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $payload['event'] ?? 'unknown',
        $payload['data']['id'] ?? null,
        $rawPayload
    ]);
    $logId = $pdo->lastInsertId();
} catch (Exception $e) {
    error_log('Biteship webhook log error: ' . $e->getMessage());
}

try {
    $event = $payload['event'] ?? null;
    $data = $payload['data'] ?? [];
    
    if (empty($event) || empty($data['id'])) {
        throw new Exception('Invalid webhook payload');
    }
    
    $biteshipOrderId = $data['id'];
    
    // Find shipment
    $stmt = $pdo->prepare("SELECT * FROM biteship_shipments WHERE biteship_order_id = ?");
    $stmt->execute([$biteshipOrderId]);
    $shipment = $stmt->fetch();
    
    if (!$shipment) {
        throw new Exception('Shipment not found: ' . $biteshipOrderId);
    }
    
    $orderId = $shipment['order_id'];
    
    // Handle different events
    switch ($event) {
        case 'order.status':
            $status = strtolower($data['status'] ?? '');
            
            // Update shipment status
            $stmt = $pdo->prepare("
                UPDATE biteship_shipments 
                SET status = ?, 
                    waybill_id = COALESCE(?, waybill_id),
                    pickup_code = COALESCE(?, pickup_code),
                    delivery_date = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $status,
                $data['waybill_id'] ?? null,
                $data['pickup_code'] ?? null,
                $data['delivery_date'] ?? null,
                $shipment['id']
            ]);
            
            // Map status to fulfillment_status
            $fulfillmentStatus = null;
            switch ($status) {
                case 'confirmed':
                case 'allocated':
                    $fulfillmentStatus = 'waiting_pickup';
                    break;
                case 'picking_up':
                case 'picked':
                    $fulfillmentStatus = 'waiting_pickup';
                    break;
                case 'dropping_off':
                case 'in_transit':
                    $fulfillmentStatus = 'in_transit';
                    break;
                case 'delivered':
                    $fulfillmentStatus = 'delivered';
                    break;
                case 'cancelled':
                case 'rejected':
                    $fulfillmentStatus = 'cancelled';
                    break;
                case 'returned':
                    $fulfillmentStatus = 'returned';
                    break;
            }
            
            // Update order fulfillment status and tracking
            if ($fulfillmentStatus) {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET fulfillment_status = ?,
                        tracking_number = COALESCE(?, tracking_number),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $fulfillmentStatus,
                    $data['waybill_id'] ?? null,
                    $orderId
                ]);
            }
            break;
            
        case 'order.waybill_id':
            $waybillId = $data['waybill_id'] ?? null;
            if ($waybillId) {
                // Update shipment
                $stmt = $pdo->prepare("UPDATE biteship_shipments SET waybill_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$waybillId, $shipment['id']]);
                
                // Update order
                $stmt = $pdo->prepare("UPDATE orders SET tracking_number = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$waybillId, $orderId]);
            }
            break;
    }
    
    // Mark log as processed
    if (isset($logId)) {
        $stmt = $pdo->prepare("UPDATE biteship_webhook_logs SET processed = 1 WHERE id = ?");
        $stmt->execute([$logId]);
    }
    
    // Always return 200 OK
    http_response_code(200);
    echo 'ok';
    
} catch (Exception $e) {
    error_log('Biteship webhook error: ' . $e->getMessage());
    
    // Update log with error
    if (isset($logId)) {
        try {
            $stmt = $pdo->prepare("UPDATE biteship_webhook_logs SET error_message = ? WHERE id = ?");
            $stmt->execute([$e->getMessage(), $logId]);
        } catch (Exception $e2) {
            // Ignore
        }
    }
    
    // Still return 200 to prevent retries
    http_response_code(200);
    echo 'error';
}
