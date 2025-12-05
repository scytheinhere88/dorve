-- ================================================================
-- FIX ALL MISSING COLUMNS - RUN THIS SQL!
-- ================================================================

-- Fix Products Table
-- Add missing columns: is_featured, is_new, is_active
ALTER TABLE products
ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0 AFTER stock,
ADD COLUMN IF NOT EXISTS is_new TINYINT(1) DEFAULT 0 AFTER is_featured,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER is_new;

-- Fix Categories Table
-- Add missing columns: sequence, is_active
ALTER TABLE categories
ADD COLUMN IF NOT EXISTS sequence INT DEFAULT 0 AFTER name,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER sequence;

-- Update sequence for existing categories
UPDATE categories SET sequence = id WHERE sequence = 0;

-- Fix Users Table
-- Rename tier to current_tier, total_topup_amount to total_topup
ALTER TABLE users
CHANGE COLUMN tier current_tier VARCHAR(20) DEFAULT 'bronze',
CHANGE COLUMN total_topup_amount total_topup DECIMAL(15,2) DEFAULT 0;

-- Fix Orders Table
-- Add total_price if not exists (might be named differently)
-- Check if we need to rename or add
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'orders'
    AND COLUMN_NAME = 'total_price'
);

-- If total_price doesn't exist, try to find similar column or add it
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS total_price DECIMAL(15,2) DEFAULT 0 AFTER subtotal;

-- Update total_price from subtotal + shipping if it's 0
UPDATE orders
SET total_price = COALESCE(subtotal, 0) + COALESCE(shipping_cost, 0)
WHERE total_price = 0 OR total_price IS NULL;

-- ================================================================
-- VERIFICATION QUERIES
-- ================================================================

-- Check products columns
SELECT 'Products columns:' as info;
SHOW COLUMNS FROM products LIKE '%featured%';
SHOW COLUMNS FROM products LIKE '%new%';
SHOW COLUMNS FROM products LIKE '%active%';

-- Check categories columns
SELECT 'Categories columns:' as info;
SHOW COLUMNS FROM categories LIKE 'sequence';
SHOW COLUMNS FROM categories LIKE '%active%';

-- Check users columns
SELECT 'Users columns:' as info;
SHOW COLUMNS FROM users LIKE '%tier%';
SHOW COLUMNS FROM users LIKE '%topup%';

-- Check orders columns
SELECT 'Orders columns:' as info;
SHOW COLUMNS FROM orders LIKE '%price%';

-- ================================================================
-- SUCCESS MESSAGE
-- ================================================================
SELECT 'ALL COLUMNS FIXED! ✅' as status;
