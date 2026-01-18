<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth-check.php';
require_admin_login();
require_once '../config.php';

$transaksi = [];
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'semua';
$message = '';
$errors = [];

if ($filter_status === 'semua') {
    $sql = "SELECT t.id_transaksi, t.tanggal_transaksi, t.total_harga, t.status_pesanan, t.metode_pembayaran, t.no_resi, u.nama_lengkap, u.no_telepon, u.alamat, COUNT(dt.id_detail) as jumlah_item FROM transaksi t LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi LEFT JOIN users u ON t.id_user = u.id_user GROUP BY t.id_transaksi ORDER BY t.tanggal_transaksi DESC";
} else {
    $sql = "SELECT t.id_transaksi, t.tanggal_transaksi, t.total_harga, t.status_pesanan, t.metode_pembayaran, t.no_resi, u.nama_lengkap, u.no_telepon, u.alamat, COUNT(dt.id_detail) as jumlah_item FROM transaksi t LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi LEFT JOIN users u ON t.id_user = u.id_user WHERE t.status_pesanan = ? GROUP BY t.id_transaksi ORDER BY t.tanggal_transaksi DESC";
}

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Prepare failed: " . $conn->error); }
if ($filter_status !== 'semua') { $stmt->bind_param('s', $filter_status); }
if (!$stmt->execute()) { die("Execute failed: " . $stmt->error); }
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $transaksi[] = $row; }
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id_transaksi = intval($_POST['id_transaksi']);
    $status_baru = isset($_POST['status_baru']) ? $_POST['status_baru'] : '';
    $no_resi = isset($_POST['no_resi']) ? trim($_POST['no_resi']) : '';
    
    if (empty($status_baru)) { $errors[] = 'Status tidak boleh kosong'; }
    if ($status_baru === 'Dikirim' && empty($no_resi)) { $errors[] = 'No. Resi harus diisi untuk status Dikirim'; }
    
    if (empty($errors)) {
        if ($no_resi) {
            $update_sql = "UPDATE transaksi SET status_pesanan = ?, no_resi = ? WHERE id_transaksi = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) { $errors[] = 'Prepare failed: ' . $conn->error; } else {
                $update_stmt->bind_param('ssi', $status_baru, $no_resi, $id_transaksi);
                if ($update_stmt->execute()) {
                    $_SESSION['success'] = '✅ Status pesanan berhasil diperbarui!';
                    header('Location: kelola-pesanan.php?status=' . urlencode($filter_status));
                    exit;
                } else { $errors[] = 'Error: ' . $update_stmt->error; }
                $update_stmt->close();
            }
        } else {
            $update_sql = "UPDATE transaksi SET status_pesanan = ? WHERE id_transaksi = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) { $errors[] = 'Prepare failed: ' . $conn->error; } else {
                $update_stmt->bind_param('si', $status_baru, $id_transaksi);
                if ($update_stmt->execute()) {
                    $_SESSION['success'] = '✅ Status pesanan berhasil diperbarui!';
                    header('Location: kelola-pesanan.php?status=' . urlencode($filter_status));
                    exit;
                } else { $errors[] = 'Error: ' . $update_stmt->error; }
                $update_stmt->close();
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
    <title>Kelola Pesanan - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-badge { padding: 0.5rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-diproses { background-color: #17a2b8; color: #fff; }
        .status-dikirim { background-color: #007bff; color: #fff; }
        .status-selesai { background-color: #28a745; color: #fff; }
        .status-dibatalkan { background-color: #dc3545; color: #fff; }
        .table-pesanan tbody tr:hover { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <div class="container-fluid py-5">
        <h1 class="mb-4">📦 Kelola Pesanan</h1>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-1">❌ <?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="btn-group" role="group">
                    <a href="kelola-pesanan.php?status=semua" class="btn btn-outline-primary <?php echo $filter_status === 'semua' ? 'active' : ''; ?>">📦 Semua</a>
                    <a href="kelola-pesanan.php?status=Pending" class="btn btn-outline-warning <?php echo $filter_status === 'Pending' ? 'active' : ''; ?>">⏳ Pending</a>
                    <a href="kelola-pesanan.php?status=Diproses" class="btn btn-outline-info <?php echo $filter_status === 'Diproses' ? 'active' : ''; ?>">⚙️ Diproses</a>
                    <a href="kelola-pesanan.php?status=Dikirim" class="btn btn-outline-primary <?php echo $filter_status === 'Dikirim' ? 'active' : ''; ?>">🚚 Dikirim</a>
                    <a href="kelola-pesanan.php?status=Selesai" class="btn btn-outline-success <?php echo $filter_status === 'Selesai' ? 'active' : ''; ?>">✅ Selesai</a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover table-pesanan mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>User</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah Item</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($transaksi)): ?>
                                    <?php foreach ($transaksi as $item): ?>
                                        <tr>
                                            <td><strong>#<?php echo $item['id_transaksi']; ?></strong></td>
                                            <td><?php echo htmlspecialchars(substr($item['nama_lengkap'], 0, 20)); ?></td>
                                            <td><?php echo date('d M Y', strtotime($item['tanggal_transaksi'])); ?></td>
                                            <td><?php echo $item['jumlah_item']; ?></td>
                                            <td>Rp <?php echo number_format($item['total_harga'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $item['status_pesanan'])); ?>">
                                                    <?php echo htmlspecialchars($item['status_pesanan']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal" onclick="showDetail(<?php echo htmlspecialchars(json_encode($item)); ?>)">👁️ Detail</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">📭 Tidak ada pesanan dengan status ini</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📋 Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id_transaksi" id="id_transaksi">
                    <div class="modal-body">
                        <div id="detailContent"></div>
                        <hr>
                        <h6>🔄 Update Status Pesanan</h6>
                        <div class="mb-3">
                            <label class="form-label">Status Baru</label>
                            <select class="form-select" name="status_baru" id="status_baru" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="Pending">Pending</option>
                                <option value="Diproses">Diproses</option>
                                <option value="Dikirim">Dikirim</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Dibatalkan">Dibatalkan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. Resi (Opsional)</label>
                            <input type="text" class="form-control" name="no_resi" id="no_resi" placeholder="Nomor resi pengiriman">
                            <small class="text-muted">Wajib diisi jika status Dikirim</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
                        <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include '../footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDetail(pesanan) {
            document.getElementById('id_transaksi').value = pesanan.id_transaksi;
            document.getElementById('status_baru').value = pesanan.status_pesanan;
            document.getElementById('no_resi').value = pesanan.no_resi || '';
            const statusClass = 'status-' + pesanan.status_pesanan.toLowerCase().replace(' ', '');
            const html = `<div class="row mb-3"><div class="col-6"><strong>📋 ID Pesanan:</strong><br> #${pesanan.id_transaksi}</div><div class="col-6"><strong>📅 Tanggal:</strong><br> ${new Date(pesanan.tanggal_transaksi).toLocaleDateString('id-ID')}</div></div><div class="row mb-3"><div class="col-12"><strong>👥 User:</strong><br> ${pesanan.nama_lengkap}</div></div><div class="row mb-3"><div class="col-6"><strong>📞 No. Telepon:</strong><br> ${pesanan.no_telepon}</div><div class="col-6"><strong>💳 Metode Pembayaran:</strong><br> ${pesanan.metode_pembayaran}</div></div><div class="row mb-3"><div class="col-12"><strong>🏢 Alamat Pengiriman:</strong><br> ${pesanan.alamat}</div></div><div class="row mb-3"><div class="col-6"><strong>📦 Jumlah Item:</strong><br> ${pesanan.jumlah_item}</div><div class="col-6"><strong>💰 Total:</strong><br> <h5>Rp ${pesanan.total_harga.toLocaleString('id-ID')}</h5></div></div><div class="row"><div class="col-12"><strong>📝 Status Saat Ini:</strong><br><span class="status-badge ${statusClass}">${pesanan.status_pesanan}</span></div></div>`;
            document.getElementById('detailContent').innerHTML = html;
        }
    </script>
</body>
</html>
