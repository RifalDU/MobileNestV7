<?php
require_once '../config.php';
require_login();

$page_title = "Checkout";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Checkout</h1>
    
    <div class="row">
        <div class="col-md-8">
            <form id="checkout-form">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Alamat Pengiriman</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap *</label>
                            <textarea class="form-control" id="alamat_pengiriman" 
                                      rows="3" required></textarea>
                            <small class="text-muted">Contoh: Jl. Merdeka No. 123, Jakarta</small>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Metode Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                       name="metode_pembayaran" value="Transfer Bank" 
                                       id="transfer" required>
                                <label class="form-check-label" for="transfer">
                                    💳 Transfer Bank
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                       name="metode_pembayaran" value="COD" id="cod">
                                <label class="form-check-label" for="cod">
                                    🏠 COD (Bayar di Tempat)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Catatan Tambahan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <textarea class="form-control" id="catatan_user" 
                                      rows="3" placeholder="Contoh: Tolong dikemas rapi, prioritas hari Jumat..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle"></i> Lanjutkan ke Pembayaran
                    </button>
                </div>
            </form>
        </div>
        
        <div class="col-md-4">
            <div class="card" style="position: sticky; top: 20px;">
                <div class="card-body" id="checkout-summary">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/api-handler.js"></script>
<script src="../js/checkout.js"></script>

<?php include '../includes/footer.php'; ?>