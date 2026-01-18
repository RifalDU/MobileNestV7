<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get transaction ID from URL
$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_transaksi === 0) {
    header('Location: keranjang.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch transaction details
$query = "SELECT * FROM transaksi WHERE id_transaksi = ? AND id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $id_transaksi, $user_id);
$stmt->execute();
$resultTransaksi = $stmt->get_result();
$transaksi = $resultTransaksi->fetch_assoc();

if (!$transaksi) {
    header('Location: keranjang.php');
    exit();
}

// ✅ FIX: Fetch transaction items with JOIN to produk for images
$query = "SELECT 
            dt.id_detail,
            dt.id_produk,
            dt.nama_produk,
            dt.harga_satuan,
            dt.jumlah,
            dt.subtotal,
            p.gambar_produk
            FROM detail_transaksi dt
            LEFT JOIN produk p ON dt.id_produk = p.id_produk
            WHERE dt.id_transaksi = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id_transaksi);
$stmt->execute();
$resultItems = $stmt->get_result();
$items = $resultItems->fetch_all(MYSQLI_ASSOC);

// Calculate subtotal from items
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['subtotal'];
}

// Fetch shipping details
$shipping = null;
$ongkir = 0;

$query = "SELECT * FROM pengiriman WHERE id_user = ? ORDER BY tanggal_pengiriman DESC LIMIT 1";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $resultShipping = $stmt->get_result();
    $shipping = $resultShipping->fetch_assoc();
    
    if ($shipping && isset($shipping['ongkir'])) {
        $ongkir = intval($shipping['ongkir']);
    }
}

if (!$shipping) {
    $shipping = [
        'nama_penerima' => 'Data tidak tersedia',
        'no_telepon' => '-',
        'alamat_lengkap' => $transaksi['alamat_pengiriman'] ?? 'Alamat pengiriman',
        'kota' => '-',
        'kecamatan' => '-',
        'provinsi' => '-',
        'kode_pos' => '-',
        'metode_pengiriman' => '-',
        'ongkir' => $ongkir
    ];
}

$page_title = "Pesanan Selesai";
include '../includes/header.php';
?>

<style>
    :root {
        --primary-color: #667eea;
        --secondary-color: #764ba2;
        --success-color: #10b981;
        --text-primary: #2c3e50;
        --text-secondary: #7f8c8d;
        --border-color: #ecf0f1;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .container-checkout {
        max-width: 1000px;
        margin: 0 auto;
    }

    /* Progress Bar */
    .progress-bar-section {
        background: white;
        padding: 30px 0;
        margin-bottom: 40px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .checkout-steps {
        display: flex;
        justify-content: space-between;
        position: relative;
    }

    .checkout-steps::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--success-color);
        z-index: 0;
    }

    .step {
        text-align: center;
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--success-color);
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 18px;
        margin-bottom: 8px;
    }

    .step-label {
        font-size: 13px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .step.active .step-label {
        color: var(--text-primary);
        font-weight: 600;
    }

    /* Success Message */
    .success-container {
        background: white;
        border-radius: 15px;
        padding: 60px 40px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .success-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--success-color), #059669);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        font-size: 40px;
        color: white;
    }

    .success-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 10px;
    }

    .success-subtitle {
        color: var(--text-secondary);
        font-size: 16px;
        margin-bottom: 30px;
    }

    .order-code {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        border-left: 4px solid var(--primary-color);
    }

    .order-code-label {
        font-size: 12px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .order-code-value {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary-color);
        font-family: 'Courier New', monospace;
    }

    /* Content Sections */
    .section-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        font-size: 20px;
        color: var(--primary-color);
    }

    /* Product Item */
    .product-item {
        display: flex;
        gap: 15px;
        align-items: flex-start;
        padding: 15px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        margin-bottom: 12px;
    }

    .product-item-image {
        width: 100px;
        height: 100px;
        border-radius: 8px;
        object-fit: cover;
        background: #f0f0f0;
        flex-shrink: 0;
    }

    .product-item-info {
        flex: 1;
    }

    .product-item-name {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .product-item-price {
        font-size: 14px;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }

    .product-item-qty {
        font-size: 14px;
        color: var(--text-secondary);
    }

    .product-item-subtotal {
        font-weight: 700;
        color: var(--primary-color);
        text-align: right;
        min-width: 100px;
    }

    .item-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 14px;
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-label {
        color: var(--text-secondary);
    }

    .item-value {
        color: var(--text-primary);
        font-weight: 600;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 14px 0;
        font-size: 14px;
    }

    .summary-row.total {
        border-top: 2px solid var(--border-color);
        padding-top: 14px;
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-color);
    }

    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 40px;
    }

    .btn-action {
        flex: 1;
        padding: 15px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-primary-action {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
    }

    .btn-primary-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-secondary-action {
        background: var(--border-color);
        color: var(--text-primary);
    }

    .btn-secondary-action:hover {
        background: #e0e0e0;
        color: var(--text-primary);
        text-decoration: none;
    }

    .status-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 12px;
        font-weight: 600;
        background: #d1fae5;
        color: var(--success-color);
    }

    @media (max-width: 768px) {
        .success-container {
            padding: 40px 20px;
        }

        .button-group {
            flex-direction: column;
        }

        .section-card {
            padding: 20px;
        }

        .success-title {
            font-size: 22px;
        }

        .product-item {
            flex-direction: column;
        }

        .product-item-image {
            width: 100%;
            height: 150px;
        }

        .product-item-subtotal {
            text-align: left;
            min-width: auto;
        }
    }
