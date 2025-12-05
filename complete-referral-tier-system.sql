/*
  ================================================================
  COMPLETE REFERRAL + TIER SYSTEM
  ================================================================

  This migration creates a comprehensive referral and customer tier system:

  1. Commission Tiers (based on topup amount)
     - Different commission % based on first topup
     - Free shipping vouchers included
     - Only paid after topup (not just registration!)

  2. Customer Tiers (Bronze/Silver/Gold/Platinum/VVIP)
     - Auto-upgrade based on total topups
     - Tier-specific vouchers
     - Exclusive benefits per tier

  3. Enhanced Referral Tracking
     - Source user tracking
     - Commission calculation
     - Topup validation
     - Voucher generation

  4. Admin Management
     - View all referrals
     - Set commission tiers
     - Track tier upgrades
     - Voucher tier targeting
*/

-- =====================================================
-- 1. UPDATE USERS TABLE - ADD TIER FIELD
-- =====================================================

ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `referral_code` VARCHAR(20) UNIQUE DEFAULT NULL AFTER `wallet_balance`,
ADD COLUMN IF NOT EXISTS `referred_by` INT(11) DEFAULT NULL AFTER `referral_code`,
ADD COLUMN IF NOT EXISTS `total_referrals` INT(11) DEFAULT 0 AFTER `referred_by`,
ADD COLUMN IF NOT EXISTS `tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'bronze' AFTER `total_referrals`,
ADD COLUMN IF NOT EXISTS `total_topup_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `tier`,
ADD KEY IF NOT EXISTS `idx_referral_code` (`referral_code`),
ADD KEY IF NOT EXISTS `idx_referred_by` (`referred_by`),
ADD KEY IF NOT EXISTS `idx_tier` (`tier`);

-- Add foreign key for referred_by
ALTER TABLE `users`
ADD CONSTRAINT `fk_users_referred_by`
FOREIGN KEY (`referred_by`) REFERENCES `users`(`id`)
ON DELETE SET NULL;

-- =====================================================
-- 2. CREATE TOPUPS TABLE (if not exists)
-- =====================================================

