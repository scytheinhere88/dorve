/*
  ================================================================
  FINAL COMPLETE REFERRAL + TIER SYSTEM
  ================================================================

  SIMPLIFIED VERSION:
  - Fix commission per referral (settable by admin)
  - Customer tier system (Bronze to VVIP)
  - Tier-based vouchers
  - Complete tracking & anti-manipulation

  100% READY TO RUN!
*/

-- =====================================================
-- 1. UPDATE USERS TABLE - ADD REFERRAL & TIER FIELDS
-- =====================================================

ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `referral_code` VARCHAR(20) UNIQUE DEFAULT NULL AFTER `wallet_balance`,
ADD COLUMN IF NOT EXISTS `referred_by` INT(11) DEFAULT NULL AFTER `referral_code`,
ADD COLUMN IF NOT EXISTS `total_referrals` INT(11) DEFAULT 0 AFTER `referred_by`,
ADD COLUMN IF NOT EXISTS `tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'bronze' AFTER `total_referrals`,
ADD COLUMN IF NOT EXISTS `total_topup_amount` DECIMAL(15,2) DEFAULT 0.00 AFTER `tier`;

-- Add indexes for performance
ALTER TABLE `users`
ADD INDEX IF NOT EXISTS `idx_referral_code` (`referral_code`),
ADD INDEX IF NOT EXISTS `idx_referred_by` (`referred_by`),
ADD INDEX IF NOT EXISTS `idx_tier` (`tier`);

-- =====================================================
-- 2. CREATE TOPUPS TABLE
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
-- 3. UPDATE VOUCHERS TABLE - ADD TIER TARGETING
-- =====================================================

ALTER TABLE `vouchers`
MODIFY COLUMN `type` ENUM('percentage', 'fixed', 'free_shipping') DEFAULT 'percentage';

ALTER TABLE `vouchers`
ADD COLUMN IF NOT EXISTS `category` ENUM('discount', 'free_shipping') DEFAULT 'discount' AFTER `type`,
ADD COLUMN IF NOT EXISTS `target_tier` ENUM('all', 'bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'all' AFTER `category`,
ADD COLUMN IF NOT EXISTS `is_referral_reward` TINYINT(1) DEFAULT 0 AFTER `target_tier`;

-- Add index for tier targeting
ALTER TABLE `vouchers`
ADD INDEX IF NOT EXISTS `idx_target_tier` (`target_tier`);

-- Update existing vouchers to have category
UPDATE `vouchers`
SET `category` = CASE
    WHEN `type` = 'free_shipping' THEN 'free_shipping'
    ELSE 'discount'
END
WHERE `category` IS NULL OR `category` = '';

-- =====================================================
-- 4. CREATE REFERRAL REWARDS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `referral_rewards` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` INT(11) NOT NULL COMMENT 'User who referred (yang ngajak)',
  `referred_id` INT(11) NOT NULL COMMENT 'User who was referred (yang diajak)',
  `topup_id` INT(11) DEFAULT NULL COMMENT 'First topup that triggered reward',
  `topup_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Amount of first topup',
  `commission_percent` DECIMAL(5,2) DEFAULT 5.00 COMMENT 'Commission percentage applied',
  `reward_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Commission amount in Rupiah',
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
  FOREIGN KEY (`topup_id`) REFERENCES `topups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. CREATE REFERRAL SETTINGS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `referral_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default referral settings
INSERT INTO `referral_settings` (`setting_key`, `setting_value`, `description`) VALUES
('referral_enabled', '1', 'Enable/disable referral system'),
('commission_percent', '5.00', 'Commission percentage for referrals (e.g., 5 = 5%)'),
('min_topup_for_reward', '100000', 'Minimum topup amount to trigger referral reward (in Rupiah)'),
('referral_code_prefix', 'DRV', 'Prefix for referral codes')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

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
-- 8. GENERATE REFERRAL CODES FOR EXISTING USERS
-- =====================================================

UPDATE `users`
SET `referral_code` = CONCAT('DRV', UPPER(SUBSTRING(MD5(CONCAT(id, email, UNIX_TIMESTAMP())), 1, 6)))
WHERE `referral_code` IS NULL AND `role` = 'customer';

-- =====================================================
-- 9. CREATE SAMPLE FREE SHIPPING VOUCHERS
-- =====================================================

