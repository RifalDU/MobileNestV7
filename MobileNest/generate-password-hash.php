<?php
// Password yang mau diset untuk admin
$new_password = 'admin123';

// Generate hash
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Tampilkan ke user
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Password Hash</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .box { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; }
        code { background: #fff; padding: 10px; display: block; margin: 5px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h2>🔐 Generate Password Hash untuk Admin</h2>
    
    <div class="box">
        <strong>Password yang akan diset:</strong>
        <code><?php echo htmlspecialchars($new_password); ?></code>
    </div>
    
    <div class="box">
        <strong>Hash yang dihasilkan:</strong>
        <code><?php echo htmlspecialchars($password_hash); ?></code>
    </div>
    
    <div class="box">
        <strong>SQL Command untuk Update Database:</strong>
        <code>UPDATE admin SET password = '<?php echo $password_hash; ?>' WHERE username = 'admin';</code>
    </div>
    
    <div class="box">
        <strong>Cara pakai SQL di atas:</strong>
        <ol>
            <li>Buka phpMyAdmin</li>
            <li>Pilih database: <code>mobilenest_db</code></li>
            <li>Klik tab: <code>SQL</code></li>
            <li>Copy-paste SQL command di atas</li>
            <li>Klik Execute/Go</li>
            <li>Tunggu sampai success</li>
        </ol>
    </div>
    
    <div class="box">
        <strong>Setelah itu, login dengan:</strong>
        <ul>
            <li>URL: <code>http://localhost/MobileNest/user/login.php</code></li>
            <li>Username: <code>admin</code></li>
            <li>Password: <code><?php echo htmlspecialchars($new_password); ?></code></li>
        </ul>
    </div>
</body>
</html>
