<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    redirect('/member/dashboard.php');
}

$error = '';
$success = '';
$referral_code_input = $_GET['ref'] ?? $_POST['referral_code'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $referral_code = trim($_POST['referral_code'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Mohon isi semua field yang wajib';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");

            if ($stmt->execute([$name, $email, $password_hash])) {
                $userId = $pdo->lastInsertId();

                // Generate referral code for new user
                $newReferralCode = 'DRV' . strtoupper(substr(md5($userId . $email), 0, 6));
                $stmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
                $stmt->execute([$newReferralCode, $userId]);

                // Handle referral if provided
                if (!empty($referral_code)) {
                    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE referral_code = ? AND role = 'customer'");
                    $stmt->execute([$referral_code]);
                    $referrer = $stmt->fetch();

                    if ($referrer) {
                        // Update referred_by
                        $stmt = $pdo->prepare("UPDATE users SET referred_by = ? WHERE id = ?");
                        $stmt->execute([$referrer['id'], $userId]);

                        // Create referral reward (pending until first topup, NO commission yet!)
                        try {
                            $stmt = $pdo->prepare("INSERT INTO referral_rewards (referrer_id, referred_id, status, reward_value) VALUES (?, ?, 'pending', 0)");
                            $stmt->execute([$referrer['id'], $userId]);
                        } catch (PDOException $e) {
                            // Table might not exist yet, ignore
                        }

                        // Increment referrer's total_referrals
                        $stmt = $pdo->prepare("UPDATE users SET total_referrals = total_referrals + 1 WHERE id = ?");
                        $stmt->execute([$referrer['id']]);
                    }
                }

                // Send verification email
                try {
                    require_once __DIR__ . '/../includes/email-helper.php';
                    $verification_token = bin2hex(random_bytes(32));
                    $verification_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

                    $stmt = $pdo->prepare("UPDATE users SET email_verification_token = ?, email_verification_expiry = ?, last_verification_sent = NOW(), verification_attempts = 1 WHERE id = ?");
                    $stmt->execute([$verification_token, $verification_expiry, $userId]);

                    $verification_link = SITE_URL . "auth/verify-email.php?token=" . $verification_token;
                    $emailSent = sendVerificationEmail($email, $name, $verification_link);

                    if ($emailSent) {
                        $_SESSION['pending_verification_email'] = $email;
                        $_SESSION['pending_verification_name'] = $name;
                        redirect('/auth/verification-pending.php');
                    } else {
                        $success = 'Registrasi berhasil! Email verifikasi gagal dikirim. Silakan coba lagi dari halaman login.';
                    }
                } catch (Exception $e) {
                    error_log('Email verification error: ' . $e->getMessage());
                    $success = 'Registrasi berhasil! Silakan login untuk melanjutkan.';
                }
            } else {
                $error = 'Registrasi gagal. Silakan coba lagi.';
            }
        }
    }
}

$page_title = 'Daftar Member Gratis - Buat Akun Dorve House | Belanja Fashion';
$page_description = 'Daftar member Dorve House gratis sekarang! Dapatkan promo eksklusif, gratis ongkir, poin reward, dan akses koleksi baju wanita terbaru. Registrasi mudah dan cepat.';
$page_keywords = 'daftar member, registrasi, buat akun, member gratis, daftar belanja online';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .auth-container {
        max-width: 480px;
        margin: 80px auto;
        padding: 0 24px;
    }

    .auth-card {
        background: var(--white);
        padding: 60px 50px;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 8px;
    }

    .auth-title {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        margin-bottom: 12px;
        text-align: center;
    }

    .auth-subtitle {
        text-align: center;
        color: var(--grey);
        font-size: 14px;
        margin-bottom: 40px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--charcoal);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-input {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid rgba(0,0,0,0.15);
        border-radius: 4px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: border-color 0.3s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--latte);
    }

    .btn-primary {
        width: 100%;
        padding: 16px;
        background: var(--charcoal);
        color: var(--white);
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        transition: background 0.3s;
    }

    .btn-primary:hover {
        background: var(--latte);
        color: var(--charcoal);
    }

    .error-message {
        background: #FFE5E5;
        color: #C41E3A;
        padding: 12px 16px;
        border-radius: 4px;
        font-size: 13px;
        margin-bottom: 24px;
        text-align: center;
    }

    .success-message {
        background: #E5FFE5;
        color: #2E7D32;
        padding: 12px 16px;
        border-radius: 4px;
        font-size: 13px;
        margin-bottom: 24px;
        text-align: center;
    }

    .auth-link {
        text-align: center;
        margin-top: 24px;
        font-size: 14px;
        color: var(--grey);
    }

    .auth-link a {
        color: var(--charcoal);
        text-decoration: none;
        font-weight: 500;
    }

    .auth-link a:hover {
        color: var(--latte);
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Join the Dorve House community</p>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
                <a href="login.php" style="display: block; margin-top: 8px; font-weight: 600;">Login now</a>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Kode Referral (Opsional)</label>
                <input type="text" name="referral_code" class="form-input"
                       value="<?php echo htmlspecialchars($referral_code_input); ?>"
                       placeholder="Masukkan kode referral jika ada"
                       style="text-transform: uppercase;">
                <small style="display: block; margin-top: 6px; color: #999; font-size: 12px;">
                    Punya kode referral dari teman? Masukkan di sini untuk dapat bonus!
                </small>
            </div>

            <button type="submit" class="btn-primary">Create Account</button>
        </form>

        <div class="auth-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
