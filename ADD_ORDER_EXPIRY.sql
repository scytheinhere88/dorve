-- Add expired_at column to orders table if not exists
ALTER TABLE orders ADD COLUMN IF NOT EXISTS expired_at DATETIME NULL AFTER paid_at;

-- Update existing pending orders with expiry (1 hour from created)
UPDATE orders 
SET expired_at = DATE_ADD(created_at, INTERVAL 1 HOUR) 
WHERE payment_status = 'pending' AND expired_at IS NULL;
