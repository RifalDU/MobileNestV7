<?php
require_once 'config.php';
require_once 'includes/brand-logos.php';
require_once 'includes/upload-handler.php';
$page_title = "Beranda";
include 'includes/header.php';

// Helper function untuk build image URL
function getImageUrl($gambar_field) {
    if (empty($gambar_field)) {
        return '';
    }
    
    // Check if it's a full URL already (http/https)
    if (strpos($gambar_field, 'http://') === 0 || strpos($gambar_field, 'https://') === 0) {
        return $gambar_field;
    }
    
    // Check if it's a filename (local upload)
    if (strpos($gambar_field, '/') === false) {
        // It's a filename - use constant URL
        if (defined('UPLOADS_PRODUK_URL')) {
            return UPLOADS_PRODUK_URL . $gambar_field;
        }
        // Fallback if constant not defined
        return 'uploads/produk/' . $gambar_field;
    }
    
    // It's already a partial path, add base URL
    return SITE_URL . '/' . $gambar_field;
}
?>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    color: white;
}
.hero-section h1 {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1.2;
}
.category-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    text-decoration: none;
    display: block;
    height: 100%;
}
.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.category-card h5 {
    color: #2c3e50;
    margin: 10px 0 0 0;
    font-weight: 600;
}
.category-logo {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 10px;
    padding: 10px;
}
.category-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}
.product-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}
.badge-bestseller { background: #f39c12; }
.badge-hot { background: #e74c3c; }
.badge-promo { background: #27ae60; }
.product-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    position: relative;
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}
.product-card img {
    height: 220px;
    object-fit: contain;
    padding: 20px;
    background: #f8f9fa;
}
.product-card .card-body {
    padding: 20px;
}
.product-rating {
    color: #f39c12;
    font-size: 14px;
}
.btn-add-cart {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    border-radius: 10px;
    padding: 10px;
    color: white;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-add-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    color: white;
}
.section-title {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 30px;
}
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="mb-4">Temukan Smartphone<br>Terbaru di Mobile Nest</h1>
                <p class="fs-5 mb-4">Dapatkan penawaran spesial untuk flagship terkini dan aksesori original.</p>
                <div class="d-flex gap-3">
                    <a href="<?php echo SITE_URL; ?>/produk/list-produk.php" class="btn btn-light btn-lg px-4 fw-bold">Beli Sekarang</a>
                    <a href="#promo" class="btn btn-outline-light btn-lg px-4 fw-bold">Lihat Promo</a>
                </div>
            </div>
            <div class="col-lg-5 text-center mt-5 mt-lg-0">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.jpg" alt="MobileNest" class="img-fluid" style="max-height: 350px; filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));">
            </div>
        </div>
    </div>
</section>

<!-- Brand Categories -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Kategori Smartphone</h2>
            <a href="<?php echo SITE_URL; ?>/produk/list-produk.php" class="text-decoration-none fw-bold">Lihat semua →</a>
        </div>
        <div class="row g-3">
            <?php
            $brands = ['Samsung', 'Xiaomi', 'Apple', 'OPPO', 'Vivo', 'Realme'];
            foreach($brands as $brand):
            ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo SITE_URL; ?>/produk/list-produk.php?brand=<?php echo urlencode($brand); ?>" class="category-card">
                    <div class="category-logo">
                        <img src="<?php echo get_brand_logo_url($brand); ?>" alt="<?php echo $brand; ?> Logo" loading="lazy" onerror="this.src='https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/smartphone.svg'">
                    </div>
                    <h5><?php echo $brand; ?></h5>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5" id="promo">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Produk Unggulan</h2>
            <a href="<?php echo SITE_URL; ?>/produk/list-produk.php" class="text-decoration-none fw-bold">Lihat semua →</a>
        </div>
        
        <div class="row g-4">
            <?php
            $sql = "SELECT * FROM produk WHERE status_produk = 'Tersedia' ORDER BY tanggal_ditambahkan DESC LIMIT 8";
            $result = mysqli_query($conn, $sql);
            
            $badges = ['Best Seller', 'Hot', 'Promo'];
            $badge_classes = ['badge-bestseller', 'badge-hot', 'badge-promo'];
            $index = 0;
            
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $img_src = !empty($row['gambar']) ? getImageUrl($row['gambar']) : '';
                    $badge_index = $index % 3;
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card product-card h-100">
                    <?php if($index < 6): ?>
                    <span class="product-badge <?php echo $badge_classes[$badge_index]; ?>"><?php echo $badges[$badge_index]; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($img_src)): ?>
                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['nama_produk']); ?>" loading="lazy">
                    <?php else: ?>
                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 220px;">
                        <i class="bi bi-phone" style="font-size: 3rem; color: #ccc;"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title" style="font-size: 16px; font-weight: 600; min-height: 48px;"><?php echo htmlspecialchars($row['nama_produk']); ?></h5>
                        <div class="product-rating mb-2">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                        </div>
                        <p class="fw-bold text-primary mb-3" style="font-size: 18px;">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></p>
                        <a href="produk/detail-produk.php?id=<?php echo $row['id_produk']; ?>" class="btn btn-add-cart w-100">
                            <i class="bi bi-cart-plus"></i> Lihat Detail
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                    $index++;
                }
            } else {
                echo '<div class="col-12 text-center text-muted py-5">Belum ada produk tersedia.</div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="p-4">
                    <i class="bi bi-truck" style="font-size: 48px; color: #667eea;"></i>
                    <h5 class="mt-3 fw-bold">Gratis Ongkir</h5>
                    <p class="text-muted">Pengiriman cepat dan aman</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <i class="bi bi-shield-check" style="font-size: 48px; color: #667eea;"></i>
                    <h5 class="mt-3 fw-bold">Garansi Resmi</h5>
                    <p class="text-muted">Produk 100% original bergaransi</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <i class="bi bi-headset" style="font-size: 48px; color: #667eea;"></i>
                    <h5 class="mt-3 fw-bold">Dukungan 24/7</h5>
                    <p class="text-muted">Tim siap membantu kapan pun</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>