<?php
/**
 * MobileNest - Helper Functions Library
 * Standardized helper functions untuk seluruh aplikasi
 * Include file ini untuk akses ke semua helper functions
 */

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === 'helpers.php') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// ===== REQUIRE CONFIG =====
require_once dirname(__DIR__) . '/config.php';

// ===== NAVIGATION HELPERS =====

/**
 * Get standardized navigation URLs
 * @return array Navigation links
 */
function get_nav_links() {
    return [
        'home' => SITE_URL . '/index.php',
        'products' => SITE_URL . '/produk/list-produk.php',
        'login' => SITE_URL . '/user/login.php',
        'register' => SITE_URL . '/user/register.php',
        'cart' => SITE_URL . '/transaksi/keranjang.php',
        'checkout' => SITE_URL . '/transaksi/checkout.php',
        'orders' => SITE_URL . '/user/pesanan.php',
        'profile' => SITE_URL . '/user/profil.php',
        'logout' => SITE_URL . '/user/logout.php',
        'admin_dashboard' => SITE_URL . '/admin/dashboard.php',
        'admin_products' => SITE_URL . '/admin/kelola-produk.php',
        'admin_orders' => SITE_URL . '/admin/kelola-transaksi.php',
        'admin_reports' => SITE_URL . '/admin/laporan.php',
    ];
}

/**
 * Get cart count from session
 * @return int Cart item count
 */
function get_cart_count() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return count($_SESSION['cart']);
    }
    return 0;
}

/**
 * Get cart total from session
 * @return float Cart total price
 */
function get_cart_total() {
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        global $conn;
        foreach ($_SESSION['cart'] as $item) {
            $id_produk = intval($item['id_produk']);
            $kuantitas = intval($item['kuantitas']);
            
            $sql = "SELECT harga FROM produk WHERE id_produk = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id_produk);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $total += ($row['harga'] * $kuantitas);
            }
            $stmt->close();
        }
    }
    return $total;
}

/**
 * Redirect to login page with optional redirect after login
 * @param string $redirect_url URL to redirect after login
 */
function redirect_to_login($redirect_url = null) {
    $login_url = SITE_URL . '/user/login.php';
    if ($redirect_url) {
        $login_url .= '?redirect=' . urlencode($redirect_url);
    }
    header('Location: ' . $login_url);
    exit('Please login first');
}

/**
 * Redirect to admin login
 */
function redirect_to_admin_login() {
    header('Location: ' . SITE_URL . '/user/login.php?type=admin&error=unauthorized');
    exit('Admin access required');
}

// ===== BREADCRUMB HELPERS =====

/**
 * Generate breadcrumb navigation
 * @param array $items Breadcrumb items [label => url]
 */
function generate_breadcrumb($items = []) {
    if (empty($items)) {
        return;
    }
    
    echo '<nav aria-label="breadcrumb" class="mb-4">';
    echo '<ol class="breadcrumb">';
    
    foreach ($items as $label => $url) {
        if ($url) {
            echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a></li>';
        } else {
            echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($label) . '</li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}

// ===== PRODUCT HELPERS =====

/**
 * Add item to cart session
 * @param int $product_id Product ID
 * @param int $quantity Quantity (default: 1)
 */
function add_to_cart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $product_id = intval($product_id);
    $quantity = intval($quantity);
    
    if ($quantity < 1) {
        return false;
    }
    
    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id_produk'] == $product_id) {
            $item['kuantitas'] += $quantity;
            $found = true;
            break;
        }
    }
    
    // Add new item if not found
    if (!$found) {
        $_SESSION['cart'][] = [
            'id_produk' => $product_id,
            'kuantitas' => $quantity
        ];
    }
    
    return true;
}

/**
 * Remove item from cart
 * @param int $product_id Product ID
 */
function remove_from_cart($product_id) {
    if (isset($_SESSION['cart'])) {
        $product_id = intval($product_id);
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
            return $item['id_produk'] != $product_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
    }
}

/**
 * Clear entire cart
 */
function clear_cart() {
    unset($_SESSION['cart']);
}

/**
 * Get product details
 * @param int $product_id Product ID
 * @return array Product data or null
 */
