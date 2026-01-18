<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MobileNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <!-- Logo & Title -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary mb-2">MobileNest</h2>
                            <p class="text-muted">Login ke Akun Anda</p>
                        </div>

                        <!-- Display Messages -->
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" action="proses-login.php" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label fw-bold">Username atau Email</label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Masukkan username atau email" 
                                       required>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Masukkan password" 
                                       required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3 fw-bold">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Masuk
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">Belum punya akun? 
                                <a href="register.php" class="text-decoration-none fw-bold">Daftar di sini</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>