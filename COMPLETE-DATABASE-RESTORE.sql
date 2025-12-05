-- =====================================================
-- DORVE HOUSE E-COMMERCE - COMPLETE DATABASE RESTORATION
-- =====================================================
-- Version: 4.0 MASTER
-- Date: 2025-12-05
-- Description: Complete database with ALL features
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- DROP ALL EXISTING TABLES (Fresh Start)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `tier_upgrades`;
DROP TABLE IF EXISTS `order_vouchers`;
DROP TABLE IF EXISTS `referral_rewards`;
DROP TABLE IF EXISTS `referral_settings`;
DROP TABLE IF EXISTS `topups`;
DROP TABLE IF EXISTS `order_timeline`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `vouchers`;
DROP TABLE IF EXISTS `shipping_methods`;
DROP TABLE IF EXISTS `wallet_transactions`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `cms_pages`;
DROP TABLE IF EXISTS `users`;

DROP VIEW IF EXISTS `order_tracking_view`;
DROP VIEW IF EXISTS `product_inventory_view`;
DROP PROCEDURE IF EXISTS `update_user_tier`;
DROP PROCEDURE IF EXISTS `process_referral_reward`;
DROP TRIGGER IF EXISTS `after_order_status_update`;
DROP TRIGGER IF EXISTS `after_variant_stock_update`;
DROP TRIGGER IF EXISTS `after_variant_insert`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. USERS TABLE
-- =====================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone_verified` BOOLEAN DEFAULT FALSE,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `wallet_balance` decimal(15,2) DEFAULT 0.00,
  `referral_code` VARCHAR(20) DEFAULT NULL,
  `referred_by` INT(11) DEFAULT NULL,
  `total_referrals` INT(11) DEFAULT 0,
  `tier` ENUM('bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'bronze',
  `total_topup_amount` DECIMAL(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `idx_role` (`role`),
  KEY `idx_referred_by` (`referred_by`),
  KEY `idx_tier` (`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. CATEGORIES TABLE
