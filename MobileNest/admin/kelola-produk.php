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

// handle actions: add, edit, delete
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD produk baru
    if ($action === 'add') {
        $nama_produk = trim($_POST['nama_produk'] ?? '');
        $merek = trim($_POST['merek'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $spesifikasi = trim($_POST['spesifikasi'] ?? '');
        $harga = !empty($_POST['harga']) ? (float)$_POST['harga'] : 0;
        $stok = !empty($_POST['stok']) ? (int)$_POST['stok'] : 0;
        $kategori = trim($_POST['kategori'] ?? '');
        $status_produk = trim($_POST['status_produk'] ?? 'Tersedia');
        $gambar = '';  // Default: gambar dari upload

        if (empty($nama_produk) || $harga <= 0) {
            $message = 'Nama produk dan harga tidak boleh kosong.';
            $msg_type = 'danger';
        } else {
            // Handle file upload
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                // Generate temporary product ID untuk naming
                $temp_id = time();
                $upload_result = UploadHandler::uploadProductImage($_FILES['gambar'], $temp_id);
                
                if ($upload_result['success']) {
                    $gambar = $upload_result['filename'];
                } else {
                    $message = 'Error upload gambar: ' . $upload_result['message'];
                    $msg_type = 'danger';
                }
            } elseif (!empty($_POST['gambar_url'])) {
                // Fallback ke URL jika user input URL
                $gambar = trim($_POST['gambar_url']);
            }

            // Hanya insert jika tidak ada error upload
            if (empty($message)) {
                $query = "INSERT INTO produk (nama_produk, merek, deskripsi, spesifikasi, harga, stok, gambar, kategori, status_produk, tanggal_ditambahkan) 
                          VALUES ('" . mysqli_real_escape_string($conn, $nama_produk) . "', '" . mysqli_real_escape_string($conn, $merek) . "', '" . mysqli_real_escape_string($conn, $deskripsi) . "', '" . mysqli_real_escape_string($conn, $spesifikasi) . "', $harga, $stok, '" . mysqli_real_escape_string($conn, $gambar) . "', '" . mysqli_real_escape_string($conn, $kategori) . "', '" . mysqli_real_escape_string($conn, $status_produk) . "', NOW())";
                
                if (mysqli_query($conn, $query)) {
                    $message = 'Produk berhasil ditambahkan.';
                    $msg_type = 'success';
                } else {
                    $message = 'Error: ' . mysqli_error($conn);
                    $msg_type = 'danger';
                }
            }
        }
    }

    // EDIT produk
    if ($action === 'edit') {
        $id_produk = (int)$_POST['id_produk'];
        $nama_produk = trim($_POST['nama_produk'] ?? '');
        $merek = trim($_POST['merek'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $spesifikasi = trim($_POST['spesifikasi'] ?? '');
        $harga = !empty($_POST['harga']) ? (float)$_POST['harga'] : 0;
        $stok = !empty($_POST['stok']) ? (int)$_POST['stok'] : 0;
        $kategori = trim($_POST['kategori'] ?? '');
        $status_produk = trim($_POST['status_produk'] ?? 'Tersedia');
        $gambar = trim($_POST['gambar_current'] ?? ''); // Current gambar

        if (empty($nama_produk) || $harga <= 0 || $id_produk <= 0) {
            $message = 'Data produk tidak valid.';
            $msg_type = 'danger';
        } else {
            // Handle file upload (jika ada file baru)
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $upload_result = UploadHandler::uploadProductImage($_FILES['gambar'], $id_produk);
                
                if ($upload_result['success']) {
                    $gambar = $upload_result['filename'];
                } else {
                    $message = 'Error upload gambar: ' . $upload_result['message'];
                    $msg_type = 'danger';
                }
            } elseif (!empty($_POST['gambar_url'])) {
                // Jika input URL baru
                $gambar = trim($_POST['gambar_url']);
            }
            // Jika tidak ada file baru dan tidak ada URL baru, gunakan gambar yang ada

            // Hanya update jika tidak ada error
            if (empty($message)) {
                $query = "UPDATE produk SET nama_produk='" . mysqli_real_escape_string($conn, $nama_produk) . "', merek='" . mysqli_real_escape_string($conn, $merek) . "', deskripsi='" . mysqli_real_escape_string($conn, $deskripsi) . "', spesifikasi='" . mysqli_real_escape_string($conn, $spesifikasi) . "', harga=$harga, stok=$stok, gambar='" . mysqli_real_escape_string($conn, $gambar) . "', kategori='" . mysqli_real_escape_string($conn, $kategori) . "', status_produk='" . mysqli_real_escape_string($conn, $status_produk) . "' WHERE id_produk=$id_produk";
                
                if (mysqli_query($conn, $query)) {
                    $message = 'Produk berhasil diperbarui.';
                    $msg_type = 'success';
                } else {
                    $message = 'Error: ' . mysqli_error($conn);
                    $msg_type = 'danger';
                }
            }
        }
    }

    // DELETE produk
    if ($action === 'delete') {
        $id_produk = (int)$_POST['id_produk'];
        if (mysqli_query($conn, "DELETE FROM produk WHERE id_produk=$id_produk")) {
            $message = 'Produk berhasil dihapus.';
            $msg_type = 'success';
        } else {
            $message = 'Error: ' . mysqli_error($conn);
            $msg_type = 'danger';
        }
    }
}

// fetch produk untuk list
$limit = 100;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = "1=1";

if (!empty($_GET['kategori'])) {
    $kategori = mysqli_real_escape_string($conn, $_GET['kategori']);
    $where .= " AND kategori = '$kategori'";
}

if (!empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND status_produk = '$status'";
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (nama_produk LIKE '%$search%' OR merek LIKE '%$search%')";
}

$sql = "SELECT id_produk, nama_produk, merek, harga, stok, gambar, kategori, status_produk, tanggal_ditambahkan FROM produk WHERE $where ORDER BY tanggal_ditambahkan DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

// fetch unique categories
$categories = [];
$cat_query = mysqli_query($conn, "SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
if ($cat_query) {
    while ($row = mysqli_fetch_assoc($cat_query)) {
        $categories[] = $row['kategori'];
    }
}

$statuses = ['Tersedia', 'Tidak Tersedia'];

// fetch produk untuk edit
$edit_produk = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = mysqli_query($conn, "SELECT id_produk, nama_produk, merek, deskripsi, spesifikasi, harga, stok, gambar, kategori, status_produk FROM produk WHERE id_produk=$edit_id");
    if ($edit_result) {
        $edit_produk = mysqli_fetch_assoc($edit_result);
    }
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Produk - MobileNest Admin</title>
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
        .table td { border-color: #e0e0e0; }
        .btn-info { background: #667eea; border-color: #667eea; color: white; }
        .btn-info:hover { background: #764ba2; border-color: #764ba2; color: white; }
        .image-preview { max-width: 300px; max-height: 300px; border-radius: 8px; margin-top: 10px; border: 2px solid #e0e0e0; }
        .upload-area { border: 2px dashed #667eea; border-radius: 8px; padding: 20px; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { background: #f0f7ff; border-color: #764ba2; }
        .upload-area.drag-over { background: #e8e9ff; border-color: #764ba2; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam"></i> MobileNest Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="kelola-produk.php"><i class="bi bi-phone"></i> Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="verifikasi-pembayaran.php"><i class="bi bi-credit-card"></i> Verifikasi</a></li>
        <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php"><i class="bi bi-receipt"></i> Transaksi</a></li>
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
        <i class="bi bi-phone"></i> Kelola Produk
      </h2>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($msg_type) ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> Tambah Produk</button>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form class="row g-3" method="get" action="kelola-produk.php">
        <div class="col-md-3">
          <label class="form-label fw-bold">Cari</label>
          <input type="search" name="search" class="form-control" placeholder="Nama/Merek..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Kategori</label>
          <select name="kategori" class="form-select">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($_GET['kategori']) && $_GET['kategori'] === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold">Status</label>
          <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>" <?= (isset($_GET['status']) && $_GET['status'] === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary fw-bold flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
          <a href="kelola-produk.php" class="btn btn-outline-secondary fw-bold"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Gambar</th>
          <th>Nama Produk</th>
          <th>Merek</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result || mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="9" class="text-center py-4 text-muted"><i class="bi bi-inbox"></i> Tidak ada produk.</td></tr>
        <?php else: ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><strong>#<?= (int)$row['id_produk'] ?></strong></td>
              <td>
                <?php if (!empty($row['gambar'])): ?>
                  <?php 
                    $image_url = $row['gambar'];
                    // Jika nama file produk (bukan URL), gunakan path uploads
                    if (strpos($image_url, 'http') === false && strpos($image_url, '/') === false) {
                      $image_url = UploadHandler::getFileUrl($row['gambar'], 'produk');
                    }
                  ?>
                  <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>" style="max-width:60px;max-height:60px;object-fit:cover;border-radius:5px;">
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['nama_produk']) ?></td>
              <td><?= htmlspecialchars($row['merek']) ?></td>
              <td><?= htmlspecialchars($row['kategori']) ?></td>
              <td><strong>Rp <?= number_format((float)$row['harga'],0,',','.') ?></strong></td>
              <td><?= (int)$row['stok'] ?></td>
              <td>
                <span class="badge bg-<?= $row['status_produk'] === 'Tersedia' ? 'success' : 'secondary' ?>">
                  <?= htmlspecialchars($row['status_produk']) ?>
                </span>
              </td>
              <td class="text-nowrap">
                <a href="?edit=<?= (int)$row['id_produk'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                <form method="post" class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_produk" value="<?= (int)$row['id_produk'] ?>">
                  <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted text-center mt-3">Menampilkan maksimal <?= $limit ?> produk per halaman.</p>
</main>

<!-- Modal Tambah Produk -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Produk Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <ul class="nav nav-tabs mb-3" id="addTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="add-upload-tab" data-bs-toggle="tab" data-bs-target="#add-upload" type="button"><i class="bi bi-cloud-arrow-up"></i> Upload Gambar</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="add-url-tab" data-bs-toggle="tab" data-bs-target="#add-url" type="button"><i class="bi bi-link"></i> URL Gambar</button>
            </li>
          </ul>

          <!-- Tab: Upload Gambar -->
          <div class="tab-content">
            <div class="tab-pane fade show active" id="add-upload" role="tabpanel">
              <div class="upload-area" id="uploadArea">
                <i class="bi bi-cloud-arrow-up" style="font-size:48px;color:#667eea;"></i>
                <p class="mt-3 mb-0"><strong>Drag gambar ke sini atau klik untuk upload</strong></p>
                <small class="text-muted">Format: JPG, PNG, WebP (Max 5MB)</small>
                <input type="file" name="gambar" id="fileInput" accept="image/*" style="display:none;">
              </div>
              <div id="imagePreview"></div>
            </div>
            <!-- Tab: URL Gambar -->
            <div class="tab-pane fade" id="add-url" role="tabpanel">
              <div class="mb-3">
                <label class="form-label fw-bold">URL Gambar</label>
                <input type="url" name="gambar_url" class="form-control" placeholder="https://...">
              </div>
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <label class="form-label fw-bold">Nama Produk *</label>
            <input type="text" name="nama_produk" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Merek</label>
            <input type="text" name="merek" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Spesifikasi</label>
            <textarea name="spesifikasi" class="form-control" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Harga *</label>
              <input type="number" name="harga" class="form-control" step="0.01" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Stok</label>
              <input type="number" name="stok" class="form-control" value="0">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Kategori</label>
              <input type="text" name="kategori" class="form-control" placeholder="Flagship">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Status</label>
              <select name="status_produk" class="form-select">
                <option value="Tersedia">Tersedia</option>
                <option value="Tidak Tersedia">Tidak Tersedia</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Produk -->
<?php if ($edit_produk): ?>
<div class="modal fade show" id="editModal" tabindex="-1" style="display: block;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id_produk" value="<?= (int)$edit_produk['id_produk'] ?>">
        <input type="hidden" name="gambar_current" value="<?= htmlspecialchars($edit_produk['gambar']) ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Produk</h5>
          <a href="kelola-produk.php" class="btn-close"></a>
        </div>
        <div class="modal-body">
          <ul class="nav nav-tabs mb-3" id="editTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="edit-upload-tab" data-bs-toggle="tab" data-bs-target="#edit-upload" type="button"><i class="bi bi-cloud-arrow-up"></i> Ganti Gambar</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="edit-url-tab" data-bs-toggle="tab" data-bs-target="#edit-url" type="button"><i class="bi bi-link"></i> URL Gambar</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="edit-current-tab" data-bs-toggle="tab" data-bs-target="#edit-current" type="button"><i class="bi bi-image"></i> Gambar Saat Ini</button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Tab: Upload Gambar Baru -->
            <div class="tab-pane fade show active" id="edit-upload" role="tabpanel">
              <div class="upload-area edit-upload-area" id="editUploadArea">
                <i class="bi bi-cloud-arrow-up" style="font-size:48px;color:#667eea;"></i>
                <p class="mt-3 mb-0"><strong>Drag gambar baru atau klik untuk upload</strong></p>
                <small class="text-muted">Kosongkan jika ingin tetap gunakan gambar lama</small>
                <input type="file" name="gambar" id="editFileInput" accept="image/*" style="display:none;">
              </div>
              <div id="editImagePreview"></div>
            </div>
            <!-- Tab: URL Gambar Baru -->
            <div class="tab-pane fade" id="edit-url" role="tabpanel">
              <div class="mb-3">
                <label class="form-label fw-bold">URL Gambar Baru</label>
                <input type="url" name="gambar_url" class="form-control" placeholder="https://...">
                <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
              </div>
            </div>
            <!-- Tab: Gambar Saat Ini -->
            <div class="tab-pane fade" id="edit-current" role="tabpanel">
              <p class="text-muted">Gambar produk saat ini:</p>
              <?php if (!empty($edit_produk['gambar'])): ?>
                <?php 
                  $image_url = $edit_produk['gambar'];
                  if (strpos($image_url, 'http') === false && strpos($image_url, '/') === false) {
                    $image_url = UploadHandler::getFileUrl($edit_produk['gambar'], 'produk');
                  }
                ?>
                <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($edit_produk['nama_produk']) ?>" class="image-preview">
              <?php else: ?>
                <p class="text-muted">Tidak ada gambar</p>
              <?php endif; ?>
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <label class="form-label fw-bold">Nama Produk *</label>
            <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($edit_produk['nama_produk']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Merek</label>
            <input type="text" name="merek" class="form-control" value="<?= htmlspecialchars($edit_produk['merek']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($edit_produk['deskripsi']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Spesifikasi</label>
            <textarea name="spesifikasi" class="form-control" rows="2"><?= htmlspecialchars($edit_produk['spesifikasi']) ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Harga *</label>
              <input type="number" name="harga" class="form-control" step="0.01" value="<?= (float)$edit_produk['harga'] ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Stok</label>
              <input type="number" name="stok" class="form-control" value="<?= (int)$edit_produk['stok'] ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Kategori</label>
              <input type="text" name="kategori" class="form-control" value="<?= htmlspecialchars($edit_produk['kategori']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Status</label>
              <select name="status_produk" class="form-select">
                <option value="Tersedia" <?= $edit_produk['status_produk'] === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                <option value="Tidak Tersedia" <?= $edit_produk['status_produk'] === 'Tidak Tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a href="kelola-produk.php" class="btn btn-secondary">Batal</a>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Image preview untuk tambah produk
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const imagePreview = document.getElementById('imagePreview');

if (uploadArea && fileInput) {
  uploadArea.addEventListener('click', () => fileInput.click());
  
  uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
  });
  
  uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('drag-over');
  });
  
  uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    fileInput.files = e.dataTransfer.files;
    handleFileSelect(fileInput, imagePreview);
  });
  
  fileInput.addEventListener('change', () => {
    handleFileSelect(fileInput, imagePreview);
  });
}

// Image preview untuk edit produk
const editUploadArea = document.getElementById('editUploadArea');
const editFileInput = document.getElementById('editFileInput');
const editImagePreview = document.getElementById('editImagePreview');

if (editUploadArea && editFileInput) {
  editUploadArea.addEventListener('click', () => editFileInput.click());
  
  editUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    editUploadArea.classList.add('drag-over');
  });
  
  editUploadArea.addEventListener('dragleave', () => {
    editUploadArea.classList.remove('drag-over');
  });
  
  editUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    editUploadArea.classList.remove('drag-over');
    editFileInput.files = e.dataTransfer.files;
    handleFileSelect(editFileInput, editImagePreview);
  });
  
  editFileInput.addEventListener('change', () => {
    handleFileSelect(editFileInput, editImagePreview);
  });
}

function handleFileSelect(input, previewElement) {
  const file = input.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewElement.innerHTML = '<img src="' + e.target.result + '" class="image-preview" alt="Preview">';
    };
    reader.readAsDataURL(file);
  }
}
</script>
</body>
</html>