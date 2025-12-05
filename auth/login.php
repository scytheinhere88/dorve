<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) {
    redirect('/member/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check email verification for customers (admin can skip)
            if ($user['role'] === 'customer' && !$user['email_verified']) {
                $error = '<strong>⚠️ Aktivasi Akun Diperlukan!</strong><br><br>Email Anda belum diverifikasi. Silakan cek inbox atau folder spam email Anda dan klik link aktivasi.<br><br>Belum menerima email? <a href="/auth/resend-verification.php?email=' . urlencode($email) . '" style="color: #1A1A1A; text-decoration: underline; font-weight: 600;">Kirim Ulang Email Verifikasi</a>';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_admin'] = ($user['role'] === 'admin') ? 1 : 0;

                $old_session_id = session_id();
                $stmt = $pdo->prepare("UPDATE cart_items SET user_id = ?, session_id = NULL WHERE session_id = ?");
                $stmt->execute([$user['id'], $old_session_id]);

                redirect('/member/dashboard.php');
            }
        } else {
            $error = 'Email atau password salah';
        }
    }
}

$page_title = 'Login Member - Masuk Akun Dorve House | Belanja Fashion Online';
$page_description = 'Login ke akun member Dorve House untuk belanja baju wanita online. Akses riwayat pesanan, wishlist, dan dapatkan promo eksklusif member. Daftar gratis sekarang!';
$page_keywords = 'login, masuk akun, member dorve, login belanja online, akun member';
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

    .divider {
        text-align: center;
        margin: 30px 0;
        position: relative;
    }

    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: rgba(0,0,0,0.1);
    }

    .divider span {
        background: var(--white);
        padding: 0 16px;
        position: relative;
        color: var(--grey);
        font-size: 13px;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Login to your Dorve House account</p>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Create one</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
