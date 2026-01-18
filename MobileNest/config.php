<?php
/**
 * MobileNest - Database Configuration & Global Setup
 * Koneksi MySQLi dan konfigurasi umum untuk seluruh aplikasi
 * Updated: Database name changed to mobilenest_db
 * Last Updated: January 8, 2026
 */


// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}


// ===== DATABASE CONNECTION =====
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';  // Password kosong jika default XAMPP
$db_name = 'mobilenest_db';  // ✅ Updated to match actual database name


// Create MySQLi connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);


// Check connection
if ($conn->connect_errno) {
    die('Database connection failed: ' . $conn->connect_error);
}


// Set charset to UTF-8
$conn->set_charset('utf8mb4');


// ===== SESSION CONFIGURATION =====
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // set to 1 if using HTTPS
    session_start();
}


// ===== GLOBAL CONSTANTS =====
define('SITE_NAME', 'MobileNest');
define('SITE_URL', 'http://localhost/MobileNest');
define('ADMIN_PATH', __DIR__ . '/admin');
define('UPLOADS_PATH', __DIR__ . '/uploads');

// ===== UPLOAD URLs (CORRECTED) =====
// NOTE: Gambar produk sebenarnya disimpan di admin/uploads/produk
define('UPLOADS_PRODUK_PATH', ADMIN_PATH . '/uploads/produk');
define('UPLOADS_PEMBAYARAN_PATH', ADMIN_PATH . '/uploads/pembayaran');
define('UPLOADS_PRODUK_URL', SITE_URL . '/admin/uploads/produk/');
define('UPLOADS_PEMBAYARAN_URL', SITE_URL . '/admin/uploads/pembayaran/');


// ===== HELPER FUNCTIONS =====


/**
 * Sanitize input to prevent XSS
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}


/**
 * Format currency to Rupiah
 */
function format_rupiah($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}


/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['admin']) && !empty($_SESSION['admin']);
}


/**
 * Check if user is logged in
 */
function is_logged_in() {
    return (isset($_SESSION['admin']) && !empty($_SESSION['admin'])) || 
           (isset($_SESSION['user']) && !empty($_SESSION['user']));
}


/**
 * Redirect to login if not authenticated
 */
function require_login($is_admin = false) {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/user/login.php');
        exit;
    }
    if ($is_admin && !is_admin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}


/**
 * Get user info from session
 */
function get_user_info() {
    if (isset($_SESSION['admin'])) {
        return [
            'id' => $_SESSION['admin'],
            'role' => 'admin',
            'username' => $_SESSION['admin_username'] ?? 'Admin'
        ];
    }
    if (isset($_SESSION['user'])) {
        return [
            'id' => $_SESSION['user'],
            'role' => 'user',
            'username' => $_SESSION['user_name'] ?? 'User'
        ];
    }
    return null;
}


/**
 * Escape SQL input (additional safety)
 */
function escape_input($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}


/**
 * Execute query and return result
 */
function execute_query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        error_log('Query Error: ' . $conn->error);
        return false;
    }
    return $result;
}


/**
 * Fetch single row as associative array
 */
function fetch_single($sql) {
    $result = execute_query($sql);
    return $result ? $result->fetch_assoc() : null;
}


/**
 * Fetch all rows as array
 */
function fetch_all($sql) {
    $result = execute_query($sql);
    if (!$result) return [];
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}


/**
 * Log activity to database (optional - buat tabel activity jika diperlukan)
 */
function log_activity($action, $description = '') {
    global $conn;
    $user_id = isset($_SESSION['admin']) ? $_SESSION['admin'] : (isset($_SESSION['user']) ? $_SESSION['user'] : 0);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('isss', $user_id, $action, $description, $ip);
        $stmt->execute();
        $stmt->close();
    }
}


/**
 * Handle file upload securely - FIXED to use correct upload path
 */
function upload_file($file_input, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880, $upload_type = 'produk') {
    if (!isset($_FILES[$file_input])) {
        return ['success' => false, 'message' => 'File not found'];
    }


    $file = $_FILES[$file_input];
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_error = $file['error'];


    // Check for upload errors
    if ($file_error !== 0) {
        return ['success' => false, 'message' => 'Upload error'];
    }


    // Check file size
    if ($file_size > $max_size) {
        return ['success' => false, 'message' => 'File too large'];
    }


    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }


    // ✅ FIX: Use correct upload path based on type
    if ($upload_type === 'pembayaran') {
        $upload_dir = UPLOADS_PEMBAYARAN_PATH;
        $upload_url = UPLOADS_PEMBAYARAN_URL;
    } else {
        $upload_dir = UPLOADS_PRODUK_PATH;
        $upload_url = UPLOADS_PRODUK_URL;
    }


    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . '/' . $new_filename;


    // Create uploads directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }


    // Move uploaded file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $upload_path,
            'url' => $upload_url . $new_filename
        ];
    }


    return ['success' => false, 'message' => 'Upload failed'];
}


/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


/**
 * Destroy session and logout
 */
function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}


/**
 * Check if database connection is active
 */
function isDatabaseConnected() {
    global $conn;
    return isset($conn) && $conn && !$conn->connect_error;
}


// ===== ERROR HANDLING =====
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);


// Custom error handler (optional)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile on line $errline");
    return true;
});


// Shutdown handler untuk mencegah white screen of death
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error) {
        error_log("Fatal Error: " . print_r($error, true));
    }
});


// Close connection on script end
register_shutdown_function(function() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
});


?>