-- ================================================================
-- PAYMENT SYSTEM TABLES
-- ================================================================

-- Create wallet_transactions if not exists
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('topup', 'debit', 'refund', 'commission') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) DEFAULT 0,
    balance_after DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    payment_method VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending',
    reference_id VARCHAR(100),
    unique_code INT,
    bank_account_id INT,
    admin_notes TEXT,
    proof_image VARCHAR(255),
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
);

-- Create payment_methods table for admin settings
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('qris', 'bank_transfer', 'gateway', 'ewallet') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    settings JSON,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create system_settings table for WhatsApp and other configs
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('whatsapp_admin', '628123456789', 'WhatsApp number for customer support (with country code, no +)'),
('whatsapp_message', 'Halo Admin, saya sudah melakukan transfer untuk topup wallet. Mohon di cek ya!', 'Default WhatsApp message template'),
('min_topup_amount', '10000', 'Minimum topup amount in IDR'),
('unique_code_min', '100', 'Minimum unique code (3 digits)'),
('unique_code_max', '999', 'Maximum unique code (3 digits)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Create bank_accounts table
CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    bank_code VARCHAR(20),
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    branch VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create payment_gateway_settings table
CREATE TABLE IF NOT EXISTS payment_gateway_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) NOT NULL UNIQUE,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    merchant_id VARCHAR(100),
    client_id VARCHAR(255),
    client_secret VARCHAR(255),
    is_production TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 0,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default payment methods
INSERT INTO payment_methods (name, slug, type, is_active, settings, display_order) VALUES
('QRIS (Midtrans)', 'qris_midtrans', 'qris', 0, '{"fee": 0, "min_amount": 10000}', 1),
('Bank Transfer', 'bank_transfer', 'bank_transfer', 1, '{"fee": 0, "min_amount": 10000, "requires_confirmation": true}', 2),
('Midtrans Payment Gateway', 'midtrans_gateway', 'gateway', 0, '{"fee": 0, "min_amount": 10000, "supports": ["credit_card", "gopay", "shopeepay", "bank_transfer"]}', 3),
('PayPal', 'paypal', 'gateway', 0, '{"fee": 0, "min_amount": 10000}', 4)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert default Indonesian banks
INSERT INTO bank_accounts (bank_name, bank_code, account_number, account_name, is_active, display_order) VALUES
('BCA (Bank Central Asia)', 'BCA', '1234567890', 'PT Dorve House', 1, 1),
('Mandiri', 'MANDIRI', '1234567890123', 'PT Dorve House', 1, 2),
('BNI (Bank Negara Indonesia)', 'BNI', '1234567890', 'PT Dorve House', 1, 3),
('BRI (Bank Rakyat Indonesia)', 'BRI', '123456789012345', 'PT Dorve House', 0, 4),
('CIMB Niaga', 'CIMB', '1234567890', 'PT Dorve House', 0, 5),
('Danamon', 'DANAMON', '1234567890', 'PT Dorve House', 0, 6),
('Permata Bank', 'PERMATA', '1234567890', 'PT Dorve House', 0, 7)
ON DUPLICATE KEY UPDATE bank_name = VALUES(bank_name);

-- Insert default gateway settings (empty, to be configured in admin)
INSERT INTO payment_gateway_settings (gateway_name, is_active) VALUES
('midtrans', 0),
('paypal', 0)
ON DUPLICATE KEY UPDATE gateway_name = VALUES(gateway_name);

SELECT '✅ Payment system tables created!' as status;
