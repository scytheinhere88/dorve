<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

$stmt = $pdo->query("SELECT * FROM cms_pages ORDER BY title ASC");
$pages = $stmt->fetchAll();

$page_title = 'Kelola Halaman CMS - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Halaman CMS</h1>
</div>

<div class="content-container">
    <p style="margin-bottom: 20px; color: #6B7280;">Manage static pages like About, Privacy Policy, Terms, etc.</p>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pages)): ?>
                <tr><td colspan="4" style="text-align: center; padding: 40px; color: #6B7280;">No pages yet</td></tr>
            <?php else: ?>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                        <td><code style="background: #F3F4F6; padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($page['slug']); ?></code></td>
                        <td><?php echo date('d M Y', strtotime($page['updated_at'])); ?></td>
                        <td>
                            <a href="/admin/pages/edit.php?id=<?php echo $page['id']; ?>" class="btn btn-secondary">Edit</a>
                            <a href="/pages/<?php echo $page['slug']; ?>.php" target="_blank" class="btn btn-secondary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
