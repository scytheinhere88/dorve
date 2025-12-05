<?php
/**
 * API: Get Shipping Rates from Biteship
 * GET/POST /api/shipping/get-rates.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/BiteshipClient.php';

try {
    // Get request data
    $data = $_SERVER['REQUEST_METHOD'] === 'POST' 
        ? json_decode(file_get_contents('php://input'), true)
        : $_GET;
    
    // Validate required fields
    $requiredFields = ['destination_city', 'destination_postal_code', 'weight'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Get store origin from config
    $origin = [
        'postal_code' => (int)BiteshipConfig::getStore('postal_code')
    ];
    
    // Prepare destination
    $destination = [
        'postal_code' => (int)$data['destination_postal_code']
    ];
    
    // Prepare items (weight in grams)
    $weight = (int)($data['weight'] ?? 1000); // default 1kg
    $items = [[
        'name' => 'Package',
        'value' => (int)($data['declared_value'] ?? 100000),
        'quantity' => 1,
        'weight' => $weight // in grams
    ]];
    
    // Get courier codes filter
    $courierCodes = $data['couriers'] ?? 'jne,jnt,sicepat,anteraja,idexpress';
    
    // Call Biteship API
    $client = new BiteshipClient();
    $response = $client->getRates($origin, $destination, $items, $courierCodes);
    
    if (!$response['success']) {
        throw new Exception($response['error'] ?? 'Failed to get rates');
    }
    
    // Normalize response
    $rates = [];
    foreach ($response['data']['pricing'] ?? [] as $pricing) {
        $rates[] = [
            'rate_id' => $pricing['company'] . '_' . $pricing['type'] . '_' . time(),
            'courier_company' => strtolower($pricing['company'] ?? ''),
            'courier_name' => $pricing['courier_name'] ?? $pricing['company'],
            'courier_service_name' => $pricing['courier_service_name'] ?? $pricing['type'],
            'courier_service_code' => $pricing['type'] ?? '',
            'duration' => $pricing['duration'] ?? '',
            'price' => (int)($pricing['price'] ?? 0),
            'insurance_fee' => (int)($pricing['insurance_fee'] ?? 0),
            'must_use_insurance' => $pricing['must_use_insurance'] ?? false,
            'company_logo' => $pricing['logo_url'] ?? null
        ];
    }
    
    // Sort by price
    usort($rates, function($a, $b) {
        return $a['price'] <=> $b['price'];
    });
    
    echo json_encode([
        'success' => true,
        'rates' => $rates,
        'origin' => [
            'city' => BiteshipConfig::getStore('city'),
            'postal_code' => BiteshipConfig::getStore('postal_code')
        ],
        'destination' => [
            'city' => $data['destination_city'],
            'postal_code' => $data['destination_postal_code']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
