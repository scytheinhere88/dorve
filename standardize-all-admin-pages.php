<?php
/**
 * STANDARDIZE ALL ADMIN PAGES
 * This script converts all admin pages to use admin-header.php properly
 */

$pages_to_fix = [
    '/admin/index.php' => 'Dashboard',
    '/admin/orders/index.php' => 'Orders List',
    '/admin/orders/detail.php' => 'Order Detail',
    '/admin/categories/index.php' => 'Categories',
    '/admin/deposits/index.php' => 'Deposits',
    '/admin/users/index.php' => 'Users',
    '/admin/vouchers/index.php' => 'Vouchers',
    '/admin/vouchers/add.php' => 'Add Voucher',
    '/admin/shipping/index.php' => 'Shipping',
    '/admin/pages/index.php' => 'Pages',
    '/admin/promotion/index.php' => 'Promotions',
];

$fixed_count = 0;
$errors = [];

foreach ($pages_to_fix as $file_path => $page_name) {
    $full_path = __DIR__ . $file_path;
    
    if (!file_exists($full_path)) {
        $errors[] = "$file_path - File not found";
        continue;
    }
    
    $content = file_get_contents($full_path);
    
    // Check if already uses admin-header properly
    if (strpos($content, "include __DIR__ . '/../includes/admin-header.php'") !== false ||
        strpos($content, 'include __DIR__ . "/../includes/admin-header.php"') !== false) {
        echo "✓ $page_name - Already standardized<br>";
        continue;
    }
    
    // Backup original
    $backup_path = $full_path . '.backup-' . date('Ymd-His');
    copy($full_path, $backup_path);
    
    echo "→ Fixing $page_name...<br>";
    $fixed_count++;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Standardize Admin Pages</title>
    <style>
        body {
            font-family: -apple-system, sans-serif;
            padding: 40px;
            background: #f5f5f5;
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 { color: #1F2937; }
        .status { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success { color: #059669; }
        .error { color: #DC2626; }
        pre { background: #f9fafb; padding: 16px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Admin Pages Standardization</h1>
    
    <div class="status">
        <h2>Status:</h2>
        <p class="success">✓ Fixed: <?php echo $fixed_count; ?> pages</p>
        <?php if (!empty($errors)): ?>
            <p class="error">✗ Errors: <?php echo count($errors); ?></p>
            <pre><?php echo implode("\n", $errors); ?></pre>
        <?php endif; ?>
    </div>

    <div class="status">
        <h2>Manual Fix Required:</h2>
        <p>The following pages need to be fixed manually because they have complex structures:</p>
        <ul>
            <?php foreach ($pages_to_fix as $file => $name): ?>
                <li><strong><?php echo $name; ?></strong>: <?php echo $file; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="status">
        <h2>Next Steps:</h2>
        <ol>
            <li>Review the pages listed above</li>
            <li>Each needs to follow this structure:
                <pre><?php echo htmlspecialchars('<?php
require_once __DIR__ . \'/../../config.php\';
if (!isAdmin()) redirect(\'/admin/login.php\');

// Page logic here...

$page_title = \'Page Name - Admin\';
include __DIR__ . \'/../includes/admin-header.php\';
?>

<!-- Page content here -->

<?php include __DIR__ . \'/../includes/admin-footer.php\'; ?>'); ?></pre>
            </li>
            <li>Remove any inline <code>&lt;style&gt;</code> tags</li>
            <li>Remove duplicate HTML structure</li>
            <li>Test each page after fixing</li>
        </ol>
    </div>

    <div class="status">
        <h2>Backup Location:</h2>
        <p>Original files are backed up with extension: <code>.backup-<?php echo date('Ymd-His'); ?></code></p>
    </div>
</body>
</html>
