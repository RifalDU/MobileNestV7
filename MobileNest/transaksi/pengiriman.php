<?php
session_start();

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../config.php';

$user_id = $_SESSION['user_id'];

$page_title = "Pengiriman & Checkout";
include '../includes/header.php';

try {
    // Get user data
    $query = "SELECT nama_lengkap, no_telepon, email FROM users WHERE id_user = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        $user = [
            'nama_lengkap' => '',
            'no_telepon' => '',
            'email' => ''
        ];
    }

    // Get cart items & calculate subtotal
    $query_cart = "SELECT k.*, p.nama_produk, p.harga FROM keranjang k 
                  JOIN produk p ON k.id_produk = p.id_produk 
                  WHERE k.id_user = ?";
    $stmt_cart = $conn->prepare($query_cart);
    if (!$stmt_cart) {
        throw new Exception('Prepare cart failed: ' . $conn->error);
    }
    $stmt_cart->bind_param('i', $user_id);
    $stmt_cart->execute();
    $cart_result = $stmt_cart->get_result();
    $cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);

    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['harga'] * $item['jumlah'];
    }
    
} catch (Exception $e) {
    error_log('Error in pengiriman.php: ' . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>

<style>
.checkout-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    position: relative;
}
.checkout-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
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
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #999;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
}
.step.active .step-circle {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}
.step.completed .step-circle {
    background: #10b981;
    color: white;
}
.step.completed .step-circle::after {
    content: '✓';
    font-weight: bold;
}
.step.active .step-label {
    color: #667eea;
    font-weight: 600;
}
.step-label {
    font-size: 14px;
    color: #999;
}

.form-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.form-card h5 {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 18px;
}
.form-group-wrapper {
    margin-bottom: 20px;
}
.form-group-wrapper label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
    font-size: 14px;
}
.form-group-wrapper input,
.form-group-wrapper textarea,
.form-group-wrapper select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}
.form-group-wrapper input:focus,
.form-group-wrapper textarea:focus,
.form-group-wrapper select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.shipping-method {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.3s;
}
.shipping-method:hover {
    border-color: #667eea;
    background: #f8f9ff;
}
.shipping-method.active {
    border-color: #667eea;
    background: #f8f9ff;
}
.shipping-method input[type="radio"] {
    margin-right: 10px;
}
.shipping-method-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}
.shipping-method-label strong {
    color: #2c3e50;
}
.shipping-method-time {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 5px;
    margin-left: 24px;
}
.shipping-cost {
    color: #667eea;
    font-weight: 700;
    font-size: 15px;
}

.summary-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    position: sticky;
    top: 100px;
}
.summary-card h5 {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}
.summary-row.total {
    border-bottom: none;
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #f0f0f0;
}
.summary-value {
    font-weight: 600;
    color: #2c3e50;
}

.product-item {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}
.product-item:last-child {
    border-bottom: none;
}
.product-name {
    color: #2c3e50;
    flex: 1;
}
.product-price {
    color: #667eea;
    font-weight: 600;
    text-align: right;
    margin-left: 10px;
}

.btn-checkout {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}
.btn-back {
    width: 100%;
    padding: 15px;
    background: #f0f0f0;
    border: none;
    border-radius: 10px;
    color: #2c3e50;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    transition: all 0.3s;
}
.btn-back:hover {
    background: #e0e0e0;
}

.message {
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
    display: none;
    font-weight: 600;
}
.message.show {
    display: block;
}
.message.error {
    background: #fee2e2;
    color: #c53030;
    border: 1px solid #fc8181;
}
.message.success {
    background: #c6f6d5;
    color: #22543d;
    border: 1px solid #9ae6b4;
}
</style>

