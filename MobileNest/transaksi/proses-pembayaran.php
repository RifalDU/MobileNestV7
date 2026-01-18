<?php
require_once '../config.php';
require_login();

$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transaction_id <= 0) {
    header('Location: ../transaksi/keranjang.php');
    exit;
}

$page_title = "Konfirmasi Pembayaran";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">📋 Konfirmasi Pembayaran</h1>
    
    <div id="transaction-details">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="../user/pesanan.php" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Kembali ke Riwayat Pesanan
        </a>
    </div>
</div>

<script src="../js/api-handler.js"></script>
<script src="../js/checkout.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const transactionId = <?php echo $transaction_id; ?>;
        loadTransactionDetails(transactionId);
    });
</script>

<?php include '../includes/footer.php'; ?>