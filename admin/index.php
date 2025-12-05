<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) redirect('/admin/login.php');

// Get statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_deposits = $pdo->query("SELECT COUNT(*) FROM wallet_topups WHERE payment_status = 'pending'")->fetchColumn();
$recent_orders = $pdo->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

$page_title = 'Dashboard - Admin';
include __DIR__ . '/includes/admin-header.php';
?>

<div class="header">
    <h1>Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_users; ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_products; ?></div>
        <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_orders; ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $pending_deposits; ?></div>
        <div class="stat-label">Pending Deposits</div>
    </div>
</div>

<div class="content-container">
    <h2 style="margin-bottom: 20px; font-size: 20px; font-weight: 600;">Recent Orders</h2>
    <table>
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_orders)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 40px; color: #6B7280;">No orders yet</td></tr>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></td>
                        <td><span style="padding: 6px 12px; background: #FEF3C7; color: #92400E; border-radius: 6px; font-size: 12px; font-weight: 600;"><?php echo ucfirst($order['payment_status']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
