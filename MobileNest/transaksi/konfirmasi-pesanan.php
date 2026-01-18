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

// ✅ FIX: STORE id_transaksi IN SESSION SO WE CAN ACCESS IT IN pembayaran.php
$_SESSION['current_transaksi_id'] = $id_transaksi;

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

// Fetch transaction items
$query = "SELECT * FROM detail_transaksi WHERE id_transaksi = ?";
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

$query = "SELECT * FROM pengiriman WHERE id_user = ? ORDER BY tanggal_pengiriman DESC LIMIT 1";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $resultShipping = $stmt->get_result();
    $shipping = $resultShipping->fetch_assoc();
}

// Get ongkir from shipping if available
$ongkir = 0;
if ($shipping && isset($shipping['ongkir'])) {
    $ongkir = intval($shipping['ongkir']);
}

// Parse alamat_pengiriman from transaksi if pengiriman not found
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

$page_title = "Konfirmasi Pesanan";
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

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 25px;
    }

    .info-item {
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
    }

    .info-label {
        font-size: 12px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .info-value {
        font-size: 16px;
        color: var(--text-primary);
        font-weight: 600;
    }

    /* Table */
    .table-custom {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .table-custom thead {
        background: #f8f9fa;
        border-bottom: 2px solid var(--border-color);
    }

    .table-custom th {
        padding: 14px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-custom td {
        padding: 16px 14px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 14px;
    }

    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .font-bold {
        font-weight: 700;
    }

    .text-primary {
        color: var(--primary-color);
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

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 15px;
    }

    .summary-row:last-child {
        border-bottom: none;
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
    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-action {
        flex: 1;
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
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        width: 100%;
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
        width: auto;
    }

    .btn-secondary:hover {
        background: #d0d0d0;
        color: var(--text-primary);
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .container-checkout {
            padding: 0 15px;
        }

        .section-card {
            padding: 20px;
        }

        .info-grid {
            grid-template-columns: 1fr;
            gap: 15px;
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

        .table-custom th,
        .table-custom td {
            padding: 10px;
            font-size: 12px;
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
        <div class="col-lg-8">
            <!-- Order Header -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-receipt"></i>
                    Detail Pesanan Anda
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Kode Pesanan</div>
                        <div class="info-value"><?php echo htmlspecialchars($transaksi['kode_transaksi'] ?? 'TRX-' . $id_transaksi); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Pesanan</div>
                        <div class="info-value"><?php echo date('d M Y - H:i', strtotime($transaksi['tanggal_transaksi'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status Pembayaran</div>
                        <div class="info-value">
                            <span style="background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">⏳ Menunggu Pembayaran</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-box"></i>
                    Daftar Produk
                </div>
                <div style="overflow-x: auto;">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Harga Satuan</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nama_produk'] ?? 'Produk'); ?></td>
                                    <td class="text-center"><?php echo intval($item['jumlah']); ?> pcs</td>
                                    <td class="text-right">Rp <?php echo number_format(intval($item['harga_satuan']), 0, ',', '.'); ?></td>
                                    <td class="text-right font-bold text-primary">Rp <?php echo number_format(intval($item['subtotal']), 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Shipping Section -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-geo-alt"></i>
                    Alamat Pengiriman
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama Penerima</div>
                        <div class="info-value"><?php echo htmlspecialchars($shipping['nama_penerima']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">No. Telepon</div>
                        <div class="info-value"><?php echo htmlspecialchars($shipping['no_telepon']); ?></div>
                    </div>
                </div>
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                    <div class="info-label" style="margin-bottom: 8px;">Alamat Lengkap</div>
                    <div class="info-value"><?php echo htmlspecialchars($shipping['alamat_lengkap']); ?></div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Kota</div>
                        <div class="info-value"><?php echo htmlspecialchars($shipping['kota']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kecamatan</div>
                        <div class="info-value"><?php echo htmlspecialchars($shipping['kecamatan']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Provinsi</div>
                        <div class="info-value"><?php echo htmlspecialchars($shipping['provinsi']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kode Pos</div>
                        <div class="info-value"><?php echo htmlspecialchars($shipping['kode_pos']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Metode Pengiriman</div>
                        <div class="info-value"><?php echo ucfirst(str_replace('_', ' ', $shipping['metode_pengiriman'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar - Summary -->
        <div class="col-lg-4">
            <div class="summary-card">
                <div class="section-title" style="margin-bottom: 20px;">
                    <i class="bi bi-calculator"></i>
                    Ringkasan Pembayaran
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Subtotal Produk</span>
                    <span class="summary-value">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Ongkos Kirim</span>
                    <span class="summary-value">Rp <?php echo number_format(intval($shipping['ongkir'] ?? 0), 0, ',', '.'); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Total Pembayaran</span>
                    <span>Rp <?php echo number_format(intval($transaksi['total_harga']), 0, ',', '.'); ?></span>
                </div>

                <div class="button-group">
                    <button class="btn-action btn-secondary" onclick="history.back()">← Kembali</button>
                    <!-- ✅ FIX: Pass id_transaksi in URL AND use session as fallback -->
                    <a href="pembayaran.php?id=<?php echo $id_transaksi; ?>" class="btn-action btn-primary">Lanjut Pembayaran →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>