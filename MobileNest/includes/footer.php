<footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/images/logo.jpg" alt="MobileNest" height="35" class="me-2">
                        <h5 class="mb-0">MobileNest</h5>
                    </div>
                    <p class="text-muted mb-0">E-Commerce Smartphone terpercaya dengan pilihan terbaik dan harga kompetitif.</p>
                </div>
                
                <div class="col-md-4 mb-3 mb-md-0">
                    <h6 class="fw-bold mb-3">Navigasi</h6>
                    <ul class="list-unstyled text-muted">
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/produk/list-produk.php" class="text-decoration-none text-muted">Produk</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/user/login.php" class="text-decoration-none text-muted">Login</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Hubungi Kami</h6>
                    <p class="text-muted mb-1">
                        <i class="bi bi-telephone"></i> +62 821 1234 5678
                    </p>
                    <p class="text-muted mb-1">
                        <i class="bi bi-envelope"></i> support@mobilenest.com
                    </p>
                    <p class="text-muted">
                        <i class="bi bi-geo-alt"></i> Jakarta, Indonesia
                    </p>
                </div>
            </div>
            
            <hr class="bg-secondary">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2025 MobileNest. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-muted me-3"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-muted"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/js/script.js"></script>
    
    <script src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/js/api-handler.js"></script>
    <script src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/js/cart.js"></script>
    
    <script>
        console.log('Footer scripts loaded correctly');
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            } else {
                console.warn('Fungsi updateCartCount belum tersedia/ter-load.');
            }
        });
    </script>
</body>
</html>