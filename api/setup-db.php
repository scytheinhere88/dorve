<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$response = ['success' => false, 'steps' => []];

try {
    // Step 1: Fix settings table
    $stmt = $pdo->query("DESCRIBE settings");
    $columns = array_column($stmt->fetchAll(), 'Field');
    
    if (in_array('value', $columns) && !in_array('setting_value', $columns)) {
        $pdo->exec("ALTER TABLE settings CHANGE COLUMN `value` `setting_value` TEXT");
        $response['steps'][] = 'Renamed value → setting_value';
    } elseif (in_array('setting_value', $columns) && in_array('value', $columns)) {
        $pdo->exec("UPDATE settings SET setting_value = `value` WHERE setting_value IS NULL OR setting_value = ''");
        $pdo->exec("ALTER TABLE settings DROP COLUMN `value`");
        $response['steps'][] = 'Merged and dropped old value column';
    } else {
        $response['steps'][] = 'Settings table already correct';
    }
    
    // Step 2: Update Biteship API key
    $apiKey = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiRG9ydmUuaWQiLCJ1c2VySWQiOiI2OTI4NDVhNDM4MzQ5ZjAyZjdhM2VhNDgiLCJpYXQiOjE3NjQ2NTYwMjV9.xmkeeT2ghfHPe7PItX5HJ0KptlC5xbIhL1TlHWn6S1U';
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('biteship_api_key', ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$apiKey, $apiKey]);
    $response['steps'][] = 'Updated Biteship API key';
    
    // Step 3: Enable Biteship
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('biteship_enabled', '1') 
                          ON DUPLICATE KEY UPDATE setting_value = '1'");
    $stmt->execute();
    $response['steps'][] = 'Enabled Biteship integration';
    
    // Step 4: Check tables
    $tables = ['biteship_shipments', 'biteship_webhook_logs', 'print_batches'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $response['steps'][] = "Table $table exists";
        } else {
            $response['steps'][] = "WARNING: Table $table missing";
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Setup completed successfully';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
