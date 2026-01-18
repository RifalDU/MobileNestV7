<?php
session_start();
require_once '../config.php';
require_once '../includes/brand-logos.php';
require_once '../includes/upload-handler.php';

// Validasi ID Produk
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list-produk.php');
    exit;
}

$id_produk = mysqli_real_escape_string($conn, $_GET['id']);
$sql = "SELECT * FROM produk WHERE id_produk = '$id_produk' AND status_produk = 'Tersedia'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Produk tidak ditemukan!');window.location='list-produk.php';</script>";
    exit;
}

$product = mysqli_fetch_assoc($result);
$page_title = $product['nama_produk'];
$brand_logo = get_brand_logo_data($product['merek']);

// Build image URL dengan UploadHandler
$image_url = '';
if (!empty($product['gambar'])) {
    // Check if it's a filename (local upload) or URL
    if (strpos($product['gambar'], 'http') === false && strpos($product['gambar'], '/') === false) {
        // It's a filename - use UploadHandler to build URL
        $image_url = UploadHandler::getFileUrl($product['gambar'], 'produk');
    } else {
        // It's already a URL
        $image_url = $product['gambar'];
    }
}

include '../includes/header.php';
?>

<style>
    .product-image-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 400px;
    }

    .product-details-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }

    .product-title {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 20px;
    }

    .brand-section {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .brand-logo {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .brand-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .brand-info {
        flex: 1;
    }

    .brand-label {
        font-size: 12px;
        color: #7f8c8d;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .brand-name {
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
    }

    .price-section {
        margin-bottom: 25px;
    }

    .product-price {
        font-size: 36px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 15px;
    }

    .stock-badge {
        display: inline-block;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 8px 15px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
    }

    .action-section {
        background: linear-gradient(135deg, #f8f9fa, #f0f2f5);
        border-radius: 15px;
        padding: 25px;
        margin: 30px 0;
    }

    .quantity-section {
        margin-bottom: 20px;
    }

    .quantity-label {
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
        display: block;
    }

    .quantity-control {
        display: flex;
        align-items: center;
        gap: 10px;
        width: fit-content;
    }

    .quantity-control button {
        width: 40px;
        height: 40px;
        border: 2px solid #ddd;
        background: white;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.2s ease;
    }

    .quantity-control button:hover {
        background: #f0f0f0;
        border-color: #667eea;
        color: #667eea;
    }

    .quantity-control input {
        width: 60px;
        height: 40px;
        text-align: center;
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 8px;
        font-weight: 600;
        font-size: 16px;
    }

    .quantity-control input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-add-to-cart {
        width: 100%;
        padding: 16px 25px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-add-to-cart:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .btn-add-to-cart:active:not(:disabled) {
        transform: translateY(0);
    }

    .btn-add-to-cart:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .description-section {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 2px solid #f0f0f0;
    }

    .description-title {
        font-size: 20px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 20px;
    }

    .description-content {
        color: #555;
        line-height: 1.8;
        font-size: 15px;
    }

    .breadcrumb-custom {
        margin-bottom: 30px;
    }

    .breadcrumb-custom a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .breadcrumb-custom a:hover {
        text-decoration: underline;
    }
</style>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-custom mb-4" aria-label="breadcrumb">
        <a href="../index.php"><i class="bi bi-house"></i> Home</a>
        <span class="mx-2" style="color: #ddd;">/</span>
        <a href="list-produk.php">Produk</a>
        <span class="mx-2" style="color: #ddd;">/</span>
        <span style="color: #7f8c8d;"><?php echo htmlspecialchars($product['nama_produk']); ?></span>
    </nav>

    <div class="row g-4">
        <!-- Product Image -->
        <div class="col-lg-5">
            <div class="product-image-container">
                <?php
                    if (!empty($image_url)) {
                        echo '<img src="' . htmlspecialchars($image_url) . '" class="img-fluid" alt="' . htmlspecialchars($product['nama_produk']) . '" style="max-width: 100%; max-height: 450px; object-fit: contain;">';
                    } else {
                        echo '<div class="text-center" style="width: 100%;">
                                <i class="bi bi-phone" style="font-size: 5rem; color: #ccc;"></i>
                              </div>';
                    }
                ?>
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="col-lg-7">
            <div class="product-details-card">
                <!-- Product Title -->
                <h1 class="product-title"><?php echo htmlspecialchars($product['nama_produk']); ?></h1>
                
                <!-- Brand Section -->
                <div class="brand-section">
                    <div class="brand-logo">
                        <?php if ($brand_logo): ?>
                            <img src="<?php echo htmlspecialchars($brand_logo['image_url']); ?>" alt="<?php echo htmlspecialchars($brand_logo['alt']); ?>">
                        <?php else: ?>
                            <i class="bi bi-phone" style="font-size: 24px; color: #667eea;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="brand-info">
                        <div class="brand-label">Merek</div>
                        <div class="brand-name"><?php echo htmlspecialchars($product['merek']); ?></div>
                    </div>
                </div>

                <!-- Price Section -->
                <div class="price-section">
                    <div class="product-price">Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></div>
                    <span class="stock-badge">
                        <i class="bi bi-check-circle"></i> Stok: <?php echo $product['stok']; ?> unit
                    </span>
                </div>

                <!-- Action Section -->
                <div class="action-section">
                    <!-- Quantity Control -->
                    <div class="quantity-section">
                        <label class="quantity-label">Jumlah Produk</label>
                        <div class="quantity-control">
                            <button type="button" onclick="decreaseQuantity()" title="Kurangi">−</button>
                            <input type="number" id="quantity" min="1" max="<?php echo $product['stok']; ?>" value="1" readonly>
                            <button type="button" onclick="increaseQuantity()" title="Tambah">+</button>
                        </div>
                    </div>

                    <!-- Add to Cart Button -->
                    <button class="btn-add-to-cart" id="btn-add-cart" type="button" onclick="handleAddToCart()">
                        <i class="bi bi-cart-plus" style="font-size: 18px;"></i> Tambah Keranjang
                    </button>
                </div>

                <!-- Description -->
                <div class="description-section">
                    <h4 class="description-title">Deskripsi Produk</h4>
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($product['deskripsi'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const maxQty = <?php echo $product['stok']; ?>;
const productId = <?php echo $product['id_produk']; ?>;

function increaseQuantity() {
    const qtyInput = document.getElementById('quantity');
    const currentQty = parseInt(qtyInput.value);
    if (currentQty < maxQty) {
        qtyInput.value = currentQty + 1;
    }
}

function decreaseQuantity() {
    const qtyInput = document.getElementById('quantity');
    const currentQty = parseInt(qtyInput.value);
    if (currentQty > 1) {
        qtyInput.value = currentQty - 1;
    }
}

async function handleAddToCart() {
    const qty = parseInt(document.getElementById('quantity').value);
    const btn = document.getElementById('btn-add-cart');
    const originalHTML = btn.innerHTML;
    
    console.log('handleAddToCart called with qty:', qty, 'productId:', productId);
    
    // Disable button saat loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
    
    try {
        console.log('Calling addToCart function...');
        const result = await addToCart(productId, qty);
        
        console.log('Raw result:', result);
        console.log('Result type:', typeof result);
        console.log('Result is object:', result && typeof result === 'object');
        
        // Validasi result
        if (!result) {
            throw new Error('No response from server');
        }
        
        if (!result.success) {
            throw new Error(result.message || 'Unknown error occurred');
        }
        
        // Success!
        console.log('✅ Add to cart success!');
        
        // Show success notification
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        alert.style.zIndex = '9999';
        alert.innerHTML = `
            <i class="bi bi-check-circle"></i> Produk berhasil ditambahkan ke keranjang!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        
        // Reset quantity
        document.getElementById('quantity').value = 1;
        
        // Update cart count
        if (typeof updateCartCount === 'function') {
            updateCartCount();
        }
        
        // Remove alert after 3 seconds
        setTimeout(() => {
            if (alert.parentNode) alert.remove();
        }, 3000);
        
    } catch (error) {
        console.error('❌ Error:', error);
        const errorMsg = error.message || 'Terjadi kesalahan sistem';
        
        // Show error notification
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
        alert.style.zIndex = '9999';
        alert.innerHTML = `
            <i class="bi bi-exclamation-circle"></i> ${errorMsg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            if (alert.parentNode) alert.remove();
        }, 4000);
        
    } finally {
        // Re-enable button
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}
</script>

<?php include '../includes/footer.php'; ?>