-- =====================================================
-- REFERRAL & FREE SHIPPING VOUCHER SYSTEM
-- =====================================================
-- This migration adds:
-- 1. Referral code system for users
-- 2. Free shipping voucher type
-- 3. Referral rewards tracking
-- 4. Commission tracking (2.5-5% from referral topups)
-- =====================================================

-- 1. ADD REFERRAL COLUMNS TO USERS TABLE
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `referral_code` VARCHAR(20) UNIQUE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `referred_by` INT(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `referral_earnings` DECIMAL(10,2) DEFAULT 0.00,
ADD INDEX IF NOT EXISTS `idx_referral_code` (`referral_code`),
ADD INDEX IF NOT EXISTS `idx_referred_by` (`referred_by`);

-- 2. UPDATE VOUCHERS TABLE - ADD FREE SHIPPING TYPE
ALTER TABLE `vouchers`
MODIFY COLUMN `type` ENUM('percentage','fixed','free_shipping') DEFAULT 'percentage';

-- 3. CREATE REFERRAL TRANSACTIONS TABLE
CREATE TABLE IF NOT EXISTS `referral_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` INT(11) NOT NULL COMMENT 'User who referred',
  `referred_id` INT(11) NOT NULL COMMENT 'User who was referred',
  `transaction_type` ENUM('topup','purchase') DEFAULT 'topup',
  `amount` DECIMAL(10,2) NOT NULL COMMENT 'Original transaction amount',
  `commission_rate` DECIMAL(5,2) NOT NULL COMMENT 'Commission percentage (2.5-5%)',
  `commission_amount` DECIMAL(10,2) NOT NULL COMMENT 'Commission earned',
  `status` ENUM('pending','completed','cancelled') DEFAULT 'pending',
  `order_id` INT(11) DEFAULT NULL COMMENT 'Related order if any',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer` (`referrer_id`),
  KEY `idx_referred` (`referred_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CREATE VOUCHER USAGE TRACKING TABLE
CREATE TABLE IF NOT EXISTS `voucher_usage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `order_id` INT(11) DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `used_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voucher` (`voucher_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ADD VOUCHER COLUMNS TO ORDERS TABLE
ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `discount_voucher_id` INT(11) DEFAULT NULL COMMENT 'Discount voucher used',
ADD COLUMN IF NOT EXISTS `shipping_voucher_id` INT(11) DEFAULT NULL COMMENT 'Free shipping voucher used',
ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `shipping_discount` DECIMAL(10,2) DEFAULT 0.00,
ADD INDEX IF NOT EXISTS `idx_discount_voucher` (`discount_voucher_id`),
ADD INDEX IF NOT EXISTS `idx_shipping_voucher` (`shipping_voucher_id`);

-- 6. GENERATE REFERRAL CODES FOR EXISTING USERS (Run only once)
-- Each user gets a unique referral code based on their ID and name
UPDATE `users`
SET `referral_code` = CONCAT('DRV', LPAD(id, 5, '0'))
WHERE `referral_code` IS NULL AND `role` = 'customer';

-- 7. CREATE SAMPLE FREE SHIPPING VOUCHERS
INSERT INTO `vouchers` (`code`, `type`, `value`, `min_purchase`, `usage_limit`, `valid_from`, `valid_until`, `is_active`) VALUES
('FREESHIP50K', 'free_shipping', 0, 50000, 1000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1),
('FREESHIP100K', 'free_shipping', 0, 100000, 500, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1),
('FREESHIP2024', 'free_shipping', 0, 75000, 2000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 1)
ON DUPLICATE KEY UPDATE `code` = `code`;

-- 8. CREATE REFERRAL REWARDS TABLE (for voucher rewards)
CREATE TABLE IF NOT EXISTS `referral_rewards` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` INT(11) NOT NULL,
  `referred_id` INT(11) NOT NULL,
  `reward_type` ENUM('commission','voucher','bonus') DEFAULT 'commission',
  `voucher_id` INT(11) DEFAULT NULL,
  `commission_amount` DECIMAL(10,2) DEFAULT 0.00,
  `milestone` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. first_topup, 1M_topup',
  `status` ENUM('pending','claimed','expired') DEFAULT 'pending',
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer` (`referrer_id`),
  KEY `idx_referred` (`referred_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTES:
-- =====================================================
-- 1. Referral codes are unique per user (e.g., DRV00001)
-- 2. Voucher types now support: percentage, fixed, free_shipping
-- 3. Orders can use max 2 vouchers: 1 discount + 1 shipping
-- 4. Referral commission: 2.5-5% configurable per transaction
-- 5. Referrer gets free shipping vouchers as rewards
-- =====================================================
