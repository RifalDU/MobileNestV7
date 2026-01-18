/**
 * ============================================
 * FILE: filter.js
 * PURPOSE: Handle product filtering
 * MODE: HYBRID (PHP initial render + AJAX filter)
 * ============================================
 */

// Debounce timer for search
let searchDebounceTimer = null;

/**
 * Build image URL for product images from API
 * Handles both local filenames and external URLs
 */
function buildImageUrl(gambar) {
    if (!gambar) {
        return '';
    }
    
    // Check if it's a filename (local upload) or URL
    if (!gambar.includes('http') && !gambar.includes('/')) {
        // It's a filename - build UploadHandler URL
        // Format: /MobileNest/api/upload/serve.php?file=filename.jpg&type=produk
        const baseUrl = window.location.origin + '/MobileNest';
        return baseUrl + '/api/upload/serve.php?file=' + encodeURIComponent(gambar) + '&type=produk';
    }
    
    // It's already a URL or path
    return gambar;
}

/**
 * Get all selected filters from checkboxes
 */
function getSelectedFilters() {
    const filters = {
        brands: [],
        prices: [],
        search: ''
    };

    // Get selected brands
    const brandCheckboxes = document.querySelectorAll('input.brand-checkbox:checked');
    brandCheckboxes.forEach(checkbox => {
        filters.brands.push(checkbox.value);
    });

    // Get selected prices
    const priceCheckboxes = document.querySelectorAll('input.price-checkbox:checked');
    priceCheckboxes.forEach(checkbox => {
        filters.prices.push(checkbox.value);
    });

    // Get search query
    const searchInput = document.getElementById('search_produk');
    if (searchInput && searchInput.value.trim()) {
        filters.search = searchInput.value.trim();
    }

    return filters;
}

/**
 * Check if any filters are active
 */
function hasActiveFilters() {
    const filters = getSelectedFilters();
    return filters.brands.length > 0 || filters.prices.length > 0 || filters.search !== '';
}

/**
 * Apply filters and fetch products from API
 */
async function applyFilter() {
    try {
        const filters = getSelectedFilters();
        console.log('Applying filters:', filters);

        // If no filters selected, fetch ALL products from database
        if (!hasActiveFilters()) {
            console.log('No filters active - fetching all products');
            await fetchAllProducts();
            return;
        }

        // Build query params for API
        const params = new URLSearchParams();
        
        if (filters.brands.length > 0) {
            params.append('brand', filters.brands.join(','));
        }

        if (filters.prices.length > 0) {
            // Parse price filters - use the max price range if multiple selected
            let minPrice = Infinity;
            let maxPrice = 0;

            filters.prices.forEach(priceRange => {
                const [min, max] = priceRange.split(':').map(Number);
                minPrice = Math.min(minPrice, min);
                maxPrice = Math.max(maxPrice, max);
            });

            params.append('min_price', minPrice);
            params.append('max_price', maxPrice);
        }

        if (filters.search) {
            params.append('search', filters.search);
        }

        // Get sort option
        const sortSelect = document.getElementById('sort_option');
        if (sortSelect && sortSelect.value) {
            params.append('sort', sortSelect.value);
        }

        // Show loading state
        showLoadingState();

        // Fetch filtered products from API
        const response = await fetch(`../produk/get-produk.php?${params.toString()}`);
        
        if (!response.ok) {
            throw new Error('API Error: ' + response.statusText);
        }

        const products = await response.json();
        console.log('Filter result:', products.length, 'products');
        
        renderProducts(products);

    } catch (error) {
        console.error('Error applying filter:', error);
        showFilterNotification('error', 'Error applying filter: ' + error.message);
    }
}

/**
 * Fetch all products from database
 */
async function fetchAllProducts() {
    try {
        // Show loading state
        showLoadingState();

        // Fetch all products (no filters)
        const response = await fetch('../produk/get-produk.php?sort=terbaru');
        
        if (!response.ok) {
            throw new Error('API Error: ' + response.statusText);
        }

        const products = await response.json();
        console.log('Fetched all products:', products.length);
        
        renderProducts(products);
        showFilterNotification('info', 'Filter direset - menampilkan semua produk');

    } catch (error) {
        console.error('Error fetching all products:', error);
        showFilterNotification('error', 'Error fetching products: ' + error.message);
    }
}

/**
 * Show loading state
 */
function showLoadingState() {
    const container = document.getElementById('products_container');
    container.innerHTML = `
        <div class="col-12 text-center text-muted py-5">
            <i class="bi bi-hourglass-split" style="font-size: 2rem;"></i>
            <p class="mt-3">Mengfilter produk...</p>
        </div>
    `;
}

