<?php
/*
 * EMAIL VERIFICATION SYSTEM SETUP
 *
 * Upload file ini ke root directory
 * Akses via browser: http://yourdomain.com/setup-email-verification.php
 *
 * Script ini akan setup:
 * - Email verification fields
 * - Verification log table
 * - Stored procedure untuk generate token
 */

require_once __DIR__ . '/config.php';

// Security: Only allow admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    die('ERROR: Only admin can run this setup!');
}

$results = [];
$errors = [];

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Setup Email Verification</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
        pre { background: #000; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
<h1>🛡️ SETUP EMAIL VERIFICATION SYSTEM</h1>
<pre>";

// Step 1: Update users table
echo "\n=== STEP 1: UPDATE USERS TABLE ===\n";
try {
    $pdo->exec("
        ALTER TABLE `users`
        ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS `email_verification_token` VARCHAR(64) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `email_verification_sent_at` TIMESTAMP NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS `email_verified_at` TIMESTAMP NULL DEFAULT NULL
    ");
    echo "✓ Email verification columns added to users table\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Users table: " . $e->getMessage();
}

// Add index
try {
    $pdo->exec("ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_verification_token` (`email_verification_token`)");
    echo "✓ Index for verification token created\n";
} catch (PDOException $e) {
    echo "⚠ Index warning: " . $e->getMessage() . "\n";
}

// Step 2: Create email verification log table
echo "\n=== STEP 2: CREATE EMAIL VERIFICATION LOG TABLE ===\n";
try {
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Email verification log table created\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Verification log: " . $e->getMessage();
}

// Step 3: Mark existing users as verified
echo "\n=== STEP 3: MARK EXISTING USERS AS VERIFIED ===\n";
try {
    $result = $pdo->exec("
        UPDATE `users`
        SET `email_verified` = 1,
            `email_verified_at` = NOW()
        WHERE `email_verified` = 0 AND `created_at` < NOW()
    ");
    echo "✓ Existing users marked as verified ($result users)\n";
    echo "  (So they won't be locked out!)\n";
} catch (PDOException $e) {
    echo "⚠ Warning: " . $e->getMessage() . "\n";
}

// Step 4: Create stored procedure
echo "\n=== STEP 4: CREATE STORED PROCEDURE ===\n";
try {
    $pdo->exec("DROP PROCEDURE IF EXISTS `create_verification_token`");
    $pdo->exec("
        CREATE PROCEDURE `create_verification_token`(IN user_id_param INT)
        BEGIN
            DECLARE new_token VARCHAR(64);

            SET new_token = SHA2(CONCAT(user_id_param, UNIX_TIMESTAMP(), RAND()), 256);

            UPDATE users
            SET email_verification_token = new_token,
                email_verification_sent_at = NOW()
            WHERE id = user_id_param;

            INSERT INTO email_verification_log (user_id, email, token, ip_address)
            SELECT id, email, new_token, NULL
            FROM users
            WHERE id = user_id_param;

            SELECT new_token as token, email, name
            FROM users
            WHERE id = user_id_param;
        END
    ");
    echo "✓ Stored procedure 'create_verification_token' created\n";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Stored procedure: " . $e->getMessage();
}

// Step 5: Test token generation
echo "\n=== STEP 5: TEST TOKEN GENERATION ===\n";
try {
    // Find an admin user to test with
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();

    if ($admin) {
        $stmt = $pdo->prepare("CALL create_verification_token(?)");
        $stmt->execute([$admin['id']]);
        $result = $stmt->fetch();

        if ($result && !empty($result['token'])) {
            echo "✓ Token generation working!\n";
            echo "  Test token (first 32 chars): " . substr($result['token'], 0, 32) . "...\n";
            echo "  Test user: " . $result['name'] . " (" . $result['email'] . ")\n";
        } else {
            echo "✗ Token generation failed\n";
            $errors[] = "Token generation test failed";
        }
    } else {
        echo "⚠ No admin user found for testing\n";
    }
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $errors[] = "Token test: " . $e->getMessage();
}

// Summary
echo "\n\n=== SETUP COMPLETE! ===\n";
if (empty($errors)) {
    echo "<span class='success'>✓ ALL STEPS COMPLETED SUCCESSFULLY!</span>\n\n";
    echo "✅ Email verification system ready\n";
    echo "✅ Existing users marked as verified\n";
    echo "✅ New users will need to verify\n";
    echo "✅ Token generation working\n\n";
    echo "<span class='info'>Next steps:</span>\n";
    echo "1. Upload auth/verify-email.php\n";
    echo "2. Upload includes/email-helper.php\n";
    echo "3. Update auth/register.php (send verification email)\n";
    echo "4. Update auth/login.php (check email_verified)\n";
    echo "5. Set up SendGrid for email sending\n";
    echo "6. Test registration & verification!\n\n";
    echo "<span class='info'>To send verification email:</span>\n";
    echo "require_once 'includes/email-helper.php';\n";
    echo "\$sent = sendVerificationEmail(\$userId, \$pdo);\n\n";
    echo "<span class='error'>⚠ IMPORTANT: DELETE THIS FILE AFTER SETUP!</span>\n";
} else {
    echo "<span class='error'>✗ COMPLETED WITH SOME ERRORS:</span>\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\nSome errors can be ignored if tables/columns already exist.\n";
}

// Show current verification status
echo "\n\n=== VERIFICATION STATUS ===\n";
try {
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_users,
            SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users,
            SUM(CASE WHEN email_verified = 0 THEN 1 ELSE 0 END) as unverified_users
        FROM users
        WHERE role = 'customer'
    ");
    $stats = $stmt->fetch();

    echo "Total customers: " . $stats['total_users'] . "\n";
    echo "Verified: " . $stats['verified_users'] . "\n";
    echo "Unverified: " . $stats['unverified_users'] . "\n";
} catch (PDOException $e) {
    echo "⚠ Could not fetch stats\n";
}

echo "</pre>
</body>
</html>";
?>
