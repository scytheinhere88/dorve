<?php
/**
 * Voucher Helper Functions
 * Handles voucher validation and application logic
 */

/**
 * Validate and get voucher details
 * @param PDO $pdo Database connection
 * @param string $code Voucher code
 * @param int $user_id User ID
 * @return array|false Voucher data or false if invalid
 */
function validateVoucher($pdo, $code, $user_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM vouchers
        WHERE code = ?
        AND is_active = 1
        AND (valid_from IS NULL OR valid_from <= CURDATE())
        AND (valid_until IS NULL OR valid_until >= CURDATE())
    ");
    $stmt->execute([$code]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        return ['success' => false, 'error' => 'Voucher tidak valid atau sudah kadaluarsa'];
    }

    // Check usage limit
    if ($voucher['usage_limit'] && $voucher['used_count'] >= $voucher['usage_limit']) {
        return ['success' => false, 'error' => 'Voucher sudah mencapai batas penggunaan'];
    }

    // Check if user already used this voucher
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM voucher_usage
        WHERE voucher_id = ? AND user_id = ?
    ");
    $stmt->execute([$voucher['id'], $user_id]);
    $usage = $stmt->fetch();

    if ($usage['count'] > 0 && $voucher['usage_limit'] !== null) {
        return ['success' => false, 'error' => 'Anda sudah menggunakan voucher ini sebelumnya'];
    }

    return ['success' => true, 'voucher' => $voucher];
}

/**
 * Calculate discount amount
 * @param array $voucher Voucher data
 * @param float $subtotal Order subtotal
 * @return float Discount amount
 */
function calculateVoucherDiscount($voucher, $subtotal) {
    if ($voucher['type'] === 'free_shipping') {
        return 0; // Free shipping doesn't affect subtotal
    }

    // Check minimum purchase
    if ($voucher['min_purchase'] > 0 && $subtotal < $voucher['min_purchase']) {
        return 0;
    }

    $discount = 0;

    if ($voucher['type'] === 'percentage') {
        $discount = ($subtotal * $voucher['value']) / 100;

        // Apply max discount if set
        if ($voucher['max_discount'] && $discount > $voucher['max_discount']) {
            $discount = $voucher['max_discount'];
        }
    } elseif ($voucher['type'] === 'fixed') {
        $discount = $voucher['value'];

        // Discount can't exceed subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
    }

    return $discount;
}

/**
 * Calculate shipping discount
 * @param array $voucher Shipping voucher data
 * @param float $shipping_cost Original shipping cost
 * @param float $subtotal Order subtotal
 * @return float Shipping discount amount
 */
function calculateShippingDiscount($voucher, $shipping_cost, $subtotal) {
    if (!$voucher || $voucher['type'] !== 'free_shipping') {
        return 0;
    }

    // Check minimum purchase for free shipping
    if ($voucher['min_purchase'] > 0 && $subtotal < $voucher['min_purchase']) {
        return 0;
    }

    // Free shipping = 100% discount on shipping
    return $shipping_cost;
}

/**
 * Apply voucher to order
 * @param PDO $pdo Database connection
 * @param int $voucher_id Voucher ID
 * @param int $user_id User ID
 * @param int $order_id Order ID
 * @param float $discount_amount Discount amount
 * @return bool Success
 */
function applyVoucherToOrder($pdo, $voucher_id, $user_id, $order_id, $discount_amount) {
    try {
        // Record usage
        $stmt = $pdo->prepare("
            INSERT INTO voucher_usage (voucher_id, user_id, order_id, discount_amount)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$voucher_id, $user_id, $order_id, $discount_amount]);

        // Increment used count
        $stmt = $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$voucher_id]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get active vouchers for display
 * @param PDO $pdo Database connection
 * @param string $type Voucher type filter (optional)
 * @return array Vouchers
 */
function getActiveVouchers($pdo, $type = null) {
    $sql = "
        SELECT * FROM vouchers
        WHERE is_active = 1
        AND (valid_until IS NULL OR valid_until >= CURDATE())
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ";

    if ($type) {
        $sql .= " AND type = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query($sql);
    }

    return $stmt->fetchAll();
}

/**
 * Format voucher for display
 * @param array $voucher Voucher data
 * @return string Formatted description
 */
function formatVoucherDescription($voucher) {
    if ($voucher['type'] === 'free_shipping') {
        if ($voucher['min_purchase'] > 0) {
            return 'Gratis Ongkir min. ' . formatPrice($voucher['min_purchase']);
        }
        return 'Gratis Ongkir';
    } elseif ($voucher['type'] === 'percentage') {
        $desc = 'Diskon ' . number_format($voucher['value'], 0) . '%';
        if ($voucher['max_discount']) {
            $desc .= ' max ' . formatPrice($voucher['max_discount']);
        }
        if ($voucher['min_purchase'] > 0) {
            $desc .= ' min. ' . formatPrice($voucher['min_purchase']);
        }
        return $desc;
    } elseif ($voucher['type'] === 'fixed') {
        $desc = 'Diskon ' . formatPrice($voucher['value']);
        if ($voucher['min_purchase'] > 0) {
            $desc .= ' min. ' . formatPrice($voucher['min_purchase']);
        }
        return $desc;
    }

    return '';
}

/**
 * Check if voucher can be applied
 * @param array $voucher Voucher data
 * @param float $subtotal Order subtotal
 * @return array [success, message]
 */
function canApplyVoucher($voucher, $subtotal) {
    if ($voucher['min_purchase'] > 0 && $subtotal < $voucher['min_purchase']) {
        return [
            'success' => false,
            'message' => 'Minimum pembelian ' . formatPrice($voucher['min_purchase'])
        ];
    }

    return ['success' => true, 'message' => 'Voucher dapat digunakan'];
}
?>
