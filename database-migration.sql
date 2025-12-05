-- ============================================
-- DORVE.ID DATABASE MIGRATION
-- Biteship Integration + Order Management System
-- Execute this file on your production server
-- ============================================

-- Step 1: Fix settings table (normalize column name)
-- Check if 'value' column exists and rename to 'setting_value'
ALTER TABLE `settings` 
CHANGE COLUMN `value` `setting_value` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;

-- If both columns exist, merge data
UPDATE `settings` 
SET `setting_value` = `value` 
WHERE `setting_value` IS NULL OR `setting_value` = '';

-- Drop old column if it still exists
-- ALTER TABLE `settings` DROP COLUMN `value`; -- Uncomment if needed

-- ============================================
-- Step 2: Update orders table for shipping
-- ============================================

ALTER TABLE `orders` 
ADD COLUMN `fulfillment_status` ENUM('new', 'waiting_print', 'waiting_pickup', 'in_transit', 'delivered', 'cancelled', 'returned') DEFAULT 'new' AFTER `payment_status`;

ALTER TABLE `orders` 
ADD COLUMN `shipping_courier` VARCHAR(100) NULL AFTER `fulfillment_status`;

ALTER TABLE `orders` 
ADD COLUMN `shipping_service` VARCHAR(100) NULL AFTER `shipping_courier`;

ALTER TABLE `orders` 
ADD COLUMN `shipping_cost` DECIMAL(15,2) DEFAULT 0 AFTER `shipping_service`;

ALTER TABLE `orders` 
ADD COLUMN `tracking_number` VARCHAR(255) NULL AFTER `shipping_cost`;

ALTER TABLE `orders` 
ADD COLUMN `notes` TEXT NULL AFTER `tracking_number`;

ALTER TABLE `orders` 
ADD INDEX `idx_tracking` (`tracking_number`);

ALTER TABLE `orders` 
ADD INDEX `idx_fulfillment` (`fulfillment_status`);

-- ============================================
-- Step 3: Create order_addresses table
-- ============================================

CREATE TABLE IF NOT EXISTS `order_addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `type` ENUM('billing', 'shipping') NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `address_line` TEXT NOT NULL,
  `district` VARCHAR(255) NULL,
  `city` VARCHAR(255) NOT NULL,
  `province` VARCHAR(255) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `country` VARCHAR(5) DEFAULT 'ID',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order` (`order_id`),
  INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Step 4: Create biteship_shipments table
-- ============================================

CREATE TABLE IF NOT EXISTS `biteship_shipments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `biteship_order_id` VARCHAR(255) NOT NULL UNIQUE,
  `courier_company` VARCHAR(100) NOT NULL,
  `courier_name` VARCHAR(100) NOT NULL,
  `courier_service_name` VARCHAR(100) NOT NULL,
  `courier_service_code` VARCHAR(100) NULL,
  `rate_id` VARCHAR(255) NULL,
  `shipping_cost` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `insurance_cost` DECIMAL(15,2) DEFAULT 0,
  `status` VARCHAR(50) DEFAULT 'pending',
  `waybill_id` VARCHAR(255) NULL,
  `label_print_batch_id` INT NULL,
  `pickup_code` VARCHAR(50) NULL,
  `delivery_date` DATETIME NULL,
  `pickup_time` VARCHAR(100) NULL,
  `destination_province` VARCHAR(255) NULL,
  `destination_city` VARCHAR(255) NULL,
  `destination_postal_code` VARCHAR(20) NULL,
  `origin_province` VARCHAR(255) NULL,
  `origin_city` VARCHAR(255) NULL,
  `origin_postal_code` VARCHAR(20) NULL,
  `weight_kg` DECIMAL(10,2) DEFAULT 0,
  `raw_response` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_order` (`order_id`),
  INDEX `idx_biteship_id` (`biteship_order_id`),
  INDEX `idx_waybill` (`waybill_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_batch` (`label_print_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Step 5: Create biteship_webhook_logs table
-- ============================================

CREATE TABLE IF NOT EXISTS `biteship_webhook_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event` VARCHAR(100) NOT NULL,
  `biteship_order_id` VARCHAR(255) NULL,
  `payload` TEXT NOT NULL,
  `processed` TINYINT(1) DEFAULT 0,
  `error_message` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_event` (`event`),
  INDEX `idx_biteship_id` (`biteship_order_id`),
  INDEX `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Step 6: Create print_batches table
-- ============================================

CREATE TABLE IF NOT EXISTS `print_batches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `batch_code` VARCHAR(50) NOT NULL UNIQUE,
  `printed_by_admin_id` INT NOT NULL,
  `printed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `total_orders` INT DEFAULT 0,
  `notes` TEXT NULL,
  INDEX `idx_batch_code` (`batch_code`),
  INDEX `idx_admin` (`printed_by_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Step 7: Insert Biteship configuration settings
-- ============================================

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
('biteship_enabled', '1'),
('biteship_api_key', 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiRG9ydmUuaWQiLCJ1c2VySWQiOiI2OTI4NDVhNDM4MzQ5ZjAyZjdhM2VhNDgiLCJpYXQiOjE3NjQ2NTYwMjV9.xmkeeT2ghfHPe7PItX5HJ0KptlC5xbIhL1TlHWn6S1U'),
('biteship_environment', 'production'),
('biteship_webhook_secret', ''),
('biteship_default_couriers', 'jne,jnt,sicepat,anteraja,idexpress'),
('store_name', 'Dorve.id Official Store'),
('store_phone', '+62-813-7737-8859'),
('store_address', 'Jakarta, Indonesia'),
('store_city', 'Jakarta Selatan'),
('store_province', 'DKI Jakarta'),
('store_postal_code', '12345'),
('store_country', 'ID')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ============================================
-- MIGRATION COMPLETE!
-- ============================================
-- Next Steps:
-- 1. Backup your database before running this
-- 2. Execute this SQL file on your production server
-- 3. Verify all tables created successfully
-- 4. Configure webhook URL in Biteship Dashboard:
--    https://dorve.id/api/biteship/webhook.php
-- 5. Test the integration from admin panel
-- ============================================