CREATE TABLE IF NOT EXISTS `topups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CREATE COMMISSION TIERS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `commission_tiers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `min_topup` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `max_topup` DECIMAL(15,2) DEFAULT NULL,
  `commission_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `free_shipping_vouchers` INT(11) DEFAULT 0 COMMENT 'Number of free shipping vouchers to give',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_topup_range` (`min_topup`, `max_topup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default commission tiers
INSERT INTO `commission_tiers` (`name`, `min_topup`, `max_topup`, `commission_percent`, `free_shipping_vouchers`, `is_active`) VALUES
('Tier 1: Under 500K', 0, 499999, 3.00, 1, 1),
('Tier 2: 500K - 1M', 500000, 999999, 4.00, 2, 1),
('Tier 3: 1M - 5M', 1000000, 4999999, 5.00, 2, 1),
('Tier 4: 5M+', 5000000, NULL, 6.00, 3, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- 4. UPDATE VOUCHERS TABLE - ADD TIER TARGETING
-- =====================================================

ALTER TABLE `vouchers`
MODIFY COLUMN `type` ENUM('percentage', 'fixed', 'free_shipping') DEFAULT 'percentage',
ADD COLUMN IF NOT EXISTS `category` ENUM('discount', 'free_shipping') DEFAULT 'discount' AFTER `type`,
ADD COLUMN IF NOT EXISTS `target_tier` ENUM('all', 'bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'all' AFTER `category`,
ADD COLUMN IF NOT EXISTS `is_referral_reward` TINYINT(1) DEFAULT 0 AFTER `target_tier`,
ADD KEY IF NOT EXISTS `idx_target_tier` (`target_tier`);

-- Update existing vouchers
UPDATE `vouchers`
SET `category` = CASE
    WHEN `type` = 'free_shipping' THEN 'free_shipping'
    ELSE 'discount'
END
WHERE `category` IS NULL OR `category` = '';

-- =====================================================
-- 5. CREATE/UPDATE REFERRAL REWARDS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `referral_rewards` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` INT(11) NOT NULL COMMENT 'User who referred',
  `referred_id` INT(11) NOT NULL COMMENT 'User who was referred',
  `topup_id` INT(11) DEFAULT NULL COMMENT 'First topup that triggered reward',
  `topup_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Amount of first topup',
  `commission_tier_id` INT(11) DEFAULT NULL COMMENT 'Which commission tier was applied',
  `commission_percent` DECIMAL(5,2) DEFAULT 0.00,
  `reward_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `free_shipping_vouchers` INT(11) DEFAULT 0,
  `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer` (`referrer_id`),
  KEY `idx_referred` (`referred_id`),
  KEY `idx_topup` (`topup_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referred_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`topup_id`) REFERENCES `topups`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`commission_tier_id`) REFERENCES `commission_tiers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. CREATE ORDER VOUCHERS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `order_vouchers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `voucher_id` INT(11) NOT NULL,
  `voucher_code` VARCHAR(50) NOT NULL,
  `voucher_type` ENUM('percentage', 'fixed', 'free_shipping') NOT NULL,
  `voucher_category` ENUM('discount', 'free_shipping') NOT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_voucher` (`voucher_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. CREATE TIER UPGRADE HISTORY TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `tier_upgrades` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `from_tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') NOT NULL,
  `to_tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') NOT NULL,
  `total_topup_at_upgrade` DECIMAL(15,2) NOT NULL,
  `upgraded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. CREATE REFERRAL VOUCHERS ISSUED TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `referral_vouchers_issued` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `referral_reward_id` INT(11) NOT NULL,
  `voucher_id` INT(11) NOT NULL,
  `issued_to_user_id` INT(11) NOT NULL,
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` TINYINT(1) DEFAULT 0,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reward` (`referral_reward_id`),
  KEY `idx_voucher` (`voucher_id`),
  KEY `idx_user` (`issued_to_user_id`),
  FOREIGN KEY (`referral_reward_id`) REFERENCES `referral_rewards`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`issued_to_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. GENERATE REFERRAL CODES FOR EXISTING USERS
-- =====================================================

UPDATE `users`
SET `referral_code` = CONCAT('DRV', UPPER(SUBSTRING(MD5(CONCAT(id, email, UNIX_TIMESTAMP())), 1, 6)))
WHERE `referral_code` IS NULL AND `role` = 'customer';

-- =====================================================
-- 10. CREATE SAMPLE FREE SHIPPING VOUCHERS
-- =====================================================

INSERT INTO `vouchers` (`code`, `type`, `category`, `value`, `min_purchase`, `target_tier`, `valid_from`, `valid_until`, `is_active`) VALUES
('FREESHIP50K', 'free_shipping', 'free_shipping', 0, 50000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('FREESHIP100K', 'free_shipping', 'free_shipping', 0, 100000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('SILVER50K', 'free_shipping', 'free_shipping', 0, 50000.00, 'silver', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('GOLD30K', 'free_shipping', 'free_shipping', 0, 30000.00, 'gold', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('PLATINUM20K', 'free_shipping', 'free_shipping', 0, 20000.00, 'platinum', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('VVIP10K', 'free_shipping', 'free_shipping', 0, 10000.00, 'vvip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1)
ON DUPLICATE KEY UPDATE `code` = VALUES(`code`);

-- =====================================================
-- 11. CREATE STORED PROCEDURE: UPDATE USER TIER
-- =====================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `update_user_tier`(IN user_id_param INT)
BEGIN
    DECLARE total_topup DECIMAL(15,2);
    DECLARE current_tier VARCHAR(20);
    DECLARE new_tier VARCHAR(20);

    -- Get total completed topups
    SELECT COALESCE(SUM(amount), 0) INTO total_topup
    FROM topups
    WHERE user_id = user_id_param AND status = 'completed';

    -- Get current tier
    SELECT tier INTO current_tier FROM users WHERE id = user_id_param;

    -- Determine new tier
    SET new_tier = CASE
        WHEN total_topup >= 20000000 THEN 'vvip'
        WHEN total_topup >= 10000000 THEN 'platinum'
        WHEN total_topup >= 3000000 THEN 'gold'
        WHEN total_topup >= 1000000 THEN 'silver'
        ELSE 'bronze'
    END;

    -- Update user tier and total_topup_amount
    UPDATE users
    SET tier = new_tier, total_topup_amount = total_topup
    WHERE id = user_id_param;

    -- Log tier upgrade if changed
    IF current_tier != new_tier THEN
        INSERT INTO tier_upgrades (user_id, from_tier, to_tier, total_topup_at_upgrade)
        VALUES (user_id_param, current_tier, new_tier, total_topup);
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 12. CREATE STORED PROCEDURE: PROCESS REFERRAL REWARD
-- =====================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `process_referral_reward`(IN topup_id_param INT)
BEGIN
    DECLARE user_id_param INT;
    DECLARE topup_amount_param DECIMAL(15,2);
    DECLARE referrer_id_param INT;
    DECLARE commission_percent_val DECIMAL(5,2);
    DECLARE reward_amount DECIMAL(15,2);
    DECLARE free_ship_count INT;
    DECLARE tier_id INT;
    DECLARE first_topup_count INT;

    -- Get topup details
    SELECT user_id, amount INTO user_id_param, topup_amount_param
    FROM topups
    WHERE id = topup_id_param AND status = 'completed';

    -- Check if this is the user's first completed topup
    SELECT COUNT(*) INTO first_topup_count
    FROM topups
    WHERE user_id = user_id_param AND status = 'completed';

    -- Only process if this is the first topup
    IF first_topup_count = 1 THEN
        -- Get referrer
        SELECT referred_by INTO referrer_id_param
        FROM users
        WHERE id = user_id_param;

        -- Only process if user was referred
        IF referrer_id_param IS NOT NULL THEN
            -- Get commission tier based on topup amount
            SELECT id, commission_percent, free_shipping_vouchers
            INTO tier_id, commission_percent_val, free_ship_count
            FROM commission_tiers
            WHERE min_topup <= topup_amount_param
              AND (max_topup IS NULL OR max_topup >= topup_amount_param)
              AND is_active = 1
            ORDER BY min_topup DESC
            LIMIT 1;

            -- Calculate reward
            SET reward_amount = (topup_amount_param * commission_percent_val / 100);

            -- Update referral_rewards
            UPDATE referral_rewards
            SET status = 'completed',
                topup_id = topup_id_param,
                topup_amount = topup_amount_param,
                commission_tier_id = tier_id,
                commission_percent = commission_percent_val,
                reward_value = reward_amount,
                free_shipping_vouchers = free_ship_count,
                completed_at = NOW()
            WHERE referred_id = user_id_param AND status = 'pending';

            -- Add commission to referrer's wallet
            UPDATE users
            SET wallet_balance = wallet_balance + reward_amount
            WHERE id = referrer_id_param;
        END IF;
    END IF;

    -- Update user tier
    CALL update_user_tier(user_id_param);
END$$

DELIMITER ;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

/*
  SUMMARY OF CHANGES:

  ✅ Users Table:
     - referral_code (unique)
     - referred_by (FK to users)
     - total_referrals (count)
     - tier (bronze/silver/gold/platinum/vvip)
     - total_topup_amount

  ✅ New Tables:
     - topups (track wallet topups)
     - commission_tiers (commission % based on topup)
     - tier_upgrades (history of tier changes)
     - referral_vouchers_issued (track issued vouchers)

  ✅ Updated Tables:
     - vouchers (added target_tier, is_referral_reward)
     - referral_rewards (enhanced with topup tracking)
     - order_vouchers (track voucher usage)

  ✅ Stored Procedures:
     - update_user_tier() - Auto-upgrade tiers
     - process_referral_reward() - Calculate & pay commission

  ✅ Default Data:
     - 4 commission tiers (3%, 4%, 5%, 6%)
     - 6 sample free shipping vouchers
     - Tier-specific vouchers

  CUSTOMER TIER THRESHOLDS:
  - Bronze: Under Rp 1M
  - Silver: Rp 1M - 3M
  - Gold: Rp 3M - 10M
  - Platinum: Rp 10M - 20M
  - VVIP: Rp 20M+

  COMMISSION TIER STRUCTURE:
  - Under 500K: 3% + 1 free ship voucher
  - 500K - 1M: 4% + 2 free ship vouchers
  - 1M - 5M: 5% + 2 free ship vouchers
  - 5M+: 6% + 3 free ship vouchers

  IMPORTANT RULES:
  1. Just registering = NO reward
  2. Must complete FIRST topup to trigger reward
  3. Commission % based on first topup amount
  4. Tier auto-upgrades based on TOTAL topups
  5. Tier-specific vouchers only visible to that tier
  6. Free shipping vouchers given based on commission tier
*/
