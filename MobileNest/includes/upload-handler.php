<?php
/**
 * Upload Handler for MobileNest
 * Secure file upload handler for products and payment proofs
 * NOTE: Files are stored in admin/uploads/ folder, not root uploads/
 */

class UploadHandler {
    
    // Configuration - USE ABSOLUTE PATHS from config.php constants
    // These are defined in config.php
    // UPLOADS_PRODUK_PATH = /path/to/MobileNest/admin/uploads/produk
    // UPLOADS_PEMBAYARAN_PATH = /path/to/MobileNest/admin/uploads/pembayaran
    
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    
    // Allowed MIME types
    const ALLOWED_PRODUK_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const ALLOWED_PEMBAYARAN_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    
    // Allowed extensions
    const ALLOWED_PRODUK_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    const ALLOWED_PEMBAYARAN_EXT = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    
    /**
     * Upload file produk
     * 
     * @param array $file $_FILES array
     * @param int $product_id Product ID
     * @return array ['success' => bool, 'filename' => string, 'message' => string]
     */
    public static function uploadProductImage($file, $product_id) {
        return self::uploadFile(
            $file,
            defined('UPLOADS_PRODUK_PATH') ? UPLOADS_PRODUK_PATH : null,
            self::ALLOWED_PRODUK_TYPES,
            self::ALLOWED_PRODUK_EXT,
            'produk_' . $product_id,
            'produk'
        );
    }
    
    /**
     * Upload file pembayaran
     * 
     * @param array $file $_FILES array
     * @param int $transaction_id Transaction ID
     * @return array ['success' => bool, 'filename' => string, 'message' => string]
     */
    public static function uploadPaymentProof($file, $transaction_id) {
        return self::uploadFile(
            $file,
            defined('UPLOADS_PEMBAYARAN_PATH') ? UPLOADS_PEMBAYARAN_PATH : null,
            self::ALLOWED_PEMBAYARAN_TYPES,
            self::ALLOWED_PEMBAYARAN_EXT,
            'pembayaran_' . $transaction_id,
            'pembayaran'
        );
    }
    
    /**
     * Generic file upload handler
     * 
     * @param array $file $_FILES array
     * @param string $upload_dir Directory to upload to (absolute path from config.php constants)
     * @param array $allowed_mimes Allowed MIME types
     * @param array $allowed_ext Allowed extensions
     * @param string $prefix File prefix
     * @param string $type Type for getFileUrl
     * @return array ['success' => bool, 'filename' => string, 'message' => string]
     */
    private static function uploadFile($file, $upload_dir, $allowed_mimes, $allowed_ext, $prefix, $type) {
        // ✅ FIX: Validate upload_dir is defined
        if (!$upload_dir) {
            return ['success' => false, 'message' => 'Upload path not configured'];
        }
        
        // Validate input
        if (empty($file) || !isset($file['tmp_name'])) {
            return ['success' => false, 'message' => 'File tidak ditemukan'];
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar (maksimal 5MB)'];
        }
        
        // Check file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            return ['success' => false, 'message' => 'Tipe file tidak diperbolehkan. Format: ' . implode(', ', $allowed_ext)];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_mimes)) {
            return ['success' => false, 'message' => 'MIME type file tidak valid'];
        }
        
        // ✅ FIX: Create upload directory if not exists - using absolute path
        if (!is_dir($upload_dir)) {
            // Create with recursive true to handle parent directories
            if (!@mkdir($upload_dir, 0755, true)) {
                // Check if directory exists (race condition)
                if (!is_dir($upload_dir)) {
                    return ['success' => false, 'message' => 'Gagal membuat direktori upload: ' . $upload_dir];
                }
            }
        }
        
        // Generate unique filename
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $filename = $prefix . '_' . $timestamp . '_' . $random . '.' . $ext;
        $filepath = $upload_dir . '/' . $filename;
        
        // ✅ Debug: Log the actual path being used
        error_log('Upload Handler - Type: ' . $type . ', Dir: ' . $upload_dir . ', File: ' . $filepath);
        
        // Move file
        if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log('Move uploaded file failed: ' . $filepath);
            return ['success' => false, 'message' => 'Gagal mengupload file ke: ' . $filepath];
        }
        
        // Set proper permissions
        @chmod($filepath, 0644);
        
        // Verify file exists after upload
        if (!file_exists($filepath)) {
            return ['success' => false, 'message' => 'File upload verified failed'];
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'message' => 'File berhasil diupload'
        ];
    }
    
    /**
     * Delete uploaded file
     * 
     * @param string $filename Filename to delete
     * @param string $type Type of file ('produk' or 'pembayaran')
     * @return array ['success' => bool, 'message' => string]
     */
    public static function deleteFile($filename, $type = 'produk') {
        $upload_dir = ($type === 'pembayaran') ? UPLOADS_PEMBAYARAN_PATH : UPLOADS_PRODUK_PATH;
        $filepath = $upload_dir . '/' . $filename;
        
        // Security check - prevent directory traversal
        $filepath = realpath($filepath);
        $upload_dir_real = realpath($upload_dir);
        
        if ($filepath === false || strpos($filepath, $upload_dir_real) !== 0) {
            return ['success' => false, 'message' => 'Invalid file path'];
        }
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'message' => 'File tidak ditemukan'];
        }
        
        if (!unlink($filepath)) {
            return ['success' => false, 'message' => 'Gagal menghapus file'];
        }
        
        return ['success' => true, 'message' => 'File berhasil dihapus'];
    }
    
    /**
     * Get file URL (returns full URL for display)
     * Located at: /admin/uploads/produk/ or /admin/uploads/pembayaran/
     * 
     * @param string $filename Filename
     * @param string $type Type of file ('produk' or 'pembayaran')
     * @return string File URL
     */
    public static function getFileUrl($filename, $type = 'produk') {
        // ✅ Use constants from config.php if available
        if (defined('UPLOADS_PRODUK_URL') && $type === 'produk') {
            return UPLOADS_PRODUK_URL . $filename;
            // Returns: http://localhost/MobileNest/admin/uploads/produk/produk_123.jpg
        }
        if (defined('UPLOADS_PEMBAYARAN_URL') && $type === 'pembayaran') {
            return UPLOADS_PEMBAYARAN_URL . $filename;
            // Returns: http://localhost/MobileNest/admin/uploads/pembayaran/pembayaran_123.jpg
        }
        
        // Fallback if constant not defined
        $subdir = ($type === 'pembayaran') ? 'pembayaran' : 'produk';
        
        // Try to determine SITE_URL from current request
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        
        // Build base URL by removing everything after /admin or /MobileNest
        $script_name = $_SERVER['SCRIPT_NAME'];  // e.g., /MobileNest/admin/kelola-produk.php
        
        // Extract site root
        if (strpos($script_name, '/admin/') !== false) {
            $base_url = substr($script_name, 0, strpos($script_name, '/admin/'));
        } else {
            $base_url = dirname(dirname($script_name));
        }
        
        return $protocol . $host . $base_url . '/admin/uploads/' . $subdir . '/' . $filename;
    }
}
?>