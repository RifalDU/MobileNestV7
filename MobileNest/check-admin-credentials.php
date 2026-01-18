<?php
/**
 * CHECK ADMIN CREDENTIALS UTILITY
 * Untuk verify data admin yang ada di database
 * Gunakan ini untuk test login dengan credentials yang benar
 */

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Credentials Checker - MobileNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .success-box { background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .error-box { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .warning-box { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 15px 0; border-radius: 4px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .table-custom { margin-top: 20px; }
        .hash-display { font-family: 'Courier New', monospace; font-size: 0.85em; word-break: break-all; }
        .btn-test { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🔐 Admin Credentials Checker</h1>
        
        <div class="info-box">
            <strong>Fungsi:</strong> Script ini menampilkan data admin dari database dan membantu verify password.
        </div>

        <?php
        // Check database connection
        if (!$conn) {
            echo '<div class="error-box"><strong>ERROR:</strong> Database connection failed</div>';
            echo '<p>' . mysqli_connect_error() . '</p>';
            exit;
        }

        // Check if admin table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'admin'");
        if (!$table_check || $table_check->num_rows === 0) {
            echo '<div class="error-box"><strong>ERROR:</strong> Table "admin" tidak ditemukan di database!</div>';
            exit;
        }

        // Fetch all admin records
        $query = "SELECT id_admin, username, email, nama_lengkap, no_telepon, tanggal_dibuat, password FROM admin ORDER BY id_admin";
        $result = $conn->query($query);

        if (!$result) {
            echo '<div class="error-box"><strong>Database Error:</strong> ' . $conn->error . '</div>';
            exit;
        }

        if ($result->num_rows === 0) {
            echo '<div class="warning-box"><strong>WARNING:</strong> Tidak ada data admin di database!</div>';
        } else {
            echo '<div class="success-box"><strong>✅ Total Admin:</strong> ' . $result->num_rows . ' user(s)</div>';
            
            // Display admin data
            echo '<div class="table-custom">';
            echo '<h3>Data Admin di Database:</h3>';
            echo '<table class="table table-striped table-hover">';
            echo '<thead class="table-dark">';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Username</th>';
            echo '<th>Email</th>';
            echo '<th>Nama Lengkap</th>';
            echo '<th>No. Telepon</th>';
            echo '<th>Tanggal Dibuat</th>';
            echo '<th>Password Hash (preview)</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            while ($admin = $result->fetch_assoc()) {
                $password_preview = substr($admin['password'], 0, 30) . '...';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($admin['id_admin']) . '</td>';
                echo '<td><strong>' . htmlspecialchars($admin['username']) . '</strong></td>';
                echo '<td>' . htmlspecialchars($admin['email'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($admin['nama_lengkap']) . '</td>';
                echo '<td>' . htmlspecialchars($admin['no_telepon'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($admin['tanggal_dibuat']) . '</td>';
                echo '<td><code class="hash-display">' . $password_preview . '</code></td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
        ?>

        <!-- Test Password Verification -->
        <div style="margin-top: 40px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
            <h3>🧪 Test Password Verification</h3>
            <p>Gunakan form di bawah untuk test apakah password yang diinput cocok dengan hash di database.</p>
            
            <form method="POST" style="margin-top: 20px;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="test_username" class="form-label"><strong>Username Admin</strong></label>
                        <input type="text" class="form-control" id="test_username" name="test_username" placeholder="Masukkan username" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="test_password" class="form-label"><strong>Password</strong></label>
                        <input type="password" class="form-control" id="test_password" name="test_password" placeholder="Masukkan password" required>
                    </div>
                </div>
                <button type="submit" name="test_password_btn" class="btn btn-primary">Test Password</button>
            </form>

            <?php
            // Handle password test
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_password_btn'])) {
                $test_username = trim($_POST['test_username'] ?? '');
                $test_password = trim($_POST['test_password'] ?? '');

                if (empty($test_username) || empty($test_password)) {
                    echo '<div class="error-box mt-3"><strong>ERROR:</strong> Username dan password harus diisi!</div>';
                } else {
                    // Query admin by username
                    $stmt = $conn->prepare("SELECT id_admin, username, email, nama_lengkap, password FROM admin WHERE username = ?");
                    $stmt->bind_param('s', $test_username);
                    $stmt->execute();
                    $test_result = $stmt->get_result();

                    if ($test_result->num_rows === 0) {
                        echo '<div class="error-box mt-3"><strong>❌ TIDAK DITEMUKAN:</strong> Admin dengan username "' . htmlspecialchars($test_username) . '" tidak ada di database!</div>';
                    } else {
                        $admin_data = $test_result->fetch_assoc();
                        
                        echo '<div class="mt-3">';
                        echo '<h5>Admin Found:</h5>';
                        echo '<p><strong>Username:</strong> ' . htmlspecialchars($admin_data['username']) . '</p>';
                        echo '<p><strong>Email:</strong> ' . htmlspecialchars($admin_data['email']) . '</p>';
                        echo '<p><strong>Nama:</strong> ' . htmlspecialchars($admin_data['nama_lengkap']) . '</p>';
                        echo '<p><strong>Password Hash:</strong> <code class="hash-display">' . htmlspecialchars($admin_data['password']) . '</code></p>';
                        echo '</div>';

                        // Verify password
                        if (password_verify($test_password, $admin_data['password'])) {
                            echo '<div class="success-box mt-3">';
                            echo '<strong>✅ PASSWORD COCOK!</strong><br>';
                            echo 'Password "' . htmlspecialchars($test_password) . '" dapat digunakan untuk login.';
                            echo '</div>';
                        } else {
                            echo '<div class="error-box mt-3">';
                            echo '<strong>❌ PASSWORD TIDAK COCOK!</strong><br>';
                            echo 'Password "' . htmlspecialchars($test_password) . '" tidak sesuai dengan hash di database.';
                            echo '</div>';
                        }
                    }

                    $stmt->close();
                }
            }
            ?>
        </div>

        <!-- Info Section -->
        <div style="margin-top: 40px; padding: 20px; background: #f0f7ff; border-radius: 8px;">
            <h3>📄 Informasi Penting</h3>
            
            <div class="info-box">
                <strong>Untuk Login sebagai Admin:</strong>
                <ol>
                    <li>Buka: <code>http://localhost/MobileNest/user/login.php</code></li>
                    <li>Masukkan <strong>username</strong> dari tabel di atas</li>
                    <li>Masukkan <strong>password</strong> yang BENAR (bukan hash)</li>
                    <li>Klik Masuk</li>
                    <li>Jika berhasil, akan redirect ke <code>admin/index.php</code></li>
                </ol>
            </div>

            <div class="warning-box">
                <strong>⚠️ Password Hash:</strong> Password yang ditampilkan di tabel adalah HASH (sudah dienkripsi), BUKAN password asli. Gunakan password asli untuk login, bukan hash-nya!
            </div>

            <div class="info-box">
                <strong>Jika lupa password:</strong> Gunakan script <code>reset-admin-password.php</code> untuk reset password admin, atau:
                <pre><code>UPDATE admin SET password = PASSWORD_HASH('password_baru', PASSWORD_DEFAULT) WHERE username = 'admin';</code></pre>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="user/login.php" class="btn btn-primary btn-lg">Go to Login Page</a>
            <a href="debug-login.php" class="btn btn-secondary btn-lg">Debug Login</a>
            <a href="test-connection.php" class="btn btn-secondary btn-lg">Test Connection</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>