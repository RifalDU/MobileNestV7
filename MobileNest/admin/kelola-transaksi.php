<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config.php';

// autentikasi
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../user/login.php');
    exit;
}

// handle actions: update status, update resi, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && !empty($_POST['id_transaksi']) && isset($_POST['status_pesanan'])) {
        $id = (int)$_POST['id_transaksi'];
        $status = trim($_POST['status_pesanan']);
        $query = "UPDATE transaksi SET status_pesanan = '$status', tanggal_diperabarui = NOW() WHERE id_transaksi = $id";
        mysqli_query($conn, $query);
        header('Location: kelola-transaksi.php');
        exit;
    }

    if ($action === 'update_resi' && !empty($_POST['id_transaksi'])) {
        $id = (int)$_POST['id_transaksi'];
        $no_resi = mysqli_real_escape_string($conn, trim($_POST['no_resi']));
        $query = "UPDATE transaksi SET no_resi = '$no_resi', tanggal_diperabarui = NOW() WHERE id_transaksi = $id";
        mysqli_query($conn, $query);
        header('Location: kelola-transaksi.php');
        exit;
    }

    if ($action === 'delete' && !empty($_POST['id_transaksi'])) {
        $id = (int)$_POST['id_transaksi'];
        $query = "DELETE FROM transaksi WHERE id_transaksi = $id";
        mysqli_query($conn, $query);
        header('Location: kelola-transaksi.php');
        exit;
    }
}

// paging / filter sederhana
$limit = 100;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// build WHERE clause
$where = "1=1";
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';

// filter status
if (!empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND status_pesanan = '$status'";
}

// filter date range
if (!empty($start) && !empty($end)) {
    $start = mysqli_real_escape_string($conn, $start);
    $end = mysqli_real_escape_string($conn, $end);
    $where .= " AND tanggal_transaksi BETWEEN '$start 00:00:00' AND '$end 23:59:59'";
}

// query
$sql = "SELECT id_transaksi, kode_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, no_resi, tanggal_transaksi FROM transaksi WHERE $where ORDER BY tanggal_transaksi DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die('Query error: ' . mysqli_error($conn));
}

// statuses
$statuses = ['Menunggu Verifikasi', 'Verified', 'Dalam Pengiriman', 'Selesai', 'Dibatalkan'];

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Transaksi - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2) !important; }
        .navbar-brand { font-weight: 700; font-size: 18px; }
        .nav-link { color: rgba(255,255,255,0.8) !important; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { color: white !important; }
        .container-fluid { margin-top: 20px; }
        .table { background: white; border-radius: 10px; overflow: hidden; }
        .table th { background: #f0f7ff; font-weight: 700; color: #2c3e50; border: none; }
        .table td { border-color: #e0e0e0; vertical-align: middle; }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .btn-info { background: #667eea; border-color: #667eea; color: white; }
        .btn-info:hover { background: #764ba2; border-color: #764ba2; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><i class="bi bi-bag-check"></i> MobileNest Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="kelola-produk.php"><i class="bi bi-phone"></i> Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="verifikasi-pembayaran.php"><i class="bi bi-credit-card"></i> Verifikasi</a></li>
        <li class="nav-item"><a class="nav-link active" href="kelola-transaksi.php"><i class="bi bi-receipt"></i> Transaksi</a></li>
        <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="bi bi-bar-chart"></i> Laporan</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../user/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container-fluid">
  <div class="row mb-4">
    <div class="col">
      <h2 style="font-weight: 700; color: #2c3e50;">
        <i class="bi bi-receipt"></i> Kelola Transaksi
      </h2>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form class="row g-3" method="get" action="kelola-transaksi.php">
        <div class="col-md-3">
          <label class="form-label fw-bold">Status</label>
          <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>" <?= (isset($_GET['status']) && $_GET['status'] === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Dari Tanggal</label>
          <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Sampai Tanggal</label>
          <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary fw-bold flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
          <a href="kelola-transaksi.php" class="btn btn-outline-secondary fw-bold"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kode</th>
          <th>Tanggal</th>
          <th>Total</th>
          <th>Status</th>
          <th>Metode</th>
          <th>No Resi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result || mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-inbox"></i> Tidak ada transaksi.</td></tr>
        <?php else: ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><strong>#<?= htmlspecialchars($row['id_transaksi']) ?></strong></td>
              <td><?= htmlspecialchars($row['kode_transaksi']) ?></td>
              <td><small><?= htmlspecialchars(date('d M Y H:i', strtotime($row['tanggal_transaksi']))) ?></small></td>
              <td><strong>Rp <?= number_format((float)$row['total_harga'],0,',','.') ?></strong></td>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <select name="status_pesanan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($statuses as $s): ?>
                      <option value="<?= htmlspecialchars($s) ?>" <?= $s === $row['status_pesanan'] ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
              <td>
                <form method="post" class="d-flex gap-1">
                  <input type="hidden" name="action" value="update_resi">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <input name="no_resi" class="form-control form-control-sm" value="<?= htmlspecialchars($row['no_resi'] ?? '') ?>" placeholder="Resi">
                  <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check"></i></button>
                </form>
              </td>
              <td class="text-nowrap">
                <a href="detail-transaksi.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> Detail</a>
                <form method="post" class="d-inline" onsubmit="return confirm('Hapus?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted text-center mt-3">Menampilkan maksimal <?= $limit ?> transaksi per halaman.</p>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>