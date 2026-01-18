<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// ✅ FIX: Get transaction ID from URL parameter OR session fallback
$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ✅ FIX: If not in GET parameter, try session
if ($id_transaksi === 0 && isset($_SESSION['current_transaksi_id'])) {
    $id_transaksi = $_SESSION['current_transaksi_id'];
}

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

// ✅ FIX: Fetch transaction items WITH JOIN to produk for images
$query_items = "SELECT 
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
$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param('i', $id_transaksi);
$stmt_items->execute();
$resultItems = $stmt_items->get_result();
$cart_items = $resultItems->fetch_all(MYSQLI_ASSOC);

// Fetch shipping details
$query_shipping = "SELECT * FROM pengiriman WHERE id_user = ? ORDER BY tanggal_pengiriman DESC LIMIT 1";
$stmt_shipping = $conn->prepare($query_shipping);
if ($stmt_shipping) {
    $stmt_shipping->bind_param('i', $user_id);
    $stmt_shipping->execute();
    $resultShipping = $stmt_shipping->get_result();
    $pengiriman = $resultShipping->fetch_assoc();
}

if (!$pengiriman) {
    header('Location: keranjang.php');
    exit();
}

// ✅ NEW: Store pengiriman ID in session untuk payment-handler
if ($pengiriman) {
    $_SESSION['id_pengiriman'] = $pengiriman['id_pengiriman'];
}

// Calculate subtotal from items
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += intval($item['subtotal']);
}

$ongkir = intval($pengiriman['ongkir'] ?? 0);
$total = intval($transaksi['total_harga']);
$id_pengiriman = $pengiriman['id_pengiriman'];

