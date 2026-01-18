<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth-check.php';
require_user_login();

$page_title = "Pengiriman";

$user_id = $_SESSION['user_id'] ?? $_SESSION['user'] ?? 0;

if ($user_id === 0) {
    header('Location: ../login.php');
    exit();
}

// ✅ FIX: Get cart items with JOIN to produk for images
$cart_items = [];
$total_price = 0;

try {
    $sql = "SELECT 
                c.id_keranjang,
                c.id_produk,
                c.jumlah,
                p.nama_produk,
                p.harga,
                p.gambar_produk,
                (c.jumlah * p.harga) as subtotal
            FROM keranjang c
            JOIN produk p ON c.id_produk = p.id_produk
            WHERE c.id_user = ?
            ORDER BY c.added_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($cart_items as $item) {
        $total_price += intval($item['subtotal']);
    }
} catch (Exception $e) {
    error_log("Cart query error: " . $e->getMessage());
}

// Get latest pengiriman for user
$pengiriman = null;
$query = "SELECT * FROM pengiriman WHERE id_user = ? ORDER BY tanggal_pengiriman DESC LIMIT 1";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pengiriman = $result->fetch_assoc();
    $stmt->close();
}

include '../includes/header.php';
?>

<style>
    :root {
        --primary-color: #667eea;
        --secondary-color: #764ba2;
        --text-primary: #2c3e50;
        --text-secondary: #7f8c8d;
        --border-color: #ecf0f1;
        --success-color: #10b981;
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
        padding: 40px 0;
        margin-bottom: 40px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
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
        height: 3px;
        background: #e0e0e0;
        z-index: 0;
    }

    .step {
        text-align: center;
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .step-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #e0e0e0;
        color: #999;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 12px;
    }

    .step.completed .step-circle {
        background: var(--success-color);
        color: white;
    }

    .step.active .step-circle {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .step-label {
        font-size: 14px;
        color: #999;
        font-weight: 500;
    }

    .step.completed .step-label,
    .step.active .step-label {
        color: var(--text-primary);
        font-weight: 600;
    }

    /* Cards */
    .section-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }

    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        font-size: 22px;
        color: var(--primary-color);
    }

    /* Product List */
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

    /* Form */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        color: var(--text-primary);
        font-family: inherit;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    /* Summary */
    .summary-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 14px;
    }

    .summary-row.total {
        border-top: 2px solid var(--border-color);
        border-bottom: none;
        padding-top: 14px;
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-color);
    }

    .summary-label {
        color: var(--text-secondary);
    }

    .summary-value {
        color: var(--text-primary);
        font-weight: 600;
    }

    /* Buttons */
    .btn-action {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        margin-top: 20px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-secondary {
        background: var(--border-color);
        color: var(--text-primary);
    }

    .btn-secondary:hover {
        background: #d0d0d0;
        color: var(--text-primary);
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .product-item {
            flex-direction: column;
        }

        .product-item-image {
            width: 100%;
            height: 150px;
        }
    }
</style>

<!-- Progress Bar -->
<div class="progress-bar-section">
    <div class="container-checkout">
        <div class="checkout-steps">
            <div class="step completed">
                <div class="step-circle">✓</div>
                <div class="step-label">Keranjang</div>
            </div>
            <div class="step active">
                <div class="step-circle">2</div>
                <div class="step-label">Pengiriman</div>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-label">Pembayaran</div>
            </div>
            <div class="step">
                <div class="step-circle">4</div>
                <div class="step-label">Selesai</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container-checkout py-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-box"></i>
                    Produk Pesanan
                </div>
                <?php foreach ($cart_items as $item): 
                    $gambar = !empty($item['gambar_produk']) 
                        ? '../assets/images/produk/' . htmlspecialchars($item['gambar_produk'])
                        : '../assets/images/placeholder.png';
                ?>
                <div class="product-item">
                    <img src="<?php echo $gambar; ?>" alt="<?php echo htmlspecialchars($item['nama_produk']); ?>" class="product-item-image" onerror="this.src='../assets/images/placeholder.png';">
                    <div class="product-item-info">
                        <div class="product-item-name"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                        <div class="product-item-price">Rp <?php echo number_format(intval($item['harga']), 0, ',', '.'); ?></div>
                        <div class="product-item-qty">Jumlah: <?php echo intval($item['jumlah']); ?> pcs</div>
                    </div>
                    <div class="product-item-subtotal">Rp <?php echo number_format(intval($item['subtotal']), 0, ',', '.'); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Shipping Address -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-geo-alt"></i>
                    Alamat Pengiriman
                </div>
                <form action="proses-pengiriman.php" method="POST" id="shippingForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nama Penerima *</label>
                            <input type="text" class="form-control" name="nama_penerima" required value="<?php echo htmlspecialchars($pengiriman['nama_penerima'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Telepon *</label>
                            <input type="tel" class="form-control" name="no_telepon" required value="<?php echo htmlspecialchars($pengiriman['no_telepon'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap *</label>
                        <textarea class="form-control" name="alamat_lengkap" rows="3" required><?php echo htmlspecialchars($pengiriman['alamat_lengkap'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Provinsi *</label>
                            <input type="text" class="form-control" name="provinsi" required value="<?php echo htmlspecialchars($pengiriman['provinsi'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kota *</label>
                            <input type="text" class="form-control" name="kota" required value="<?php echo htmlspecialchars($pengiriman['kota'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kecamatan *</label>
                            <input type="text" class="form-control" name="kecamatan" required value="<?php echo htmlspecialchars($pengiriman['kecamatan'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kode Pos *</label>
                            <input type="text" class="form-control" name="kode_pos" required value="<?php echo htmlspecialchars($pengiriman['kode_pos'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Metode Pengiriman *</label>
                        <select class="form-control" name="metode_pengiriman" required>
                            <option value="">-- Pilih Metode --</option>
                            <option value="reguler" <?php echo (isset($pengiriman['metode_pengiriman']) && $pengiriman['metode_pengiriman'] === 'reguler') ? 'selected' : ''; ?>>Reguler (3-5 hari)</option>
                            <option value="express" <?php echo (isset($pengiriman['metode_pengiriman']) && $pengiriman['metode_pengiriman'] === 'express') ? 'selected' : ''; ?>>Express (1-2 hari)</option>
                            <option value="same_day" <?php echo (isset($pengiriman['metode_pengiriman']) && $pengiriman['metode_pengiriman'] === 'same_day') ? 'selected' : ''; ?>>Same Day (Hari Sama)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ongkos Kirim (Rp) *</label>
                        <input type="number" class="form-control" name="ongkir" id="ongkir" min="0" required value="<?php echo htmlspecialchars($pengiriman['ongkir'] ?? '0'); ?>" onchange="updateTotal()">
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar - Summary -->
        <div class="col-lg-4">
            <div class="summary-card">
                <div class="section-title" style="margin-bottom: 20px;">
                    <i class="bi bi-calculator"></i>
                    Ringkasan Pesanan
                </div>

                <div class="summary-row">
                    <span class="summary-label">Subtotal Produk</span>
                    <span class="summary-value" id="subtotal">Rp <?php echo number_format($total_price, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Ongkos Kirim</span>
                    <span class="summary-value" id="ongkir-display">Rp <?php echo number_format(intval($pengiriman['ongkir'] ?? 0), 0, ',', '.'); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Total</span>
                    <span id="total-display">Rp <?php echo number_format($total_price + intval($pengiriman['ongkir'] ?? 0), 0, ',', '.'); ?></span>
                </div>

                <button form="shippingForm" type="submit" class="btn-action btn-primary">
                    <i class="bi bi-arrow-right"></i> Lanjut ke Pembayaran
                </button>
                <button type="button" class="btn-action btn-secondary" onclick="history.back();">
                    <i class="bi bi-arrow-left"></i> Kembali
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const subtotalValue = <?php echo $total_price; ?>;

function updateTotal() {
    const ongkir = parseInt(document.getElementById('ongkir').value) || 0;
    const total = subtotalValue + ongkir;
    
    document.getElementById('ongkir-display').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(ongkir);
    document.getElementById('total-display').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
}
</script>

<?php include '../includes/footer.php'; ?>
