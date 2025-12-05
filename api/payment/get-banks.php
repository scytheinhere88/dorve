<?php
/**
 * API: Get Available Banks for Direct Transfer
 */
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

try {
    // Get active banks
    $stmt = $pdo->query("
        SELECT bank_code, bank_name, account_number, account_name, icon
        FROM payment_banks 
        WHERE is_active = 1 
        ORDER BY sort_order
    ");
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($banks);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