$page_title = "Pembayaran";
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
        transition: all 0.3s ease;
    }

    .section-card:hover {
        box-shadow: 0 4px 25px rgba(0,0,0,0.08);
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

    /* Product Item */
    .product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        font-size: 14px;
        border-bottom: 1px solid var(--border-color);
    }

    .product-item:last-child {
        border-bottom: none;
    }

    .product-name {
        flex: 1;
        color: var(--text-secondary);
    }

    .product-qty {
        color: var(--text-secondary);
        font-size: 13px;
        margin: 0 10px;
    }

    .product-price {
        color: var(--text-primary);
        font-weight: 600;
        text-align: right;
        min-width: 100px;
    }

    /* Payment Method Cards */
    .payment-method {
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-method:hover {
        border-color: var(--primary-color);
        background-color: #f8faff;
    }

    .payment-method.active {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
    }

    .payment-method input[type="radio"] {
        margin-right: 12px;
        cursor: pointer;
    }

    .payment-method label {
        cursor: pointer;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .payment-method-label {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 15px;
    }

    .payment-method-desc {
        font-size: 13px;
        color: var(--text-secondary);
        margin-top: 6px;
        margin-left: 32px;
    }

    /* Form Controls */
    .form-label-custom {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 10px;
        font-size: 15px;
        display: block;
    }

    .form-control-custom {
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 12px 15px;
        font-size: 15px;
        color: var(--text-primary);
        transition: all 0.3s ease;
        width: 100%;
        font-family: inherit;
    }

    .form-control-custom:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .form-helper-text {
        font-size: 13px;
        color: var(--text-secondary);
        margin-top: 6px;
        display: block;
    }

    .form-group {
        margin-bottom: 20px;
    }

    /* File Preview */
    #file-preview {
        margin-top: 10px;
    }

    .file-preview-item {
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        color: #166534;
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .file-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    /* Summary Card */
    .summary-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }

    .summary-card .section-title {
        margin-bottom: 20px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        font-size: 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .summary-row:last-child {
        border-bottom: none;
    }

    .summary-row.total {
        border-top: 2px solid var(--border-color);
        border-bottom: none;
        padding-top: 12px;
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
    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-action {
        padding: 14px 24px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        font-family: inherit;
    }

    .btn-primary {
        flex: 1;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .btn-secondary {
        background: var(--border-color);
        color: var(--text-primary);
        width: auto;
    }

    .btn-secondary:hover {
        background: #d0d0d0;
        color: var(--text-primary);
        text-decoration: none;
    }

    /* Messages */
    .message {
        padding: 14px 16px;
        border-radius: 10px;
        margin-top: 15px;
        font-size: 14px;
        display: none;
        animation: slideIn 0.3s ease;
    }

    .message.show {
        display: block;
    }

    .message.error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .message.success {
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .container-checkout {
            padding: 0 15px;
        }

        .section-card {
            padding: 20px;
        }

        .summary-card {
            position: static;
            margin-top: 20px;
        }

        .button-group {
            flex-direction: column;
        }

        .btn-action {
            width: 100% !important;
        }

        .progress-bar-section {
            padding: 25px 0;
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
            <div class="step completed">
                <div class="step-circle">✓</div>
                <div class="step-label">Pengiriman</div>
            </div>
            <div class="step active">
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
        <!-- Form Section -->
        <div class="col-lg-8">
            <!-- Payment Method -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-credit-card"></i>
                    Pilih Metode Pembayaran
                </div>

                <form id="paymentMethodForm" enctype="multipart/form-data">
                    <div class="payment-method active" onclick="selectPayment(this, 'bank_transfer')">
                        <label>
                            <input type="radio" name="metode_pembayaran" value="bank_transfer" checked>
                            <span class="payment-method-label">🏦 Transfer Bank</span>
                        </label>
                        <div class="payment-method-desc">Transfer via BCA, Mandiri, BNI, atau bank lainnya</div>
                    </div>

                    <div class="payment-method" onclick="selectPayment(this, 'ewallet')">
                        <label>
                            <input type="radio" name="metode_pembayaran" value="ewallet">
                            <span class="payment-method-label">📱 E-Wallet</span>
                        </label>
                        <div class="payment-method-desc">OVO, GoPay, Dana, LinkAja, atau e-wallet lainnya</div>
                    </div>

                    <div class="payment-method" onclick="selectPayment(this, 'credit_card')">
                        <label>
                            <input type="radio" name="metode_pembayaran" value="credit_card">
                            <span class="payment-method-label">💳 Kartu Kredit</span>
                        </label>
                        <div class="payment-method-desc">Visa, Mastercard, atau kartu kredit lainnya</div>
                    </div>

                    <div class="payment-method" onclick="selectPayment(this, 'cod')">
                        <label>
                            <input type="radio" name="metode_pembayaran" value="cod">
                            <span class="payment-method-label">🚚 Bayar di Tempat (COD)</span>
                        </label>
                        <div class="payment-method-desc">Bayar saat barang sampai ke tangan Anda</div>
                    </div>
                </form>
            </div>

            <!-- Upload Bukti -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-file-image"></i>
                    Upload Bukti Pembayaran
                </div>

                <form id="paymentForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="bukti_pembayaran" class="form-label-custom">📎 Bukti Pembayaran *</label>
                        <input type="file" class="form-control-custom" id="bukti_pembayaran" name="bukti_pembayaran" accept=".jpg,.jpeg,.png" required>
                        <span class="form-helper-text">JPG atau PNG, maksimal 5MB</span>
                        <div id="file-preview"></div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="id_transaksi" value="<?php echo $id_transaksi; ?>">
                    <input type="hidden" name="id_user" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="id_pengiriman" value="<?php echo $id_pengiriman; ?>">
                    <input type="hidden" name="metode_pembayaran" id="hidden_metode" value="bank_transfer">

                    <div id="message" class="message"></div>

                    <div class="button-group">
                        <button type="button" class="btn-action btn-secondary" onclick="history.back()">← Kembali</button>
                        <button type="submit" class="btn-action btn-primary">✓ Konfirmasi Pesanan →</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar - Summary -->
        <div class="col-lg-4">
            <div class="summary-card">
                <div class="section-title">
                    <i class="bi bi-receipt"></i>
                    Ringkasan Belanja
                </div>
                
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 15px; padding-right: 5px;">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="product-item">
                            <span class="product-name"><?php echo htmlspecialchars($item['nama_produk']); ?></span>
                            <span class="product-qty">x<?php echo intval($item['jumlah']); ?></span>
                            <span class="product-price">Rp <?php echo number_format(intval($item['subtotal']), 0, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Subtotal Produk</span>
                    <span class="summary-value">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Ongkos Kirim</span>
                    <span class="summary-value">Rp <?php echo number_format($ongkir, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Pembayaran</span>
                    <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const fileInput = document.getElementById('bukti_pembayaran');
    const filePreview = document.getElementById('file-preview');
    const form = document.getElementById('paymentForm');
    const messageDiv = document.getElementById('message');
    const hiddenMetode = document.getElementById('hidden_metode');

    function selectPayment(element, method) {
        // Remove active class from all payment methods
        document.querySelectorAll('.payment-method').forEach(card => {
            card.classList.remove('active');
        });
        
        // Add active class to clicked card
        element.classList.add('active');
        
        // Update radio button and hidden field
        document.querySelector(`input[value="${method}"]`).checked = true;
        hiddenMetode.value = method;
    }

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            const size = (file.size / 1024 / 1024).toFixed(2);
            
            if (file.size > 5 * 1024 * 1024) {
                filePreview.innerHTML = '<div class="file-preview-item file-error">❌ File terlalu besar (max 5MB)</div>';
                fileInput.value = '';
                return;
            }

            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                filePreview.innerHTML = '<div class="file-preview-item file-error">❌ Format hanya JPG atau PNG</div>';
                fileInput.value = '';
                return;
            }

            filePreview.innerHTML = `<div class="file-preview-item">✅ ${file.name} (${size}MB)</div>`;
        } else {
            filePreview.innerHTML = '';
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!fileInput.files.length) {
            messageDiv.innerHTML = '❌ Harap upload bukti pembayaran';
            messageDiv.className = 'message show error';
            return;
        }

        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Processing...';

        try {
            const response = await fetch('../api/payment-handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                messageDiv.innerHTML = '✅ Pesanan berhasil dikonfirmasi! Redirecting...';
                messageDiv.className = 'message show success';
                setTimeout(() => {
                    window.location.href = '../user/pesanan.php';
                }, 2000);
            } else {
                messageDiv.innerHTML = '❌ ' + (result.message || 'Terjadi kesalahan');
                messageDiv.className = 'message show error';
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        } catch (error) {
            messageDiv.innerHTML = '❌ Error: ' + error.message;
            messageDiv.className = 'message show error';
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