-- =====================================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. PRODUCTS TABLE
-- =====================================================
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `gender` enum('men','women','unisex') DEFAULT 'unisex',
  `is_new` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `stock` int(11) DEFAULT 0,
  `sold_count` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_gender` (`gender`),
  KEY `idx_active` (`is_active`),
  KEY `idx_new` (`is_new`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_price` (`price`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. PRODUCT VARIANTS TABLE
-- =====================================================
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_stock` (`stock`),
  KEY `idx_active` (`is_active`),
  UNIQUE KEY `unique_variant` (`product_id`, `color`, `size`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. ORDERS TABLE
-- =====================================================
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipping','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'wallet',
  `payment_status` varchar(50) DEFAULT 'pending',
  `shipping_method` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `courier` varchar(50) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `estimated_delivery_days` int(11) DEFAULT NULL,
  `estimated_delivery_date` date DEFAULT NULL,
  `shipping_notes` text DEFAULT NULL,
  `cancelled_reason` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_courier` (`courier`),
  KEY `idx_tracking` (`tracking_number`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user_status` (`user_id`, `status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. ORDER ITEMS TABLE
-- =====================================================
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  KEY `idx_product_variant` (`product_id`, `variant_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. ORDER TIMELINE TABLE
-- =====================================================
CREATE TABLE `order_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. CART ITEMS TABLE
-- =====================================================
CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. ADDRESSES TABLE
-- =====================================================
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `full_address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. VOUCHERS TABLE
-- =====================================================
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed','free_shipping') DEFAULT 'percentage',
  `category` ENUM('discount', 'free_shipping') DEFAULT 'discount',
  `value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `target_tier` ENUM('all', 'bronze', 'silver', 'gold', 'platinum', 'vvip') DEFAULT 'all',
  `is_referral_reward` TINYINT(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_target_tier` (`target_tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. ORDER VOUCHERS TABLE
-- =====================================================
CREATE TABLE `order_vouchers` (
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
-- 12. SHIPPING METHODS TABLE
-- =====================================================
CREATE TABLE `shipping_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estimated_days` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. WALLET TRANSACTIONS TABLE
-- =====================================================
CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('topup','payment','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. TOPUPS TABLE
-- =====================================================
CREATE TABLE `topups` (
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
-- 15. REFERRAL REWARDS TABLE
-- =====================================================
CREATE TABLE `referral_rewards` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` INT(11) NOT NULL COMMENT 'User who referred',
  `referred_id` INT(11) NOT NULL COMMENT 'User who was referred',
  `topup_id` INT(11) DEFAULT NULL COMMENT 'First topup that triggered reward',
  `topup_amount` DECIMAL(15,2) DEFAULT 0.00,
  `commission_percent` DECIMAL(5,2) DEFAULT 5.00,
  `reward_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
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
-- 16. REFERRAL SETTINGS TABLE
-- =====================================================
CREATE TABLE `referral_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 17. TIER UPGRADES TABLE
-- =====================================================
CREATE TABLE `tier_upgrades` (
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
-- 18. SETTINGS TABLE
-- =====================================================
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 19. REVIEWS TABLE
-- =====================================================
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `idx_approved` (`is_approved`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 20. CMS PAGES TABLE
-- =====================================================
CREATE TABLE `cms_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Admin Users with specified credentials
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `wallet_balance`, `tier`) VALUES
(1, 'Admin Dorve 1', 'admin1@dorve.co', '$2y$10$YLGKjvKFZ8sEWRx1s.q8eORnXKzQZEZ8pYH5KQxC3qvKJ6PFYz9yq', 'admin', 0.00, 'vvip'),
(2, 'Admin Dorve 2', 'admin2@dorve.co', '$2y$10$AboKGbzWQCHx1lLLrTaRu.1aSLPxz8p3yxJKRqLZT8dxVmTq0AKwy', 'admin', 0.00, 'vvip');

-- Default Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `sort_order`, `is_active`) VALUES
(1, 'T-Shirts', 't-shirts', 'Comfortable and stylish t-shirts', 1, 1),
(2, 'Hoodies', 'hoodies', 'Cozy hoodies and sweatshirts', 2, 1),
(3, 'Jeans', 'jeans', 'Trendy jeans and denim', 3, 1),
(4, 'Dresses', 'dresses', 'Beautiful dresses for all occasions', 4, 1),
(5, 'Jackets', 'jackets', 'Stylish jackets and outerwear', 5, 1),
(6, 'Accessories', 'accessories', 'Fashion accessories', 6, 1),
(7, 'Shoes', 'shoes', 'Comfortable and trendy footwear', 7, 1),
(8, 'Bags', 'bags', 'Stylish bags and backpacks', 8, 1);

-- Default Shipping Methods
INSERT INTO `shipping_methods` (`id`, `name`, `description`, `cost`, `estimated_days`, `sort_order`, `is_active`) VALUES
(1, 'Regular Shipping', 'Standard delivery 3-5 hari kerja', 15000.00, '3-5 days', 1, 1),
(2, 'Express Shipping', 'Fast delivery 1-2 hari kerja', 25000.00, '1-2 days', 2, 1),
(3, 'Free Shipping', 'Gratis ongkir untuk pembelian di atas Rp 500,000', 0.00, '5-7 days', 3, 1),
(4, 'Same Day Delivery', 'Pengiriman di hari yang sama (Jakarta only)', 50000.00, 'Same day', 4, 1);

-- Default Vouchers (Tier-based)
INSERT INTO `vouchers` (`code`, `type`, `category`, `value`, `min_purchase`, `target_tier`, `valid_from`, `valid_until`, `is_active`) VALUES
('WELCOME10', 'percentage', 'discount', 10.00, 100000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('NEWUSER50K', 'fixed', 'discount', 50000.00, 500000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('FREESHIP50K', 'free_shipping', 'free_shipping', 0, 50000.00, 'all', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('SILVER20', 'percentage', 'discount', 20.00, 200000.00, 'silver', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('GOLD25', 'percentage', 'discount', 25.00, 300000.00, 'gold', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('PLATINUM30', 'percentage', 'discount', 30.00, 500000.00, 'platinum', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('VVIP40', 'percentage', 'discount', 40.00, 1000000.00, 'vvip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('VVIPFREE', 'free_shipping', 'free_shipping', 0, 0.00, 'vvip', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1);

-- Referral Settings
INSERT INTO `referral_settings` (`setting_key`, `setting_value`, `description`) VALUES
('referral_enabled', '1', 'Enable/disable referral system'),
('commission_percent', '5.00', 'Commission percentage for referrals (e.g., 5 = 5%)'),
('min_topup_for_reward', '100000', 'Minimum topup amount to trigger referral reward (in Rupiah)'),
('referral_code_prefix', 'DRV', 'Prefix for referral codes');

-- System Settings
INSERT INTO `settings` (`setting_key`, `value`, `type`) VALUES
('store_name', 'Dorve House', 'text'),
('store_email', 'info@dorve.co', 'text'),
('store_phone', '081377378859', 'text'),
('store_address', 'Jakarta, Indonesia', 'text'),
('currency', 'IDR', 'text'),
('currency_symbol', 'Rp', 'text'),
('marquee_text', '🎉 Welcome to Dorve House! Free shipping for orders above Rp 500,000! 🚚', 'text'),
('marquee_enabled', '1', 'boolean'),
('promotion_banner_enabled', '1', 'boolean'),
('promotion_banner_image', '', 'text'),
('promotion_banner_link', '/pages/all-products.php', 'text'),
('whatsapp_number', '6281377378859', 'text'),
('midtrans_enabled', '0', 'boolean'),
('midtrans_server_key', '', 'text'),
('midtrans_client_key', '', 'text'),
('midtrans_environment', 'sandbox', 'text'),
('shipping_aggregator', 'manual', 'text'),
('bitship_enabled', '0', 'boolean'),
('bitship_api_key', '', 'text'),
('shipper_enabled', '0', 'boolean'),
('shipper_api_key', '', 'text'),
('tax_percentage', '0', 'text'),
('free_shipping_min', '500000', 'text');

-- Sample CMS Pages
INSERT INTO `cms_pages` (`title`, `slug`, `content`, `meta_title`, `is_active`) VALUES
('About Us', 'about-us', '<h1>Tentang Dorve House</h1><p>Dorve House adalah brand fashion lokal yang menghadirkan produk berkualitas dengan desain modern dan stylish.</p>', 'About Dorve House', 1),
('Privacy Policy', 'privacy-policy', '<h1>Kebijakan Privasi</h1><p>Kami menghargai privasi Anda dan berkomitmen untuk melindungi data pribadi Anda.</p>', 'Privacy Policy - Dorve House', 1),
('Terms & Conditions', 'terms-conditions', '<h1>Syarat & Ketentuan</h1><p>Dengan menggunakan website ini, Anda setuju dengan syarat dan ketentuan yang berlaku.</p>', 'Terms & Conditions - Dorve House', 1),
('Shipping Policy', 'shipping-policy', '<h1>Kebijakan Pengiriman</h1><p>Kami menyediakan berbagai metode pengiriman untuk kemudahan Anda.</p>', 'Shipping Policy - Dorve House', 1),
('FAQ', 'faq', '<h1>Frequently Asked Questions</h1><p>Pertanyaan yang sering diajukan tentang Dorve House.</p>', 'FAQ - Dorve House', 1);

-- =====================================================
-- CREATE STORED PROCEDURES
-- =====================================================

DELIMITER $$

-- Update User Tier
DROP PROCEDURE IF EXISTS `update_user_tier`$$
CREATE PROCEDURE `update_user_tier`(IN user_id_param INT)
BEGIN
    DECLARE total_topup DECIMAL(15,2);
    DECLARE current_tier VARCHAR(20);
    DECLARE new_tier VARCHAR(20);

    SELECT COALESCE(SUM(amount), 0) INTO total_topup
    FROM topups
    WHERE user_id = user_id_param AND status = 'completed';

    SELECT tier INTO current_tier FROM users WHERE id = user_id_param;

    SET new_tier = CASE
        WHEN total_topup >= 20000000 THEN 'vvip'
        WHEN total_topup >= 10000000 THEN 'platinum'
        WHEN total_topup >= 3000000 THEN 'gold'
        WHEN total_topup >= 1000000 THEN 'silver'
        ELSE 'bronze'
    END;

    UPDATE users
    SET tier = new_tier, total_topup_amount = total_topup
    WHERE id = user_id_param;

    IF current_tier != new_tier THEN
        INSERT INTO tier_upgrades (user_id, from_tier, to_tier, total_topup_at_upgrade)
        VALUES (user_id_param, current_tier, new_tier, total_topup);
    END IF;
END$$

-- Process Referral Reward
DROP PROCEDURE IF EXISTS `process_referral_reward`$$
CREATE PROCEDURE `process_referral_reward`(IN topup_id_param INT)
BEGIN
    DECLARE user_id_param INT;
    DECLARE topup_amount_param DECIMAL(15,2);
    DECLARE referrer_id_param INT;
    DECLARE commission_percent_val DECIMAL(5,2);
    DECLARE reward_amount DECIMAL(15,2);
    DECLARE first_topup_count INT;
    DECLARE min_topup_required DECIMAL(15,2);

    SELECT user_id, amount INTO user_id_param, topup_amount_param
    FROM topups
    WHERE id = topup_id_param AND status = 'completed';

    SELECT COUNT(*) INTO first_topup_count
    FROM topups
    WHERE user_id = user_id_param AND status = 'completed';

    IF first_topup_count = 1 THEN
        SELECT referred_by INTO referrer_id_param
        FROM users
        WHERE id = user_id_param;

        IF referrer_id_param IS NOT NULL THEN
            SELECT CAST(setting_value AS DECIMAL(5,2)) INTO commission_percent_val
            FROM referral_settings
            WHERE setting_key = 'commission_percent';

            SELECT CAST(setting_value AS DECIMAL(15,2)) INTO min_topup_required
            FROM referral_settings
            WHERE setting_key = 'min_topup_for_reward';

            IF topup_amount_param >= min_topup_required THEN
                SET reward_amount = (topup_amount_param * commission_percent_val / 100);

                UPDATE referral_rewards
                SET status = 'completed',
                    topup_id = topup_id_param,
                    topup_amount = topup_amount_param,
                    commission_percent = commission_percent_val,
                    reward_value = reward_amount,
                    completed_at = NOW()
                WHERE referred_id = user_id_param AND status = 'pending';

                UPDATE users
                SET wallet_balance = wallet_balance + reward_amount
                WHERE id = referrer_id_param;
            END IF;
        END IF;
    END IF;

    CALL update_user_tier(user_id_param);
END$$

DELIMITER ;

-- =====================================================
-- CREATE TRIGGERS
-- =====================================================

DELIMITER $$

-- Auto-create timeline entry when order status changes
DROP TRIGGER IF EXISTS `after_order_status_update`$$
CREATE TRIGGER `after_order_status_update`
AFTER UPDATE ON `orders`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO order_timeline (order_id, status, title, description)
        VALUES (
            NEW.id,
            NEW.status,
            CASE NEW.status
                WHEN 'pending' THEN 'Order Placed'
                WHEN 'processing' THEN 'Order Processing'
                WHEN 'shipping' THEN 'Order Shipped'
                WHEN 'delivered' THEN 'Order Delivered'
                WHEN 'cancelled' THEN 'Order Cancelled'
                ELSE 'Status Updated'
            END,
            CASE NEW.status
                WHEN 'pending' THEN 'Pesanan Anda telah diterima dan menunggu konfirmasi'
                WHEN 'processing' THEN 'Pesanan Anda sedang diproses'
                WHEN 'shipping' THEN CONCAT('Pesanan Anda telah dikirim via ', COALESCE(NEW.courier, 'kurir'))
                WHEN 'delivered' THEN 'Pesanan Anda telah diterima'
                WHEN 'cancelled' THEN COALESCE(NEW.cancelled_reason, 'Pesanan Anda telah dibatalkan')
                ELSE 'Status pesanan telah diupdate'
            END
        );
    END IF;
END$$

-- Update product stock when variant stock changes
DROP TRIGGER IF EXISTS `after_variant_stock_update`$$
CREATE TRIGGER `after_variant_stock_update`
AFTER UPDATE ON `product_variants`
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = (
        SELECT COALESCE(SUM(stock), 0)
        FROM product_variants
        WHERE product_id = NEW.product_id AND is_active = 1
    )
    WHERE id = NEW.product_id;
END$$

-- Update product stock when new variant added
DROP TRIGGER IF EXISTS `after_variant_insert`$$
CREATE TRIGGER `after_variant_insert`
AFTER INSERT ON `product_variants`
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = (
        SELECT COALESCE(SUM(stock), 0)
        FROM product_variants
        WHERE product_id = NEW.product_id AND is_active = 1
    )
    WHERE id = NEW.product_id;
END$$

DELIMITER ;

-- =====================================================
-- CREATE VIEWS
-- =====================================================

-- Order tracking view
CREATE OR REPLACE VIEW `order_tracking_view` AS
SELECT
    o.id,
    o.user_id,
    o.order_number,
    o.total_amount,
    o.status,
    o.courier,
    o.tracking_number,
    o.estimated_delivery_days,
    o.estimated_delivery_date,
    o.shipping_notes,
    o.created_at as order_date,
    o.updated_at,
    u.name as customer_name,
    u.email as customer_email,
    u.phone as customer_phone,
    o.shipping_address,
    (SELECT created_at FROM order_timeline WHERE order_id = o.id ORDER BY created_at DESC LIMIT 1) as last_update
FROM orders o
LEFT JOIN users u ON o.user_id = u.id;

-- Product with variants view
CREATE OR REPLACE VIEW `product_inventory_view` AS
SELECT
    p.id as product_id,
    p.name as product_name,
    p.slug,
    p.gender,
    c.name as category_name,
    pv.id as variant_id,
    pv.color,
    pv.size,
    pv.stock,
    pv.sku,
    (p.price + pv.price_adjustment) as variant_price
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN product_variants pv ON p.id = pv.product_id
WHERE p.is_active = 1;

COMMIT;

-- =====================================================
-- RESTORATION COMPLETE!
-- =====================================================

SELECT 
    '✅ DATABASE RESTORATION COMPLETE!' as STATUS,
    'All tables, procedures, triggers, and views created' as MESSAGE,
    'Admin credentials ready' as ADMIN_INFO,
    '2 Admin users created' as USERS,
    'All systems fully functional' as FEATURES;
