<?php
session_start();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $logId = intval($_GET['id'] ?? 0);
    
    if (!$logId) {
        throw new Exception('Log ID required');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM biteship_webhook_logs WHERE id = ?");
    $stmt->execute([$logId]);
    $log = $stmt->fetch();
    
    if (!$log) {
        throw new Exception('Log not found');
    }
    
    echo json_encode([
        'success' => true,
        'log' => $log
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}