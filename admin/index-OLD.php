<?php
require_once __DIR__ . '/../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

$stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'published'");
$total_products = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$total_orders = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$total_users = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'");
$total_revenue = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
$recent_orders = $stmt->fetchAll();

$page_title = 'Admin Dashboard - Dorve';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #F8F9FA;
            color: #1A1A1A;
        }

        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .admin-sidebar {
            background: #1A1A1A;
            color: white;
            padding: 30px 0;
        }

        .admin-logo {
            padding: 0 30px 30px;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 2px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav a {
            display: block;
            padding: 12px 30px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .admin-main {
            padding: 40px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .admin-title {
            font-size: 32px;
            font-weight: 600;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #6F6F6F;
            font-size: 14px;
        }

        .content-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #E8E8E8;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6F6F6F;
        }

        td {
            padding: 16px 12px;
            border-bottom: 1px solid #F0F0F0;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        .btn-primary {
            background: #1A1A1A;
            color: white;
        }

        .btn-secondary {
            background: #E8E8E8;
            color: #1A1A1A;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #D4EDDA;
            color: #155724;
        }

        .badge-warning {
            background: #FFF3CD;
            color: #856404;
        }

        .badge-danger {
            background: #F8D7DA;
            color: #721C24;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">DORVE HOUSE ADMIN</div>
            <ul class="admin-nav">
                <li><a href="/admin/index.php" class="active">Dashboard</a></li>
                <li><a href="/admin/products/">Products</a></li>
                <li><a href="/admin/categories/">Categories</a></li>
                <li><a href="/admin/orders/">Orders</a></li>
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
                <h1 class="admin-title">Dashboard</h1>
                <div class="admin-user">
                    <span>Admin</span>
                    <a href="/auth/logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatPrice($total_revenue); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <div class="content-card">
                <h2 class="card-title">Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <?php
                            $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                            $stmt->execute([$order['user_id']]);
                            $customer = $stmt->fetch();
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $order['shipping_status'] === 'delivered' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['shipping_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/orders/detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
