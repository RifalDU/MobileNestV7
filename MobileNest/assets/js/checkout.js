/**
 * Checkout Management
 */

/**
 * Load checkout summary
 */
async function loadCheckoutSummary() {
    try {
        const summary = document.getElementById('checkout-summary');
        if (!summary) return;
        
        const result = await getCartItems();
        
        if (!result.success || result.count === 0) {
            summary.innerHTML = `
                <div class="alert alert-warning">
                    Keranjang Anda kosong. <a href="keranjang.php">Kembali ke keranjang</a>
                </div>
            `;
            return;
        }
        
        let totalPrice = 0;
        result.items.forEach(item => {
            totalPrice += (item.harga * item.quantity);
        });
        
        const shippingCost = totalPrice > 500000 ? 0 : 15000;
        const totalWithShipping = totalPrice + shippingCost;
        
        summary.innerHTML = `
            <h5>Ringkasan Belanja</h5>
            <hr>
            <div class="row mb-2">
                <div class="col-6"><small>Subtotal</small></div>
                <div class="col-6 text-end"><small>Rp ${formatPrice(totalPrice)}</small></div>
            </div>
            <div class="row mb-3">
                <div class="col-6"><small>Biaya Pengiriman</small></div>
                <div class="col-6 text-end">
                    <small>${shippingCost === 0 ? '<span class="badge bg-success">GRATIS</span>' : 'Rp ' + formatPrice(shippingCost)}</small>
                </div>
            </div>
            <hr>
            <div class="row mb-3">
                <div class="col-6"><strong>Total</strong></div>
                <div class="col-6 text-end"><strong>Rp ${formatPrice(totalWithShipping)}</strong></div>
            </div>
            <button class="btn btn-primary w-100" form="checkout-form">
                <i class="bi bi-check-circle"></i> Konfirmasi Pembayaran
            </button>
        `;
    } catch (error) {
        console.error('Error loading checkout summary:', error);
    }
}

/**
 * Handle checkout form submission
 */
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkout-form');
    
    if (checkoutForm) {
        // Load summary
        loadCheckoutSummary();
        
        // Handle form submit
        checkoutForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const alamat = document.getElementById('alamat_pengiriman').value;
            const metode = document.querySelector('input[name="metode_pembayaran"]:checked');
            const catatan = document.getElementById('catatan_user').value;
            
            if (!alamat) {
                alert('Alamat pengiriman harus diisi!');
                return;
            }
            
            if (!metode) {
                alert('Metode pembayaran harus dipilih!');
                return;
            }
            
            // Show loading
            const btn = checkoutForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            
            try {
                // Create order via API
                const cartResult = await getCartItems();
                
                if (!cartResult.success || cartResult.count === 0) {
                    alert('Keranjang Anda kosong!');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    return;
                }
                
                // Prepare order data
                const totalPrice = cartResult.items.reduce((sum, item) => sum + (item.harga * item.quantity), 0);
                const shippingCost = totalPrice > 500000 ? 0 : 15000;
                const totalWithShipping = totalPrice + shippingCost;
                
                // Submit to server
                const formData = new FormData();
                formData.append('action', 'create_order');
                formData.append('alamat_pengiriman', alamat);
                formData.append('metode_pembayaran', metode.value);
                formData.append('catatan_user', catatan);
                formData.append('total_harga', totalWithShipping);
                formData.append('cart_items', JSON.stringify(cartResult.items));
                
                const response = await fetch('../api/order.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to confirmation page
                    window.location.href = `proses-pembayaran.php?id=${result.order_id}`;
                } else {
                    alert('Gagal membuat pesanan: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error creating order:', error);
                alert('Terjadi kesalahan saat membuat pesanan');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
    
    // Load transaction details if on confirmation page
    if (window.location.pathname.includes('proses-pembayaran.php')) {
        const transactionId = new URLSearchParams(window.location.search).get('id');
        if (transactionId) {
            loadTransactionDetails(transactionId);
        }
    }
    
    // Load transaction history if on pesanan page
    if (window.location.pathname.includes('pesanan.php')) {
        loadTransactionHistory();
    }
});

/**
 * Load transaction details
 */
async function loadTransactionDetails(id) {
    try {
        const container = document.getElementById('transaction-details');
        if (!container) return;
        
        const response = await fetch(`../api/order.php?action=get&id=${id}`);
        const result = await response.json();
        
        if (!result.success) {
            container.innerHTML = `<div class="alert alert-danger">Pesanan tidak ditemukan</div>`;
            return;
        }
        
        const order = result.data;
        let itemsHtml = '';
        
        if (order.items && Array.isArray(order.items)) {
            order.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.nama_produk}</td>
                        <td class="text-end">${item.quantity}</td>
                        <td class="text-end">Rp ${formatPrice(item.harga)}</td>
                        <td class="text-end">Rp ${formatPrice(item.harga * item.quantity)}</td>
                    </tr>
                `;
            });
        }
        
        container.innerHTML = `
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">✓ Pesanan Berhasil Dibuat</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>No. Pesanan:</strong> #${order.id_transaksi}<br>
                            <strong>Tanggal:</strong> ${new Date(order.tanggal_transaksi).toLocaleDateString('id-ID')}<br>
                            <strong>Status:</strong> <span class="badge bg-info">${order.status_transaksi}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Metode Pembayaran:</strong> ${order.metode_pembayaran}<br>
                            <strong>Total:</strong> <span class="h5 text-primary">Rp ${formatPrice(order.total_harga)}</span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Alamat Pengiriman</h6>
                    <p>${order.alamat_pengiriman}</p>
                    
                    <hr>
                    
                    <h6>Detail Pesanan</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error loading transaction details:', error);
        document.getElementById('transaction-details').innerHTML = `
            <div class="alert alert-danger">Terjadi kesalahan saat memuat detail pesanan</div>
        `;
    }
}

/**
 * Load transaction history
 */
async function loadTransactionHistory() {
    try {
        const container = document.getElementById('transactions-history');
        if (!container) return;
        
        const response = await fetch('../api/order.php?action=list');
        const result = await response.json();
        
        if (!result.success || !result.data || result.data.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <h5 class="mt-3">Belum ada pesanan</h5>
                    <p>Mulai berbelanja dan buat pesanan Anda pertama kali.</p>
                    <a href="../produk/list-produk.php" class="btn btn-primary">
                        <i class="bi bi-shop"></i> Belanja Sekarang
                    </a>
                </div>
            `;
            return;
        }
        
        let html = '';
        result.data.forEach(order => {
            const statusColor = order.status_transaksi === 'Pending' ? 'warning' : 
                               order.status_transaksi === 'Completed' ? 'success' : 'secondary';
            
            html += `
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-0">#${order.id_transaksi}</h6>
                                <small class="text-muted">${new Date(order.tanggal_transaksi).toLocaleDateString('id-ID')}</small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge bg-${statusColor}">${order.status_transaksi}</span>
                            </div>
                            <div class="col-md-3 text-end">
                                <strong>Rp ${formatPrice(order.total_harga)}</strong>
                                <br>
                                <a href="proses-pembayaran.php?id=${order.id_transaksi}" class="btn btn-sm btn-outline-primary mt-2">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading transaction history:', error);
        document.getElementById('transactions-history').innerHTML = `
            <div class="alert alert-danger">Terjadi kesalahan saat memuat riwayat pesanan</div>
        `;
    }
}