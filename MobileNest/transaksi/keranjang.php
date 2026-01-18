<?php
session_start();
require_once '../config.php';
$page_title = "Keranjang & Checkout";
include '../includes/header.php';
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
.step.active .step-label {
    color: #667eea;
    font-weight: 600;
}
.step-label {
    font-size: 14px;
    color: #999;
}
.cart-item {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    display: flex;
    gap: 20px;
    align-items: center;
}
.cart-item img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    border-radius: 10px;
}
.cart-item-info {
    flex: 1;
}
.cart-item-title {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 5px;
    color: #2c3e50;
}
.cart-item-price {
    color: #667eea;
    font-weight: 600;
    font-size: 14px;
}
.quantity-control {
    display: flex;
    align-items: center;
    gap: 10px;
}
.quantity-control button {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
}
.quantity-control input {
    width: 50px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 5px;
}
.voucher-box {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin: 20px 0;
}
.voucher-input-group {
    display: flex;
    gap: 10px;
}
.voucher-input-group input {
    flex: 1;
    padding: 10px 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
}
.voucher-input-group button {
    padding: 10px 25px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
}
.summary-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    position: sticky;
    top: 100px;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}
.summary-row.total {
    border-bottom: none;
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    margin-top: 10px;
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
    margin-top: 20px;
}
.btn-remove {
    color: #e74c3c;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
}
.empty-cart-message {
    background: linear-gradient(135deg, #e8f0fe 0%, #f3e5f5 100%);
    border-radius: 15px;
    padding: 60px 20px;
    text-align: center;
    margin-bottom: 30px;
}
.empty-cart-message i {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 20px;
}
.empty-cart-message h4 {
    color: #2c3e50;
    margin: 20px 0 10px;
}
.empty-cart-message p {
    color: #7f8c8d;
    margin-bottom: 20px;
}
</style>

<div class="container py-5">
    <!-- Progress Steps -->
    <div class="checkout-steps">
        <div class="step active">
            <div class="step-circle">1</div>
            <div class="step-label">Keranjang</div>
        </div>
        <div class="step">
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
            <!-- Cart Items Container (populated by JS) -->
            <div id="cart-items-container">
                <!-- Loading indicator -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Voucher Box -->
            <div class="voucher-box" id="voucher-section" style="display: none;">
                <label class="fw-bold mb-2">Kode Voucher</label>
                <small class="text-muted d-block mb-2">(contoh: MOBILENEST10)</small>
                <div class="voucher-input-group">
                    <input type="text" placeholder="Masukkan kode voucher" id="voucher-input">
                    <button id="apply-voucher">Apply</button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="summary-card" id="cart-summary" style="display: none;">
                <h5 class="fw-bold mb-4">Ringkasan Belanja</h5>
                <div class="summary-row">
                    <span>Total Produk</span>
                    <span class="fw-bold" id="total-items">0</span>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="fw-bold" id="subtotal">Rp 0</span>
                </div>
                <div class="summary-row">
                    <span>Diskon</span>
                    <span class="fw-bold text-success" id="discount">-Rp 0</span>
                </div>
                <div class="summary-row">
                    <span>Ongkir</span>
                    <span class="fw-bold" id="shipping">Rp 20.000</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span style="color: #667eea;" id="total-price">Rp 0</span>
                </div>
                <button class="btn-checkout" onclick="window.location.href='pengiriman.php'">Lanjut ke Pengiriman <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/api-handler.js"></script>
<script src="../assets/js/cart.js"></script>

<?php include '../includes/footer.php'; ?>