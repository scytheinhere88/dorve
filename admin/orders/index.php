<?php
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Get all orders
$stmt = $pdo->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

$page_title = 'Kelola Pesanan - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<div class="header">
    <h1>Kelola Pesanan</h1>
</div>

<div class="content-container">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Shipping</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #6B7280;">
                            Belum ada pesanan
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <small style="color: #6B7280;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                            </td>
                            <td><strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></td>
                            <td>
                                <?php
                                $payment_badge = '';
                                switch($order['payment_status']) {
                                    case 'paid':
                                        $payment_badge = '<span style="padding: 6px 12px; background: #ECFDF5; color: #059669; border-radius: 6px; font-size: 13px; font-weight: 600;">Paid</span>';
                                        break;
                                    case 'pending':
                                        $payment_badge = '<span style="padding: 6px 12px; background: #FEF3C7; color: #92400E; border-radius: 6px; font-size: 13px; font-weight: 600;">Pending</span>';
                                        break;
                                    default:
                                        $payment_badge = '<span style="padding: 6px 12px; background: #F3F4F6; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 600;">' . ucfirst($order['payment_status']) . '</span>';
                                }
                                echo $payment_badge;
                                ?>
                            </td>
                            <td>
                                <?php
                                $shipping_badge = '';
                                switch($order['shipping_status']) {
                                    case 'delivered':
                                        $shipping_badge = '<span style="padding: 6px 12px; background: #ECFDF5; color: #059669; border-radius: 6px; font-size: 13px; font-weight: 600;">Delivered</span>';
                                        break;
                                    case 'shipped':
                                        $shipping_badge = '<span style="padding: 6px 12px; background: #DBEAFE; color: #1E40AF; border-radius: 6px; font-size: 13px; font-weight: 600;">Shipped</span>';
                                        break;
                                    case 'processing':
                                        $shipping_badge = '<span style="padding: 6px 12px; background: #FEF3C7; color: #92400E; border-radius: 6px; font-size: 13px; font-weight: 600;">Processing</span>';
                                        break;
                                    default:
                                        $shipping_badge = '<span style="padding: 6px 12px; background: #F3F4F6; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 600;">' . ucfirst($order['shipping_status']) . '</span>';
                                }
                                echo $shipping_badge;
                                ?>
                            </td>
                            <td>
                                <a href="/admin/orders/detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
