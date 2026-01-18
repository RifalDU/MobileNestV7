<?php
require_once '../config.php';

$page_title = "Daftar";
$css_path = "../assets/css/style.css";
$js_path = "../assets/js/script.js";
$logo_path = "../assets/images/logo.jpg";
$home_url = "../index.php";
$produk_url = "../produk/list-produk.php";
$login_url = "login.php";
$register_url = "register.php";
$keranjang_url = "../transaksi/keranjang.php";

include '../includes/header.php';
?>

    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                <div class="card shadow border-0 rounded-lg">
                    <div class="card-body p-4 p-sm-5">
                        <!-- Logo & Title -->
                        <div class="text-center mb-4">
                            <img src="<?php echo $logo_path; ?>" alt="MobileNest Logo" height="50" class="mb-3">
                            <h3 class="fw-bold text-primary">MobileNest</h3>
                            <p class="text-muted">Buat akun baru Anda</p>
                        </div>

                        <!-- Form -->
                        <form action="proses-register.php" method="POST">
                            <div class="mb-3">
                                <label for="nama" class="form-label fw-bold">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama_lengkap" placeholder="Masukkan nama lengkap" required>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label fw-bold">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Buat username Anda" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan email Anda" required>
                            </div>

                            <div class="mb-3">
                                <label for="telepon" class="form-label fw-bold">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="telepon" name="no_telepon" placeholder="Masukkan nomor telepon">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Buat password" required>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label fw-bold">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Konfirmasi password" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="agree" required>
                                <label class="form-check-label" for="agree">
                                    Saya setuju dengan <a href="#">Syarat & Ketentuan</a>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="bi bi-person-plus"></i> Daftar
                            </button>
                        </form>

                        <!-- Divider -->
                        <div class="my-4 text-center">
                            <small class="text-muted">atau</small>
                        </div>

                        <!-- Login Link -->
                        <div class="text-center">
                            <p class="mb-0">Sudah punya akun? 
                                <a href="login.php" class="text-decoration-none fw-bold">Login di sini</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
