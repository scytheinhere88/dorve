<?php
session_start();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Test if webhook endpoint is accessible
    $webhookUrl = 'https://dorve.id/api/biteship/webhook.php';
    
    // Send a test POST request
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => true]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        // Update test status
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('biteship_webhook_test_status', 'ok'), ('biteship_webhook_test_time', NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Webhook Endpoint Accessible',
            'details' => "Endpoint responded with HTTP $httpCode. Configure this URL in Biteship Dashboard."
        ]);
    } else {
        throw new Exception("Webhook returned HTTP $httpCode");
    }
    
} catch (Exception $e) {
    // Update failed status
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('biteship_webhook_test_status', 'failed'), ('biteship_webhook_test_time', NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute();
    
    echo json_encode([
        'success' => false,
        'message' => 'Webhook Test Failed',
        'error' => $e->getMessage(),
        'details' => 'Make sure the webhook URL is publicly accessible'
    ]);
}