<div class="container py-5">
    <!-- Progress Steps -->
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

    <div class="row">
        <div class="col-lg-8">
            <form id="shippingForm">
                <!-- Data Penerima -->
                <div class="form-card">
                    <h5>👤 Data Penerima</h5>
                    <div class="form-row">
                        <div class="form-group-wrapper">
                            <label>Nama Penerima *</label>
                            <input type="text" id="nama_penerima" name="nama_penerima" value="<?php echo htmlspecialchars($user['nama_lengkap'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group-wrapper">
                            <label>Nomor Telepon *</label>
                            <input type="tel" id="no_telepon" name="no_telepon" value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group-wrapper">
                        <label>Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Alamat Pengiriman -->
                <div class="form-card">
                    <h5>📍 Alamat Pengiriman</h5>
                    <div class="form-group-wrapper">
                        <label>Alamat Lengkap *</label>
                        <textarea id="alamat_lengkap" name="alamat_lengkap" rows="3" required placeholder="Jalan, nomor rumah, kelurahan, RT/RW"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group-wrapper">
                            <label>Provinsi *</label>
                            <input type="text" id="provinsi" name="provinsi" placeholder="cth: Jawa Barat" required>
                        </div>
                        <div class="form-group-wrapper">
                            <label>Kota/Kabupaten *</label>
                            <input type="text" id="kota" name="kota" placeholder="cth: Bandung" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group-wrapper">
                            <label>Kecamatan *</label>
                            <input type="text" id="kecamatan" name="kecamatan" placeholder="cth: Cibeunying Kidul" required>
                        </div>
                        <div class="form-group-wrapper">
                            <label>Kode Pos *</label>
                            <input type="text" id="kode_pos" name="kode_pos" placeholder="cth: 40141" required>
                        </div>
                    </div>
                    <div class="form-group-wrapper">
                        <label>Catatan Pengiriman (Opsional)</label>
                        <textarea id="catatan" name="catatan" rows="2" placeholder="cth: Titip ke tetangga jika tidak ada"></textarea>
                    </div>
                </div>

                <!-- Metode Pengiriman -->
                <div class="form-card">
                    <h5>🚚 Metode Pengiriman</h5>
                    
                    <div class="shipping-method active" onclick="selectShipping(this, 'regular', 20000)">
                        <div class="shipping-method-label">
                            <div>
                                <input type="radio" name="metode_pengiriman" id="regular" value="regular" checked>
                                <label for="regular" style="cursor: pointer; margin: 0; display: inline;">
                                    <strong>🚚 Regular (5-7 Hari)</strong>
                                </label>
                                <div class="shipping-method-time">Pengiriman standar ke seluruh Indonesia</div>
                            </div>
                            <span class="shipping-cost">Rp 20.000</span>
                        </div>
                    </div>

                    <div class="shipping-method" onclick="selectShipping(this, 'express', 50000)">
                        <div class="shipping-method-label">
                            <div>
                                <input type="radio" name="metode_pengiriman" id="express" value="express">
                                <label for="express" style="cursor: pointer; margin: 0; display: inline;">
                                    <strong>⚡ Express (2-3 Hari)</strong>
                                </label>
                                <div class="shipping-method-time">Pengiriman lebih cepat ke kota-kota besar</div>
                            </div>
                            <span class="shipping-cost">Rp 50.000</span>
                        </div>
                    </div>

                    <div class="shipping-method" onclick="selectShipping(this, 'same_day', 100000)">
                        <div class="shipping-method-label">
                            <div>
                                <input type="radio" name="metode_pengiriman" id="same_day" value="same_day">
                                <label for="same_day" style="cursor: pointer; margin: 0; display: inline;">
                                    <strong>🏃 Same Day (Hari Sama)</strong>
                                </label>
                                <div class="shipping-method-time">Pengiriman di hari yang sama (Jakarta & sekitarnya)</div>
                            </div>
                            <span class="shipping-cost">Rp 100.000</span>
                        </div>
                    </div>

                    <div id="message" class="message"></div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-back" onclick="history.back()">← Kembali</button>
                    <button type="submit" class="btn-checkout" style="flex: 1; margin-top: 0;">Lanjut ke Pembayaran →</button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="summary-card">
                <h5>📋 Ringkasan Belanja</h5>
                
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 15px;">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="product-item">
                            <span class="product-name"><?php echo htmlspecialchars($item['nama_produk']); ?> x<?php echo $item['jumlah']; ?></span>
                            <span class="product-price">Rp <?php echo number_format($item['harga'] * $item['jumlah']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="summary-value" id="subtotal-display">Rp <?php echo number_format($subtotal); ?></span>
                </div>
                <div class="summary-row">
                    <span>Diskon</span>
                    <span class="summary-value" style="color: #10b981;">-Rp 0</span>
                </div>
                <div class="summary-row">
                    <span>Ongkir</span>
                    <span class="summary-value" id="ongkir-display">Rp 20.000</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span id="total-display" style="color: #667eea;">Rp <?php echo number_format($subtotal + 20000); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const subtotal = <?php echo $subtotal; ?>;
const form = document.getElementById('shippingForm');
const messageDiv = document.getElementById('message');
const ongkirDisplay = document.getElementById('ongkir-display');
const totalDisplay = document.getElementById('total-display');

function selectShipping(element, method, cost) {
    // Remove active class from all
    document.querySelectorAll('.shipping-method').forEach(card => {
        card.classList.remove('active');
    });
    
    // Add active class to clicked
    element.classList.add('active');
    document.getElementById(method).checked = true;
    
    // Update totals
    const total = subtotal + cost;
    ongkirDisplay.textContent = 'Rp ' + cost.toLocaleString('id-ID');
    totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(form);

    try {
        const response = await fetch('../api/checkout-handler.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        console.log('Checkout API Response:', result);

        if (result.success) {
            // Redirect to confirmation page with transaction ID
            window.location.href = 'konfirmasi-pesanan.php?id=' + result.id_transaksi;
        } else {
            messageDiv.textContent = result.message || 'Terjadi kesalahan';
            messageDiv.className = 'message show error';
            console.error('Checkout error:', result.message);
        }
    } catch (error) {
        messageDiv.textContent = 'Error: ' + error.message;
        messageDiv.className = 'message show error';
        console.error('Fetch error:', error);
    }
});
</script>

<?php include '../includes/footer.php'; ?>