/**
 * Render products from API response
 */
function renderProducts(products) {
    const container = document.getElementById('products_container');
    
    if (!container) {
        console.error('products_container not found');
        return;
    }

    // Update product count
    const countElement = document.getElementById('product_count');
    if (countElement) {
        countElement.textContent = products.length;
    }

    if (products.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Tidak ada produk yang sesuai dengan filter Anda</p>
            </div>
        `;
        return;
    }

    // Render product cards
    container.innerHTML = products.map(product => {
        const imageUrl = buildImageUrl(product.gambar);
        return `
        <div class="product-card">
            <div class="card border-0 shadow-sm h-100 transition">
                <!-- Product Image -->
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px; position: relative; overflow: hidden;">
                    ${imageUrl ? `
                        <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(product.nama_produk)}" style="width: 100%; height: 100%; object-fit: cover;">
                    ` : `
                        <i class="bi bi-phone" style="font-size: 3rem; color: #ccc;"></i>
                    `}
                    <span class="badge bg-danger position-absolute top-0 end-0 m-2">-15%</span>
                </div>
                
                <!-- Product Info -->
                <div class="card-body">
                    <h6 class="card-title mb-2" title="${escapeHtml(product.nama_produk)}">${escapeHtml(product.nama_produk)}</h6>
                    
                    <!-- Brand Info -->
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-secondary">${escapeHtml(product.merek)}</span>
                        <span class="badge bg-info ms-2">Stok: ${product.stok}</span>
                    </div>
                    
                    <!-- Rating -->
                    <div class="mb-2">
                        <span class="text-warning">
                            ${getRatingStars(product.rating || 4.5)}
                        </span>
                        <span class="text-muted small">(${product.terjual || 0})</span>
                    </div>
                    
                    <!-- Price -->
                    <h5 class="text-primary mb-3">Rp ${formatPrice(product.harga)}</h5>
                    
                    <!-- Buttons -->
                    <div class="d-grid gap-2">
                        <a href="detail-produk.php?id=${product.id_produk}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-search"></i> Lihat Detail
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addToCart(${product.id_produk}, 1)">
                            <i class="bi bi-cart-plus"></i> Keranjang
                        </button>
                    </div>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

/**
 * Reset all filters
 */
function resetFilter() {
    console.log('Resetting all filters');

    // Uncheck all checkboxes
    document.querySelectorAll('input.brand-checkbox, input.price-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Clear search
    const searchInput = document.getElementById('search_produk');
    if (searchInput) {
        searchInput.value = '';
    }

    // Reset sort
    const sortSelect = document.getElementById('sort_option');
    if (sortSelect) {
        sortSelect.value = 'terbaru';
    }

    // Fetch all products from database (not just show PHP ones)
    fetchAllProducts();
}

/**
 * Show notification
 */
function showFilterNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';

    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed bottom-0 end-0 m-3`;
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        <i class="bi bi-${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alert);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

/**
 * Format price to Indonesian format
 */
function formatPrice(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Get rating stars HTML
 */
function getRatingStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= Math.floor(rating)) {
            stars += '<i class="bi bi-star-fill"></i>';
        } else if (i - rating < 1) {
            stars += '<i class="bi bi-star-half"></i>';
        } else {
            stars += '<i class="bi bi-star"></i>';
        }
    }
    return stars;
}

/**
 * Initialize filter event listeners
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Filter JS initialized (Hybrid Mode)');

    // Setup filter button click handlers
    document.querySelectorAll('button').forEach(btn => {
        const text = btn.textContent.toLowerCase();
        if (text.includes('terapkan') || text.includes('filter')) {
            btn.onclick = applyFilter;
        }
        if (text.includes('reset')) {
            btn.onclick = resetFilter;
        }
    });

    // Setup search input handler with DEBOUNCE (wait 500ms after user stops typing)
    const searchInput = document.getElementById('search_produk');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Clear previous timer
            clearTimeout(searchDebounceTimer);
            
            // Set new timer - only call applyFilter after 500ms of no typing
            searchDebounceTimer = setTimeout(() => {
                applyFilter();
            }, 500);
        });
    }

    // Setup sort change handler - auto-apply on change
    const sortSelect = document.getElementById('sort_option');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            applyFilter();
        });
    }

    // Setup checkbox change handlers - optional auto-apply
    document.querySelectorAll('.brand-checkbox, .price-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Optional: uncomment to auto-apply on checkbox change
            // applyFilter();
        });
    });

    // Note: Don't call applyFilter() on init for hybrid mode
    // Products already rendered by PHP
    // Only call applyFilter() when user applies filters

    console.log('Filter JS setup complete (Hybrid Mode)');
});