<?php
require_once __DIR__ . '/../../config.php';
if (!isAdmin()) redirect('/admin/login.php');

// Banners table doesn't exist - use empty array
$banners = [];

$page_title = 'Kelola Promosi & Banner - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Promosi & Banner</h1>
</div>

<div class="content-container">
    <p style="margin-bottom: 20px; color: #6B7280;">Manage homepage banners and promotional content</p>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Image</th>
                <th>Link</th>
                <th>Order</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($banners)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: #6B7280;">No banners yet</td></tr>
            <?php else: ?>
                <?php foreach ($banners as $banner): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($banner['title']); ?></strong></td>
                        <td><?php if ($banner['image_url']): ?><img src="<?php echo htmlspecialchars($banner['image_url']); ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;"><?php endif; ?></td>
                        <td><small style="color: #6B7280;"><?php echo htmlspecialchars($banner['link_url'] ?: 'No link'); ?></small></td>
                        <td><?php echo $banner['display_order']; ?></td>
                        <td><span style="padding: 6px 12px; background: <?php echo $banner['is_active'] ? '#ECFDF5' : '#F3F4F6'; ?>; color: <?php echo $banner['is_active'] ? '#059669' : '#6B7280'; ?>; border-radius: 6px; font-size: 12px; font-weight: 600;"><?php echo $banner['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                        <td><a href="/admin/promotion/edit.php?id=<?php echo $banner['id']; ?>" class="btn btn-secondary">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
