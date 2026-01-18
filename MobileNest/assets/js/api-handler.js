/**
 * API Handler - MobileNest
 */

// Gunakan SITE_URL dari footer jika ada, kalau tidak fallback ke logic lama
const baseUrl = (typeof SITE_URL !== 'undefined') ? SITE_URL : window.location.origin + '/MobileNest';
const API_BASE = baseUrl + '/api/';

console.log('API Base configured:', API_BASE);

async function apiRequest(endpoint, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };

        if (method !== 'GET' && data) {
            options.body = JSON.stringify(data);
        }

        console.log('API Request:', `${API_BASE}${endpoint}`, method, data);
        
        const response = await fetch(`${API_BASE}${endpoint}`, options);
        const result = await response.json();
        
        console.log('API Response:', result);
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Connection error' };
    }
}

// Wrapper functions
async function getCartItems() { 
    console.log('getCartItems called');
    return await apiRequest('cart.php?action=get'); 
}

async function getCartCount() { 
    console.log('getCartCount called');
    return await apiRequest('cart.php?action=count'); 
}

async function addToCart(id_produk, quantity = 1) {
    console.log('addToCart called with:', id_produk, quantity);
    const result = await apiRequest('cart.php?action=add', 'POST', { id_produk, quantity });
    console.log('addToCart result:', result);
    return result;
}

async function removeFromCart(id_produk) {
    console.log('removeFromCart called with:', id_produk);
    return await apiRequest('cart.php?action=remove', 'POST', { id_produk });
}

async function updateCartQuantity(id_produk, quantity) {
    console.log('updateCartQuantity called with:', id_produk, quantity);
    return await apiRequest('cart.php?action=update', 'POST', { id_produk, quantity });
}