<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/upload-handler.php';

// autentikasi
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../user/login.php');
    exit;
}

// handle approve/reject - FIX: Use simple VARCHAR instead of ENUM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_transaksi = (int)($_POST['id_transaksi'] ?? 0);

    if (($action === 'approve' || $action === 'reject') && $id_transaksi > 0) {
        // ✅ FIX: Use simple string values instead of ENUM
        $new_status = ($action === 'approve') ? 'Diproses' : 'Dibatalkan';
        
        // ✅ Use prepared statement for safety
        $stmt = $conn->prepare("UPDATE transaksi SET status_pesanan = ?, tanggal_diperbarui = NOW() WHERE id_transaksi = ?");
        
        if ($stmt) {
            $stmt->bind_param('si', $new_status, $id_transaksi);
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = ($action === 'approve') ? '✅ Pembayaran berhasil diverifikasi! Status: Diproses' : '❌ Pembayaran ditolak!';
                $_SESSION['msg_type'] = ($action === 'approve') ? 'success' : 'warning';
            } else {
                $_SESSION['msg'] = '⚠️ Error update: ' . $stmt->error;
                $_SESSION['msg_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['msg'] = '⚠️ Error prepare: ' . $conn->error;
            $_SESSION['msg_type'] = 'danger';
        }
        
        header('Location: verifikasi-pembayaran.php');
        exit;
    }
}

// Get pending payments - FIX: Don't rely on specific status values
$query_list = "SELECT id_transaksi, kode_transaksi, id_user, total_harga, tanggal_transaksi, bukti_pembayaran, status_pesanan FROM transaksi WHERE status_pesanan IN ('Menunggu Verifikasi', 'Menunggu Pembayaran', 'Menunggu Konfirmasi') ORDER BY tanggal_transaksi DESC";
$result = mysqli_query($conn, $query_list);

if (!$result) {
    die('Query error: ' . mysqli_error($conn));
}

$pending_count = mysqli_num_rows($result);

