<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$user_id = $_GET['id'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE users SET 
            name = ?, 
            email = ?, 
            phone = ?, 
            address = ?,
            role = ?,
            tier = ?,
            wallet_balance = ?
            WHERE id = ?");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['role'],
            $_POST['tier'],
            $_POST['wallet_balance'],
            $user_id
        ]);
        
        $_SESSION['success'] = 'User updated successfully!';
        redirect('/admin/users/');
    }
    
    if ($action === 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET is_suspended = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User suspended!';
        redirect('/admin/users/edit.php?id=' . $user_id);
    }
    
    if ($action === 'unsuspend') {
        $stmt = $pdo->prepare("UPDATE users SET is_suspended = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User unsuspended!';
        redirect('/admin/users/edit.php?id=' . $user_id);
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User deleted!';
        redirect('/admin/users/');
    }
    
    if ($action === 'update_password') {
        $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password, $user_id]);
        $_SESSION['success'] = 'Password updated!';
        redirect('/admin/users/edit.php?id=' . $user_id);
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'User not found!';
    redirect('/admin/users/');
}

$page_title = 'Edit User - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #374151;
}
.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #D1D5DB;
    border-radius: 6px;
    font-size: 14px;
}
.form-group textarea {
    min-height: 80px;
    resize: vertical;
}
.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}
.btn-primary {
    background: #3B82F6;
    color: white;
}
.btn-danger {
    background: #EF4444;
    color: white;
}
.btn-warning {
    background: #F59E0B;
    color: white;
}
.btn-success {
    background: #10B981;
    color: white;
}
.alert {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}
.alert-success {
    background: #D1FAE5;
    color: #065F46;
}
.user-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.status-active {
    background: #D1FAE5;
    color: #065F46;
}
.status-suspended {
    background: #FEE2E2;
    color: #991B1B;
}
</style>

<div class="header">
    <h1>Edit User: <?php echo htmlspecialchars($user['name']); ?></h1>
    <span class="user-status <?php echo isset($user['is_suspended']) && $user['is_suspended'] ? 'status-suspended' : 'status-active'; ?>">
        <?php echo isset($user['is_suspended']) && $user['is_suspended'] ? 'SUSPENDED' : 'ACTIVE'; ?>
    </span>
</div>

<div class="content-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="action" value="update">
        
        <div class="form-grid">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tier</label>
                <select name="tier">
                    <option value="bronze" <?php echo $user['tier'] === 'bronze' ? 'selected' : ''; ?>>Bronze</option>
                    <option value="silver" <?php echo $user['tier'] === 'silver' ? 'selected' : ''; ?>>Silver</option>
                    <option value="gold" <?php echo $user['tier'] === 'gold' ? 'selected' : ''; ?>>Gold</option>
                    <option value="platinum" <?php echo $user['tier'] === 'platinum' ? 'selected' : ''; ?>>Platinum</option>
                    <option value="vvip" <?php echo $user['tier'] === 'vvip' ? 'selected' : ''; ?>>VVIP</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Wallet Balance (Rp)</label>
                <input type="number" name="wallet_balance" value="<?php echo $user['wallet_balance']; ?>" step="0.01">
            </div>
        </div>
        
        <div class="form-group">
            <label>Address</label>
            <textarea name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">💾 Update User</button>
            <a href="/admin/users/" class="btn" style="background: #6B7280; color: white;">← Back</a>
        </div>
    </form>
    
    <hr style="margin: 30px 0;">
    
    <h3>Change Password</h3>
    <form method="POST" action="" onsubmit="return confirm('Change password for this user?');">
        <input type="hidden" name="action" value="update_password">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" minlength="6" required>
        </div>
        <button type="submit" class="btn btn-warning">🔑 Change Password</button>
    </form>
    
    <hr style="margin: 30px 0;">
    
    <h3>Danger Zone</h3>
    <div class="btn-group">
        <?php if (isset($user['is_suspended']) && $user['is_suspended']): ?>
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="action" value="unsuspend">
                <button type="submit" class="btn btn-success" onclick="return confirm('Unsuspend this user?');">✅ Unsuspend Account</button>
            </form>
        <?php else: ?>
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="action" value="suspend">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Suspend this user?');">⚠️ Suspend Account</button>
            </form>
        <?php endif; ?>
        
        <?php if ($user['role'] !== 'admin'): ?>
            <form method="POST" action="" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger" onclick="return confirm('DELETE this user permanently? This cannot be undone!');">🗑️ Delete Account</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
