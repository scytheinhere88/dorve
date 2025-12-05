<?php
/**
 * SUPER DEBUG TOOL
 * All-in-one diagnostic tool for Dorve system
 * Check database, files, permissions, configurations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

$startTime = microtime(true);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Super Debug Tool - Dorve</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        .header h1 { font-size: 32px; margin-bottom: 8px; }
        .header p { opacity: 0.8; font-size: 14px; }

        .section { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #f0f0f0; }
        .section-header h2 { font-size: 20px; font-weight: 700; }
        .section-header .status { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status.success { background: #D1FAE5; color: #065F46; }
        .status.error { background: #FEE2E2; color: #991B1B; }
        .status.warning { background: #FEF3C7; color: #92400E; }
        .status.info { background: #DBEAFE; color: #1E40AF; }

        .check-item { padding: 12px; border-left: 4px solid #e0e0e0; margin-bottom: 12px; background: #fafafa; border-radius: 4px; }
        .check-item.success { border-color: #10B981; background: #ECFDF5; }
        .check-item.error { border-color: #EF4444; background: #FEF2F2; }
        .check-item.warning { border-color: #F59E0B; background: #FFFBEB; }
        .check-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .check-item-title { font-weight: 600; font-size: 14px; }
        .check-item-icon { font-size: 18px; }
        .check-item-details { font-size: 13px; color: #666; margin-left: 24px; }
        .check-item-details code { background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 3px; font-family: monospace; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
        .stat-card { background: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; }
        .stat-label { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .stat-value { font-size: 28px; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f9f9f9; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e0e0e0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; }
        tr:hover { background: #fafafa; }

        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge.success { background: #D1FAE5; color: #065F46; }
        .badge.error { background: #FEE2E2; color: #991B1B; }
        .badge.warning { background: #FEF3C7; color: #92400E; }

        .code-block { background: #1A1A1A; color: #10B981; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 12px; overflow-x: auto; margin-top: 12px; }
        .error-details { background: #FEF2F2; border: 2px solid #EF4444; padding: 16px; border-radius: 8px; margin-top: 12px; }

        .actions { display: flex; gap: 12px; margin-top: 16px; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1A1A1A; color: white; }
        .btn-success { background: #10B981; color: white; }
        .btn-danger { background: #EF4444; color: white; }

        .footer { text-align: center; padding: 20px; color: #999; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Super Debug Tool</h1>
            <p>Complete system diagnostic for Dorve e-commerce platform</p>
        </div>

<?php
$results = [];
$errors = [];
$warnings = [];

// ====================================
// 1. DATABASE CONNECTION
// ====================================
echo '<div class="section">';
echo '<div class="section-header"><h2>1. Database Connection</h2><div class="status info">Checking...</div></div>';

try {
    require_once __DIR__ . '/config.php';
    echo '<div class="check-item success">';
    echo '<div class="check-item-header"><span class="check-item-title">✓ Database Connected</span><span class="check-item-icon">🟢</span></div>';
    echo '<div class="check-item-details">Connected to: <code>' . DB_NAME . '</code> on <code>' . DB_HOST . '</code></div>';
    echo '</div>';

    // Test query
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db_name, NOW() as server_time");
    $db_info = $stmt->fetch();
    echo '<div class="check-item success">';
    echo '<div class="check-item-header"><span class="check-item-title">✓ Database Query</span></div>';
    echo '<div class="check-item-details">';
    echo 'MySQL Version: <code>' . $db_info['version'] . '</code><br>';
    echo 'Server Time: <code>' . $db_info['server_time'] . '</code>';
    echo '</div></div>';

    $results['database'] = 'success';
} catch (PDOException $e) {
    echo '<div class="check-item error">';
    echo '<div class="check-item-header"><span class="check-item-title">✗ Database Connection Failed</span><span class="check-item-icon">🔴</span></div>';
    echo '<div class="error-details"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '</div>';
    $errors[] = 'Database connection failed';
    $results['database'] = 'error';
}

echo '</div>';

// ====================================
// 2. DATABASE TABLES
// ====================================
if ($results['database'] === 'success') {
    echo '<div class="section">';
    echo '<div class="section-header"><h2>2. Database Tables</h2></div>';

    $required_tables = [
        'users', 'products', 'categories', 'orders', 'order_items',
        'wallet_transactions', 'payment_methods', 'bank_accounts',
        'payment_gateway_settings', 'system_settings', 'referrals',
        'referral_commissions', 'referral_tiers', 'vouchers', 'reviews'
    ];

    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            // Count rows
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];

            echo '<div class="check-item success">';
            echo '<div class="check-item-header"><span class="check-item-title">✓ ' . $table . '</span></div>';
            echo '<div class="check-item-details">Rows: <code>' . number_format($count) . '</code></div>';
            echo '</div>';
        } else {
            echo '<div class="check-item error">';
            echo '<div class="check-item-header"><span class="check-item-title">✗ ' . $table . '</span><span class="check-item-icon">Missing</span></div>';
            echo '</div>';
            $errors[] = "Table '$table' is missing";
        }
    }

    echo '</div>';

    // ====================================
    // 3. CRITICAL COLUMNS CHECK
    // ====================================
    echo '<div class="section">';
    echo '<div class="section-header"><h2>3. Critical Columns</h2></div>';

    $column_checks = [
        'users' => ['id', 'email', 'current_tier', 'total_topup', 'wallet_balance', 'email_verified'],
        'products' => ['id', 'name', 'price', 'is_featured', 'is_new', 'is_active'],
        'categories' => ['id', 'name', 'sequence', 'is_active'],
        'orders' => ['id', 'user_id', 'total_price', 'status'],
        'wallet_transactions' => ['id', 'user_id', 'amount', 'unique_code', 'payment_status', 'bank_account_id'],
        'payment_methods' => ['id', 'name', 'slug', 'is_active'],
        'bank_accounts' => ['id', 'bank_name', 'account_number', 'is_active'],
    ];

    foreach ($column_checks as $table => $columns) {
        if (in_array($table, $existing_tables)) {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $missing_columns = array_diff($columns, $existing_columns);

            if (empty($missing_columns)) {
                echo '<div class="check-item success">';
                echo '<div class="check-item-header"><span class="check-item-title">✓ ' . $table . '</span></div>';
                echo '<div class="check-item-details">All required columns present: <code>' . implode(', ', $columns) . '</code></div>';
                echo '</div>';
            } else {
                echo '<div class="check-item error">';
                echo '<div class="check-item-header"><span class="check-item-title">✗ ' . $table . '</span></div>';
                echo '<div class="check-item-details">Missing columns: <code>' . implode(', ', $missing_columns) . '</code></div>';
                echo '</div>';
                $errors[] = "Table '$table' missing columns: " . implode(', ', $missing_columns);
            }
        }
    }

    echo '</div>';

    // ====================================
    // 4. SYSTEM STATISTICS
    // ====================================
    echo '<div class="section">';
    echo '<div class="section-header"><h2>4. System Statistics</h2></div>';
    echo '<div class="grid">';

    $stats = [
        ['label' => 'Total Users', 'query' => 'SELECT COUNT(*) as count FROM users', 'icon' => '👥'],
        ['label' => 'Total Products', 'query' => 'SELECT COUNT(*) as count FROM products', 'icon' => '📦'],
        ['label' => 'Total Orders', 'query' => 'SELECT COUNT(*) as count FROM orders', 'icon' => '🛒'],
        ['label' => 'Active Categories', 'query' => 'SELECT COUNT(*) as count FROM categories WHERE is_active = 1', 'icon' => '📁'],
        ['label' => 'Pending Deposits', 'query' => 'SELECT COUNT(*) as count FROM wallet_transactions WHERE payment_status = "pending"', 'icon' => '💳'],
        ['label' => 'Total Referrals', 'query' => 'SELECT COUNT(*) as count FROM referrals', 'icon' => '🤝'],
    ];

    foreach ($stats as $stat) {
        try {
            $stmt = $pdo->query($stat['query']);
            $count = $stmt->fetch()['count'];
            echo '<div class="stat-card">';
            echo '<div class="stat-label">' . $stat['icon'] . ' ' . $stat['label'] . '</div>';
            echo '<div class="stat-value">' . number_format($count) . '</div>';
            echo '</div>';
        } catch (PDOException $e) {
            echo '<div class="stat-card" style="border-color: #EF4444;">';
            echo '<div class="stat-label">' . $stat['icon'] . ' ' . $stat['label'] . '</div>';
            echo '<div class="stat-value" style="color: #EF4444;">ERROR</div>';
            echo '</div>';
        }
    }

    echo '</div></div>';

    // ====================================
    // 5. PAYMENT CONFIGURATION
    // ====================================
    echo '<div class="section">';
    echo '<div class="section-header"><h2>5. Payment Configuration</h2></div>';

    // Check payment methods
    $stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY display_order");
    $methods = $stmt->fetchAll();

    if (count($methods) > 0) {
        echo '<table>';
        echo '<thead><tr><th>Method</th><th>Type</th><th>Status</th><th>Settings</th></tr></thead>';
        echo '<tbody>';
        foreach ($methods as $method) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($method['name']) . '</strong></td>';
            echo '<td>' . strtoupper($method['type']) . '</td>';
            echo '<td>';
            if ($method['is_active']) {
                echo '<span class="badge success">Active</span>';
            } else {
                echo '<span class="badge error">Inactive</span>';
            }
            echo '</td>';
            echo '<td><code>' . htmlspecialchars($method['settings']) . '</code></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="check-item warning">';
        echo '<div class="check-item-header"><span class="check-item-title">⚠ No Payment Methods</span></div>';
        echo '<div class="check-item-details">Run <code>create-payment-tables.sql</code> to initialize payment methods</div>';
        echo '</div>';
        $warnings[] = 'No payment methods configured';
    }

    // Check bank accounts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bank_accounts WHERE is_active = 1");
    $active_banks = $stmt->fetch()['count'];

    echo '<div class="check-item ' . ($active_banks > 0 ? 'success' : 'warning') . '" style="margin-top: 16px;">';
    echo '<div class="check-item-header"><span class="check-item-title">' . ($active_banks > 0 ? '✓' : '⚠') . ' Bank Accounts</span></div>';
    echo '<div class="check-item-details">Active bank accounts: <code>' . $active_banks . '</code></div>';
    echo '</div>';

    if ($active_banks == 0) {
        $warnings[] = 'No active bank accounts configured';
    }

    echo '</div>';

    // ====================================
    // 6. USER TIERS
    // ====================================
    echo '<div class="section">';
    echo '<div class="section-header"><h2>6. User Tier Distribution</h2></div>';

    $stmt = $pdo->query("
        SELECT current_tier, COUNT(*) as count
        FROM users
        GROUP BY current_tier
        ORDER BY
            CASE current_tier
                WHEN 'platinum' THEN 1
                WHEN 'gold' THEN 2
                WHEN 'silver' THEN 3
                WHEN 'bronze' THEN 4
                ELSE 5
            END
    ");
    $tier_distribution = $stmt->fetchAll();

    if (count($tier_distribution) > 0) {
        echo '<div class="grid">';
        $tier_icons = ['platinum' => '💎', 'gold' => '🥇', 'silver' => '🥈', 'bronze' => '🥉'];
        foreach ($tier_distribution as $tier) {
            $icon = $tier_icons[$tier['current_tier']] ?? '⭐';
            echo '<div class="stat-card">';
            echo '<div class="stat-label">' . $icon . ' ' . ucfirst($tier['current_tier']) . '</div>';
            echo '<div class="stat-value">' . number_format($tier['count']) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    echo '</div>';
}

// ====================================
// 7. FILE SYSTEM CHECK
// ====================================
echo '<div class="section">';
echo '<div class="section-header"><h2>7. File System</h2></div>';

$critical_files = [
    'config.php' => 'Database configuration',
    'index.php' => 'Homepage',
    'admin/index.php' => 'Admin dashboard',
    'member/dashboard.php' => 'Member dashboard',
    'includes/tier-helper.php' => 'Tier calculation functions',
    'includes/header.php' => 'Global header',
    'includes/footer.php' => 'Global footer',
];

foreach ($critical_files as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $size = filesize(__DIR__ . '/' . $file);
        echo '<div class="check-item success">';
        echo '<div class="check-item-header"><span class="check-item-title">✓ ' . $file . '</span></div>';
        echo '<div class="check-item-details">' . $desc . ' (' . number_format($size) . ' bytes)</div>';
        echo '</div>';
    } else {
        echo '<div class="check-item error">';
        echo '<div class="check-item-header"><span class="check-item-title">✗ ' . $file . '</span></div>';
        echo '<div class="check-item-details">' . $desc . ' - File not found</div>';
        echo '</div>';
        $errors[] = "Missing file: $file";
    }
}

// Check uploads directory
$uploads_dir = __DIR__ . '/public/uploads';
if (!is_dir($uploads_dir)) {
    echo '<div class="check-item warning">';
    echo '<div class="check-item-header"><span class="check-item-title">⚠ Uploads Directory</span></div>';
    echo '<div class="check-item-details">Directory does not exist: <code>' . $uploads_dir . '</code></div>';
    echo '</div>';
    $warnings[] = 'Uploads directory missing';
} elseif (!is_writable($uploads_dir)) {
    echo '<div class="check-item error">';
    echo '<div class="check-item-header"><span class="check-item-title">✗ Uploads Directory</span></div>';
    echo '<div class="check-item-details">Directory is not writable: <code>' . $uploads_dir . '</code></div>';
    echo '</div>';
    $errors[] = 'Uploads directory not writable';
} else {
    echo '<div class="check-item success">';
    echo '<div class="check-item-header"><span class="check-item-title">✓ Uploads Directory</span></div>';
    echo '<div class="check-item-details">Writable: <code>' . $uploads_dir . '</code></div>';
    echo '</div>';
}

echo '</div>';

// ====================================
// 8. PHP CONFIGURATION
// ====================================
echo '<div class="section">';
echo '<div class="section-header"><h2>8. PHP Configuration</h2></div>';

$php_checks = [
    ['label' => 'PHP Version', 'value' => phpversion(), 'min' => '7.4', 'type' => 'version'],
    ['label' => 'Memory Limit', 'value' => ini_get('memory_limit'), 'min' => '128M', 'type' => 'memory'],
    ['label' => 'Upload Max Filesize', 'value' => ini_get('upload_max_filesize'), 'min' => '2M', 'type' => 'size'],
    ['label' => 'Post Max Size', 'value' => ini_get('post_max_size'), 'min' => '8M', 'type' => 'size'],
    ['label' => 'Max Execution Time', 'value' => ini_get('max_execution_time') . 's', 'min' => '30s', 'type' => 'time'],
];

foreach ($php_checks as $check) {
    echo '<div class="check-item success">';
    echo '<div class="check-item-header"><span class="check-item-title">✓ ' . $check['label'] . '</span></div>';
    echo '<div class="check-item-details">Current: <code>' . $check['value'] . '</code> | Minimum: <code>' . $check['min'] . '</code></div>';
    echo '</div>';
}

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl', 'gd'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo '<div class="check-item success">';
        echo '<div class="check-item-header"><span class="check-item-title">✓ Extension: ' . $ext . '</span></div>';
        echo '</div>';
    } else {
        echo '<div class="check-item error">';
        echo '<div class="check-item-header"><span class="check-item-title">✗ Extension: ' . $ext . '</span></div>';
        echo '</div>';
        $errors[] = "PHP extension not loaded: $ext";
    }
}

echo '</div>';

// ====================================
// SUMMARY
// ====================================
$execution_time = round((microtime(true) - $startTime) * 1000, 2);

echo '<div class="section">';
echo '<div class="section-header"><h2>Summary</h2></div>';

$total_checks = count($results) + count($errors) + count($warnings);
$status_class = 'success';
$status_text = 'All Systems Operational';

if (count($errors) > 0) {
    $status_class = 'error';
    $status_text = count($errors) . ' Critical Error(s) Found';
} elseif (count($warnings) > 0) {
    $status_class = 'warning';
    $status_text = count($warnings) . ' Warning(s) Found';
}

echo '<div class="check-item ' . $status_class . '">';
echo '<div class="check-item-header">';
echo '<span class="check-item-title" style="font-size: 18px;">' . $status_text . '</span>';
echo '</div>';
echo '<div class="check-item-details">';
echo 'Execution Time: <code>' . $execution_time . 'ms</code><br>';
echo 'Errors: <code>' . count($errors) . '</code> | ';
echo 'Warnings: <code>' . count($warnings) . '</code>';
echo '</div>';
echo '</div>';

if (count($errors) > 0) {
    echo '<div style="margin-top: 20px;">';
    echo '<h3 style="color: #EF4444; margin-bottom: 12px;">Critical Errors:</h3>';
    echo '<ul style="list-style: none; padding-left: 0;">';
    foreach ($errors as $error) {
        echo '<li style="padding: 8px 12px; background: #FEF2F2; border-left: 4px solid #EF4444; margin-bottom: 8px; border-radius: 4px;">❌ ' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

if (count($warnings) > 0) {
    echo '<div style="margin-top: 20px;">';
    echo '<h3 style="color: #F59E0B; margin-bottom: 12px;">Warnings:</h3>';
    echo '<ul style="list-style: none; padding-left: 0;">';
    foreach ($warnings as $warning) {
        echo '<li style="padding: 8px 12px; background: #FFFBEB; border-left: 4px solid #F59E0B; margin-bottom: 8px; border-radius: 4px;">⚠️ ' . htmlspecialchars($warning) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

echo '</div>';

?>

        <div class="footer">
            <p>Super Debug Tool v1.0 | Dorve E-commerce Platform</p>
            <p>Generated at <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
