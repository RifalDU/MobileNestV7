<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin'])) {
    header('Location: ../user/login.php');
    exit;
}

$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MobileNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .admin-nav { background: linear-gradient(135deg, #667eea, #764ba2); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; font-size: 18px; }
        .sidebar { position: sticky; top: 100px; }
        .card { border-radius: 10px; border: none; }
        .card-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: 700; }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-icon { font-size: 2.5rem; margin-bottom: 10px; }
        .stat-number { font-size: 24px; font-weight: 700; margin: 10px 0; }
        .stat-label { color: #7f8c8d; font-size: 14px; }
        .menu-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border-radius: 8px;
            border: 2px solid transparent;
            text-decoration: none;
            color: #667eea;
            background: white;
            font-weight: 600;
            transition: all 0.3s;
        }
        .menu-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .menu-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .table-responsive { border-radius: 8px; }
        .badge { font-weight: 600; }
    </style>
</head>
<body>
    <!-- ADMIN NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-speedometer2"></i> MobileNest Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="../user/profil.php"><i class="bi bi-person"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../user/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ADMIN LAYOUT -->
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-lg-3 mb-4">
                <div class="sidebar">
                    <h5 style="font-weight: 700; margin-bottom: 20px; color: #2c3e50;">
                        <i class="bi bi-list"></i> Menu Admin
                    </h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="index.php" class="menu-btn active">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="verifikasi-pembayaran.php" class="menu-btn">
                            <i class="bi bi-credit-card"></i> Verifikasi Pembayaran
                        </a>
                        <a href="kelola-transaksi.php" class="menu-btn">
                            <i class="bi bi-receipt"></i> Kelola Transaksi
                        </a>
                        <a href="kelola-produk.php" class="menu-btn">
                            <i class="bi bi-box-seam"></i> Kelola Produk
                        </a>
                        <a href="laporan.php" class="menu-btn">
                            <i class="bi bi-bar-chart"></i> Laporan
                        </a>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-lg-9">
                <h1 style="font-weight: 700; margin-bottom: 30px; color: #2c3e50;">
                    <i class="bi bi-speedometer2"></i> Dashboard Admin
                </h1>

                <!-- STATS CARDS -->
                <div class="row mb-5">
                    <!-- Pending Payments -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="color: #f59e0b;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stat-number" style="color: #f59e0b;">
                                <?php 
                                    $pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status_pesanan = 'Menunggu Verifikasi'"))['total'] ?? 0;
                                    echo $pending; 
                                ?>
                            </div>
                            <div class="stat-label">Pembayaran Pending</div>
                        </div>
                    </div>

                    <!-- Total Produk -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="color: #667eea;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="stat-number" style="color: #667eea;">
                                <?php 
                                    $total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produk"))['total'];
                                    echo $total_produk; 
                                ?>
                            </div>
                            <div class="stat-label">Total Produk</div>
                        </div>
                    </div>

                    <!-- Total User -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="color: #10b981;">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-number" style="color: #10b981;">
                                <?php 
                                    $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
                                    echo $total_users; 
                                ?>
                            </div>
                            <div class="stat-label">Total User</div>
                        </div>
                    </div>

                    <!-- Total Revenue -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="color: #ef4444;">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                            <div class="stat-number" style="color: #ef4444; font-size: 18px;">
                                Rp <?php 
                                    $total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM transaksi WHERE status_pesanan IN ('Verified', 'Dalam Pengiriman', 'Selesai')"))['total'] ?? 0;
                                    echo number_format($total_revenue, 0, ',', '.'); 
                                ?>
                            </div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                </div>

                <!-- ALERT: Pending Payments -->
                <?php if ($pending > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin-bottom: 30px;">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <strong>Perhatian!</strong> Ada <?php echo $pending; ?> pembayaran yang menunggu verifikasi.
                        <a href="verifikasi-pembayaran.php" class="alert-link fw-bold">Verifikasi Sekarang →</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- QUICK ACTIONS -->
                <div class="card mb-5">
                    <div class="card-header">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2 d-sm-flex flex-wrap">
                            <a href="verifikasi-pembayaran.php" class="btn btn-warning fw-bold">
                                <i class="bi bi-credit-card"></i> Verifikasi Pembayaran
                            </a>
                            <a href="kelola-produk.php?action=tambah" class="btn btn-primary fw-bold">
                                <i class="bi bi-plus-circle"></i> Tambah Produk Baru
                            </a>
                            <a href="kelola-produk.php" class="btn btn-outline-primary fw-bold">
                                <i class="bi bi-list"></i> Kelola Produk
                            </a>
                            <a href="../index.php" class="btn btn-outline-secondary fw-bold">
                                <i class="bi bi-house"></i> Ke Home
                            </a>
                        </div>
                    </div>
                </div>

                <!-- RECENT TRANSACTIONS -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-receipt"></i> Transaksi Terbaru
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead style="background: #f9f9f9;">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tanggal</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Metode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT id_transaksi, tanggal_transaksi, total_harga, status_pesanan, metode_pembayaran FROM transaksi ORDER BY tanggal_transaksi DESC LIMIT 5";
                                    $result = mysqli_query($conn, $sql);
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $status_class = strtolower(str_replace(' ', '_', $row['status_pesanan']));
                                            ?>
                                            <tr>
                                                <td><strong>#<?php echo $row['id_transaksi']; ?></strong></td>
                                                <td><small><?php echo date('d M Y H:i', strtotime($row['tanggal_transaksi'])); ?></small></td>
                                                <td><strong>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($row['status_pesanan']) {
                                                            'Menunggu Verifikasi' => 'warning',
                                                            'Verified' => 'info',
                                                            'Dalam Pengiriman' => 'primary',
                                                            'Selesai' => 'success',
                                                            'Dibatalkan' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>"><?php echo htmlspecialchars($row['status_pesanan']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['metode_pembayaran']); ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada transaksi</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-light border-top py-4 mt-5">
        <div class="container-fluid text-center">
            <p class="mb-0 text-muted">© 2026 MobileNest Admin Dashboard. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>