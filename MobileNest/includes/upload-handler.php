<?php
/**
 * Upload Handler for MobileNest
 * Secure file upload handler for products and payment proofs
 */

class UploadHandler {
    
    // Configuration
    const UPLOAD_DIR_PRODUK = 'uploads/produk/';
    const UPLOAD_DIR_PEMBAYARAN = 'uploads/pembayaran/';
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
            self::UPLOAD_DIR_PRODUK,
            self::ALLOWED_PRODUK_TYPES,
            self::ALLOWED_PRODUK_EXT,
            'produk_' . $product_id
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
            self::UPLOAD_DIR_PEMBAYARAN,
            self::ALLOWED_PEMBAYARAN_TYPES,
            self::ALLOWED_PEMBAYARAN_EXT,
            'pembayaran_' . $transaction_id
        );
    }
    
    /**
     * Generic file upload handler
     * 
     * @param array $file $_FILES array
     * @param string $upload_dir Directory to upload to
     * @param array $allowed_mimes Allowed MIME types
     * @param array $allowed_ext Allowed extensions
     * @param string $prefix File prefix
     * @return array ['success' => bool, 'filename' => string, 'message' => string]
     */
    private static function uploadFile($file, $upload_dir, $allowed_mimes, $allowed_ext, $prefix) {
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
        
        // Create upload directory if not exists
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return ['success' => false, 'message' => 'Gagal membuat direktori upload'];
            }
        }
        
        // Generate unique filename
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $filename = $prefix . '_' . $timestamp . '_' . $random . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'message' => 'Gagal mengupload file'];
        }
        
        // Set proper permissions
        chmod($filepath, 0644);
        
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
        $upload_dir = ($type === 'pembayaran') ? self::UPLOAD_DIR_PEMBAYARAN : self::UPLOAD_DIR_PRODUK;
        $filepath = $upload_dir . $filename;
        
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
     * Get file URL
     * 
     * @param string $filename Filename
     * @param string $type Type of file ('produk' or 'pembayaran')
     * @return string File URL
     */
    public static function getFileUrl($filename, $type = 'produk') {
        $upload_dir = ($type === 'pembayaran') ? self::UPLOAD_DIR_PEMBAYARAN : self::UPLOAD_DIR_PRODUK;
        return $upload_dir . $filename;
    }
}
?>