<?php
/**
 * Admin Dashboard - Alternative view
 * Only accessible by admin users
 * Includes role check middleware
 */

require_once '../api/auth/check_auth.php';

// Check if user is admin
if ($user_role !== 'admin') {
    http_response_code(403);
    header('Location: ../index.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MobileNest</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar h2 {
            font-size: 24px;
            cursor: pointer;
        }
        
        .navbar h2:hover {
            opacity: 0.9;
        }
        
        .user-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .badge-admin {
            background: rgba(255,255,255,0.3);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .welcome-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .welcome-box h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .welcome-box p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .user-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .user-details strong {
            color: #667eea;
        }
        
        .menu-section {
            margin-bottom: 30px;
        }
        
        .menu-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .menu-item {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border-top: 3px solid #667eea;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
        }
        
        .menu-item-icon {
            font-size: 40px;
        }
        
        .menu-item-title {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }
        
        .menu-item-desc {
            font-size: 13px;
            color: #666;
        }
        
        .status-info {
            background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .status-info h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(102,126,234,0.2);
            font-size: 14px;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-value {
            font-weight: 600;
            color: #667eea;
        }

        @media (max-width: 768px) {
            .user-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .navbar h2 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div>
            <h2 onclick="location.href='index.php'" title="Go to main dashboard">🏢 MobileNest Admin</h2>
        </div>
        <div class="user-info">
            <span class="badge-admin">👤 ADMIN</span>
            <span><?= htmlspecialchars($nama_lengkap) ?></span>
            <a href="../user/logout.php" class="btn-logout">🚪 Logout</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Box -->
        <div class="welcome-box">
            <h1>👋 Selamat Datang, <?= htmlspecialchars($nama_lengkap) ?>!</h1>
            <p>Anda login sebagai <strong>Administrator</strong> MobileNest</p>
            <p>Kelola produk, pengguna, dan transaksi dari sini.</p>
            <div class="user-details">
                <strong>Login Info:</strong><br>
                Username: <strong><?= htmlspecialchars($username) ?></strong><br>
                Email: <strong><?= htmlspecialchars($email) ?></strong><br>
                Login Time: <strong><?= isset($_SESSION['login_time']) ? date('d M Y H:i:s', $_SESSION['login_time']) : 'N/A' ?></strong>
            </div>
        </div>
        
        <!-- Status Info -->
        <div class="status-info">
            <h3>📊 Quick Stats</h3>
            <div class="status-item">
                <span>Total Produk:</span>
                <span class="status-value">13</span>
            </div>
            <div class="status-item">
                <span>Total Pengguna:</span>
                <span class="status-value">6</span>
            </div>
            <div class="status-item">
                <span>Total Transaksi:</span>
                <span class="status-value">0</span>
            </div>
            <div class="status-item">
                <span>Promosi Aktif:</span>
                <span class="status-value">2</span>
            </div>
        </div>
        
        <!-- Admin Menu -->
        <div class="menu-section">
            <h2>⚙️ Management Menu</h2>
            <div class="menu-grid">
                <a href="kelola-produk.php" class="menu-item">
                    <div class="menu-item-icon">📦</div>
                    <div class="menu-item-title">Kelola Produk</div>
                    <div class="menu-item-desc">Tambah, edit, hapus produk</div>
                </a>
                
                <a href="kelola-users.php" class="menu-item">
                    <div class="menu-item-icon">👥</div>
                    <div class="menu-item-title">Kelola Pengguna</div>
                    <div class="menu-item-desc">Manage akun customer</div>
                </a>
                
                <a href="kelola-transaksi.php" class="menu-item">
                    <div class="menu-item-icon">📋</div>
                    <div class="menu-item-title">Kelola Pesanan</div>
                    <div class="menu-item-desc">Lihat & proses pesanan</div>
                </a>
                
                <a href="laporan.php" class="menu-item">
                    <div class="menu-item-icon">📊</div>
                    <div class="menu-item-title">Laporan & Analitik</div>
                    <div class="menu-item-desc">Analisis penjualan</div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>