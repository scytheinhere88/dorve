<?php
/**
 * =====================================================
 * DORVE HOUSE - DATABASE RESTORATION SCRIPT
 * =====================================================
 * Auto-import semua database dengan satu klik
 * Version: 4.0
 * Date: 2025-12-05
 * =====================================================
 */

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes timeout

// Load config
require_once __DIR__ . '/config.php';

// Response header
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dorve House - Database Restoration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .status {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-icon {
            font-size: 24px;
            margin-right: 15px;
            min-width: 30px;
        }
        .status-text {
            flex: 1;
        }
        .status-text strong {
            display: block;
            color: #333;
            margin-bottom: 5px;
        }
        .status-text span {
            color: #666;
            font-size: 14px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .warning {
            color: #ffc107;
        }
        .info {
            color: #17a2b8;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .progress {
            background: #e9ecef;
            border-radius: 10px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .logs {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .logs div {
            padding: 5px 0;
        }
        .db-info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .db-info strong {
            color: #1976D2;
        }
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Dorve House Database Restoration</h1>
            <p>Restore semua database dengan sekali klik</p>
        </div>

        <?php
        // Function to execute SQL file
        function executeSQLFile($pdo, $filename) {
            $logs = [];
            
            if (!file_exists($filename)) {
                return [
                    'success' => false,
                    'message' => "File tidak ditemukan: $filename",
                    'logs' => $logs
                ];
            }

            $sql = file_get_contents($filename);
            
            if ($sql === false) {
                return [
                    'success' => false,
                    'message' => "Gagal membaca file: $filename",
                    'logs' => $logs
                ];
            }

            // Remove comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('#/\*.*?\*/#s', '', $sql);

            // Split by delimiter
            $statements = [];
            $delimiter = ';';
            $current = '';
            $inDelimiter = false;
            
            $lines = explode("\n", $sql);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Check for DELIMITER command
                if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
                    if ($current) {
                        $statements[] = $current;
                        $current = '';
                    }
                    $delimiter = $matches[1];
                    $inDelimiter = true;
                    continue;
                }
                
                if (empty($line)) continue;
                
                $current .= $line . "\n";
                
                // Check if statement ends with current delimiter
                if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
                    $statements[] = rtrim($current, $delimiter . "\n");
                    $current = '';
                }
            }
            
            if ($current) {
                $statements[] = $current;
            }

            // Execute statements
            $executed = 0;
            $errors = 0;
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;
                
                try {
                    $pdo->exec($statement);
                    $executed++;
                    
                    // Log short summary
                    $preview = substr($statement, 0, 100);
                    if (strlen($statement) > 100) $preview .= '...';
                    $logs[] = "✓ Executed: " . $preview;
                    
                } catch (PDOException $e) {
                    $errors++;
                    $logs[] = "✗ Error: " . $e->getMessage();
                }
            }

            return [
                'success' => $errors === 0,
                'message' => "Executed: $executed statements, Errors: $errors",
                'logs' => $logs,
                'executed' => $executed,
                'errors' => $errors
            ];
        }

        // Check if restore is requested
        if (isset($_GET['action']) && $_GET['action'] === 'restore') {
            echo '<div class="status">';
            echo '<div class="status-item">';
            echo '<div class="status-icon">⏳</div>';
            echo '<div class="status-text"><strong>Processing...</strong><span>Sedang melakukan restore database</span></div>';
            echo '</div>';
            echo '</div>';

            echo '<div class="progress">';
            echo '<div class="progress-bar" style="width: 50%">50%</div>';
            echo '</div>';

            flush();
            ob_flush();

            // Execute SQL file (V2 - Safer approach)
            $sqlFile = __DIR__ . '/COMPLETE-DATABASE-RESTORE-V2.sql';
            $result = executeSQLFile($pdo, $sqlFile);

            echo '<div class="progress">';
            echo '<div class="progress-bar" style="width: 100%">100%</div>';
            echo '</div>';

            if ($result['success']) {
                echo '<div class="status">';
                echo '<div class="status-item">';
                echo '<div class="status-icon success">✅</div>';
                echo '<div class="status-text">';
                echo '<strong>Database Restored Successfully!</strong>';
                echo '<span>' . $result['message'] . '</span>';
                echo '</div>';
                echo '</div>';
                
                // Admin credentials
                echo '<div class="status-item">';
                echo '<div class="status-icon">👤</div>';
                echo '<div class="status-text">';
                echo '<strong>Admin Credentials</strong>';
                echo '<span>1. admin1@dorve.co / Dorve889<br>2. admin2@dorve.co / Admin889</span>';
                echo '</div>';
                echo '</div>';

                // Features
                echo '<div class="status-item">';
                echo '<div class="status-icon">🎉</div>';
                echo '<div class="status-text">';
                echo '<strong>Features Ready</strong>';
                echo '<span>All features including Products, Orders, Wallet, Referral System, Tier System, Vouchers are now active!</span>';
                echo '</div>';
                echo '</div>';

                echo '</div>';

                // Show logs
                if (!empty($result['logs'])) {
                    echo '<div class="logs">';
                    echo '<div><strong>📋 Execution Logs:</strong></div>';
                    foreach (array_slice($result['logs'], -50) as $log) {
                        echo '<div>' . htmlspecialchars($log) . '</div>';
                    }
                    if (count($result['logs']) > 50) {
                        echo '<div>... (showing last 50 of ' . count($result['logs']) . ' logs)</div>';
                    }
                    echo '</div>';
                }

                echo '<div class="actions">';
                echo '<a href="/admin/login.php" class="btn">🔐 Login Admin Panel</a> ';
                echo '<a href="/" class="btn" style="background: #28a745;">🏠 Go to Homepage</a>';
                echo '</div>';

            } else {
                echo '<div class="status">';
                echo '<div class="status-item">';
                echo '<div class="status-icon error">❌</div>';
                echo '<div class="status-text">';
                echo '<strong>Restore Failed!</strong>';
                echo '<span>' . htmlspecialchars($result['message']) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '</div>';

                // Show error logs
                if (!empty($result['logs'])) {
                    echo '<div class="logs">';
                    echo '<div><strong>📋 Error Logs:</strong></div>';
                    foreach ($result['logs'] as $log) {
                        echo '<div>' . htmlspecialchars($log) . '</div>';
                    }
                    echo '</div>';
                }

                echo '<div class="actions">';
                echo '<a href="restore-database.php" class="btn">🔄 Try Again</a>';
                echo '</div>';
            }

        } else {
            // Show initial screen
            ?>
            
            <div class="db-info">
                <strong>📊 Database Information:</strong><br>
                Host: <?php echo DB_HOST; ?><br>
                Database: <?php echo DB_NAME; ?><br>
                User: <?php echo DB_USER; ?>
            </div>

            <div class="status">
                <div class="status-item">
                    <div class="status-icon">🗄️</div>
                    <div class="status-text">
                        <strong>20 Tables</strong>
                        <span>Users, Products, Orders, Vouchers, Wallet, Referrals, dll</span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon">👥</div>
                    <div class="status-text">
                        <strong>2 Admin Users</strong>
                        <span>Pre-configured admin accounts ready to use</span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon">⚙️</div>
                    <div class="status-text">
                        <strong>All Features</strong>
                        <span>Referral System, Tier System, Wallet, Vouchers</span>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="status-icon">🎨</div>
                    <div class="status-text">
                        <strong>Sample Data</strong>
                        <span>8 Categories, Shipping Methods, Vouchers, Settings</span>
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="?action=restore" class="btn" onclick="return confirm('Apakah Anda yakin ingin restore database? Semua data existing akan dihapus!');">
                    🚀 Start Database Restoration
                </a>
            </div>

            <div style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                <strong>⚠️ Warning:</strong> Proses ini akan menghapus semua data existing dan membuat database baru!
            </div>

            <?php
        }
        ?>

    </div>

    <script>
        // Auto-scroll logs
        const logs = document.querySelector('.logs');
        if (logs) {
            logs.scrollTop = logs.scrollHeight;
        }
    </script>
</body>
</html>
