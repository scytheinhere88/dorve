<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$page_title = 'Kelola Pengguna - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Pengguna</h1>
</div>

<div class="content-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tier</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span style="padding: 4px 10px; background: <?php echo $user['role'] === 'admin' ? '#DBEAFE' : '#F3F4F6'; ?>; color: <?php echo $user['role'] === 'admin' ? '#1E40AF' : '#374151'; ?>; border-radius: 6px; font-size: 12px; font-weight: 600;"><?php echo ucfirst($user['role']); ?></span></td>
                    <td><?php echo strtoupper($user['tier'] ?? 'bronze'); ?></td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td><a href="/admin/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">Edit</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
