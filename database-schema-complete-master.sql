-- =====================================================
-- DORVE HOUSE E-COMMERCE - COMPLETE DATABASE SCHEMA
-- =====================================================
-- Version: 3.0
-- Date: 2025-11-25
-- Description: Complete database with all features
-- Safe to run multiple times (uses IF NOT EXISTS)
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- 1. USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add phone column if not exists
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone_verified` BOOLEAN DEFAULT FALSE;

-- =====================================================
-- 2. CATEGORIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
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
CREATE TABLE IF NOT EXISTS `products` (
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
  KEY `category_id` (`category_id`),
  KEY `idx_gender` (`gender`),
  KEY `idx_active` (`is_active`),
  KEY `idx_new` (`is_new`),
  KEY `idx_featured` (`is_featured`),
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add gender column if not exists
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `gender` enum('men','women','unisex') DEFAULT 'unisex';

-- =====================================================
-- 4. PRODUCT VARIANTS TABLE (Stock per Size/Color)
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_variants` (
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
  KEY `product_id` (`product_id`),
  KEY `idx_stock` (`stock`),
  KEY `idx_active` (`is_active`),
  UNIQUE KEY `unique_variant` (`product_id`, `color`, `size`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
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
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add new order columns if not exist
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `courier` varchar(50) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `tracking_number` varchar(100) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `estimated_delivery_days` int(11) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `estimated_delivery_date` date DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `shipping_notes` text DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `cancelled_reason` text DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `cancelled_at` timestamp NULL DEFAULT NULL;

-- Update status enum if needed
ALTER TABLE `orders` MODIFY COLUMN `status` enum('pending','processing','shipping','delivered','cancelled') DEFAULT 'pending';

-- =====================================================
-- 6. ORDER ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
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
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. ORDER TIMELINE TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_timeline` (
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
CREATE TABLE IF NOT EXISTS `cart_items` (
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
CREATE TABLE IF NOT EXISTS `addresses` (
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
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. SHIPPING METHODS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `shipping_methods` (
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
-- 12. WALLET TRANSACTIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
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
-- 13. SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `settings` (
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
-- 14. REVIEWS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `reviews` (
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
-- 15. CMS PAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `cms_pages` (
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

-- Default Admin User (password: admin123)
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `role`, `wallet_balance`)
VALUES (1, 'Admin', 'admin@dorvehouse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0.00);

-- Default Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`, `sort_order`) VALUES
(1, 'Tops', 'tops', 'Stylish tops and shirts', 1),
(2, 'Hoodies', 'hoodies', 'Comfortable hoodies and sweatshirts', 2),
(3, 'Bottoms', 'bottoms', 'Pants, jeans, and shorts', 3),
(4, 'Dresses', 'dresses', 'Beautiful dresses', 4),
(5, 'Accessories', 'accessories', 'Fashion accessories', 5);

-- Default Shipping Methods
INSERT IGNORE INTO `shipping_methods` (`id`, `name`, `description`, `cost`, `estimated_days`, `sort_order`) VALUES
(1, 'Regular Shipping', 'Standard delivery', 15000.00, '3-5 days', 1),
(2, 'Express Shipping', 'Fast delivery', 25000.00, '1-2 days', 2),
(3, 'Free Shipping', 'Free for orders above Rp 500,000', 0.00, '5-7 days', 3);

-- Default Settings
INSERT INTO `settings` (`setting_key`, `value`, `type`) VALUES
('store_name', 'Dorve House', 'text'),
('store_email', 'info@dorvehouse.com', 'text'),
('store_phone', '0812-3456-7890', 'text'),
('store_address', 'Jakarta, Indonesia', 'text'),
('currency', 'IDR', 'text'),
('currency_symbol', 'Rp', 'text'),
('marquee_text', '🎉 Welcome to Dorve House! Free shipping for orders above Rp 500,000! 🚚', 'text'),
('marquee_enabled', '1', 'boolean'),
('promotion_banner_enabled', '0', 'boolean'),
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
('shipper_api_key', '', 'text')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- =====================================================
-- CREATE VIEWS FOR EASY QUERIES
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

-- =====================================================
-- CREATE TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger: Auto-create timeline entry when order status changes
DROP TRIGGER IF EXISTS `after_order_status_update`//
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
                WHEN 'pending' THEN 'Your order has been placed and is waiting for confirmation'
                WHEN 'processing' THEN 'Your order is being prepared for shipment'
                WHEN 'shipping' THEN CONCAT('Your order has been shipped via ', COALESCE(NEW.courier, 'courier'))
                WHEN 'delivered' THEN 'Your order has been delivered successfully'
                WHEN 'cancelled' THEN COALESCE(NEW.cancelled_reason, 'Your order has been cancelled')
                ELSE 'Order status has been updated'
            END
        );
    END IF;
END//

-- Trigger: Update product stock when variant stock changes
DROP TRIGGER IF EXISTS `after_variant_stock_update`//
CREATE TRIGGER `after_variant_stock_update`
AFTER UPDATE ON `product_variants`
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = (
        SELECT SUM(stock)
        FROM product_variants
        WHERE product_id = NEW.product_id AND is_active = 1
    )
    WHERE id = NEW.product_id;
END//

-- Trigger: Update product stock when new variant added
DROP TRIGGER IF EXISTS `after_variant_insert`//
CREATE TRIGGER `after_variant_insert`
AFTER INSERT ON `product_variants`
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = (
        SELECT SUM(stock)
        FROM product_variants
        WHERE product_id = NEW.product_id AND is_active = 1
    )
    WHERE id = NEW.product_id;
END//

DELIMITER ;

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes (IF NOT EXISTS equivalent)
ALTER TABLE `products` ADD INDEX IF NOT EXISTS `idx_category_gender` (`category_id`, `gender`);
ALTER TABLE `products` ADD INDEX IF NOT EXISTS `idx_price` (`price`);
ALTER TABLE `orders` ADD INDEX IF NOT EXISTS `idx_user_status` (`user_id`, `status`);
ALTER TABLE `order_items` ADD INDEX IF NOT EXISTS `idx_product_variant` (`product_id`, `variant_id`);

COMMIT;

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT '✅ Database schema created/updated successfully!' as status,
       'All tables, triggers, and views are ready!' as message,
       'Safe to run multiple times - uses IF NOT EXISTS' as note;
