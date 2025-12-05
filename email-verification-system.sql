/*
  ================================================================
  EMAIL VERIFICATION SYSTEM
  ================================================================

  Prevents fake registrations and referral abuse!

  Features:
  - User must verify email before account is active
  - Verification link sent via email
  - Cannot login until verified
  - Cannot trigger referral rewards until verified
  - Secure token system
  - Token expires after 24 hours
*/

-- =====================================================
-- 1. UPDATE USERS TABLE - ADD EMAIL VERIFICATION
-- =====================================================

ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0 AFTER `email`,
ADD COLUMN IF NOT EXISTS `email_verification_token` VARCHAR(64) DEFAULT NULL AFTER `email_verified`,
ADD COLUMN IF NOT EXISTS `email_verification_sent_at` TIMESTAMP NULL DEFAULT NULL AFTER `email_verification_token`,
ADD COLUMN IF NOT EXISTS `email_verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `email_verification_sent_at`;

-- Add index for verification token
ALTER TABLE `users`
ADD INDEX IF NOT EXISTS `idx_verification_token` (`email_verification_token`);

-- =====================================================
-- 2. CREATE EMAIL VERIFICATION LOG TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `email_verification_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_token` (`token`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. UPDATE REFERRAL_REWARDS - REQUIRE VERIFICATION
-- =====================================================

-- Add verification requirement note
ALTER TABLE `referral_rewards`
ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL COMMENT 'Additional notes about the reward';

-- =====================================================
-- 4. UPDATE EXISTING USERS - MARK AS VERIFIED
-- =====================================================

-- Mark all existing users as verified (so they're not locked out)
UPDATE `users`
SET `email_verified` = 1,
    `email_verified_at` = NOW()
WHERE `email_verified` = 0 AND `created_at` < NOW();

-- =====================================================
-- 5. CREATE STORED PROCEDURE: SEND VERIFICATION EMAIL
-- =====================================================

DROP PROCEDURE IF EXISTS `create_verification_token`;

DELIMITER $$

CREATE PROCEDURE `create_verification_token`(IN user_id_param INT)
BEGIN
    DECLARE new_token VARCHAR(64);

    -- Generate secure random token
    SET new_token = SHA2(CONCAT(user_id_param, UNIX_TIMESTAMP(), RAND()), 256);

    -- Update user with new token
    UPDATE users
    SET email_verification_token = new_token,
        email_verification_sent_at = NOW()
    WHERE id = user_id_param;

    -- Log the verification email
    INSERT INTO email_verification_log (user_id, email, token, ip_address)
    SELECT id, email, new_token, NULL
    FROM users
    WHERE id = user_id_param;

    -- Return the token
    SELECT new_token as token, email, name
    FROM users
    WHERE id = user_id_param;
END$$

DELIMITER ;

-- =====================================================
-- MIGRATION COMPLETE!
-- =====================================================

/*
  ================================================================
  SUMMARY:
  ================================================================

  ✅ USERS TABLE UPDATED:
     - email_verified (0 = not verified, 1 = verified)
     - email_verification_token (unique secure token)
     - email_verification_sent_at (when email was sent)
     - email_verified_at (when user clicked verification link)

  ✅ NEW TABLE:
     - email_verification_log (track all verification attempts)

  ✅ STORED PROCEDURE:
     - create_verification_token() (generate secure token)

  ✅ EXISTING USERS:
     - All marked as verified (won't be locked out)

  ================================================================
  HOW IT WORKS:
  ================================================================

  REGISTRATION FLOW:
  ------------------
  1. User fills registration form
  2. Account created with email_verified = 0
  3. Verification token generated
  4. Email sent with verification link
  5. User cannot login until verified
  6. User clicks link → email_verified = 1
  7. User can now login & use system

  VERIFICATION LINK FORMAT:
  -------------------------
  https://yourdomain.com/auth/verify-email.php?token=abc123xyz...

  REFERRAL PROTECTION:
  --------------------
  - Referral rewards stay 'pending' until verified
  - Cannot abuse system with fake emails
  - Must be real, active email to get commission

  TOKEN EXPIRY:
  -------------
  - Tokens expire after 24 hours
  - User can request new verification email
  - Old tokens automatically invalid

  ================================================================
  IMPORTANT NOTES:
  ================================================================

  1. MUST CONFIGURE EMAIL SENDING:
     - Use PHPMailer, SendGrid, or other email service
     - Cannot use mail() function (blocked by most hosts)
     - Recommend: SendGrid free tier (100 emails/day)

  2. EXISTING USERS:
     - Already marked as verified
     - Won't be affected by this update
     - Only NEW registrations require verification

  3. ADMIN USERS:
     - Should be manually verified
     - Or skip verification for admin role

  4. TESTING:
     - Test with real email address
     - Check spam folder
     - Verify link works
     - Test expired tokens

  ================================================================
  NEXT STEPS:
  ================================================================

  1. Run this SQL migration
  2. Create verify-email.php handler
  3. Update registration to send email
  4. Update login to check verification
  5. Set up email sending service
  6. Test complete flow

  ================================================================
*/
