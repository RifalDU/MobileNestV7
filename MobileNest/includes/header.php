<?php
// FILE: includes/header.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_user_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// ✅ FIX: Get nama lengkap dari session dengan benar
// User login set: $_SESSION['user_name'] (untuk user) atau $_SESSION['admin_name'] (untuk admin)
// Default: 'User' jika tidak ada
$user_name = $_SESSION['user_name'] ?? ($_SESSION['admin_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'MobileNest'; ?> - E-Commerce Smartphone</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="shortcut icon" href="/MobileNest/assets/images/logo.jpg" type="image/x-icon">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/MobileNest/index.php">
                <img src="/MobileNest/assets/images/logo.jpg" alt="Logo" height="40" class="me-2 rounded">
                <span class="text-primary">MobileNest</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/MobileNest/index.php"><i class="bi bi-house"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/MobileNest/produk/list-produk.php"><i class="bi bi-phone"></i> Produk</a></li>
                    
                    <?php if ($is_user_logged_in): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="/MobileNest/user/profil.php">Profil</a></li>
                                <li><a class="dropdown-item" href="/MobileNest/user/pesanan.php">Pesanan</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/MobileNest/user/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/MobileNest/user/login.php">Masuk</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2 px-3" href="/MobileNest/user/register.php">Daftar</a></li>
                    <?php endif; ?>

                    <li class="nav-item ms-lg-2">
                        <a class="nav-link position-relative btn btn-light border px-3" href="/MobileNest/transaksi/keranjang.php">
                            <i class="bi bi-cart-fill text-primary"></i>
                            <span id="cart-count-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>