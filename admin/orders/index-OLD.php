<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$stmt = $pdo->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

$page_title = 'Orders Management - Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #F8F9FA; color: #1A1A1A; }
        .admin-layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .admin-sidebar { background: #1A1A1A; color: white; padding: 30px 0; }
        .admin-logo { padding: 0 30px 30px; font-size: 24px; font-weight: 600; letter-spacing: 2px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; }
        .admin-nav { list-style: none; }
        .admin-nav a { display: block; padding: 12px 30px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.1); color: white; }
        .admin-main { padding: 40px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .admin-title { font-size: 32px; font-weight: 600; }
        .content-card { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #E8E8E8; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        td { padding: 16px 12px; border-bottom: 1px solid #F0F0F0; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #D4EDDA; color: #155724; }
        .badge-warning { background: #FFF3CD; color: #856404; }
        .btn { padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 500; background: #1A1A1A; color: white; display: inline-block; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE HOUSE ADMIN</div>
            <ul class="admin-nav">
                <li><a href="/admin/index.php">Dashboard</a></li>
                <li><a href="/admin/products/">Products</a></li>
                <li><a href="/admin/categories/">Categories</a></li>
                <li><a href="/admin/orders/" class="active">Orders</a></li>
                <li><a href="/admin/users/">Users</a></li>
                <li><a href="/admin/vouchers/">Vouchers</a></li>
                <li><a href="/admin/shipping/">Shipping</a></li>
                <li><a href="/admin/pages/">CMS Pages</a></li>
                <li><a href="/admin/settings/">Settings</a></li>
                <li><a href="/index.php">← Back to Site</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Orders Management</h1>
            </div>

            <div class="content-card">
                <table>
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Shipping</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td><span class="badge badge-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                <td><span class="badge badge-<?php echo $order['shipping_status'] === 'delivered' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['shipping_status']); ?></span></td>
                                <td><a href="/admin/orders/detail.php?id=<?php echo $order['id']; ?>" class="btn">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