function get_product($product_id) {
    global $conn;
    
    $product_id = intval($product_id);
    $sql = "SELECT * FROM produk WHERE id_produk = ? AND status_produk = 'Tersedia'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// ===== PAYMENT HELPERS =====

/**
 * Get payment methods
 * @return array Payment methods
 */
function get_payment_methods() {
    return [
        'Transfer Bank' => 'Transfer ke rekening bank kami',
        'E-Wallet' => 'Pembayaran via E-Wallet (OVO, DANA, GoPay, dll)',
        'COD' => 'Bayar tunai saat barang sampai',
        'Kartu Kredit' => 'Pembayaran dengan kartu kredit'
    ];
}

/**
 * Get transaction statuses
 * @return array Statuses
 */
function get_transaction_statuses() {
    return [
        'Pending' => 'Menunggu Pembayaran',
        'Diproses' => 'Sedang Diproses',
        'Dikirim' => 'Sedang Dikirim',
        'Selesai' => 'Pesanan Selesai',
        'Dibatalkan' => 'Pesanan Dibatalkan'
    ];
}

/**
 * Get status badge CSS class
 * @param string $status Transaction status
 * @return string CSS class name
 */
function get_status_badge_class($status) {
    $statuses = [
        'Pending' => 'warning',
        'Diproses' => 'info',
        'Dikirim' => 'primary',
        'Selesai' => 'success',
        'Dibatalkan' => 'danger'
    ];
    
    return $statuses[$status] ?? 'secondary';
}

// ===== USER HELPERS =====

/**
 * Get current user info
 * @return array User info or null
 */
function get_current_user() {
    return get_user_info();
}

/**
 * Get user by ID
 * @param int $user_id User ID
 * @return array User data or null
 */
function get_user_by_id($user_id) {
    global $conn;
    
    $user_id = intval($user_id);
    $sql = "SELECT id_user, username, nama_lengkap, email, no_telepon, alamat, tanggal_daftar FROM users WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Count user orders
 * @param int $user_id User ID
 * @return int Order count
 */
function count_user_orders($user_id) {
    global $conn;
    
    $user_id = intval($user_id);
    $sql = "SELECT COUNT(*) as count FROM transaksi WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return intval($row['count']);
}

/**
 * Count user pending orders
 * @param int $user_id User ID
 * @return int Pending order count
 */
function count_pending_orders($user_id) {
    global $conn;
    
    $user_id = intval($user_id);
    $sql = "SELECT COUNT(*) as count FROM transaksi WHERE id_user = ? AND status_pesanan = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return intval($row['count']);
}

// ===== ADMIN HELPERS =====

/**
 * Get dashboard statistics
 * @return array Statistics data
 */
function get_dashboard_stats() {
    global $conn;
    
    $stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'total_users' => 0,
        'total_products' => 0,
        'monthly_revenue' => 0,
        'pending_orders' => 0,
        'low_stock_products' => 0
    ];
    
    // Total orders
    $result = $conn->query("SELECT COUNT(*) as count FROM transaksi");
    $stats['total_orders'] = $result->fetch_assoc()['count'];
    
    // Total revenue
    $result = $conn->query("SELECT SUM(total_harga) as total FROM transaksi WHERE status_pesanan != 'Dibatalkan'");
    $row = $result->fetch_assoc();
    $stats['total_revenue'] = $row['total'] ?? 0;
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    // Total products
    $result = $conn->query("SELECT COUNT(*) as count FROM produk");
    $stats['total_products'] = $result->fetch_assoc()['count'];
    
    // Monthly revenue
    $result = $conn->query("SELECT SUM(total_harga) as total FROM transaksi WHERE MONTH(tanggal_transaksi) = MONTH(NOW()) AND YEAR(tanggal_transaksi) = YEAR(NOW()) AND status_pesanan != 'Dibatalkan'");
    $row = $result->fetch_assoc();
    $stats['monthly_revenue'] = $row['total'] ?? 0;
    
    // Pending orders
    $result = $conn->query("SELECT COUNT(*) as count FROM transaksi WHERE status_pesanan = 'Pending'");
    $stats['pending_orders'] = $result->fetch_assoc()['count'];
    
    // Low stock products
    $result = $conn->query("SELECT COUNT(*) as count FROM produk WHERE stok <= 5");
    $stats['low_stock_products'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

// ===== UTILITY HELPERS =====

/**
 * Truncate text to specified length
 * @param string $text Text to truncate
 * @param int $length Length limit
 * @param string $suffix Suffix if truncated
 * @return string Truncated text
 */
function truncate($text, $length = 50, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get time ago string
 * @param string $datetime DateTime string
 * @return string Time ago text
 */
function get_time_ago($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $diff = $current - $time;
    
    if ($diff < 60) {
        return $diff . ' detik yang lalu';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit yang lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam yang lalu';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' hari yang lalu';
    } else {
        return date('d M Y', $time);
    }
}

/**
 * Format date in Indonesian
 * @param string $date Date string
 * @param string $format Format pattern
 * @return string Formatted date
 */
function format_date_id($date, $format = 'd M Y') {
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $timestamp = strtotime($date);
    return strtr(date($format, $timestamp), 
        ['Jan' => $months[0], 'Feb' => $months[1], 'Mar' => $months[2], 'Apr' => $months[3],
         'May' => $months[4], 'Jun' => $months[5], 'Jul' => $months[6], 'Aug' => $months[7],
         'Sep' => $months[8], 'Oct' => $months[9], 'Nov' => $months[10], 'Dec' => $months[11]]);
}

/**
 * Format phone number
 * @param string $phone Phone number
 * @return string Formatted phone
 */
function format_phone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    
    if (substr($phone, 0, 2) == '62') {
        return '+' . $phone;
    } elseif (substr($phone, 0, 1) == '0') {
        return '+62' . substr($phone, 1);
    }
    
    return $phone;
}

/**
 * Validate email format
 * @param string $email Email address
 * @return bool Valid or not
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 * @param int $length String length
 * @return string Random string
 */
function generate_random_string($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

?>