// get selected payment details
$selected_payment = null;
if (!empty($_GET['id'])) {
    $selected_id = (int)$_GET['id'];
    
    $query_detail = "SELECT * FROM transaksi WHERE id_transaksi = $selected_id";
    $detail_result = mysqli_query($conn, $query_detail);
    
    if ($detail_result && mysqli_num_rows($detail_result) > 0) {
        $selected_payment = mysqli_fetch_assoc($detail_result);

        // get user info
        $user_query = "SELECT id_user, nama_lengkap, email, no_telepon FROM users WHERE id_user = " . (int)$selected_payment['id_user'];
        $user_result = mysqli_query($conn, $user_query);
        if ($user_result) {
            $selected_payment['user'] = mysqli_fetch_assoc($user_result);
        }

        // get transaksi items - JOIN dengan produk
        $items_query = "SELECT dt.id_produk, p.nama_produk, dt.harga_satuan, dt.jumlah, dt.subtotal FROM detail_transaksi dt LEFT JOIN produk p ON dt.id_produk = p.id_produk WHERE dt.id_transaksi = $selected_id";
        $items_result = mysqli_query($conn, $items_query);
        $selected_payment['items'] = [];
        if ($items_result) {
            while ($item = mysqli_fetch_assoc($items_result)) {
                $selected_payment['items'][] = $item;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2) !important; }
        .navbar-brand { font-weight: 700; font-size: 18px; }
        .nav-link { color: rgba(255,255,255,0.8) !important; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { color: white !important; }
        .container-main { display: grid; grid-template-columns: 350px 1fr; gap: 20px; margin-top: 20px; }
        @media (max-width: 768px) { .container-main { grid-template-columns: 1fr; } }
        .list-group-item { border: none; border-bottom: 1px solid #e0e0e0; cursor: pointer; transition: all 0.2s; }
        .list-group-item:hover { background: #f0f7ff; }
        .list-group-item.active { background: #667eea; color: white; border-color: #667eea; }
        .detail-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .detail-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .payment-proof { max-width: 100%; max-height: 400px; object-fit: contain; border-radius: 8px; margin: 15px 0; }
        .info-row { display: grid; grid-template-columns: 150px 1fr; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #667eea; }
        .items-table { font-size: 14px; }
        .items-table th { background: #f0f7ff; font-weight: 600; color: #2c3e50; border: none; }
        .action-btns { display: flex; gap: 10px; margin-top: 20px; }
        .action-btns button { flex: 1; }
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="bi bi-credit-card"></i> MobileNest Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="kelola-produk.php"><i class="bi bi-phone"></i> Produk</a></li>
                    <li class="nav-item"><a class="nav-link active" href="verifikasi-pembayaran.php"><i class="bi bi-credit-card"></i> Verifikasi</a></li>
                    <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php"><i class="bi bi-receipt"></i> Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="bi bi-bar-chart"></i> Laporan</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../user/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid">
        <div class="row mb-4" style="margin-top: 20px;">
            <div class="col">
                <h2 style="font-weight: 700; color: #2c3e50;">
                    <i class="bi bi-credit-card"></i> Verifikasi Pembayaran
                    <span class="badge bg-warning ms-2"><?= $pending_count ?> pending</span>
                </h2>
            </div>
        </div>

        <?php if (!empty($_SESSION['msg'])): ?>
            <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <div class="container-main" style="margin-bottom: 40px;">
            <!-- LEFT: List Pembayaran Pending -->
            <div>
                <h5 style="font-weight: 600; margin-bottom: 15px;"><i class="bi bi-list"></i> Pembayaran Pending</h5>
                <div class="list-group" style="border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <?php if ($pending_count === 0): ?>
                        <div class="empty-state" style="background: white;">
                            <i class="bi bi-check-circle" style="font-size: 48px; color: #10b981;"></i>
                            <p style="margin-top: 15px;">Tidak ada pembayaran pending!</p>
                        </div>
                    <?php else: ?>
                        <?php while ($payment = mysqli_fetch_assoc($result)): ?>
                            <a href="?id=<?= urlencode($payment['id_transaksi']) ?>" class="list-group-item list-group-item-action <?= (!empty($_GET['id']) && $_GET['id'] == $payment['id_transaksi']) ? 'active' : '' ?>">
                                <div style="font-weight: 600;">#<?= htmlspecialchars($payment['kode_transaksi']) ?></div>
                                <small class="text-muted" style="display: block; margin-top: 5px;"><?= htmlspecialchars(date('d M Y H:i', strtotime($payment['tanggal_transaksi']))) ?></small>
                                <small style="display: block; margin-top: 3px;">Rp <?= number_format((float)$payment['total_harga'], 0, ',', '.') ?></small>
                                <span class="status-badge pending" style="display: block; margin-top: 5px; width: fit-content;"><?= htmlspecialchars($payment['status_pesanan']) ?></span>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: Detail Pembayaran -->
            <div>
                <?php if ($selected_payment): ?>
                    <div class="detail-card">
                        <div class="detail-header">
                            <h5 style="margin: 0; font-weight: 700;">
                                <i class="bi bi-receipt-cutoff"></i> Detail Pembayaran
                            </h5>
                            <small style="opacity: 0.9;">ID: #<?= htmlspecialchars($selected_payment['id_transaksi']) ?> | Status: <?= htmlspecialchars($selected_payment['status_pesanan']) ?></small>
                        </div>

                        <div style="padding: 20px;">
                            <!-- Bukti Pembayaran -->
                            <div style="margin-bottom: 25px;">
                                <h6 style="font-weight: 600; margin-bottom: 10px;"><i class="bi bi-image"></i> Bukti Pembayaran</h6>
                                <?php if (!empty($selected_payment['bukti_pembayaran'])): ?>
                                    <?php $payment_proof_url = UploadHandler::getFileUrl($selected_payment['bukti_pembayaran'], 'pembayaran'); ?>
                                    <img src="<?= htmlspecialchars($payment_proof_url) ?>" alt="Bukti Pembayaran" class="payment-proof" onerror="this.src='https://via.placeholder.com/400x300?text=Image+Not+Found'">
                                <?php else: ?>
                                    <p class="text-muted"><i class="bi bi-exclamation-circle"></i> Bukti pembayaran tidak ditemukan</p>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <!-- Info Customer -->
                            <h6 style="font-weight: 600; margin-bottom: 15px;"><i class="bi bi-person"></i> Informasi Customer</h6>
                            <?php if (!empty($selected_payment['user'])): ?>
                                <div class="info-row">
                                    <span class="info-label">Nama:</span>
                                    <span><?= htmlspecialchars($selected_payment['user']['nama_lengkap']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email:</span>
                                    <span><?= htmlspecialchars($selected_payment['user']['email']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">No HP:</span>
                                    <span><?= htmlspecialchars($selected_payment['user']['no_telepon'] ?? '-') ?></span>
                                </div>
                            <?php endif; ?>

                            <hr>

                            <!-- Info Pengiriman -->
                            <h6 style="font-weight: 600; margin-bottom: 15px;"><i class="bi bi-box-seam"></i> Alamat Pengiriman</h6>
                            <?php if (!empty($selected_payment['alamat_pengiriman'])): ?>
                                <div class="info-row">
                                    <span class="info-label">Alamat:</span>
                                    <span><?= htmlspecialchars($selected_payment['alamat_pengiriman']) ?></span>
                                </div>
                                <div class="info-row" style="border-bottom: none;">
                                    <span class="info-label">Ekspedisi:</span>
                                    <span><?= htmlspecialchars($selected_payment['ekspedisi'] ?? '-') ?></span>
                                </div>
                            <?php else: ?>
                                <p class="text-muted"><i class="bi bi-exclamation-circle"></i> Info pengiriman belum tersedia</p>
                            <?php endif; ?>

                            <hr>

                            <!-- Items -->
                            <h6 style="font-weight: 600; margin-bottom: 15px;"><i class="bi bi-box"></i> Rincian Produk</h6>
                            <?php if (!empty($selected_payment['items']) && count($selected_payment['items']) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table items-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Qty</th>
                                                <th>Harga</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($selected_payment['items'] as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['nama_produk'] ?? 'N/A') ?></td>
                                                    <td><?= (int)$item['jumlah'] ?></td>
                                                    <td>Rp <?= number_format((float)$item['harga_satuan'], 0, ',', '.') ?></td>
                                                    <td><strong>Rp <?= number_format((float)$item['subtotal'], 0, ',', '.') ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted"><i class="bi bi-exclamation-circle"></i> Tidak ada item dalam transaksi</p>
                            <?php endif; ?>

                            <hr>

                            <!-- Total -->
                            <div style="background: #f0f7ff; padding: 15px; border-radius: 8px; margin: 15px 0;">
                                <div style="display: grid; grid-template-columns: 150px 1fr; gap: 15px; font-weight: 600;">
                                    <span>Total Pembayaran:</span>
                                    <span style="color: #667eea; font-size: 18px;">Rp <?= number_format((float)$selected_payment['total_harga'], 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="action-btns">
                                <form method="post" class="w-100">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id_transaksi" value="<?= (int)$selected_payment['id_transaksi'] ?>">
                                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Verifikasi pembayaran ini?');">
                                        <i class="bi bi-check-circle"></i> Verifikasi Pembayaran
                                    </button>
                                </form>
                            </div>
                            <div class="action-btns">
                                <form method="post" class="w-100">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id_transaksi" value="<?= (int)$selected_payment['id_transaksi'] ?>">
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Tolak pembayaran ini?');">
                                        <i class="bi bi-x-circle"></i> Tolak Pembayaran
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="detail-card">
                        <div style="padding: 60px 20px; text-align: center; color: #999;">
                            <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 15px;"></i>
                            <p>Pilih pembayaran dari daftar untuk melihat detail</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>