<?php
/**
 * DEBUG SCRIPT - Login Error Troubleshooting
 * Use this to diagnose HTTP 500 errors during login
 * 
 * HOW TO USE:
 * 1. Open: http://localhost/MobileNest/debug-login.php
 * 2. Follow the checklist
 * 3. Test login with test credentials
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug - MobileNest</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 20px; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; margin-bottom: 15px; }
        .section { margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table td, table th { padding: 10px; text-align: left; border: 1px solid #ddd; }
        table th { background: #007bff; color: white; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Login Debug - MobileNest</h1>
        <div class="info">
            <strong>Purpose:</strong> This page helps diagnose HTTP 500 errors during user login.
        </div>

        <?php
        echo "<h2>1. PHP Configuration Check</h2>";
        echo "<div class='section'>";
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
        
        $php_version = phpversion();
        echo "<tr><td>PHP Version</td><td>$php_version</td>";
        echo "<td class='" . (version_compare($php_version, '7.4', '>=') ? 'status-ok' : 'status-error') . "'>";
        echo version_compare($php_version, '7.4', '>=') ? '✓ OK' : '✗ Too old';
        echo "</td></tr>";
        
        $display_errors = ini_get('display_errors');
        echo "<tr><td>Display Errors</td><td>$display_errors</td><td class='status-ok'>✓ Enabled</td></tr>";
        
        $error_reporting = error_reporting();
        echo "<tr><td>Error Reporting</td><td>$error_reporting (E_ALL)</td><td class='status-ok'>✓ Enabled</td></tr>";
        
        $max_execution = ini_get('max_execution_time');
        echo "<tr><td>Max Execution Time</td><td>{$max_execution}s</td><td class='status-ok'>✓ OK</td></tr>";
        
        echo "</table>";
        echo "</div>";

        // Test 2: Database Connection
        echo "<h2>2. Database Connection Test</h2>";
        echo "<div class='section'>";
        
        try {
            require_once 'config.php';
            
            if ($conn && $conn->ping()) {
                echo "<div class='success'>✓ Database connection successful!</div>";
                
                // Test database name
                $db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0];
                echo "<p><strong>Connected to database:</strong> $db_name</p>";
                
                // Check if users table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'users'");
                if ($table_check && $table_check->num_rows > 0) {
                    echo "<div class='success'>✓ 'users' table exists</div>";
                    
                    // Count users
                    $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                    echo "<p><strong>Total users in database:</strong> $user_count</p>";
                } else {
                    echo "<div class='error'>✗ 'users' table NOT found! Please import database schema.</div>";
                }
            } else {
                echo "<div class='error'>✗ Database connection failed!</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>✗ Error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";

        // Test 3: Session Check
        echo "<h2>3. Session Configuration</h2>";
        echo "<div class='section'>";
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "<div class='success'>✓ Session is active</div>";
            echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
            echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
        } else {
            echo "<div class='warning'>⚠ Session not started</div>";
        }
        
        echo "</div>";

        // Test 4: File Permissions
        echo "<h2>4. File Permissions Check</h2>";
        echo "<div class='section'>";
        echo "<table>";
        echo "<tr><th>File</th><th>Exists</th><th>Readable</th><th>Path</th></tr>";
        
        $files_to_check = [
            'config.php',
            'user/login.php',
            'user/proses-login.php',
            'includes/header.php',
            'includes/footer.php'
        ];
        
        foreach ($files_to_check as $file) {
            $exists = file_exists($file);
            $readable = $exists ? is_readable($file) : false;
            $full_path = $exists ? realpath($file) : 'N/A';
            
            echo "<tr>";
            echo "<td>$file</td>";
            echo "<td class='" . ($exists ? 'status-ok' : 'status-error') . "'>" . ($exists ? '✓ Yes' : '✗ No') . "</td>";
            echo "<td class='" . ($readable ? 'status-ok' : 'status-error') . "'>" . ($readable ? '✓ Yes' : '✗ No') . "</td>";
            echo "<td style='font-size: 0.85em;'>$full_path</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";

        // Test 5: Test Login with Sample Data
        echo "<h2>5. Test Login Credentials</h2>";
        echo "<div class='section'>";
        
        if (isset($conn) && $conn) {
            echo "<p>Fetching sample user data from database...</p>";
            
            $sample_users = $conn->query(
                "SELECT id_user, username, email, nama_lengkap, 
                SUBSTRING(password, 1, 20) as password_preview 
                FROM users LIMIT 3"
            );
            
            if ($sample_users && $sample_users->num_rows > 0) {
                echo "<div class='success'>✓ Found users in database</div>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Password Hash (preview)</th></tr>";
                
                while ($user = $sample_users->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$user['id_user']}</td>";
                    echo "<td>{$user['username']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$user['nama_lengkap']}</td>";
                    echo "<td style='font-family: monospace; font-size: 0.8em;'>{$user['password_preview']}...</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                echo "<div class='info'>";
                echo "<strong>How to test:</strong><br>";
                echo "1. Use one of the usernames/emails above<br>";
                echo "2. If you don't know the password, you can create a test account:<br>";
                echo "<code>INSERT INTO users (username, password, email, nama_lengkap) VALUES ('testuser', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'test@example.com', 'Test User');</code>";
                echo "</div>";
            } else {
                echo "<div class='error'>✗ No users found in database!</div>";
                echo "<p>You need to add users first. Use register.php or insert manually:</p>";
                echo "<pre>";
                echo "INSERT INTO users (username, password, email, nama_lengkap, nomor_telepon) \n";
                echo "VALUES ('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin@mobilenest.com', 'Admin User', '081234567890');";
                echo "</pre>";
            }
        }
        
        echo "</div>";

        // Test 6: Live Login Test
        echo "<h2>6. Live Login Test</h2>";
        echo "<div class='section'>";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
            echo "<h3>Testing Login...</h3>";
            
            $test_username = $_POST['test_username'] ?? '';
            $test_password = $_POST['test_password'] ?? '';
            
            if (empty($test_username) || empty($test_password)) {
                echo "<div class='error'>✗ Username and password required!</div>";
            } else {
                try {
                    // Simulate login process
                    $stmt = $conn->prepare("SELECT id_user, nama_lengkap, email, username, password FROM users WHERE username = ? OR email = ? LIMIT 1");
                    $stmt->bind_param('ss', $test_username, $test_username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        echo "<div class='success'>✓ User found in database</div>";
                        echo "<p><strong>User ID:</strong> {$user['id_user']}</p>";
                        echo "<p><strong>Name:</strong> {$user['nama_lengkap']}</p>";
                        
                        if (password_verify($test_password, $user['password'])) {
                            echo "<div class='success'>✓✓ Password CORRECT! Login would succeed.</div>";
                            echo "<p><strong>Session data that would be set:</strong></p>";
                            echo "<pre>";
                            echo "\$_SESSION['user'] = {$user['id_user']}\n";
                            echo "\$_SESSION['user_name'] = '{$user['nama_lengkap']}'\n";
                            echo "\$_SESSION['user_email'] = '{$user['email']}'\n";
                            echo "\$_SESSION['username'] = '{$user['username']}'\n";
                            echo "\$_SESSION['role'] = 'user'";
                            echo "</pre>";
                        } else {
                            echo "<div class='error'>✗ Password INCORRECT!</div>";
                        }
                    } else {
                        echo "<div class='error'>✗ User NOT found in database</div>";
                    }
                    
                    $stmt->close();
                } catch (Exception $e) {
                    echo "<div class='error'>✗ Error during test: " . $e->getMessage() . "</div>";
                }
            }
        }
        
        echo "<form method='POST' style='margin-top: 20px;'>";
        echo "<p><strong>Test your login credentials here:</strong></p>";
        echo "<input type='text' name='test_username' placeholder='Username or Email' style='padding: 10px; width: 300px; margin: 5px;' required>";
        echo "<input type='password' name='test_password' placeholder='Password' style='padding: 10px; width: 300px; margin: 5px;' required>";
        echo "<button type='submit' name='test_login' class='btn'>Test Login</button>";
        echo "</form>";
        
        echo "</div>";

        // Test 7: Error Log
        echo "<h2>7. Recent Errors</h2>";
        echo "<div class='section'>";
        
        if (file_exists('error.log')) {
            $error_log = file_get_contents('error.log');
            if (!empty($error_log)) {
                $lines = explode("\n", $error_log);
                $recent_errors = array_slice(array_reverse($lines), 0, 10);
                
                echo "<p><strong>Last 10 error entries:</strong></p>";
                echo "<pre style='max-height: 300px; overflow-y: auto;'>";
                echo htmlspecialchars(implode("\n", $recent_errors));
                echo "</pre>";
            } else {
                echo "<div class='success'>✓ No errors logged</div>";
            }
        } else {
            echo "<div class='info'>ℹ error.log file not found</div>";
        }
        
        echo "</div>";

        // Action Buttons
        echo "<h2>Quick Actions</h2>";
        echo "<div class='section'>";
        echo "<a href='user/login.php' class='btn'>Go to Login Page</a>";
        echo "<a href='test-connection.php' class='btn'>Test Database Connection</a>";
        echo "<a href='verify-database-structure.php' class='btn'>Verify Database Structure</a>";
        echo "<button onclick='location.reload()' class='btn'>Refresh This Page</button>";
        echo "</div>";
        ?>

        <div class="info" style="margin-top: 30px;">
            <strong>Common Solutions for HTTP 500 Error:</strong>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Check if MySQL service is running in XAMPP</li>
                <li>Verify database name is correct in config.php</li>
                <li>Ensure users table exists with correct structure</li>
                <li>Check PHP error logs for specific errors</li>
                <li>Verify password hashing (use password_hash() not MD5/SHA1)</li>
                <li>Clear browser cache and cookies</li>
                <li>Check file permissions (should be readable)</li>
            </ol>
        </div>
    </div>
</body>
</html>