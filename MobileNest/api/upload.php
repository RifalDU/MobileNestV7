<?php
/**
 * Upload API Endpoint
 * Handles file uploads for products and payment proofs
 */

header('Content-Type: application/json');
include '../config.php';
include '../includes/upload-handler.php';

$response = ['success' => false, 'message' => ''];
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($request_method !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    if (empty($action)) {
        throw new Exception('Action not specified');
    }
    
    // Handle product image upload
    if ($action === 'upload_product') {
        if (!isset($_FILES['image'])) {
            throw new Exception('File tidak ditemukan');
        }
        
        if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
            throw new Exception('Product ID diperlukan');
        }
        
        $product_id = intval($_POST['product_id']);
        $result = UploadHandler::uploadProductImage($_FILES['image'], $product_id);
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }
        
        // Optional: Update database with filename
        try {
            $stmt = $conn->prepare("UPDATE produk SET gambar = ? WHERE id_produk = ? LIMIT 1");
            $stmt->bind_param('si', $result['filename'], $product_id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Log error but don't fail - file was uploaded successfully
            error_log('Database update failed: ' . $e->getMessage());
        }
        
        $response = [
            'success' => true,
            'message' => 'Gambar produk berhasil diupload',
            'filename' => $result['filename'],
            'url' => UploadHandler::getFileUrl($result['filename'])
        ];
    }
    
    // Handle payment proof upload
    elseif ($action === 'upload_payment') {
        if (!isset($_FILES['proof'])) {
            throw new Exception('File tidak ditemukan');
        }
        
        if (!isset($_POST['transaction_id']) || empty($_POST['transaction_id'])) {
            throw new Exception('Transaction ID diperlukan');
        }
        
        $transaction_id = intval($_POST['transaction_id']);
        $result = UploadHandler::uploadPaymentProof($_FILES['proof'], $transaction_id);
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }
        
        // Optional: Update database with filename
        try {
            $stmt = $conn->prepare("UPDATE transaksi SET bukti_pembayaran = ? WHERE id_transaksi = ? LIMIT 1");
            $stmt->bind_param('si', $result['filename'], $transaction_id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Log error but don't fail
            error_log('Database update failed: ' . $e->getMessage());
        }
        
        $response = [
            'success' => true,
            'message' => 'Bukti pembayaran berhasil diupload',
            'filename' => $result['filename'],
            'url' => UploadHandler::getFileUrl($result['filename'], 'pembayaran')
        ];
    }
    
    else {
        throw new Exception('Action tidak dikenali: ' . htmlspecialchars($action));
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code(400);
}

echo json_encode($response);
?>