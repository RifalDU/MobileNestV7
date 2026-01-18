<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../user/login.php');
    exit;
}

$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date   = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
$sd_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date);
$ed_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date);
if (!$sd_ok) $start_date = date('Y-m-d', strtotime('-30 days'));
if (!$ed_ok) $end_date = date('Y-m-d');

$start_datetime = $start_date . ' 00:00:00';
$end_datetime   = $end_date . ' 23:59:59';

// Count transactions
$count_query = "SELECT COUNT(*) as cnt FROM transaksi WHERE tanggal_transaksi BETWEEN '$start_datetime' AND '$end_datetime'";
$count_result = mysqli_query($conn, $count_query);
$totalTransactions = 0;
if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    $totalTransactions = (int)$row['cnt'];
} else {
    error_log('Count query error: ' . mysqli_error($conn));
}

// Sum revenue
$sum_query = "SELECT IFNULL(SUM(total_harga),0) as total FROM transaksi WHERE tanggal_transaksi BETWEEN '$start_datetime' AND '$end_datetime'";
$sum_result = mysqli_query($conn, $sum_query);
$totalRevenue = 0;
if ($sum_result) {
    $row = mysqli_fetch_assoc($sum_result);
    $totalRevenue = (float)$row['total'];
} else {
    error_log('Sum query error: ' . mysqli_error($conn));
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $list_query = "SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi FROM transaksi WHERE tanggal_transaksi BETWEEN '$start_datetime' AND '$end_datetime' ORDER BY tanggal_transaksi DESC";
    $list_result = mysqli_query($conn, $list_query);
    
    if (!$list_result) {
        die('CSV Query error: ' . mysqli_error($conn));
    }
    
    $filename = 'laporan_transaksi_' . $start_date . '_to_' . $end_date . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID Transaksi','ID User','Tanggal Transaksi','Total Harga','Status Pesanan','Metode Pembayaran','No Resi','Alamat Pengiriman']);
    
    while ($row = mysqli_fetch_assoc($list_result)) {
        fputcsv($out, [
            $row['id_transaksi'],
            $row['id_user'],
            $row['tanggal_transaksi'],
            $row['total_harga'],
            $row['status_pesanan'],
            $row['metode_pembayaran'],
            $row['no_resi'] ?? '',
            $row['alamat_pengiriman'],
        ]);
    }
    fclose($out);
    exit;
}

// Display list (limited to 1000)
$display_limit = 1000;
$list_query = "SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi FROM transaksi WHERE tanggal_transaksi BETWEEN '$start_datetime' AND '$end_datetime' ORDER BY tanggal_transaksi DESC LIMIT $display_limit";
$result = mysqli_query($conn, $list_query);

if (!$result) {
    die('Display query error: ' . mysqli_error($conn));
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Laporan Transaksi - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2) !important; }
        .navbar-brand { font-weight: 700; font-size: 18px; }
        .nav-link { color: rgba(255,255,255,0.8) !important; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { color: white !important; }
        .container-fluid { margin-top: 20px; }
        .stat-card { background: white; border-radius: 10px; overflow: hidden; border-left: 4px solid #667eea; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card h6 { font-size: 12px; text-transform: uppercase; color: #667eea; font-weight: 700; margin-bottom: 8px; }
        .stat-card .stat-value { font-size: 24px; font-weight: 700; color: #2c3e50; }
        .table { background: white; border-radius: 10px; overflow: hidden; }
        .table th { background: #f0f7ff; font-weight: 700; color: #2c3e50; border: none; }
        .table td { border-color: #e0e0e0; }
        .badge-lg { padding: 8px 12px; font-size: 13px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><i class="bi bi-file-earmark-text"></i> MobileNest Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="kelola-produk.php"><i class="bi bi-phone"></i> Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="verifikasi-pembayaran.php"><i class="bi bi-credit-card"></i> Verifikasi</a></li>
        <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php"><i class="bi bi-receipt"></i> Transaksi</a></li>
        <li class="nav-item"><a class="nav-link active" href="laporan.php"><i class="bi bi-bar-chart"></i> Laporan</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../user/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container-fluid">
  <div class="row mb-4">
    <div class="col">
      <h2 style="font-weight: 700; color: #2c3e50;">
        <i class="bi bi-bar-chart"></i> Laporan Transaksi
      </h2>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="get" action="laporan.php">
        <div class="col-md-3">
          <label class="form-label fw-bold">Dari Tanggal</label>
          <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Sampai Tanggal</label>
          <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary fw-bold flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
          <a href="laporan.php" class="btn btn-outline-secondary fw-bold"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
        <div class="col-md-3 text-end">
          <a href="laporan.php?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success fw-bold"><i class="bi bi-download"></i> Export CSV</a>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="stat-card p-4">
        <h6 class="mb-0"><i class="bi bi-receipt"></i> Total Transaksi</h6>
        <div class="stat-value mt-2"><?= number_format($totalTransactions, 0) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card p-4" style="border-left-color: #28a745;">
        <h6 class="mb-0 text-success"><i class="bi bi-cash-coin"></i> Total Pendapatan</h6>
        <div class="stat-value mt-2" style="color: #28a745;">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card p-4" style="border-left-color: #ffc107;">
        <h6 class="mb-0 text-warning"><i class="bi bi-calculator"></i> Rata-rata Transaksi</h6>
        <div class="stat-value mt-2" style="color: #ffc107;"><?= $totalTransactions > 0 ? 'Rp ' . number_format($totalRevenue / $totalTransactions, 0, ',', '.') : 'Rp 0' ?></div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Tanggal</th>
          <th>Total</th>
          <th>Status</th>
          <th>Metode</th>
          <th>No Resi</th>
          <th>Alamat</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-inbox"></i> Tidak ada transaksi pada rentang waktu ini.</td></tr>
        <?php else: ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><strong>#<?= htmlspecialchars($row['id_transaksi']) ?></strong></td>
              <td><?= htmlspecialchars($row['id_user']) ?></td>
              <td><small><?= htmlspecialchars(date('d M Y H:i', strtotime($row['tanggal_transaksi']))) ?></small></td>
              <td><strong>Rp <?= number_format((float)$row['total_harga'], 0, ',', '.') ?></strong></td>
              <td>
                <?php
                $status = $row['status_pesanan'];
                if ($status === 'Selesai') {
                    $badge_class = 'success';
                } elseif ($status === 'Dalam Pengiriman') {
                    $badge_class = 'info';
                } elseif ($status === 'Menunggu Verifikasi') {
                    $badge_class = 'warning';
                } elseif ($status === 'Dibatalkan') {
                    $badge_class = 'danger';
                } else {
                    $badge_class = 'secondary';
                }
                ?>
                <span class="badge bg-<?= $badge_class ?> badge-lg"><?= htmlspecialchars($status) ?></span>
              </td>
              <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
              <td><?= !empty($row['no_resi']) ? htmlspecialchars($row['no_resi']) : '<span class="text-muted">-</span>' ?></td>
              <td style="max-width: 200px;">
                <small class="text-truncate d-inline-block w-100"><?= htmlspecialchars($row['alamat_pengiriman']) ?></small>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted text-center mt-3"><small><i class="bi bi-info-circle"></i> Daftar dibatasi <?= $display_limit ?> baris. Gunakan Export CSV untuk data lengkap.</small></p>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>