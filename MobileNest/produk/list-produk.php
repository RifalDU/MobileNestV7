<?php
session_start();
require_once '../config.php';
require_once '../includes/brand-logos.php';
require_once '../includes/upload-handler.php';

$page_title = "Daftar Produk";
include '../includes/header.php';
?>

<style>
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .card.transition {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .card.transition:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
    }

    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="container">
        <!-- Header -->
        <h1 class="mb-2">Daftar Produk</h1>
        <p class="text-muted mb-4">Temukan smartphone pilihan terbaik di MobileNest</p>
        
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title fw-bold mb-3">🔍 Filter Produk</h6>
                        
                        <!-- Cari Produk -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Cari Produk</label>
                            <input type="text" class="form-control form-control-sm" 
                                   placeholder="Ketik nama produk..." id="search_produk">
                        </div>
                        
                        <!-- Filter Merek dengan Logo -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small mb-3">📱 Merek</label>
                            <div id="brand_filter_container">
                                <?php
                                $available_brands = get_all_brands();
                                foreach ($available_brands as $brand):
                                    $logo_data = get_brand_logo_data($brand);
                                    if ($logo_data):
                                ?>
                                <div class="form-check d-flex align-items-center mb-2 p-2" style="cursor: pointer;">
                                    <div style="width: 28px; height: 28px; margin-right: 8px; flex-shrink: 0;">
                                        <img src="<?php echo htmlspecialchars($logo_data['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($brand); ?> Logo" 
                                             style="width: 100%; height: 100%; object-fit: contain;" />
                                    </div>
                                    <div class="form-check" style="flex-grow: 1;">
                                        <input class="form-check-input brand-checkbox" type="checkbox" 
                                               value="<?php echo htmlspecialchars($brand); ?>" 
                                               id="merek_<?php echo strtolower(str_replace(' ', '_', $brand)); ?>" />
                                        <label class="form-check-label small" 
                                               for="merek_<?php echo strtolower(str_replace(' ', '_', $brand)); ?>">
                                            <?php echo htmlspecialchars($brand); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        
                        <!-- Filter Harga -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small mb-3">💰 Harga</label>
                            <div class="form-check">
                                <input class="form-check-input price-checkbox" type="checkbox" 
                                       value="1000000:3000000" id="harga_1" />
                                <label class="form-check-label small" for="harga_1">Rp 1 - 3 Juta</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input price-checkbox" type="checkbox" 
                                       value="3000000:7000000" id="harga_2" />
                                <label class="form-check-label small" for="harga_2">Rp 3 - 7 Juta</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input price-checkbox" type="checkbox" 
                                       value="7000000:15000000" id="harga_3" />
                                <label class="form-check-label small" for="harga_3">Rp 7 - 15 Juta</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input price-checkbox" type="checkbox" 
                                       value="15000000:999999999" id="harga_4" />
                                <label class="form-check-label small" for="harga_4">Rp 15+ Juta</label>
                            </div>
                        </div>
                        
                        <!-- Tombol Filter -->
                        <button class="btn btn-primary btn-sm w-100 mb-2" onclick="applyFilter()">
                            <i class="bi bi-funnel"></i> Terapkan Filter
                        </button>
                        <button class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilter()">
                            <i class="bi bi-arrow-clockwise"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9 col-md-8">
                <!-- Products Count & Sort -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <p class="text-muted mb-0 small">Menampilkan <strong id="product_count">0</strong> produk</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <select class="form-select form-select-sm w-auto" style="display: inline-block;" 
                                id="sort_option" onchange="applyFilter()">
                            <option value="terbaru">Terbaru</option>
                            <option value="harga_rendah">Harga Terendah</option>
                            <option value="harga_tinggi">Harga Tertinggi</option>
                            <option value="populer">Paling Populer</option>
                        </select>
                    </div>
                </div>
                
                <!-- Products Grid Container -->
                <div id="products_container" class="product-grid">
                    <?php
                    // HYBRID APPROACH: Render initial products from PHP
                    // Then filter.js can update via AJAX if user applies filters
                    
                    $sql = "SELECT * FROM produk WHERE status_produk = 'Tersedia' ORDER BY id_produk DESC";
                    $result = mysqli_query($conn, $sql);
                    
                    if (mysqli_num_rows($result) > 0) {
                        while ($produk = mysqli_fetch_assoc($result)) {
                            $brand_logo = get_brand_logo_data($produk['merek']);
                            // Build image URL dengan UploadHandler
                            $image_url = '';
                            if (!empty($produk['gambar'])) {
                                // Check if it's a filename (local upload) or URL
                                if (strpos($produk['gambar'], 'http') === false && strpos($produk['gambar'], '/') === false) {
                                    // It's a filename - use UploadHandler to build URL
                                    $image_url = UploadHandler::getFileUrl($produk['gambar'], 'produk');
                                } else {
                                    // It's already a URL
                                    $image_url = $produk['gambar'];
                                }
                            }
                    ?>
                    <div class="product-card" data-product-id="<?php echo $produk['id_produk']; ?>">
                        <div class="card border-0 shadow-sm h-100 transition">
                            <!-- Product Image -->
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px; position: relative; overflow: hidden;">
                                <?php if (!empty($image_url)): ?>
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                         alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>" 
                                         style="width: 100%; height: 100%; object-fit: cover;" />
                                <?php else: ?>
                                    <i class="bi bi-phone" style="font-size: 3rem; color: #ccc;"></i>
                                <?php endif; ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">-15%</span>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="card-body">
                                <h6 class="card-title mb-2"><?php echo htmlspecialchars($produk['nama_produk']); ?></h6>
                                
                                <!-- Brand dengan Logo -->
                                <div class="d-flex align-items-center mb-2">
                                    <?php if ($brand_logo): ?>
                                        <img src="<?php echo htmlspecialchars($brand_logo['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($produk['merek']); ?> Logo" 
                                             style="width: 25px; height: 25px; object-fit: contain; margin-right: 8px;">
                                    <?php endif; ?>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($produk['merek']); ?></p>
                                </div>
                                
                                <!-- Rating -->
                                <div class="mb-2">
                                    <span class="text-warning">
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-fill"></i>
                                        <i class="bi bi-star-half"></i>
                                    </span>
                                    <span class="text-muted small">(152)</span>
                                </div>
                                
                                <!-- Price -->
                                <h5 class="text-primary mb-3">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></h5>
                                
                                <!-- Button (Only Lihat Detail) -->
                                <div class="d-grid gap-2">
                                    <a href="detail-produk.php?id=<?php echo $produk['id_produk']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-search"></i> Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p class="text-center text-muted">Tidak ada produk tersedia</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Set initial product count
    var productCount = document.querySelectorAll('.product-card').length;
    document.getElementById('product_count').textContent = productCount;
    
    console.log('list-produk.php loaded');
    console.log('Initial products loaded:', productCount);
    console.log('products_container element:', document.getElementById('products_container'));
</script>

<!-- Load filter.js FIRST (for AJAX filtering) -->
<script src="../assets/js/filter.js"></script>

<?php include '../includes/footer.php'; ?>