</style>

<!-- Progress Bar -->
<div class="progress-bar-section">
    <div class="container-checkout">
        <div class="checkout-steps">
            <div class="step">
                <div class="step-circle">✓</div>
                <div class="step-label">Keranjang</div>
            </div>
            <div class="step">
                <div class="step-circle">✓</div>
                <div class="step-label">Pengiriman</div>
            </div>
            <div class="step">
                <div class="step-circle">✓</div>
                <div class="step-label">Pembayaran</div>
            </div>
            <div class="step active">
                <div class="step-circle">✓</div>
                <div class="step-label">Selesai</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container-checkout py-5">
    <!-- Success Message -->
    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1 class="success-title">Pesanan Anda Berhasil!</h1>
        <p class="success-subtitle">Terima kasih telah berbelanja di MobileNest. Pesanan Anda telah dikonfirmasi.</p>
        
        <div class="order-code">
            <div class="order-code-label">📦 Kode Pesanan</div>
            <div class="order-code-value"><?php echo htmlspecialchars($transaksi['kode_transaksi'] ?? 'TRX-' . $id_transaksi); ?></div>
        </div>

        <span class="status-badge">✓ Menunggu Pembayaran</span>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-box"></i>
                    Produk Pesanan Anda
                </div>
                <?php foreach ($items as $item): 
                    $gambar = !empty($item['gambar_produk']) 
                        ? '../assets/images/produk/' . htmlspecialchars($item['gambar_produk'])
                        : '../assets/images/placeholder.png';
                ?>
                <div class="product-item">
                    <img src="<?php echo $gambar; ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>" class="product-item-image" onerror="this.src='../assets/images/placeholder.png';">
                    <div class="product-item-info">
                        <div class="product-item-name"><?php echo htmlspecialchars($item['nama_produk'] ?? 'Produk'); ?></div>
                        <div class="product-item-price">Rp <?php echo number_format(intval($item['harga_satuan']), 0, ',', '.'); ?></div>
                        <div class="product-item-qty">Jumlah: <?php echo intval($item['jumlah']); ?> pcs</div>
                    </div>
                    <div class="product-item-subtotal">Rp <?php echo number_format(intval($item['subtotal']), 0, ',', '.'); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Shipping Details -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-truck"></i>
                    Detail Pengiriman
                </div>
                <div class="item-row">
                    <span class="item-label">Nama Penerima</span>
                    <span class="item-value"><?php echo htmlspecialchars($shipping['nama_penerima']); ?></span>
                </div>
                <div class="item-row">
                    <span class="item-label">No. Telepon</span>
                    <span class="item-value"><?php echo htmlspecialchars($shipping['no_telepon']); ?></span>
                </div>
                <div class="item-row">
                    <span class="item-label">Alamat Lengkap</span>
                    <span class="item-value" style="text-align: right; max-width: 50%;"><?php echo htmlspecialchars($shipping['alamat_lengkap']); ?></span>
                </div>
                <div class="item-row">
                    <span class="item-label">Kota / Provinsi</span>
                    <span class="item-value"><?php echo htmlspecialchars($shipping['kota'] . ', ' . $shipping['provinsi']); ?></span>
                </div>
                <div class="item-row">
                    <span class="item-label">Metode Pengiriman</span>
                    <span class="item-value"><?php echo ucfirst(str_replace('_', ' ', $shipping['metode_pengiriman'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="col-lg-4">
            <div class="section-card" style="position: sticky; top: 100px;">
                <div class="section-title">
                    <i class="bi bi-receipt"></i>
                    Ringkasan Pembayaran
                </div>
                <div class="summary-row">
                    <span class="item-label">Subtotal Produk</span>
                    <span class="item-value">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="item-label">Ongkos Kirim</span>
                    <span class="item-value">Rp <?php echo number_format(intval($shipping['ongkir'] ?? 0), 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Pembayaran</span>
                    <span>Rp <?php echo number_format(intval($transaksi['total_harga']), 0, ',', '.'); ?></span>
                </div>
                
                <div class="button-group">
                    <a href="keranjang.php" class="btn-action btn-primary-action">Belanja Lagi</a>
                    <a href="../index.php" class="btn-action btn-secondary-action">Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
