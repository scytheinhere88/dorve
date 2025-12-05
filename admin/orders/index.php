<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = ["o.id IS NOT NULL"];
$params = [];

if ($status !== 'all') {
    $where[] = "o.fulfillment_status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    // Search by: order_number, order ID, tracking_number, customer name, customer email
    $where[] = "(o.order_number LIKE ? OR o.id = ? OR o.tracking_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = is_numeric($search) ? intval($search) : 0; // Order ID search
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Get orders with shipping address
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        oa.city as customer_city,
        oa.province as customer_province,
        bs.waybill_id,
        bs.courier_company,
        bs.courier_service_name,
        bs.label_print_batch_id
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_addresses oa ON o.id = oa.order_id AND oa.type = 'shipping'
    LEFT JOIN biteship_shipments bs ON o.id = bs.order_id
    WHERE $whereClause
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) as total
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_addresses oa ON o.id = oa.order_id AND oa.type = 'shipping'
    WHERE $whereClause
");
$countStmt->execute(array_slice($params, 0, -2)); // Remove limit & offset
$totalOrders = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrders / $limit);

// Get status counts
$statusCounts = [];
$statuses = ['all', 'new', 'waiting_print', 'waiting_pickup', 'in_transit', 'delivered', 'cancelled'];
foreach ($statuses as $s) {
    if ($s === 'all') {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE fulfillment_status = ?");
        $stmt->execute([$s]);
    }
    $statusCounts[$s] = $stmt->fetch()['count'];
}

$page_title = 'Kelola Pesanan - Admin';
include __DIR__ . '/../includes/admin-header.php';
?>

