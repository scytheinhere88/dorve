-- CREATE REFERRAL TABLES
-- Run this in phpMyAdmin if fix-database-issues.php doesn't work

-- 1. Create referrals table (without foreign keys to avoid conflicts)
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `referrer_id` INT NOT NULL,
  `referred_id` INT NOT NULL,
  `referral_code` VARCHAR(20),
  `status` ENUM('pending', 'active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_referral` (`referrer_id`, `referred_id`),
  INDEX `idx_referrer` (`referrer_id`),
  INDEX `idx_referred` (`referred_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create referral_commissions table (without foreign keys)
CREATE TABLE IF NOT EXISTS `referral_commissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `referrer_id` INT NOT NULL,
  `referred_id` INT NOT NULL,
  `transaction_id` INT,
  `commission_amount` DECIMAL(15,2) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL,
  `transaction_amount` DECIMAL(15,2) NOT NULL,
  `status` ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_referrer` (`referrer_id`),
  INDEX `idx_referred` (`referred_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
