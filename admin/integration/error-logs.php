<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isAdmin()) {
    redirect('/admin/login.php');
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if ($filter === 'unprocessed') {
    $where[] = 'processed = 0';
} elseif ($filter === 'errors') {
    $where[] = 'error_message IS NOT NULL AND error_message != \"\"';
}

$whereClause = empty($where) ? '1=1' : implode(' AND ', $where);

// Get logs
$stmt = $pdo->prepare("
    SELECT * FROM biteship_webhook_logs
    WHERE $whereClause
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$limit, $offset]));
$logs = $stmt->fetchAll();

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM biteship_webhook_logs WHERE $whereClause");
$countStmt->execute($params);
$totalLogs = $countStmt->fetch()['total'];
$totalPages = ceil($totalLogs / $limit);

// Get statistics
$stats = [];
$stats['total'] = $pdo->query("SELECT COUNT(*) FROM biteship_webhook_logs")->fetchColumn();
$stats['unprocessed'] = $pdo->query("SELECT COUNT(*) FROM biteship_webhook_logs WHERE processed = 0")->fetchColumn();
$stats['errors'] = $pdo->query("SELECT COUNT(*) FROM biteship_webhook_logs WHERE error_message IS NOT NULL AND error_message != ''")->fetchColumn();
$stats['today'] = $pdo->query("SELECT COUNT(*) FROM biteship_webhook_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();

include __DIR__ . '/../includes/admin-header.php';
?>

<style>
.header-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.stat-card h3 {
    font-size: 14px;
    color: #6B7280;
    margin-bottom: 8px;
    font-weight: 500;
}
.stat-card .value {
    font-size: 32px;
    font-weight: 700;
    color: #1F2937;
}
.stat-card.error .value {
    color: #EF4444;
}
.stat-card.warning .value {
    color: #F59E0B;
}
.stat-card.success .value {
    color: #10B981;
}
.filter-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #E5E7EB;
    padding-bottom: 0;
}
.filter-tab {
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6B7280;
    text-decoration: none;
    transition: all 0.2s;
}
.filter-tab:hover {
    color: #374151;
    background: #F9FAFB;
}
.filter-tab.active {
    color: #3B82F6;
    border-bottom-color: #3B82F6;
}
.log-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.log-table table {
    width: 100%;
    border-collapse: collapse;
}
.log-table th {
    background: #F9FAFB;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 13px;
    border-bottom: 1px solid #E5E7EB;
}
.log-table td {
    padding: 16px;
    border-bottom: 1px solid #F3F4F6;
    font-size: 14px;
}
.log-table tr:hover {
    background: #F9FAFB;
}
.event-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}
.event-status { background: #DBEAFE; color: #1E40AF; }
.event-waybill { background: #DDD6FE; color: #5B21B6; }
.event-unknown { background: #F3F4F6; color: #6B7280; }
.status-processed { color: #10B981; }
.status-unprocessed { color: #F59E0B; }
.status-error { color: #EF4444; }
.payload-preview {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #6B7280;
}
.btn-view {
    padding: 6px 12px;
    background: #3B82F6;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}
</style>

<div class="header">
    <h1>📊 Biteship Integration - Error & Webhook Logs</h1>
</div>

<!-- Statistics Cards -->
<div class="header-stats">
    <div class="stat-card">
        <h3>Total Webhooks</h3>
        <div class="value"><?php echo number_format($stats['total']); ?></div>
    </div>
    <div class="stat-card success">
        <h3>Today's Webhooks</h3>
        <div class="value"><?php echo number_format($stats['today']); ?></div>
    </div>
    <div class="stat-card warning">
        <h3>Unprocessed</h3>
        <div class="value"><?php echo number_format($stats['unprocessed']); ?></div>
    </div>
    <div class="stat-card error">
        <h3>Errors</h3>
        <div class="value"><?php echo number_format($stats['errors']); ?></div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
        📋 All Logs
    </a>
    <a href="?filter=unprocessed" class="filter-tab <?php echo $filter === 'unprocessed' ? 'active' : ''; ?>">
        ⏳ Unprocessed (<?php echo $stats['unprocessed']; ?>)
    </a>
    <a href="?filter=errors" class="filter-tab <?php echo $filter === 'errors' ? 'active' : ''; ?>">
        ❌ Errors (<?php echo $stats['errors']; ?>)
    </a>
</div>

<!-- Logs Table -->
<div class="log-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event</th>
                <th>Biteship Order ID</th>
                <th>Status</th>
                <th>Payload Preview</th>
                <th>Timestamp</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #9CA3AF;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                        <p>No logs found</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><strong>#<?php echo $log['id']; ?></strong></td>
                        <td>
                            <?php
                            $eventClass = 'event-unknown';
                            if (strpos($log['event'], 'status') !== false) $eventClass = 'event-status';
                            elseif (strpos($log['event'], 'waybill') !== false) $eventClass = 'event-waybill';
                            ?>
                            <span class="event-badge <?php echo $eventClass; ?>">
                                <?php echo htmlspecialchars($log['event']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($log['biteship_order_id']): ?>
                                <code style="font-size: 12px;"><?php echo htmlspecialchars($log['biteship_order_id']); ?></code>
                            <?php else: ?>
                                <span style="color: #9CA3AF;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['error_message']): ?>
                                <span class="status-error">❌ Error</span>
                            <?php elseif ($log['processed']): ?>
                                <span class="status-processed">✅ Processed</span>
                            <?php else: ?>
                                <span class="status-unprocessed">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="payload-preview"><?php echo htmlspecialchars(substr($log['payload'], 0, 80)); ?>...</div>
                            <?php if ($log['error_message']): ?>
                                <div style="color: #EF4444; font-size: 12px; margin-top: 4px;">
                                    <?php echo htmlspecialchars($log['error_message']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px; color: #6B7280;">
                            <?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?>
                        </td>
                        <td>
                            <button class="btn-view" onclick="viewLog(<?php echo $log['id']; ?>)">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination" style="margin-top: 24px; display: flex; gap: 8px; justify-content: center;">
    <?php if ($page > 1): ?>
        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" style="padding: 8px 12px; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; color: #374151;">« Prev</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
            <span style="padding: 8px 12px; background: #3B82F6; color: white; border-radius: 6px;"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" style="padding: 8px 12px; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; color: #374151;"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" style="padding: 8px 12px; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; color: #374151;">Next »</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal for viewing full log -->
<div id="logModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; padding: 40px;" onclick="if(event.target === this) closeModal()">
    <div style="max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; max-height: 90vh; overflow: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Webhook Log Details</h2>
            <button onclick="closeModal()" style="padding: 8px 16px; border: none; background: #E5E7EB; border-radius: 6px; cursor: pointer;">Close</button>
        </div>
        <div id="logContent"></div>
    </div>
</div>

<script>
function viewLog(logId) {
    fetch('/admin/integration/get-log.php?id=' + logId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const log = data.log;
                let html = '<div style="font-size: 14px;">';
                html += '<div style="margin-bottom: 20px; padding: 16px; background: #F9FAFB; border-radius: 8px;">';
                html += '<strong>Event:</strong> ' + log.event + '<br>';
                html += '<strong>Biteship Order ID:</strong> ' + (log.biteship_order_id || '-') + '<br>';
                html += '<strong>Status:</strong> ' + (log.processed ? '✅ Processed' : '⏳ Unprocessed') + '<br>';
                html += '<strong>Timestamp:</strong> ' + log.created_at + '<br>';
                if (log.error_message) {
                    html += '<strong style="color: #EF4444;">Error:</strong> <span style="color: #EF4444;">' + log.error_message + '</span><br>';
                }
                html += '</div>';
                html += '<div><strong>Full Payload:</strong></div>';
                html += '<pre style="background: #1F2937; color: #F9FAFB; padding: 16px; border-radius: 8px; overflow-x: auto; margin-top: 12px; font-size: 12px;">' + JSON.stringify(JSON.parse(log.payload), null, 2) + '</pre>';
                html += '</div>';
                document.getElementById('logContent').innerHTML = html;
                document.getElementById('logModal').style.display = 'block';
            }
        })
        .catch(err => {
            alert('Error loading log: ' + err.message);
        });
}

function closeModal() {
    document.getElementById('logModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>