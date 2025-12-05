<?php
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if ($token) {
    // Find user with this token
    $stmt = $pdo->prepare("
        SELECT id, name, email, email_verified, email_verification_expiry
        FROM users
        WHERE email_verification_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if already verified
        if ($user['email_verified'] == 1) {
            $error = 'Email sudah terverifikasi sebelumnya. Silakan login.';
        } 
        // Check if token expired
        elseif ($user['email_verification_expiry'] && strtotime($user['email_verification_expiry']) < time()) {
            $error = 'Link verifikasi sudah kadaluarsa (lebih dari 24 jam). Silakan minta link verifikasi baru dari halaman login.';
        } 
        else {
            // Token is valid, verify the email
            try {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET email_verified = 1,
                        email_verification_token = NULL,
                        email_verification_expiry = NULL,
                        verification_attempts = 0
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                $success = true;
                
                // Auto login after verification
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = 'customer';
            } catch (Exception $e) {
                error_log('Verification error: ' . $e->getMessage());
                $error = 'Terjadi kesalahan saat verifikasi. Silakan coba lagi.';
            }
        }
    } else {
        $error = 'Link verifikasi tidak valid.';
    }
} else {
    $error = 'Token verifikasi tidak ditemukan.';
}

$page_title = 'Verifikasi Email | Dorve House';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .verify-container {
        max-width: 600px;
        margin: 120px auto;
        padding: 0 20px;
        text-align: center;
    }

    .verify-card {
        background: white;
        padding: 60px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }

    .verify-icon {
        font-size: 72px;
        margin-bottom: 24px;
    }

    .verify-title {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 16px;
        font-family: 'Playfair Display', serif;
    }

    .verify-message {
        font-size: 16px;
        color: var(--grey);
        line-height: 1.6;
        margin-bottom: 32px;
    }

    .verify-actions {
        display: flex;
        gap: 16px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 14px 32px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-block;
    }

    .btn-primary {
        background: var(--charcoal);
        color: white;
    }

    .btn-primary:hover {
        background: #000;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: var(--cream);
        color: var(--charcoal);
    }

    .btn-secondary:hover {
        background: #E0D5C1;
    }

    .error-box {
        background: #FEE;
        border: 1px solid #FCC;
        padding: 20px;
        border-radius: 8px;
        color: #C33;
        margin-bottom: 24px;
    }

    .success-box {
        background: #EFE;
        border: 1px solid #CFC;
        padding: 20px;
        border-radius: 8px;
        color: #363;
    }

    @media (max-width: 768px) {
        .verify-container {
            margin: 60px auto;
        }

        .verify-card {
            padding: 40px 24px;
        }

        .verify-title {
            font-size: 24px;
        }

        .verify-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>

<div class="verify-container">
    <div class="verify-card">
        <?php if ($success): ?>
            <div class="verify-icon">✓</div>
            <h1 class="verify-title">Email Berhasil Diverifikasi!</h1>
            <div class="verify-message">
                <div class="success-box">
                    Selamat! Email Anda telah berhasil diverifikasi.<br>
                    Akun Anda sekarang aktif dan siap digunakan.
                </div>
                <p style="margin-top: 16px;">
                    Terima kasih telah bergabung dengan Dorve House!<br>
                    Anda sekarang dapat login dan mulai berbelanja.
                </p>
            </div>
            <div class="verify-actions">
                <a href="/auth/login.php" class="btn btn-primary">Login Sekarang</a>
                <a href="/" class="btn btn-secondary">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <div class="verify-icon">✗</div>
            <h1 class="verify-title">Verifikasi Gagal</h1>
            <div class="verify-message">
                <div class="error-box">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php if (strpos($error, 'kadaluarsa') !== false): ?>
                    <p>
                        Link verifikasi Anda sudah tidak berlaku.<br>
                        Silakan login untuk mendapatkan link verifikasi baru.
                    </p>
                <?php else: ?>
                    <p>
                        Jika Anda mengalami masalah, silakan hubungi customer support kami.
                    </p>
                <?php endif; ?>
            </div>
            <div class="verify-actions">
                <a href="/auth/login.php" class="btn btn-primary">Ke Halaman Login</a>
                <a href="/" class="btn btn-secondary">Kembali ke Home</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
    <div style="margin-top: 40px; padding: 24px; background: var(--cream); border-radius: 8px; text-align: left;">
        <h3 style="font-size: 18px; margin-bottom: 12px; font-weight: 600;">🎁 Bonus Untuk Anda:</h3>
        <ul style="margin-left: 24px; line-height: 1.8; color: var(--grey);">
            <li>Tier: <strong>Bronze</strong> (akan upgrade otomatis saat topup)</li>
            <li>Dapatkan <strong>referral code</strong> untuk ajak teman</li>
            <li>Voucher welcome untuk pembelian pertama Anda</li>
            <li>Gratis ongkir untuk order tertentu</li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
