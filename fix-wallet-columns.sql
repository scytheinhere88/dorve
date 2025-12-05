-- Add missing columns to wallet_transactions table for unique code system

-- Add amount_original column (stores original amount before unique code)
ALTER TABLE wallet_transactions
ADD COLUMN IF NOT EXISTS amount_original DECIMAL(15,2) DEFAULT NULL AFTER amount;

-- Add unique_code column (stores the random code added to amount)
ALTER TABLE wallet_transactions
ADD COLUMN IF NOT EXISTS unique_code INT DEFAULT NULL AFTER amount_original;

-- Add bank_account_id column (foreign key to bank_accounts)
ALTER TABLE wallet_transactions
ADD COLUMN IF NOT EXISTS bank_account_id INT DEFAULT NULL AFTER payment_method;

-- Add proof_image column (stores path to uploaded payment proof)
ALTER TABLE wallet_transactions
ADD COLUMN IF NOT EXISTS proof_image VARCHAR(500) DEFAULT NULL AFTER description;

-- Add indexes for better performance
ALTER TABLE wallet_transactions
ADD INDEX IF NOT EXISTS idx_bank_account (bank_account_id);

ALTER TABLE wallet_transactions
ADD INDEX IF NOT EXISTS idx_payment_status (payment_status);

-- Update existing records to set amount_original = amount where NULL
UPDATE wallet_transactions
SET amount_original = amount,
    unique_code = 0
WHERE amount_original IS NULL AND type = 'topup';

-- Create uploads directory structure (run this via PHP if needed)
-- /uploads/payment-proofs/

SELECT 'Wallet columns added successfully!' as status;