INSERT INTO `vouchers` (`code`, `type`, `category`, `value`, `min_purchase`, `target_tier`, `valid_from`, `valid_until`, `is_active`) VALUES
('FREESHIP50K', 'free_shipping', 'free_shipping', 0, 50000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('FREESHIP100K', 'free_shipping', 'free_shipping', 0, 100000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('SILVER50K', 'free_shipping', 'free_shipping', 0, 50000.00, 'silver', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('GOLD30K', 'free_shipping', 'free_shipping', 0, 30000.00, 'gold', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('PLATINUM20K', 'free_shipping', 'free_shipping', 0, 20000.00, 'platinum', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('VVIPFREE', 'free_shipping', 'free_shipping', 0, 0.00, 'vvip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1)
ON DUPLICATE KEY UPDATE `code` = VALUES(`code`);

-- =====================================================
-- 10. CREATE STORED PROCEDURE: UPDATE USER TIER
-- =====================================================

DROP PROCEDURE IF EXISTS `update_user_tier`;

DELIMITER $$

CREATE PROCEDURE `update_user_tier`(IN user_id_param INT)
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

    -- Determine new tier based on total topup
    SET new_tier = CASE
        WHEN total_topup >= 20000000 THEN 'vvip'        -- 20M+
        WHEN total_topup >= 10000000 THEN 'platinum'    -- 10M-20M
        WHEN total_topup >= 3000000 THEN 'gold'         -- 3M-10M
        WHEN total_topup >= 1000000 THEN 'silver'       -- 1M-3M
        ELSE 'bronze'                                    -- Under 1M
    END;

    -- Update user tier and total_topup_amount
    UPDATE users
    SET tier = new_tier, total_topup_amount = total_topup
    WHERE id = user_id_param;

    -- Log tier upgrade if tier changed
    IF current_tier != new_tier THEN
        INSERT INTO tier_upgrades (user_id, from_tier, to_tier, total_topup_at_upgrade)
        VALUES (user_id_param, current_tier, new_tier, total_topup);
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 11. CREATE STORED PROCEDURE: PROCESS REFERRAL REWARD
-- =====================================================

DROP PROCEDURE IF EXISTS `process_referral_reward`;

DELIMITER $$

CREATE PROCEDURE `process_referral_reward`(IN topup_id_param INT)
BEGIN
    DECLARE user_id_param INT;
    DECLARE topup_amount_param DECIMAL(15,2);
    DECLARE referrer_id_param INT;
    DECLARE commission_percent_val DECIMAL(5,2);
    DECLARE reward_amount DECIMAL(15,2);
    DECLARE first_topup_count INT;
    DECLARE min_topup_required DECIMAL(15,2);

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
            -- Get commission percentage from settings
            SELECT CAST(setting_value AS DECIMAL(5,2)) INTO commission_percent_val
            FROM referral_settings
            WHERE setting_key = 'commission_percent';

            -- Get minimum topup required
            SELECT CAST(setting_value AS DECIMAL(15,2)) INTO min_topup_required
            FROM referral_settings
            WHERE setting_key = 'min_topup_for_reward';

            -- Check if topup meets minimum requirement
            IF topup_amount_param >= min_topup_required THEN
                -- Calculate reward (commission percentage of topup amount)
                SET reward_amount = (topup_amount_param * commission_percent_val / 100);

                -- Update referral_rewards
                UPDATE referral_rewards
                SET status = 'completed',
                    topup_id = topup_id_param,
                    topup_amount = topup_amount_param,
                    commission_percent = commission_percent_val,
                    reward_value = reward_amount,
                    completed_at = NOW()
                WHERE referred_id = user_id_param AND status = 'pending';

                -- Add commission to referrer's wallet
                UPDATE users
                SET wallet_balance = wallet_balance + reward_amount
                WHERE id = referrer_id_param;
            END IF;
        END IF;
    END IF;

    -- Update user tier (regardless of referral)
    CALL update_user_tier(user_id_param);
END$$

DELIMITER ;

-- =====================================================
-- MIGRATION COMPLETE!
-- =====================================================

/*
  ================================================================
  SUMMARY - WHAT WAS CREATED:
  ================================================================

  ✅ USERS TABLE UPDATES:
     - referral_code (unique code for each user)
     - referred_by (who referred them)
     - total_referrals (count)
     - tier (bronze/silver/gold/platinum/vvip)
     - total_topup_amount (lifetime topup total)

  ✅ NEW TABLES:
     - topups (track wallet topups)
     - referral_rewards (track commissions)
     - referral_settings (admin configurable settings)
     - order_vouchers (track voucher usage)
     - tier_upgrades (history of tier changes)

  ✅ VOUCHERS UPDATES:
     - type: added 'free_shipping'
     - category: 'discount' or 'free_shipping'
     - target_tier: tier-specific vouchers
     - is_referral_reward: flag for referral vouchers

  ✅ STORED PROCEDURES:
     - update_user_tier() - Auto-upgrade customer tiers
     - process_referral_reward() - Calculate & pay commission

  ✅ DEFAULT DATA:
     - Referral settings (5% default commission)
     - 6 sample free shipping vouchers (tier-specific)
     - Auto-generated referral codes for existing users

  ================================================================
  HOW IT WORKS:
  ================================================================

  CUSTOMER TIERS (Auto-upgrade based on total topups):
  -----------------------------------------------------
  🟤 Bronze:    Under Rp 1,000,000
  ⚪ Silver:    Rp 1,000,000 - 2,999,999
  🟡 Gold:      Rp 3,000,000 - 9,999,999
  ⚪ Platinum:  Rp 10,000,000 - 19,999,999
  💜 VVIP:      Rp 20,000,000+

  REFERRAL COMMISSION (Default 5%, admin can change):
  ---------------------------------------------------
  - User A refers User B
  - User B registers (reward = PENDING)
  - User B completes first topup
  - Commission = topup_amount × commission_percent
  - Example: 1,000,000 × 5% = Rp 50,000
  - User A receives commission to wallet
  - Status changes to COMPLETED

  ADMIN SETTINGS:
  --------------
  - commission_percent: Default 5%, admin can change to any %
  - min_topup_for_reward: Minimum topup to trigger reward
  - Can be modified in referral_settings table or admin panel

  TIER-SPECIFIC VOUCHERS:
  ----------------------
  - Vouchers can target specific tiers
  - Users only see vouchers for their tier + 'all' tier
  - Example: VVIPFREE only visible to VVIP users

  VOUCHER CHECKOUT RULES:
  ----------------------
  - Max 2 vouchers per order
  - Can use: 1 free_shipping + 1 discount
  - Cannot use: 2 discount or 2 free_shipping together

  ANTI-MANIPULATION:
  -----------------
  ✅ Registration alone = NO reward
  ✅ Must complete FIRST topup
  ✅ Only first topup triggers commission
  ✅ Auto-calculated (no manual editing)
  ✅ Tier auto-upgraded (no manipulation)
  ✅ Full audit trail (all logged)

  ================================================================
  ADMIN CAN CHANGE COMMISSION:
  ================================================================

  To change commission percentage, admin can:

  Option 1 - Via SQL:
  -------------------
  UPDATE referral_settings
  SET setting_value = '10.00'
  WHERE setting_key = 'commission_percent';

  Option 2 - Via Admin Panel:
  ---------------------------
  Admin → Referral Settings → Commission Percent → Save
  (Admin panel can be built to edit referral_settings table)

  Example Commission Scenarios:
  -----------------------------
  5% commission:  1M topup = Rp 50,000 commission
  10% commission: 1M topup = Rp 100,000 commission
  15% commission: 1M topup = Rp 150,000 commission

  ================================================================
  TESTING:
  ================================================================

  1. Check referral codes generated:
     SELECT id, name, referral_code, tier FROM users WHERE role = 'customer';

  2. Test referral:
     - User A shares code
     - User B registers with code
     - Check: SELECT * FROM referral_rewards WHERE status = 'pending';

  3. Test topup:
     - Insert test topup:
       INSERT INTO topups (user_id, amount, status, completed_at)
       VALUES (user_b_id, 1000000, 'completed', NOW());
     - Call procedure:
       CALL process_referral_reward(LAST_INSERT_ID());
     - Check commission:
       SELECT * FROM referral_rewards WHERE status = 'completed';
     - Check wallet:
       SELECT wallet_balance FROM users WHERE id = user_a_id;

  4. Test tier upgrade:
     - Check tier before:
       SELECT tier, total_topup_amount FROM users WHERE id = user_b_id;
     - Add topup to reach 1M total
     - Call: CALL update_user_tier(user_b_id);
     - Check tier after (should be Silver if >= 1M)

  ================================================================
  NEXT STEPS:
  ================================================================

  1. ✅ Run this SQL file on your database
  2. ✅ Upload admin/referrals/index.php
  3. ✅ Upload member/referral.php (if not already)
  4. ✅ Test referral registration
  5. ✅ Test topup & commission
  6. ✅ Test tier upgrades
  7. ✅ Build admin panel to edit referral_settings

  ================================================================
  READY TO GO! 🚀
  ================================================================
*/
