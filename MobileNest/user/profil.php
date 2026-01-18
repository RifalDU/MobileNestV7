<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth-check.php';
require_user_login();

// GET USER ID - Support both old and new session variables
$user_id = $_SESSION['user_id'] ?? $_SESSION['user'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$errors = [];
$message = '';

$sql = "SELECT * FROM users WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_profil'])) {
        $nama = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        $telepon = trim($_POST['no_telepon']);
        $alamat = trim($_POST['alamat']);
        $foto_url = trim($_POST['foto_url']);
        
        if (empty($nama)) $errors[] = 'Nama tidak boleh kosong';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
        
        if (empty($errors)) {
            $update = $conn->prepare("UPDATE users SET nama_lengkap=?, email=?, no_telepon=?, alamat=? WHERE id_user=?");
            $update->bind_param('ssssi', $nama, $email, $telepon, $alamat, $user_id);
            if ($update->execute()) {
                $message = 'Profil berhasil diperbarui!';
                $user_data = ['nama_lengkap'=>$nama, 'email'=>$email, 'no_telepon'=>$telepon, 'alamat'=>$alamat];
            }
            $update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Akun Saya - MobileNest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f5f7fa; }
.sidebar-menu {
    background: white;
    border-radius: 15px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.sidebar-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 30px 20px;
    text-align: center;
}
.sidebar-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: white;
    color: #667eea;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    margin-bottom: 10px;
}
.sidebar-menu-item {
    padding: 15px 25px;
    border-left: 4px solid transparent;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #2c3e50;
    text-decoration: none;
    border-bottom: 1px solid #f0f0f0;
}
.sidebar-menu-item:hover {
    background: #f8f9fa;
    color: #667eea;
}
.sidebar-menu-item.active {
    background: #f0f4ff;
    border-left-color: #667eea;
    color: #667eea;
    font-weight: 600;
}
.sidebar-menu-item i {
    font-size: 20px;
    width: 24px;
}
.content-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.content-card h4 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 25px;
}
.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 15px;
}
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}
.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    border-radius: 10px;
    padding: 12px 30px;
    font-weight: 600;
}
.btn-logout {
    background: #e74c3c;
    color: white;
    border: none;
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
}
.photo-upload-box {
    border: 2px dashed #ddd;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    background: #f8f9fa;
}
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container py-5">
<div class="row">

<!-- Sidebar -->
<div class="col-md-3">
<div class="sidebar-menu">
    <div class="sidebar-header">
        <div class="sidebar-avatar"><i class="bi bi-person-circle"></i></div>
        <h5 class="mb-0"><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></h5>
        <small><?php echo htmlspecialchars($user_data['email']); ?></small>
    </div>
    <a href="#profil" class="sidebar-menu-item active" data-tab="profil">
        <i class="bi bi-person"></i> Profil
    </a>
    <a href="#alamat" class="sidebar-menu-item" data-tab="alamat">
        <i class="bi bi-geo-alt"></i> Alamat
    </a>
    <a href="#riwayat" class="sidebar-menu-item" data-tab="riwayat">
        <i class="bi bi-clock-history"></i> Riwayat Pesanan
    </a>
    <a href="#ulasan" class="sidebar-menu-item" data-tab="ulasan">
        <i class="bi bi-star"></i> Ulasan
    </a>
    <a href="#keamanan" class="sidebar-menu-item" data-tab="keamanan">
        <i class="bi bi-shield-lock"></i> Keamanan
    </a>
    <a href="#notifikasi" class="sidebar-menu-item" data-tab="notifikasi">
        <i class="bi bi-bell"></i> Notifikasi
    </a>
    <div style="padding: 15px 25px;">
        <button class="btn-logout"><i class="bi bi-box-arrow-right"></i> Keluar</button>
    </div>
</div>
</div>

<!-- Content -->
<div class="col-md-9">
<?php if($message): ?>
<div class="alert alert-success mb-3"><?php echo $message; ?></div>
<?php endif; ?>

<?php if($errors): ?>
<div class="alert alert-danger mb-3">
<?php foreach($errors as $e) echo "<div>$e</div>"; ?>
</div>
<?php endif; ?>

<div class="content-card tab-content-area" id="profil-tab">
<h4><i class="bi bi-person-circle"></i> Profil Saya</h4>
<form method="POST">
<div class="row">
<div class="col-md-6 mb-3">
    <label class="form-label fw-bold">Nama Lengkap</label>
    <input type="text" class="form-control" name="nama_lengkap" value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required>
</div>
<div class="col-md-6 mb-3">
    <label class="form-label fw-bold">Email</label>
    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
</div>
<div class="col-md-6 mb-3">
    <label class="form-label fw-bold">No. Telepon</label>
    <input type="text" class="form-control" name="no_telepon" value="<?php echo htmlspecialchars($user_data['no_telepon']); ?>">
</div>
<div class="col-md-12 mb-3">
    <label class="form-label fw-bold">Alamat Lengkap</label>
    <textarea class="form-control" name="alamat" rows="3"><?php echo htmlspecialchars($user_data['alamat']); ?></textarea>
</div>
<div class="col-md-12 mb-3">
    <label class="form-label fw-bold">Foto Profil</label>
    <div class="photo-upload-box">
        <i class="bi bi-cloud-upload" style="font-size: 48px; color: #667eea;"></i>
        <p class="mt-3 mb-2">Klik untuk upload atau</p>
        <input type="text" class="form-control" name="foto_url" placeholder="https://example.com/photo.jpg">
        <small class="text-muted">Masukkan URL foto profil Anda</small>
    </div>
</div>
<div class="col-md-12">
    <button type="submit" name="edit_profil" class="btn btn-primary">
        <i class="bi bi-save"></i> Simpan Perubahan
    </button>
</div>
</div>
</form>
</div>

<div class="content-card tab-content-area" id="alamat-tab" style="display:none;">
<h4><i class="bi bi-geo-alt"></i> Alamat Pengiriman</h4>
<p class="text-muted">Kelola alamat pengiriman Anda</p>
<button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Alamat Baru</button>
</div>

<div class="content-card tab-content-area" id="riwayat-tab" style="display:none;">
<h4><i class="bi bi-clock-history"></i> Riwayat Pesanan</h4>
<p class="text-muted">Lihat semua pesanan Anda</p>
</div>

<div class="content-card tab-content-area" id="ulasan-tab" style="display:none;">
<h4><i class="bi bi-star"></i> Ulasan Saya</h4>
<p class="text-muted">Kelola ulasan produk yang telah Anda beli</p>
</div>

<div class="content-card tab-content-area" id="keamanan-tab" style="display:none;">
<h4><i class="bi bi-shield-lock"></i> Keamanan Akun</h4>
<p class="text-muted">Ubah password dan pengaturan keamanan</p>
</div>

<div class="content-card tab-content-area" id="notifikasi-tab" style="display:none;">
<h4><i class="bi bi-bell"></i> Pengaturan Notifikasi</h4>
<p class="text-muted">Atur preferensi notifikasi Anda</p>
</div>

</div>
</div>
</div>

<script>
document.querySelectorAll('.sidebar-menu-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.sidebar-menu-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        
        const tab = this.dataset.tab;
        document.querySelectorAll('.tab-content-area').forEach(t => t.style.display = 'none');
        document.getElementById(tab + '-tab').style.display = 'block';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>