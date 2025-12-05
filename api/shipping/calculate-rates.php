<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/BiteshipClient.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Get destination from input
    $destination = [
        'postal_code' => $input['postal_code'] ?? null,
        'area_id' => $input['area_id'] ?? null,
        'latitude' => $input['latitude'] ?? null,
        'longitude' => $input['longitude'] ?? null
    ];
    
    // Get items
    $items = $input['items'] ?? [];
    
    if (empty($items)) {
        throw new Exception('No items provided');
    }
    
    // Get store origin from settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'store_%'");
    $storeSettings = [];
    while ($row = $stmt->fetch()) {
        $storeSettings[$row['setting_key']] = $row['setting_value'];
    }
    
    $origin = [
        'postal_code' => $storeSettings['store_postal_code'] ?? '12345'
    ];
    
    // Initialize Biteship client
    $client = new BiteshipClient();
    
    // Get courier codes from settings
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'biteship_default_couriers'");
    $stmt->execute();
    $courierCodes = $stmt->fetchColumn() ?: 'jne,jnt,sicepat,anteraja,idexpress';
    
    // Get rates
    $result = $client->getRates($origin, $destination, $items, $courierCodes);
    
    if ($result['success']) {
        $pricing = $result['data']['pricing'] ?? [];
        
        // Format response - ONLY AVAILABLE COURIERS
        $rates = [];
        $unavailableCouriers = [];
        
        foreach ($pricing as $rate) {
            $price = floatval($rate['price'] ?? 0);
            $courierCompany = $rate['courier_company'] ?? '';
            $courierService = $rate['courier_service_name'] ?? '';
            
            // Only include couriers with valid price (available)
            if ($price > 0 && !empty($courierCompany)) {
                $rates[] = [
                    'courier_company' => $courierCompany,
                    'courier_name' => $rate['courier_name'] ?? '',
                    'courier_service_name' => $courierService,
                    'rate_id' => $rate['rate_id'] ?? '',
                    'price' => $price,
                    'duration' => $rate['duration'] ?? '',
                    'description' => $rate['description'] ?? '',
                    'available' => true
                ];
            } else {
                // Track unavailable couriers for logging
                $unavailableCouriers[] = $courierCompany . ' - ' . $courierService;
            }
        }
        
        // Sort by price (cheapest first)
        usort($rates, function($a, $b) {
            return $a['price'] - $b['price'];
        });
        
        echo json_encode([
            'success' => true,
            'rates' => $rates,
            'total_available' => count($rates),
            'unavailable_couriers' => $unavailableCouriers, // For debugging
            'message' => count($rates) > 0 ? 'Rates available' : 'No couriers available for this area'
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Failed to get shipping rates');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}