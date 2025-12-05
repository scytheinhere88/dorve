<?php
/**
 * Helper Functions
 */

/**
 * Get count of pending orders for current user
 */
function getPendingOrdersCount() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return 0;
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE user_id = ? 
        AND payment_status = 'pending' 
        AND (expired_at IS NULL OR expired_at > NOW())
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

/**
 * Format price to IDR
 */
function formatPrice($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Get payment method badge HTML
 */
function getPaymentMethodBadge($method) {
    $badges = [
        'wallet' => '<span style="background: #1A1A1A; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">💰 Wallet</span>',
        'midtrans' => '<span style="background: #667EEA; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">🌐 Midtrans</span>',
        'bank_transfer' => '<span style="background: #10B981; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">🏦 Bank Transfer</span>'
    ];
    
    return $badges[$method] ?? '<span style="background: #6B7280; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">' . ucfirst($method) . '</span>';
}

/**
 * Get payment status badge HTML
 */
function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span style="background: #FEF3C7; color: #92400E; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">⏰ PENDING</span>',
        'paid' => '<span style="background: #D1FAE5; color: #065F46; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">✅ PAID</span>',
        'failed' => '<span style="background: #FEE2E2; color: #991B1B; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">❌ FAILED</span>',
        'expired' => '<span style="background: #F3F4F6; color: #6B7280; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">⏱️ EXPIRED</span>',
        'cancelled' => '<span style="background: #F3F4F6; color: #6B7280; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">✕ CANCELLED</span>'
    ];
    
    return $badges[$status] ?? '<span style="background: #E5E7EB; color: #374151; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">' . strtoupper($status) . '</span>';
}
