/**
 * Cart Management - Handle cart display and interactions
 */

const SHIPPING_COST = 20000;
const UPLOADS_PRODUK_URL = 'http://localhost/MobileNest/admin/uploads/produk/';

/**
 * Update cart count badge in navbar
 */
async function updateCartCount() {
    try {
        const result = await getCartCount();
        const badge = document.getElementById('cart-count-badge');
        
        if (badge && result.success) {
            if (result.count > 0) {
                badge.textContent = result.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

/**
 * Get product image URL
 * @param {string} gambar - Filename from database (e.g., 'produk_123_abc.jpg')
 * @returns {string} Full image URL
 */
function getProductImageUrl(gambar) {
    if (!gambar) {
        return '../assets/images/placeholder.jpg';
    }
    
    // If it's already a full URL
    if (gambar.startsWith('http://') || gambar.startsWith('https://')) {
        return gambar;
    }
    
    // If it's just a filename, add the uploads path
    if (!gambar.includes('/')) {
        return UPLOADS_PRODUK_URL + gambar;
    }
    
    // If it's a path, prepend site URL
    return '../' + gambar;
}

/**
 * Format price to Indonesian currency
 */
function formatPrice(price) {
    // Handle NaN or undefined
    if (isNaN(price) || price === undefined || price === null) {
        price = 0;
    }
    
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price);
}

/**
 * Load and display cart items
 */
async function loadCartItems() {
    try {
        const container = document.getElementById('cart-items-container');
        const summaryCard = document.getElementById('cart-summary');
        const voucherSection = document.getElementById('voucher-section');
        
        if (!container) {
            console.log('Cart container not found');
            return;
        }
        
        console.log('Loading cart items...');
        const result = await getCartItems();
        console.log('Cart result:', result);
        
        if (!result.success) {
            container.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="bi bi-exclamation-circle"></i>
                    <p class="mt-2">Gagal memuat keranjang: ${result.message}</p>
                </div>
            `;
            if (summaryCard) summaryCard.style.display = 'none';
            if (voucherSection) voucherSection.style.display = 'none';
            return;
        }
        
        // Empty cart
        if (!result.items || result.items.length === 0) {
            container.innerHTML = `
                <div class="empty-cart-message">
                    <i class="bi bi-cart-x"></i>
                    <h4>Keranjang Anda Kosong</h4>
                    <p>Mulai berbelanja dan tambahkan produk ke keranjang Anda.</p>
                    <a href="../produk/list-produk.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-shop"></i> Lanjut Belanja
                    </a>
                </div>
            `;
            if (summaryCard) summaryCard.style.display = 'none';
            if (voucherSection) voucherSection.style.display = 'none';
            return;
        }
        
        // Cart has items - show items and summary
        let html = '';
        let totalPrice = 0;
        
        result.items.forEach(item => {
            // Ensure numeric types
            const quantity = parseInt(item.quantity) || 0;
            const harga = parseFloat(item.harga) || 0;
            const subtotal = harga * quantity;
            totalPrice += subtotal;
            
            // Get correct image URL ✅
            const imageUrl = getProductImageUrl(item.gambar);
            
            console.log(`Item: ${item.nama_produk}, Qty: ${quantity}, Price: ${harga}, Subtotal: ${subtotal}`);
            console.log(`Image URL: ${imageUrl}`);
            
            html += `
                <div class="cart-item">
                    <img src="${imageUrl}" alt="${item.nama_produk}" onerror="this.src='../assets/images/placeholder.jpg'">
                    <div class="cart-item-info">
                        <div class="cart-item-title">${item.nama_produk}</div>
                        <div class="cart-item-price">Harga: Rp ${formatPrice(harga)}</div>
                    </div>
                    <div class="quantity-control">
                        <button onclick="updateQuantity(${item.id_produk}, ${quantity - 1})">-</button>
                        <input type="number" value="${quantity}" readonly>
                        <button onclick="updateQuantity(${item.id_produk}, ${quantity + 1})">+</button>
                    </div>
                    <div>
                        <div class="fw-bold" style="color: #667eea; margin-bottom: 10px;">Rp ${formatPrice(subtotal)}</div>
                        <button class="btn-remove" onclick="removeItem(${item.id_produk})">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Show voucher section and summary
        if (voucherSection) voucherSection.style.display = 'block';
        if (summaryCard) summaryCard.style.display = 'block';
        
        // Calculate totals
        const discount = 0; // TODO: implement voucher system
        const shipping = SHIPPING_COST;
        const total = totalPrice + shipping - discount;
        
        console.log(`Total calculation: ${totalPrice} + ${shipping} - ${discount} = ${total}`);
        
        // Update summary
        document.getElementById('total-items').textContent = result.items.length;
        document.getElementById('subtotal').textContent = 'Rp ' + formatPrice(totalPrice);
        document.getElementById('discount').textContent = '-Rp ' + formatPrice(discount);
        document.getElementById('shipping').textContent = 'Rp ' + formatPrice(shipping);
        document.getElementById('total-price').textContent = 'Rp ' + formatPrice(total);
        
    } catch (error) {
        console.error('Error loading cart items:', error);
        const container = document.getElementById('cart-items-container');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    Terjadi kesalahan saat memuat keranjang: ${error.message}
                </div>
            `;
        }
    }
}

/**
 * Update item quantity
 */
async function updateQuantity(id_produk, newQuantity) {
    if (newQuantity <= 0) {
        removeItem(id_produk);
        return;
    }
    
    const result = await updateCartQuantity(id_produk, newQuantity);
    if (result.success) {
        loadCartItems();
        updateCartCount();
        showNotification('success', 'Quantity berhasil diperbarui');
    } else {
        showNotification('error', 'Gagal update quantity: ' + result.message);
    }
}

/**
 * Remove item from cart
 */
async function removeItem(id_produk) {
    if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
        const result = await removeFromCart(id_produk);
        if (result.success) {
            loadCartItems();
            updateCartCount();
            showNotification('success', 'Item berhasil dihapus dari keranjang');
        } else {
            showNotification('error', 'Gagal menghapus item: ' + result.message);
        }
    }
}

/**
 * Show notification
 */
function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        <i class="bi bi-${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cart JS initialized');
    
    // Load cart if on cart page
    if (document.getElementById('cart-items-container')) {
        console.log('Cart page detected, loading items...');
        loadCartItems();
    }
    
    // Update cart count
    updateCartCount();
});