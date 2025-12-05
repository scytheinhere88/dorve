<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/BiteshipClient.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = $input['order_id'] ?? null;
    
    if (!$orderId) {
        throw new Exception('Order ID required');
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, 
               oa_ship.name as ship_name,
               oa_ship.phone as ship_phone,
               oa_ship.address_line as ship_address,
               oa_ship.district as ship_district,
               oa_ship.city as ship_city,
               oa_ship.province as ship_province,
               oa_ship.postal_code as ship_postal
        FROM orders o
        LEFT JOIN order_addresses oa_ship ON o.id = oa_ship.order_id AND oa_ship.type = 'shipping'
        WHERE o.id = ? AND o.payment_status = 'paid'
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Order not found or not paid');
    }
    
    // Check if shipment already exists
    $stmt = $pdo->prepare("SELECT id FROM biteship_shipments WHERE order_id = ?");
    $stmt->execute([$orderId]);
    if ($stmt->fetch()) {
        throw new Exception('Shipment already created for this order');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.weight
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    // Calculate total weight
    $totalWeight = 0;
    $biteshipItems = [];
    foreach ($items as $item) {
        $weight = floatval($item['weight'] ?? 0.5); // Default 0.5kg if not set
        $totalWeight += $weight * $item['qty'];
        
        $biteshipItems[] = [
            'name' => $item['name'],
            'description' => $item['name'],
            'value' => intval($item['price']),
            'quantity' => intval($item['qty']),
            'weight' => intval($weight * 1000) // Convert to grams
        ];
    }
    
    // Get store settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'store_%'");
    $store = [];
    while ($row = $stmt->fetch()) {
        $store[str_replace('store_', '', $row['setting_key'])] = $row['setting_value'];
    }
    
    // Prepare Biteship order data
    $biteshipOrderData = [
        'origin_contact_name' => $store['name'] ?? 'Dorve.id',
        'origin_contact_phone' => $store['phone'] ?? '+6281377378859',
        'origin_address' => $store['address'] ?? '',
        'origin_postal_code' => intval($store['postal_code'] ?? 12345),
        
        'destination_contact_name' => $order['ship_name'],
        'destination_contact_phone' => $order['ship_phone'],
        'destination_address' => $order['ship_address'],
        'destination_postal_code' => intval($order['ship_postal']),
        
        'courier_company' => $order['shipping_courier'] ?? 'jne',
        'courier_type' => $order['shipping_service'] ?? 'reg',
        
        'delivery_type' => 'now',
        'order_note' => $order['notes'] ?? '',
        'items' => $biteshipItems
    ];
    
    // Create order in Biteship
    $client = new BiteshipClient();
    $result = $client->createOrder($biteshipOrderData);
    
    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Failed to create Biteship order');
    }
    
    $biteshipData = $result['data'];
    
    // Save shipment to database
    $stmt = $pdo->prepare("
        INSERT INTO biteship_shipments (
            order_id, biteship_order_id, courier_company, courier_name,
            courier_service_name, courier_service_code, shipping_cost,
            status, waybill_id, pickup_code, destination_province,
            destination_city, destination_postal_code, weight_kg, raw_response
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $orderId,
        $biteshipData['id'] ?? '',
        $biteshipData['courier']['company'] ?? '',
        $biteshipData['courier']['name'] ?? '',
        $biteshipData['courier']['service_name'] ?? '',
        $biteshipData['courier']['service_code'] ?? '',
        $biteshipData['price'] ?? 0,
        $biteshipData['status'] ?? 'pending',
        $biteshipData['waybill_id'] ?? null,
        $biteshipData['courier']['link'] ?? null,
        $order['ship_province'],
        $order['ship_city'],
        $order['ship_postal'],
        $totalWeight,
        json_encode($biteshipData)
    ]);
    
    // Update order
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET fulfillment_status = 'waiting_print',
            tracking_number = ?
        WHERE id = ?
    ");
    $stmt->execute([$biteshipData['waybill_id'] ?? null, $orderId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Shipment created successfully',
        'biteship_order_id' => $biteshipData['id'] ?? '',
        'waybill_id' => $biteshipData['waybill_id'] ?? null
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}