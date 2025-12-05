<?php
/**
 * =====================================================
 * DORVE HOUSE - SUPER DEBUG TOOL
 * =====================================================
 * Comprehensive website health checker
 * Checks: Database, Pages, Admin, Member Area, Config
 * =====================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(120);

// Security: Only allow localhost or specific IP
$allowed_ips = ['127.0.0.1', '::1'];
// Uncomment to enable IP restriction
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
//     die('Access denied');
// }

$results = [
    'database' => [],
    'config' => [],
    'pages' => [],
    'admin' => [],
    'member' => [],
    'files' => [],
    'overall' => 'unknown'
];

$errors = 0;
$warnings = 0;
$success = 0;

function test_result($category, $test_name, $status, $message, $details = '') {
    global $results, $errors, $warnings, $success;
    
    $results[$category][] = [
        'test' => $test_name,
        'status' => $status,
        'message' => $message,
        'details' => $details
    ];
    
    if ($status === 'error') $errors++;
    elseif ($status === 'warning') $warnings++;
    elseif ($status === 'success') $success++;
}

// =====================================================
// 1. CONFIG CHECK
// =====================================================
echo "<!-- Checking Config -->\n";

if (file_exists(__DIR__ . '/config.php')) {
    test_result('config', 'Config File', 'success', 'config.php exists');
    require_once __DIR__ . '/config.php';
} else {
    test_result('config', 'Config File', 'error', 'config.php NOT FOUND');
}

// Check constants
$required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required_constants as $const) {
    if (defined($const)) {
        test_result('config', "Constant: $const", 'success', 'Defined');
    } else {
        test_result('config', "Constant: $const", 'error', 'NOT DEFINED');
    }
}

// =====================================================
// 2. DATABASE CHECK
// =====================================================
echo "<!-- Checking Database -->\n";

try {
    if (isset($pdo)) {
        test_result('database', 'PDO Connection', 'success', 'Connected to database');
        
        // Check tables
        $required_tables = [
            'users', 'categories', 'products', 'product_variants', 
            'orders', 'order_items', 'cart_items', 'addresses',
            'vouchers', 'shipping_methods', 'wallet_transactions',
            'topups', 'referral_rewards', 'settings', 'cms_pages'
        ];
        
        foreach ($required_tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $result = $stmt->fetch();
                test_result('database', "Table: $table", 'success', "Exists ({$result['count']} rows)");
            } catch (PDOException $e) {
                test_result('database', "Table: $table", 'error', 'NOT EXISTS', $e->getMessage());
            }
        }
        
        // Check admin users
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                test_result('database', 'Admin Users', 'success', "{$result['count']} admin(s) found");
            } else {
                test_result('database', 'Admin Users', 'error', 'No admin users found');
            }
        } catch (PDOException $e) {
            test_result('database', 'Admin Users', 'error', 'Query failed', $e->getMessage());
        }
        
        // Check products
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                test_result('database', 'Active Products', 'success', "{$result['count']} product(s) found");
            } else {
                test_result('database', 'Active Products', 'warning', 'No active products');
            }
        } catch (PDOException $e) {
            test_result('database', 'Active Products', 'error', 'Query failed', $e->getMessage());
        }
        
    } else {
        test_result('database', 'PDO Connection', 'error', 'PDO object not initialized');
    }
} catch (Exception $e) {
    test_result('database', 'Database Connection', 'error', 'Connection failed', $e->getMessage());
}

// =====================================================
// 3. PUBLIC PAGES CHECK
// =====================================================
echo "<!-- Checking Public Pages -->\n";

$pages_to_check = [
    'Homepage' => '/index.php',
    'All Products' => '/pages/all-products.php',
    'New Collection' => '/pages/new-collection.php',
    'FAQ' => '/pages/faq.php',
    'Privacy Policy' => '/pages/privacy-policy.php',
    'Shipping Policy' => '/pages/shipping-policy.php',
    'Terms' => '/pages/terms.php',
    'Product Detail' => '/pages/product-detail.php',
    'Cart' => '/pages/cart.php',
    'Checkout' => '/pages/checkout.php'
];

foreach ($pages_to_check as $name => $path) {
    $full_path = __DIR__ . $path;
    
    if (!file_exists($full_path)) {
        test_result('pages', $name, 'error', "File not found: $path");
        continue;
    }
    
    // Check for syntax errors
    $output = shell_exec("php -l " . escapeshellarg($full_path) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        test_result('pages', $name, 'success', 'Syntax OK');
    } else {
        test_result('pages', $name, 'error', 'Syntax error', $output);
    }
}

// =====================================================
// 4. ADMIN PANEL CHECK
// =====================================================
echo "<!-- Checking Admin Panel -->\n";

$admin_pages = [
    'Admin Login' => '/admin/login.php',
    'Admin Dashboard' => '/admin/index.php',
    'Products Management' => '/admin/products/index.php',
    'Categories' => '/admin/categories/index.php',
    'Orders' => '/admin/orders/index.php',
    'Users' => '/admin/users/index.php',
    'Vouchers' => '/admin/vouchers/index.php',
    'Settings' => '/admin/settings/index.php',
    'Deposits' => '/admin/deposits/index.php'
];

foreach ($admin_pages as $name => $path) {
    $full_path = __DIR__ . $path;
    
    if (!file_exists($full_path)) {
        test_result('admin', $name, 'error', "File not found: $path");
        continue;
    }
    
    $output = shell_exec("php -l " . escapeshellarg($full_path) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        test_result('admin', $name, 'success', 'Syntax OK');
    } else {
        test_result('admin', $name, 'error', 'Syntax error', $output);
    }
}

// =====================================================
// 5. MEMBER AREA CHECK
// =====================================================
echo "<!-- Checking Member Area -->\n";

$member_pages = [
    'Member Dashboard' => '/member/dashboard.php',
    'Member Orders' => '/member/orders.php',
    'Member Wallet' => '/member/wallet.php',
    'Member Profile' => '/member/profile.php',
    'Member Referral' => '/member/referral.php'
];

foreach ($member_pages as $name => $path) {
    $full_path = __DIR__ . $path;
    
    if (!file_exists($full_path)) {
        test_result('member', $name, 'warning', "File not found: $path");
        continue;
    }
    
    $output = shell_exec("php -l " . escapeshellarg($full_path) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        test_result('member', $name, 'success', 'Syntax OK');
    } else {
        test_result('member', $name, 'error', 'Syntax error', $output);
    }
}

// =====================================================
// 6. CRITICAL FILES CHECK
// =====================================================
echo "<!-- Checking Critical Files -->\n";

$critical_files = [
    'Config' => '/config.php',
    'Header Include' => '/includes/header.php',
    'Footer Include' => '/includes/footer.php'
];

foreach ($critical_files as $name => $path) {
    $full_path = __DIR__ . $path;
    
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        test_result('files', $name, 'success', "Exists ($size bytes)");
    } else {
        test_result('files', $name, 'error', 'NOT FOUND');
    }
}

// Check permissions
$writable_dirs = ['/uploads', '/assets/uploads'];
foreach ($writable_dirs as $dir) {
    $full_path = __DIR__ . $dir;
    if (is_dir($full_path)) {
        if (is_writable($full_path)) {
            test_result('files', "Directory: $dir", 'success', 'Writable');
        } else {
            test_result('files', "Directory: $dir", 'warning', 'NOT writable');
        }
    } else {
        test_result('files', "Directory: $dir", 'warning', 'Does not exist');
    }
}

// =====================================================
// CALCULATE OVERALL STATUS
// =====================================================
if ($errors > 0) {
    $results['overall'] = 'critical';
} elseif ($warnings > 5) {
    $results['overall'] = 'warning';
} else {
    $results['overall'] = 'healthy';
}

// =====================================================
// HTML OUTPUT
// =====================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dorve House - Super Debug</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0a;
            color: #e0e0e0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #333;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #fff;
        }
        
        .status-overall {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 18px;
            margin-top: 15px;
        }
        
        .status-overall.healthy {
            background: #28a745;
            color: white;
        }
        
        .status-overall.warning {
            background: #ffc107;
            color: #000;
        }
        
        .status-overall.critical {
            background: #dc3545;
            color: white;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-box {
            flex: 1;
            background: #1a1a1a;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #333;
        }
        
        .stat-box .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-box.success .number { color: #28a745; }
        .stat-box.warning .number { color: #ffc107; }
        .stat-box.error .number { color: #dc3545; }
        
        .section {
            background: #1a1a1a;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid #333;
        }
        
        .section h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #fff;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            background: #0d0d0d;
            border-radius: 8px;
            border: 1px solid #222;
        }
        
        .test-status {
            width: 80px;
            padding: 5px 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .test-status.success {
            background: #28a745;
            color: white;
        }
        
        .test-status.warning {
            background: #ffc107;
            color: #000;
        }
        
        .test-status.error {
            background: #dc3545;
            color: white;
        }
        
        .test-name {
            font-weight: 600;
            min-width: 250px;
            color: #fff;
        }
        
        .test-message {
            flex: 1;
            color: #aaa;
        }
        
        .test-details {
            font-size: 12px;
            color: #888;
            margin-left: 345px;
            margin-top: 5px;
            padding: 10px;
            background: #000;
            border-radius: 5px;
            font-family: monospace;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 0 10px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .timestamp {
            text-align: center;
            color: #666;
            margin-top: 30px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Dorve House - Super Debug Tool</h1>
            <p>Comprehensive Website Health Check</p>
            
            <div class="status-overall <?php echo $results['overall']; ?>">
                <?php 
                if ($results['overall'] === 'healthy') echo '✅ HEALTHY';
                elseif ($results['overall'] === 'warning') echo '⚠️ WARNING';
                else echo '❌ CRITICAL';
                ?>
            </div>
            
            <div class="stats">
                <div class="stat-box success">
                    <div class="number"><?php echo $success; ?></div>
                    <div>Passed</div>
                </div>
                <div class="stat-box warning">
                    <div class="number"><?php echo $warnings; ?></div>
                    <div>Warnings</div>
                </div>
                <div class="stat-box error">
                    <div class="number"><?php echo $errors; ?></div>
                    <div>Errors</div>
                </div>
            </div>
        </div>
        
        <?php foreach (['config' => 'Configuration', 'database' => 'Database', 'pages' => 'Public Pages', 'admin' => 'Admin Panel', 'member' => 'Member Area', 'files' => 'File System'] as $key => $title): ?>
            <?php if (!empty($results[$key])): ?>
            <div class="section">
                <h2><?php echo $title; ?></h2>
                <?php foreach ($results[$key] as $item): ?>
                    <div class="test-item">
                        <div class="test-status <?php echo $item['status']; ?>">
                            <?php echo strtoupper($item['status']); ?>
                        </div>
                        <div class="test-name"><?php echo htmlspecialchars($item['test']); ?></div>
                        <div class="test-message"><?php echo htmlspecialchars($item['message']); ?></div>
                    </div>
                    <?php if (!empty($item['details'])): ?>
                        <div class="test-details"><?php echo htmlspecialchars($item['details']); ?></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div class="actions">
            <button class="btn" onclick="location.reload()">🔄 Refresh Check</button>
            <a href="/" class="btn">🏠 Back to Website</a>
            <a href="/admin/login.php" class="btn">🔐 Admin Login</a>
        </div>
        
        <div class="timestamp">
            Last checked: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
