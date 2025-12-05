<?php
/**
 * Referral Helper Functions
 * Handles dynamic referral reward calculation and distribution
 */

function getReferralSettings() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM referral_settings");
    $settings_raw = $stmt->fetchAll();
    
    $settings = [];
    foreach ($settings_raw as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Default values if not set
    $defaults = [
        'referral_enabled' => '1',
        'commission_type' => 'percentage',
        'commission_percent' => '5.00',
        'commission_fixed' => '50000',
        'min_topup_for_reward' => '100000',
        'reward_type' => 'wallet',
        'voucher_type' => 'percentage',
        'voucher_value' => '10',
        'voucher_min_purchase' => '50000',
        'voucher_validity_days' => '30',
        'max_rewards_per_referrer' => '0',
        'require_transaction' => '1',
    ];
    
    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }
    
    return $settings;
}

function processReferralReward($referred_user_id, $topup_amount) {
    global $pdo;
    
    try {
        // Get referral settings
        $settings = getReferralSettings();
        
        // Check if referral system is enabled
        if ($settings['referral_enabled'] != '1') {
            return ['success' => false, 'message' => 'Referral system is disabled'];
        }
        
        // Get referred user info
        $stmt = $pdo->prepare("SELECT id, referred_by, name, email FROM users WHERE id = ?");
        $stmt->execute([$referred_user_id]);
        $referred_user = $stmt->fetch();
        
        if (!$referred_user || !$referred_user['referred_by']) {
            return ['success' => false, 'message' => 'No referrer found'];
        }
        
        $referrer_id = $referred_user['referred_by'];
        
        // Check if referral reward already exists
        $stmt = $pdo->prepare("SELECT id, status, reward_value FROM referral_rewards WHERE referrer_id = ? AND referred_id = ?");
        $stmt->execute([$referrer_id, $referred_user_id]);
        $existing_reward = $stmt->fetch();
        
        if (!$existing_reward) {
            return ['success' => false, 'message' => 'Referral reward record not found'];
        }
        
        // Check if already completed
        if ($existing_reward['status'] === 'completed') {
            return ['success' => false, 'message' => 'Reward already processed'];
        }
        
        // Check if topup meets minimum requirement
        if ($topup_amount < floatval($settings['min_topup_for_reward'])) {
            return ['success' => false, 'message' => 'Topup amount below minimum threshold'];
        }
        
        // Calculate commission
        $commission = 0;
        if ($settings['commission_type'] === 'percentage') {
            $commission = $topup_amount * (floatval($settings['commission_percent']) / 100);
        } else {
            $commission = floatval($settings['commission_fixed']);
        }
        
        // Round commission
        $commission = round($commission, 0);
        
        $pdo->beginTransaction();
        
        // Update referral reward status and value
        $stmt = $pdo->prepare("
            UPDATE referral_rewards 
            SET status = 'completed', 
                reward_value = ?, 
                completed_at = NOW(),
                topup_amount = ?
            WHERE id = ?
        ");
        $stmt->execute([$commission, $topup_amount, $existing_reward['id']]);
        
        // Award commission based on reward type
        if ($settings['reward_type'] === 'wallet' || $settings['reward_type'] === 'both') {
            // Add commission to referrer's wallet
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$commission, $referrer_id]);
            
            // Create wallet transaction record
            $stmt = $pdo->prepare("
                INSERT INTO wallet_transactions 
                (user_id, type, amount, balance_before, balance_after, description, payment_status, reference_id, created_at)
                VALUES (?, 'referral_commission', ?, 
                    (SELECT wallet_balance - ? FROM users WHERE id = ?),
                    (SELECT wallet_balance FROM users WHERE id = ?),
                    ?, 'completed', ?, NOW())
            ");
            
            $reference = 'REF-' . $existing_reward['id'] . '-' . time();
            $description = "Referral commission from " . $referred_user['name'] . " (" . $referred_user['email'] . ")";
            
            $stmt->execute([
                $referrer_id,
                $commission,
                $commission,
                $referrer_id,
                $referrer_id,
                $description,
                $reference
            ]);
        }
        
        // Create voucher if needed
        if ($settings['reward_type'] === 'voucher' || $settings['reward_type'] === 'both') {
            $voucher_code = 'REF' . strtoupper(substr(md5($existing_reward['id'] . time()), 0, 8));
            $voucher_value = floatval($settings['voucher_value']);
            $voucher_type = $settings['voucher_type'];
            $valid_until = date('Y-m-d 23:59:59', strtotime('+' . $settings['voucher_validity_days'] . ' days'));
            
            $stmt = $pdo->prepare("
                INSERT INTO vouchers 
                (code, type, value, min_purchase, max_uses, used_count, valid_until, is_active, created_by, created_at, description)
                VALUES (?, ?, ?, ?, 1, 0, ?, 1, 0, NOW(), ?)
            ");
            
            $description = "Referral reward voucher - " . $referred_user['name'];
            $stmt->execute([
                $voucher_code,
                $voucher_type,
                $voucher_value,
                floatval($settings['voucher_min_purchase']),
                $valid_until,
                $description
            ]);
            
            $voucher_id = $pdo->lastInsertId();
            
            // Assign voucher to referrer
            $stmt = $pdo->prepare("
                INSERT INTO user_vouchers (user_id, voucher_id, is_used, created_at)
                VALUES (?, ?, 0, NOW())
            ");
            $stmt->execute([$referrer_id, $voucher_id]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => 'Referral reward processed successfully',
            'commission' => $commission,
            'referrer_id' => $referrer_id
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

function checkAndProcessReferralReward($user_id) {
    global $pdo;
    
    // Get user's first completed topup
    $stmt = $pdo->prepare("
        SELECT amount FROM topups 
        WHERE user_id = ? AND status = 'completed' 
        ORDER BY completed_at ASC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $first_topup = $stmt->fetch();
    
    if ($first_topup) {
        return processReferralReward($user_id, $first_topup['amount']);
    }
    
    return ['success' => false, 'message' => 'No completed topup found'];
}