<style>
.search-bar-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.search-bar {
    display: flex;
    gap: 12px;
    align-items: center;
}
.search-bar input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}
.search-bar input:focus {
    outline: none;
    border-color: #3B82F6;
}
.search-bar button {
    padding: 12px 24px;
    background: #3B82F6;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}
.search-bar button:hover {
    background: #2563EB;
}
.search-hint {
    margin-top: 8px;
    font-size: 12px;
    color: #6B7280;
}
.search-hint code {
    background: #F3F4F6;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}
.order-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #E5E7EB;
    padding-bottom: 0;
    flex-wrap: wrap;
}
.order-tab {
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6B7280;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.order-tab:hover {
    color: #374151;
    background: #F9FAFB;
}
.order-tab.active {
    color: #3B82F6;
    border-bottom-color: #3B82F6;
}
.order-tab .badge {
    background: #E5E7EB;
    color: #374151;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.order-tab.active .badge {
    background: #DBEAFE;
    color: #3B82F6;
}
.order-table {
    width: 100%;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.order-table table {
    width: 100%;
    border-collapse: collapse;
}
.order-table th {
    background: #F9FAFB;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    border-bottom: 1px solid #E5E7EB;
}
.order-table td {
    padding: 16px;
    border-bottom: 1px solid #F3F4F6;
    font-size: 14px;
}
.order-table tr:last-child td {
    border-bottom: none;
}
.order-table tr:hover {
    background: #F9FAFB;
}
.status-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.status-new { background: #FEF3C7; color: #92400E; }
.status-waiting_print { background: #DBEAFE; color: #1E40AF; }
.status-waiting_pickup { background: #E0E7FF; color: #3730A3; }
.status-in_transit { background: #DDD6FE; color: #5B21B6; }
.status-delivered { background: #D1FAE5; color: #065F46; }
.status-cancelled { background: #FEE2E2; color: #991B1B; }
.bulk-actions {
    background: white;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    align-items: center;
    gap: 12px;
}
.bulk-actions.show {
    display: flex;
}
.pagination {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-top: 24px;
}
.pagination a, .pagination span {
    padding: 8px 12px;
    border: 1px solid #E5E7EB;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
}
.pagination a:hover {
    background: #F3F4F6;
}
.pagination .active {
    background: #3B82F6;
    color: white;
    border-color: #3B82F6;
}
.order-id-display {
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: #6B7280;
    display: block;
    margin-top: 2px;
}
</style>

<div class="header">
    <h1>📦 Order Management</h1>
</div>

<!-- Enhanced Search Bar -->
<div class="search-bar-container">
    <div class="search-bar">
        <input type="text" 
               id="searchInput" 
               placeholder="🔍 Cari berdasarkan Order Number, Order ID, Tracking, Nama Customer, atau Email..." 
               value="<?php echo htmlspecialchars($search); ?>"
               onkeypress="if(event.key==='Enter') searchOrders()">
        <button onclick="searchOrders()">Cari</button>
        <?php if (!empty($search)): ?>
            <button onclick="clearSearch()" style="background: #6B7280;">Clear</button>
        <?php endif; ?>
    </div>
    <div class="search-hint">
        💡 Tips: Cari dengan <code>Order Number</code> (DRV-xxx), <code>Order ID</code> (#123), <code>Tracking Number</code>, <code>Nama</code>, atau <code>Email</code> customer
    </div>
    <?php if (!empty($search)): ?>
        <div style="margin-top: 12px; padding: 12px; background: #DBEAFE; border-radius: 6px; border-left: 4px solid #3B82F6;">
            <strong style="color: #1E40AF;">Hasil pencarian untuk:</strong> 
            <code style="background: white; padding: 4px 8px; border-radius: 4px; color: #1E40AF; font-weight: 600;"><?php echo htmlspecialchars($search); ?></code>
            <span style="color: #1E40AF; margin-left: 12px;">(<?php echo $totalOrders; ?> order ditemukan)</span>
        </div>
    <?php endif; ?>
</div>

<!-- Bulk Actions Bar (Hidden by default) -->
<div class="bulk-actions" id="bulkActions">
    <span id="selectedCount">0 pesanan dipilih</span>
    <button class="btn btn-primary" onclick="printSelectedLabels()">🖨️ Print Labels</button>
    <button class="btn btn-secondary" onclick="updateSelectedStatus('waiting_pickup')">📤 Mark as Waiting Pickup</button>
    <button class="btn btn-secondary" onclick="clearSelection()">✕ Clear Selection</button>
</div>

<!-- Status Tabs -->
<div class="order-tabs">
    <a href="?status=all<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="order-tab <?php echo $status === 'all' ? 'active' : ''; ?>">
        📋 Semua <span class="badge"><?php echo $statusCounts['all']; ?></span>
    </a>
    <a href="?status=new<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="order-tab <?php echo $status === 'new' ? 'active' : ''; ?>">
        🆕 Baru <span class="badge"><?php echo $statusCounts['new']; ?></span>
    </a>
    <a href="?status=waiting_print<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="order-tab <?php echo $status === 'waiting_print' ? 'active' : ''; ?>">
        🖨️ Siap Print <span class="badge"><?php echo $statusCounts['waiting_print']; ?></span>
    </a>
    <a href="?status=waiting_pickup<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="order-tab <?php echo $status === 'waiting_pickup' ? 'active' : ''; ?>">
        📤 Menunggu Pickup <span class="badge"><?php echo $statusCounts['waiting_pickup']; ?></span>
    </a>
    <a href="?status=in_transit<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="order-tab <?php echo $status === 'in_transit' ? 'active' : ''; ?>">
        🚚 Dalam Pengiriman <span class="badge"><?php echo $statusCounts['in_transit']; ?></span>
    </a>
    <a href="?status=delivered<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="order-tab <?php echo $status === 'delivered' ? 'active' : ''; ?>">
        ✅ Terkirim <span class="badge"><?php echo $statusCounts['delivered']; ?></span>
    </a>
    <a href="?status=cancelled<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="order-tab <?php echo $status === 'cancelled' ? 'active' : ''; ?>">
        ❌ Dibatalkan <span class="badge"><?php echo $statusCounts['cancelled']; ?></span>
    </a>
</div>

<div class="header">
    <h1>Kelola Pesanan</h1>
</div>

<!-- Orders Table -->
<div class="order-table">
    <table>
        <thead>
            <tr>
                <th width="40"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                <th>Order Number / ID</th>
                <th>Customer</th>
                <th>Tujuan</th>
                <th>Kurir</th>
                <th>Tracking</th>
                <th>Status</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px; color: #9CA3AF;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                        <p>Tidak ada pesanan ditemukan</p>
                        <?php if (!empty($search)): ?>
                            <button onclick="clearSearch()" style="margin-top: 12px; padding: 8px 16px; background: #3B82F6; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                Tampilkan Semua Order
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox" value="<?php echo $order['id']; ?>" 
                                   data-has-waybill="<?php echo !empty($order['waybill_id']) ? '1' : '0'; ?>">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            <span class="order-id-display">Order ID: #<?php echo $order['id']; ?></span>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></div>
                            <small style="color: #6B7280;"><?php echo htmlspecialchars($order['customer_email'] ?? '-'); ?></small>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($order['customer_city'] ?? '-'); ?></div>
                            <small style="color: #6B7280;"><?php echo htmlspecialchars($order['customer_province'] ?? '-'); ?></small>
                        </td>
                        <td>
                            <?php if ($order['courier_company']): ?>
                                <div><strong><?php echo strtoupper($order['courier_company']); ?></strong></div>
                                <small style="color: #6B7280;"><?php echo htmlspecialchars($order['courier_service_name'] ?? ''); ?></small>
                            <?php else: ?>
                                <span style="color: #9CA3AF;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($order['waybill_id']): ?>
                                <a href="/admin/orders/detail.php?id=<?php echo $order['id']; ?>" 
                                   style="color: #3B82F6; text-decoration: none; font-family: monospace; font-size: 12px;">
                                    <?php echo htmlspecialchars($order['waybill_id']); ?>
                                </a>
                            <?php elseif ($order['tracking_number']): ?>
                                <span style="font-family: monospace; font-size: 12px; color: #6B7280;">
                                    <?php echo htmlspecialchars($order['tracking_number']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #9CA3AF;">Belum ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $order['fulfillment_status']; ?>">
                                <?php 
                                $statusLabels = [
                                    'new' => 'Baru',
                                    'waiting_print' => 'Siap Print',
                                    'waiting_pickup' => 'Menunggu Pickup',
                                    'in_transit' => 'Dalam Pengiriman',
                                    'delivered' => 'Terkirim',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                echo $statusLabels[$order['fulfillment_status']] ?? $order['fulfillment_status'];
                                ?>
                            </span>
                        </td>
                        <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                        <td>
                            <small><?php echo date('d M Y', strtotime($order['created_at'])); ?></small>
                        </td>
                        <td>
                            <a href="/admin/orders/detail.php?id=<?php echo $order['id']; ?>" 
                               style="color: #3B82F6; text-decoration: none; font-weight: 500;">
                                Detail →
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?status=<?php echo $status; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">« Prev</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?status=<?php echo $status; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?status=<?php echo $status; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next »</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
// Search functionality
function searchOrders() {
    const query = document.getElementById('searchInput').value;
    const currentStatus = '<?php echo $status; ?>';
    window.location.href = '?status=' + currentStatus + '&search=' + encodeURIComponent(query);
}

function clearSearch() {
    const currentStatus = '<?php echo $status; ?>';
    window.location.href = '?status=' + currentStatus;
}

// Checkbox management
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActions();
}

document.querySelectorAll('.order-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (checked.length > 0) {
        bulkActions.classList.add('show');
        selectedCount.textContent = `${checked.length} pesanan dipilih`;
    } else {
        bulkActions.classList.remove('show');
    }
}

function clearSelection() {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}

function printSelectedLabels() {
    const checked = Array.from(document.querySelectorAll('.order-checkbox:checked'));
    const orderIds = checked.map(cb => cb.value);
    
    if (orderIds.length === 0) {
        alert('Pilih minimal 1 pesanan untuk print label');
        return;
    }
    
    // Check if all selected orders have waybill
    const withoutWaybill = checked.filter(cb => cb.dataset.hasWaybill === '0');
    if (withoutWaybill.length > 0) {
        alert(`${withoutWaybill.length} pesanan belum memiliki waybill. Hanya pesanan dengan waybill yang bisa diprint.`);
        return;
    }
    
    // Open print page in new window
    const url = '/admin/orders/print-batch.php?order_ids=' + orderIds.join(',');
    window.open(url, '_blank', 'width=800,height=600');
}

function updateSelectedStatus(newStatus) {
    const checked = Array.from(document.querySelectorAll('.order-checkbox:checked'));
    const orderIds = checked.map(cb => cb.value);
    
    if (orderIds.length === 0) {
        alert('Pilih minimal 1 pesanan');
        return;
    }
    
    if (!confirm(`Update status ${orderIds.length} pesanan ke "${newStatus}"?`)) {
        return;
    }
    
    // Send AJAX request
    fetch('/admin/orders/update-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_ids: orderIds, status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status berhasil diupdate!');
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
