<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth-check.php';
require_user_login();

$page_title = "Riwayat Pesanan";

// Get user_id from session (might be 'user_id' or 'user')
$user_id = $_SESSION['user_id'] ?? $_SESSION['user'] ?? 0;

if ($user_id === 0) {
    header('Location: ../login.php');
    exit();
}

$orders = [];

// ✅ FIX: Query untuk ambil pesanan user dari transaksi table - SESUAI DATABASE_SCHEMA-1.md
try {
    // Kolom yang benar dari DATABASE_SCHEMA-1.md:
    // - status_pesanan (bukan status_transaksi)
    // - no_resi (bukan resi_pengiriman)
    // - tanggal_selesai TIDAK ADA, hapus
    $sql = "SELECT t.id_transaksi, 
                   t.kode_transaksi as order_code,
                   t.tanggal_transaksi, 
                   t.total_harga,
                   t.status_pesanan,
                   t.metode_pembayaran,
                   t.no_resi,
                   COUNT(dt.id_detail) as total_items
            FROM transaksi t
            LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
            WHERE t.id_user = ?
            GROUP BY t.id_transaksi
            ORDER BY t.tanggal_transaksi DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    error_log("Pesanan found: " . count($orders) . " orders");
} catch (Exception $e) {
    error_log("Pesanan query error: " . $e->getMessage());
    $orders = [];
}

include '../includes/header.php';
?>

<style>
body { background: #f5f7fa; }
.orders-container {
    max-width: 1000px;
    margin: 0 auto;
}
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.empty-state-icon {
    font-size: 120px;
    color: #e0e0e0;
    margin-bottom: 20px;
}
.empty-state h3 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 10px;
}
.empty-state p {
    color: #7f8c8d;
    font-size: 16px;
    margin-bottom: 30px;
}
.order-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 5px solid #667eea;
}
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}
.order-id {
    font-weight: 700;
    color: #2c3e50;
    font-size: 16px;
}
.order-date {
    color: #7f8c8d;
    font-size: 14px;
}
.status-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.status-Pending,
.status-menunggu_verifikasi,
.status-Menunggu Verifikasi {
    background: #fff3cd;
    color: #856404;
}
.status-Dikemas,
.status-dikemas {
    background: #cfe2ff;
    color: #084298;
}
.status-Dikirim,
.status-dalam_pengiriman,
.status-Dalam Pengiriman {
    background: #cff4fc;
    color: #055160;
}
.status-Selesai,
.status-selesai {
    background: #d1e7dd;
    color: #0f5132;
}
.status-Dibatalkan,
.status-dibatalkan {
    background: #f8d7da;
    color: #842029;
}
.status-Verified,
.status-verified {
    background: #d4edff;
    color: #0c63e4;
}
.order-details {
    color: #2c3e50;
}
.order-total {
    font-size: 20px;
    font-weight: 700;
    color: #667eea;
    margin-top: 15px;
}
.order-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.btn-detail {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}
.btn-detail:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.3);
}
.btn-outline {
    padding: 10px 20px;
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}
.btn-outline:hover {
    background: #667eea;
    color: white;
}
.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}
.filter-tab {
    padding: 10px 20px;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    color: #7f8c8d;
    transition: all 0.3s;
}
.filter-tab:hover,
.filter-tab.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: transparent;
}
</style>

<div class="container py-5">
    <div class="orders-container">
        <h1 class="mb-4" style="font-weight: 700; color: #2c3e50;">
            <i class="bi bi-clock-history"></i> Riwayat Pesanan
        </h1>
        
        <?php if (count($orders) > 0): ?>
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" data-status="all">Semua</div>
                <div class="filter-tab" data-status="Menunggu Verifikasi">Menunggu Verifikasi</div>
                <div class="filter-tab" data-status="Verified">Verified</div>
                <div class="filter-tab" data-status="Dalam Pengiriman">Dalam Pengiriman</div>
                <div class="filter-tab" data-status="Selesai">Selesai</div>
                <div class="filter-tab" data-status="Dibatalkan">Dibatalkan</div>
            </div>
            
            <!-- Orders List -->
            <?php foreach($orders as $order): 
                $status = $order['status_pesanan'] ?? 'Unknown';
                $status_class = 'status-' . str_replace(' ', '_', strtolower($status));
            ?>
            <div class="order-card" data-status="<?php echo htmlspecialchars($status); ?>">
                <div class="order-header">
                    <div>
                        <div class="order-id">
                            <i class="bi bi-receipt"></i> 
                            <?php echo htmlspecialchars($order['order_code'] ?? 'Order #' . str_pad($order['id_transaksi'], 6, '0', STR_PAD_LEFT)); ?>
                        </div>
                        <div class="order-date">
                            <i class="bi bi-calendar3"></i> 
                            <?php echo date('d M Y, H:i', strtotime($order['tanggal_transaksi'])); ?>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>
                
                <div class="order-details">
                    <div class="mb-2">
                        <i class="bi bi-box-seam"></i> 
                        <strong><?php echo intval($order['total_items']); ?></strong> produk
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-credit-card"></i> 
                        <strong><?php echo htmlspecialchars($order['metode_pembayaran'] ?? 'Belum ditentukan'); ?></strong>
                    </div>
                    <?php if(!empty($order['no_resi'])): ?>
                    <div class="mb-2">
                        <i class="bi bi-truck"></i> 
                        Resi: <strong><?php echo htmlspecialchars($order['no_resi']); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="order-total">
                    Total: Rp <?php echo number_format(intval($order['total_harga'] ?? 0), 0, ',', '.'); ?>
                </div>
                
                <div class="order-actions">
                    <a href="../transaksi/selesai.php?id=<?php echo $order['id_transaksi']; ?>" class="btn-detail">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                    <?php if($status === 'Dalam Pengiriman'): ?>
                    <a href="#" class="btn-outline" onclick="confirmDelivery(<?php echo $order['id_transaksi']; ?>); return false;">
                        <i class="bi bi-check-circle"></i> Pesanan Diterima
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-bag-x"></i>
                </div>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki riwayat pesanan.<br>Yuk, mulai belanja sekarang!</p>
                <a href="../produk/list-produk.php" class="btn-detail">
                    <i class="bi bi-shop"></i> Mulai Belanja
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Filter functionality
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const status = this.dataset.status;
        
        document.querySelectorAll('.order-card').forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

function confirmDelivery(orderId) {
    if (confirm('Apakah pesanan ini sudah diterima?')) {
        // TODO: Add API call to update order status
        alert('Status pesanan akan diupdate');
    }
}
</script>

<?php include '../includes/footer.php'; ?>