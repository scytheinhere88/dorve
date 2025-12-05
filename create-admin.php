<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - Dorve</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #1a202c;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .success, .error, .warning {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-weight: 600;
            width: 120px;
            color: #4a5568;
        }
        .info-value {
            color: #1a202c;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        code {
            background: #2d3748;
            color: #f7fafc;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Create Admin User</h1>
        <p class="subtitle">Dorve E-Commerce Platform</p>

        <?php
        // Include config
        require_once __DIR__ . '/config.php';

        // Define 3 admin accounts
        $admins = [
            [
                'name' => 'Admin 1',
                'email' => 'admin1@dorve.co',
                'password' => 'Dorve889'
            ],
            [
                'name' => 'Admin 2',
                'email' => 'admin2@dorve.co',
                'password' => 'Admin889'
            ],
            [
                'name' => 'Admin 3',
                'email' => 'admin3@dorve.co',
                'password' => 'Dorvenaikterus'
            ]
        ];

        $created_count = 0;
        $updated_count = 0;
        $failed_count = 0;

        try {
            foreach ($admins as $admin) {
                $email = $admin['email'];
                $password = $admin['password'];
                $name = $admin['name'];

                // Hash password
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                try {
                    // Check if admin already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        // Update existing user
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, is_admin = 1, is_verified = 1 WHERE email = ?");
                        $stmt->execute([$password_hash, $email]);
                        $updated_count++;
                    } else {
                        // Insert new admin
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, is_admin, is_verified, wallet_balance) VALUES (?, ?, ?, 1, 1, 0.00)");
                        $stmt->execute([$name, $email, $password_hash]);
                        $created_count++;
                    }
                } catch (PDOException $e) {
                    $failed_count++;
                }
            }

            // Show success summary
            if ($created_count > 0) {
                echo '<div class="success">✅ <strong>' . $created_count . ' admin account(s) CREATED successfully!</strong></div>';
            }
            if ($updated_count > 0) {
                echo '<div class="success">✅ <strong>' . $updated_count . ' admin account(s) UPDATED successfully!</strong></div>';
            }
            if ($failed_count > 0) {
                echo '<div class="error">❌ <strong>' . $failed_count . ' admin account(s) FAILED!</strong></div>';
            }

            // Show all admin accounts
            echo '<h2 style="margin: 30px 0 20px; font-size: 20px; color: #1a202c;">📋 Admin Accounts Created</h2>';

            foreach ($admins as $index => $admin) {
                echo '<div class="info-box" style="margin-bottom: 20px;">';
                echo '<h3 style="margin-bottom: 12px; color: #4a5568; font-size: 16px;">Account #' . ($index + 1) . '</h3>';
                echo '<div class="info-row"><span class="info-label">📧 Email:</span><span class="info-value">' . htmlspecialchars($admin['email']) . '</span></div>';
                echo '<div class="info-row"><span class="info-label">🔑 Password:</span><span class="info-value">' . htmlspecialchars($admin['password']) . '</span></div>';
                echo '<div class="info-row"><span class="info-label">👤 Name:</span><span class="info-value">' . htmlspecialchars($admin['name']) . '</span></div>';
                echo '<div class="info-row"><span class="info-label">✅ Status:</span><span class="info-value">Admin + Verified</span></div>';
                echo '</div>';
            }

            echo '<a href="/admin/login.php" class="btn">Login to Admin Panel →</a>';

            echo '<div class="warning" style="margin-top: 30px;">';
            echo '<strong>⚠️ IMPORTANT SECURITY WARNING:</strong><br>';
            echo 'Delete this file (<code>create-admin.php</code>) immediately after use!<br>';
            echo 'Keeping this file accessible is a security risk.';
            echo '</div>';

        } catch (PDOException $e) {
            echo '<div class="error"><strong>❌ Database Error:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>
