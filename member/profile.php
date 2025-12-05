<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if ($name) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $_SESSION['user_id']]);
        $success = 'Profile updated successfully!';
        $user = getCurrentUser();
    } else {
        $error = 'Name is required';
    }
}

$page_title = 'Edit Profil - Ubah Data & Alamat Pengiriman | Member Dorve';
$page_description = 'Edit profil member Dorve: ubah data pribadi, alamat pengiriman, nomor HP. Pastikan data Anda selalu update untuk pengalaman belanja lebih baik.';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .member-layout { max-width: 1400px; margin: 80px auto; padding: 0 40px; display: grid; grid-template-columns: 280px 1fr; gap: 60px; }
    .member-sidebar { position: sticky; top: 120px; height: fit-content; }
    .sidebar-header { padding: 30px; background: var(--cream); margin-bottom: 24px; border-radius: 8px; }
    .sidebar-header h3 { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 8px; }
    .sidebar-header p { font-size: 14px; color: var(--grey); }
    .sidebar-nav { list-style: none; }
    .sidebar-nav li { margin-bottom: 8px; }
    .sidebar-nav a { display: block; padding: 14px 20px; color: var(--charcoal); text-decoration: none; transition: all 0.3s; border-radius: 4px; font-size: 14px; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--cream); padding-left: 28px; }
    .logout-btn { margin-top: 24px; display: block; width: 100%; padding: 14px 20px; background: var(--white); border: 1px solid rgba(0,0,0,0.15); color: #C41E3A; text-decoration: none; text-align: center; border-radius: 4px; font-size: 14px; transition: all 0.3s; }
    .logout-btn:hover { background: #C41E3A; color: var(--white); }
    .member-content h1 { font-family: 'Playfair Display', serif; font-size: 36px; margin-bottom: 40px; }
    .form-card { background: var(--white); border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; padding: 40px; max-width: 600px; }
    .form-group { margin-bottom: 24px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
    .form-group input { width: 100%; padding: 14px 16px; border: 1px solid rgba(0,0,0,0.15); border-radius: 4px; font-size: 15px; font-family: 'Inter', sans-serif; }
    .form-group input:focus { outline: none; border-color: var(--charcoal); }
    .btn { padding: 14px 32px; background: var(--charcoal); color: var(--white); border: none; border-radius: 4px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.3s; }
    .btn:hover { background: #000; }
    .alert { padding: 16px; border-radius: 4px; margin-bottom: 24px; font-size: 14px; }
    .alert-success { background: #D4EDDA; color: #155724; border: 1px solid #C3E6CB; }
    .alert-error { background: #F8D7DA; color: #721C24; border: 1px solid #F5C6CB; }
</style>

<div class="member-layout">
    <aside class="member-sidebar">
        <div class="sidebar-header">
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <ul class="sidebar-nav">
            <li><a href="/member/dashboard.php">Dashboard</a></li>
            <li><a href="/member/wallet.php">My Wallet</a></li>
            <li><a href="/member/orders.php">My Orders</a></li>
            <li><a href="/member/reviews.php">Reviews</a></li>
            <li><a href="/member/profile.php" class="active">Edit Profile</a></li>
            <li><a href="/member/address.php">Address Book</a></li>
            <li><a href="/member/password.php">Change Password</a></li>
        </ul>

        <a href="/auth/logout.php" class="logout-btn">Logout</a>
    </aside>

    <div class="member-content">
        <h1>Edit Profile</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #F5F5F5; cursor: not-allowed;">
                    <small style="color: var(--grey); font-size: 13px; margin-top: 4px; display: block;">Email cannot be changed</small>
                </div>

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
