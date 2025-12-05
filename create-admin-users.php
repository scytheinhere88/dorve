<?php
/**
 * Create Admin Users Script
 * Creates 2 admin users with specified credentials
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Create Admin Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Create Admin Users</h1>

<?php

if (!isset($_GET['create'])) {
    ?>
    <div class="info">
        <strong>Admin users yang akan dibuat:</strong>
        <table>
            <tr>
                <th>Email</th>
                <th>Password</th>
                <th>Role</th>
            </tr>
            <tr>
                <td>admin1@dorve.id</td>
                <td>Qwerty88*</td>
                <td>admin</td>
            </tr>
            <tr>
                <td>admin2@dorve.id</td>
                <td>Asdfgh88*</td>
                <td>admin</td>
            </tr>
        </table>
    </div>
    
    <p>Klik tombol di bawah untuk create admin users:</p>
    <a href="?create=1" class="btn">Create Admin Users</a>
    <?php
    exit;
}

// Create admin users
try {
    // Check if database is connected
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }
    
    // Admin credentials
    $admins = [
        [
            'email' => 'admin1@dorve.id',
            'password' => 'Qwerty88*',
            'name' => 'Admin Dorve 1'
        ],
        [
            'email' => 'admin2@dorve.id',
            'password' => 'Asdfgh88*',
            'name' => 'Admin Dorve 2'
        ]
    ];
    
    $created = 0;
    $updated = 0;
    $errors = [];
    
    foreach ($admins as $admin) {
        // Hash password
        $hashed_password = password_hash($admin['password'], PASSWORD_BCRYPT);
        
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$admin['email']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing user
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, 
                    role = 'admin', 
                    name = ?,
                    tier = 'vvip',
                    wallet_balance = 0.00
                WHERE email = ?
            ");
            $stmt->execute([$hashed_password, $admin['name'], $admin['email']]);
            $updated++;
            
            echo "<div class='success'>";
            echo "✅ <strong>Updated:</strong> {$admin['email']}<br>";
            echo "Password: {$admin['password']}<br>";
            echo "Role: admin";
            echo "</div>";
        } else {
            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, wallet_balance, tier, created_at) 
                VALUES (?, ?, ?, 'admin', 0.00, 'vvip', NOW())
            ");
            $stmt->execute([$admin['name'], $admin['email'], $hashed_password]);
            $created++;
            
            echo "<div class='success'>";
            echo "✅ <strong>Created:</strong> {$admin['email']}<br>";
            echo "Password: {$admin['password']}<br>";
            echo "Role: admin";
            echo "</div>";
        }
    }
    
    // Summary
    echo "<div class='info'>";
    echo "<strong>Summary:</strong><br>";
    echo "✅ Created: $created user(s)<br>";
    echo "🔄 Updated: $updated user(s)<br>";
    echo "❌ Errors: " . count($errors);
    echo "</div>";
    
    // Test credentials
    echo "<h3>🧪 Test Login:</h3>";
    echo "<div class='info'>";
    echo "<table>";
    echo "<tr><th>Email</th><th>Password</th><th>Test</th></tr>";
    
    foreach ($admins as $admin) {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$admin['email']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $verify = password_verify($admin['password'], $user['password']);
            echo "<tr>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['password']}</td>";
            if ($verify) {
                echo "<td style='color: green;'>✅ Password Valid</td>";
            } else {
                echo "<td style='color: red;'>❌ Password Invalid</td>";
            }
            echo "</tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    echo "<p><a href='/admin/login.php' class='btn'>Go to Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

?>

    </div>
</body>
</html>
