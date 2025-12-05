<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/BiteshipClient.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $client = new BiteshipClient();
    
    // Test with a simple area search (Jakarta)
    $result = $client->getAreas('Jakarta');
    
    if ($result['success']) {
        // Update test status in settings
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('biteship_api_test_status', 'connected'), ('biteship_api_test_time', NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute();
        
        $areaCount = count($result['data']['areas'] ?? []);
        
        echo json_encode([
            'success' => true,
            'message' => 'Biteship API Connection Successful!',
            'details' => "Found $areaCount areas for Jakarta. API is working correctly."
        ]);
    } else {
        // Update failed status
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('biteship_api_test_status', 'failed'), ('biteship_api_test_time', NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute();
        
        throw new Exception($result['error'] ?? 'Unknown API error');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API Test Failed',
        'error' => $e->getMessage()
    ]);
}