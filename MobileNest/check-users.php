<?php
/**
 * CHECK USERS - Find actual usernames in database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Users - MobileNest</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; }
        .info { background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 20px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
        .hash-preview { font-family: 'Courier New', monospace; font-size: 0.85em; color: #666; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #0056b3; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .status-bad { color: #dc3545; font-weight: bold; }
        .status-good { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Check Users Database</h1>
        
        <?php
        if ($conn) {
            echo "<div class='success'>✓ Database connected: mobilenest_db</div>";
            
            // Get all users with password info
            $sql = "SELECT 
                        id_user,
                        username,
                        email,
                        nama_lengkap,
                        SUBSTRING(password, 1, 20) as password_preview,
                        LENGTH(password) as password_length,
                        CASE 
                            WHEN LENGTH(password) = 60 AND password LIKE '$2y$%' THEN 'bcrypt (CORRECT)'
                            WHEN LENGTH(password) = 32 THEN 'MD5 (WRONG)'
                            WHEN LENGTH(password) = 40 THEN 'SHA1 (WRONG)'
                            ELSE 'Unknown format'
                        END as password_type
                    FROM users
                    ORDER BY id_user";
            
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                echo "<h2>All Users in Database</h2>";
                echo "<div class='info'><strong>Total users found:</strong> " . $result->num_rows . "</div>";
                
                echo "<table>";
                echo "<tr>";
                echo "<th>ID</th>";
                echo "<th>Username</th>";
                echo "<th>Email</th>";
                echo "<th>Name</th>";
                echo "<th>Password Preview</th>";
                echo "<th>Length</th>";
                echo "<th>Type</th>";
                echo "<th>Status</th>";
                echo "</tr>";
                
                $md5_users = [];
                
                while ($user = $result->fetch_assoc()) {
                    $is_correct = ($user['password_length'] == 60 && strpos($user['password_preview'], '$2y$') === 0);
                    
                    if ($user['password_length'] == 32) {
                        $md5_users[] = $user;
                    }
                    
                    echo "<tr>";
                    echo "<td>{$user['id_user']}</td>";
                    echo "<td><strong>{$user['username']}</strong></td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$user['nama_lengkap']}</td>";
                    echo "<td class='hash-preview'>{$user['password_preview']}...</td>";
                    echo "<td>{$user['password_length']}</td>";
                    echo "<td>{$user['password_type']}</td>";
                    echo "<td class='" . ($is_correct ? 'status-good' : 'status-bad') . "'>";
                    echo $is_correct ? '✓ OK' : '✗ NEEDS FIX';
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Show MD5 users that need fixing
                if (count($md5_users) > 0) {
                    echo "<div class='warning'>";
                    echo "<h3>⚠️ Users with MD5 Password (Need to be fixed)</h3>";
                    echo "<p><strong>Count:</strong> " . count($md5_users) . " users</p>";
                    echo "<p><strong>Usernames:</strong></p>";
                    echo "<ul>";
                    foreach ($md5_users as $user) {
                        echo "<li><strong>{$user['username']}</strong> (ID: {$user['id_user']}, Email: {$user['email']})</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    
                    // Generate fix queries
                    echo "<div class='info'>";
                    echo "<h3>🔧 SQL Queries to Fix These Users</h3>";
                    echo "<p>Copy salah satu query di bawah dan jalankan di phpMyAdmin:</p>";
                    
                    echo "<h4>Option 1: Fix ALL MD5 users at once</h4>";
                    echo "<pre>";
                    echo "-- Update ALL users with MD5 password\n";
                    echo "UPDATE users \n";
                    echo "SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'\n";
                    echo "WHERE LENGTH(password) = 32;\n\n";
                    echo "-- New password for all: password123";
                    echo "</pre>";
                    
                    echo "<h4>Option 2: Fix individual users</h4>";
                    echo "<pre>";
                    foreach ($md5_users as $user) {
                        echo "-- Fix user: {$user['username']} (ID: {$user['id_user']})\n";
                        echo "UPDATE users \n";
                        echo "SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'\n";
                        echo "WHERE id_user = {$user['id_user']};\n";
                        echo "-- Username: {$user['username']}, Password: password123\n\n";
                    }
                    echo "</pre>";
                    echo "</div>";
                    
                } else {
                    echo "<div class='success'>";
                    echo "<h3>✓ All passwords are using correct format (bcrypt)</h3>";
                    echo "<p>No MD5 passwords found. All users should be able to login.</p>";
                    echo "</div>";
                }
                
            } else {
                echo "<div class='error'>✗ No users found in database!</div>";
            }
            
        } else {
            echo "<div class='error'>✗ Database connection failed!</div>";
        }
        ?>
        
        <h2>Quick Actions</h2>
        <a href="debug-login.php" class="btn">Test Login</a>
        <a href="user/login.php" class="btn">Go to Login Page</a>
        <a href="?" class="btn">Refresh</a>
    </div>
</body>
</html>