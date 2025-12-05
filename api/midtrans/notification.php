<?php
/**
 * Midtrans Notification Handler (Webhook)
 * Handle payment notifications from Midtrans
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/MidtransHelper.php';

header('Content-Type: application/json');

// Log notification
file_put_contents(
    __DIR__ . '/../../logs/midtrans_notifications.log',
    date('Y-m-d H:i:s') . ' - ' . file_get_contents('php://input') . "\n",
    FILE_APPEND
);

try {
    // Get notification body
    $json = file_get_contents('php://input');
    $notification = json_decode($json, true);
    
    if (!$notification) {
        throw new Exception('Invalid notification data');
    }
    
    $orderId = $notification['order_id'] ?? '';
    $transactionStatus = $notification['transaction_status'] ?? '';
    $fraudStatus = $notification['fraud_status'] ?? '';
    $statusCode = $notification['status_code'] ?? '';
    $grossAmount = $notification['gross_amount'] ?? '';
    $signatureKey = $notification['signature_key'] ?? '';
    $transactionId = $notification['transaction_id'] ?? '';
    $paymentType = $notification['payment_type'] ?? '';
    
    // Verify signature
    $midtrans = new MidtransHelper($pdo);
    if (!$midtrans->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
        throw new Exception('Invalid signature');
    }
    
    // Determine if this is topup or order
    $isTopup = strpos($orderId, 'TOPUP-') === 0;
    
    if ($isTopup) {
        // Handle Topup
        $stmt = $pdo->prepare("SELECT * FROM wallet_topups WHERE midtrans_order_id = ?");
        $stmt->execute([$orderId]);
        $topup = $stmt->fetch();
        
        if (!$topup) {
            throw new Exception('Topup not found');
        }
        
        // Update based on status
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                // Success
                $stmt = $pdo->prepare("
                    UPDATE wallet_topups 
                    SET payment_status = 'paid', midtrans_transaction_id = ?, paid_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$transactionId, $topup['id']]);
                
                // Add balance to user wallet
                $amount = $topup['amount'];
                $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->execute([$amount, $topup['user_id']]);
            }
        } elseif ($transactionStatus == 'settlement') {
            // Success
            $stmt = $pdo->prepare("
                UPDATE wallet_topups 
                SET payment_status = 'paid', midtrans_transaction_id = ?, paid_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$transactionId, $topup['id']]);
            
            // Add balance to user wallet
            $amount = $topup['amount'];
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $topup['user_id']]);
            
        } elseif ($transactionStatus == 'pending') {
            $stmt = $pdo->prepare("UPDATE wallet_topups SET payment_status = 'pending' WHERE id = ?");
            $stmt->execute([$topup['id']]);
            
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $newStatus = $transactionStatus == 'deny' ? 'failed' : ($transactionStatus == 'expire' ? 'expired' : 'failed');
            $stmt = $pdo->prepare("UPDATE wallet_topups SET payment_status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $topup['id']]);
        }
        
    } else {
        // Handle Order
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE midtrans_order_id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        // Update based on status
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid', midtrans_transaction_id = ?, paid_at = NOW(), 
                        fulfillment_status = 'processing'
                    WHERE id = ?
                ");
                $stmt->execute([$transactionId, $order['id']]);
                
                // Clear cart
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $stmt->execute([$order['user_id']]);
                
                // Record voucher usage if any
                if (!empty($order['voucher_codes'])) {
                    $codes = explode(',', $order['voucher_codes']);
                    foreach ($codes as $code) {
                        $stmt = $pdo->prepare("SELECT id FROM vouchers WHERE code = ?");
                        $stmt->execute([trim($code)]);
                        $voucherId = $stmt->fetchColumn();
                        
                        if ($voucherId) {
                            $stmt = $pdo->prepare("
                                INSERT IGNORE INTO voucher_usage (order_id, user_id, voucher_id, voucher_code, voucher_type, discount_amount) 
                                VALUES (?, ?, ?, ?, 'discount', ?)
                            ");
                            $stmt->execute([$order['id'], $order['user_id'], $voucherId, trim($code), $order['voucher_discount']]);
                            
                            $pdo->exec("UPDATE vouchers SET total_used = total_used + 1 WHERE id = $voucherId");
                            $pdo->exec("
                                INSERT INTO user_vouchers (user_id, voucher_id, usage_count) 
                                VALUES ({$order['user_id']}, $voucherId, 1) 
                                ON DUPLICATE KEY UPDATE usage_count = usage_count + 1
                            ");
                        }
                    }
                }
            }
        } elseif ($transactionStatus == 'settlement') {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'paid', midtrans_transaction_id = ?, paid_at = NOW(), 
                    fulfillment_status = 'processing'
                WHERE id = ?
            ");
            $stmt->execute([$transactionId, $order['id']]);
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$order['user_id']]);
            
            // Record voucher usage if any
            if (!empty($order['voucher_codes'])) {
                $codes = explode(',', $order['voucher_codes']);
                foreach ($codes as $code) {
                    $stmt = $pdo->prepare("SELECT id FROM vouchers WHERE code = ?");
                    $stmt->execute([trim($code)]);
                    $voucherId = $stmt->fetchColumn();
                    
                    if ($voucherId) {
                        $stmt = $pdo->prepare("
                            INSERT IGNORE INTO voucher_usage (order_id, user_id, voucher_id, voucher_code, voucher_type, discount_amount) 
                            VALUES (?, ?, ?, ?, 'discount', ?)
                        ");
                        $stmt->execute([$order['id'], $order['user_id'], $voucherId, trim($code), $order['voucher_discount']]);
                        
                        $pdo->exec("UPDATE vouchers SET total_used = total_used + 1 WHERE id = $voucherId");
                        $pdo->exec("
                            INSERT INTO user_vouchers (user_id, voucher_id, usage_count) 
                            VALUES ({$order['user_id']}, $voucherId, 1) 
                            ON DUPLICATE KEY UPDATE usage_count = usage_count + 1
                        ");
                    }
                }
            }
            
            // AUTO CREATE BITESHIP SHIPMENT
            try {
                // Get Biteship API key
                $biteshipKey = ''; // Add your Biteship key here or from settings
                
                // Get order address
                $stmt = $pdo->prepare("SELECT * FROM order_addresses WHERE order_id = ? LIMIT 1");
                $stmt->execute([$order['id']]);
                $address = $stmt->fetch();
                
                if ($address && $order['shipping_courier'] && $order['shipping_service']) {
                    // Create Biteship shipment
                    $biteshipData = [
                        'origin_contact_name' => 'Dorve Store',
                        'origin_address' => 'Your store address',
                        'destination_contact_name' => $address['recipient_name'] ?? '',
                        'destination_contact_phone' => $address['phone'] ?? '',
                        'destination_address' => $address['address'] ?? '',
                        'courier_company' => $order['shipping_courier'],
                        'courier_type' => $order['shipping_service'],
                        'delivery_type' => 'now',
                        'order_note' => 'Order #' . $order['order_number']
                    ];
                    
                    // Call Biteship API (add proper implementation)
                    // For now, just log
                    file_put_contents(
                        __DIR__ . '/../../logs/biteship_auto_create.log',
                        date('Y-m-d H:i:s') . ' - Order #' . $order['id'] . ' ready for shipment' . "\n",
                        FILE_APPEND
                    );
                }
            } catch (Exception $e) {
                // Log error but don't fail the transaction
                file_put_contents(
                    __DIR__ . '/../../logs/biteship_errors.log',
                    date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }
            
        } elseif ($transactionStatus == 'pending') {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'pending' WHERE id = ?");
            $stmt->execute([$order['id']]);
            
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
            $stmt->execute([$order['id']]);
        }
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}