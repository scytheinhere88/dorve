<?php
/**
 * Test All Pages Script - Syntax Check Only
 * Checks PHP syntax without executing the files
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable auto-redirect
if (!defined('NO_REDIRECT')) {
    define('NO_REDIRECT', true);
}

$pages_to_test = [
    // Public Pages
    'Homepage' => '/index.php',
    'All Products' => '/pages/all-products.php',
    'New Collection' => '/pages/new-collection.php',
    'FAQ' => '/pages/faq.php',
    'Product Detail' => '/pages/product-detail.php',
    'Privacy Policy' => '/pages/privacy-policy.php',
    'Terms' => '/pages/terms.php',
    
    // Auth Pages
    'Login' => '/auth/login.php',
    'Register' => '/auth/register.php',
    
    // Member Pages
    'Member Dashboard' => '/member/dashboard.php',
    'Member Wallet' => '/member/wallet.php',
    'Member Orders' => '/member/orders.php',
    'Member Referral' => '/member/referral.php',
    'Member Profile' => '/member/profile.php',
    
    // Admin Pages
    'Admin Dashboard' => '/admin/index.php',
    'Admin Products' => '/admin/products/index.php',
    'Admin Orders' => '/admin/orders/index.php',
    'Admin Deposits' => '/admin/deposits/index.php',
];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Page Test Results</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
        }
        .result {
            margin: 10px 0;
            padding: 10px;
            border-left: 4px solid;
        }
        .success {
            border-color: #00ff00;
            background: #003300;
        }
        .error {
            border-color: #ff0000;
            background: #330000;
            color: #ff6666;
        }
        .warning {
            border-color: #ffaa00;
            background: #332200;
            color: #ffcc66;
        }
        pre {
            white-space: pre-wrap;
            font-size: 11px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>🧪 Page Test Results</h1>
    <p>Testing <?php echo count($pages_to_test); ?> pages...</p>
    <hr>
    
    <?php
    foreach ($pages_to_test as $name => $path) {
        $full_path = __DIR__ . $path;
        
        echo "<div class='result ";
        
        if (!file_exists($full_path)) {
            echo "error'>";
            echo "❌ <strong>$name</strong> - FILE NOT FOUND<br>";
            echo "Path: $full_path";
            echo "</div>";
            continue;
        }
        
        // Capture output and errors
        ob_start();
        $error_occurred = false;
        $error_message = '';
        
        set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error_occurred, &$error_message) {
            $error_occurred = true;
            $error_message .= "Error [$errno]: $errstr in $errfile on line $errline\n";
            return true;
        });
        
        try {
            // Simulate some session data for member pages
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['user_id'] = 1; // Fake user for testing
            
            include $full_path;
            
        } catch (Throwable $e) {
            $error_occurred = true;
            $error_message = $e->getMessage() . "\n" . $e->getTraceAsString();
        }
        
        restore_error_handler();
        $output = ob_get_clean();
        
        if ($error_occurred) {
            echo "error'>";
            echo "❌ <strong>$name</strong> - ERROR<br>";
            echo "<pre>$error_message</pre>";
        } else {
            echo "success'>";
            echo "✅ <strong>$name</strong> - OK";
        }
        
        echo "</div>";
    }
    ?>
    
    <hr>
    <p>Test completed!</p>
</body>
</html>
