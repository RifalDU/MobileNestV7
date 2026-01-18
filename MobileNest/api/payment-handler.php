<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';
require_once 'response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '../logs/payment_debug.log');

try {
    // 🔍 DEBUG: Log incoming data
    error_log('\n\n=== PAYMENT HANDLER START ===');
    error_log('POST data: ' . json_encode($_POST));
    error_log('FILES: ' . json_encode(array_keys($_FILES)));
    error_log('SESSION keys: ' . json_encode(array_keys($_SESSION)));
    error_log('Session id_pengiriman: ' . ($_SESSION['id_pengiriman'] ?? 'NOT SET'));
    error_log('Session user_id: ' . ($_SESSION['user_id'] ?? 'NOT SET'));
    
    // Check session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Session user_id not set');
    }

    // 🔍 FIX: Accept id_pengiriman from POST if session doesn't have it
    $user_id = $_SESSION['user_id'];
    $id_pengiriman = $_SESSION['id_pengiriman'] ?? (isset($_POST['id_pengiriman']) ? intval($_POST['id_pengiriman']) : 0);
    $id_transaksi = isset($_POST['id_transaksi']) ? intval($_POST['id_transaksi']) : 0;

    error_log('Resolved id_pengiriman: ' . $id_pengiriman);
    error_log('Resolved id_transaksi: ' . $id_transaksi);

    if ($id_transaksi === 0) {
        throw new Exception('ID transaksi tidak valid');
    }

    if ($id_pengiriman === 0) {
        throw new Exception('ID pengiriman tidak valid atau session expired. Silakan reload halaman pembayaran.');
    }

    // Validate input
    $metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');

    error_log('Metode: ' . $metode_pembayaran);

    if (empty($metode_pembayaran)) {
        throw new Exception('Metode pembayaran harus dipilih');
    }

    // Validate file upload
    if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['bukti_pembayaran']['error'] ?? 'unknown';
        throw new Exception('File upload gagal. Error code: ' . $error_code);
    }

    $file = $_FILES['bukti_pembayaran'];

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File terlalu besar (max 5MB). Size: ' . ($file['size'] / 1024 / 1024) . 'MB');
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    error_log('File mime type: ' . $mime_type);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Format file hanya JPG atau PNG. Got: ' . $mime_type);
    }

    // Create uploads directory if not exists
    $upload_dir = '../uploads/pembayaran';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Gagal membuat folder upload: ' . $upload_dir);
        }
    }

    // Generate filename
    $file_ext = $mime_type === 'image/jpeg' ? 'jpg' : 'png';
    $filename = 'pembayaran_' . $user_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . '/' . $filename;

    error_log('Uploading to: ' . $filepath);

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Gagal menyimpan file: ' . $filepath);
    }

    error_log('File uploaded successfully: ' . $filepath);

    // Get transaction details
    $query_trans = "SELECT * FROM transaksi WHERE id_transaksi = ? AND id_user = ?";
    $stmt_trans = $conn->prepare($query_trans);
    if (!$stmt_trans) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt_trans->bind_param('ii', $id_transaksi, $user_id);
    $stmt_trans->execute();
    $result_trans = $stmt_trans->get_result();
    $transaksi = $result_trans->fetch_assoc();
    $stmt_trans->close();

    if (!$transaksi) {
        throw new Exception('Transaksi tidak ditemukan: ' . $id_transaksi);
    }

    error_log('Transaction found: ' . json_encode($transaksi));

    // Get transaction items
    $query_items = "SELECT * FROM detail_transaksi WHERE id_transaksi = ?";
    $stmt_items = $conn->prepare($query_items);
    if (!$stmt_items) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt_items->bind_param('i', $id_transaksi);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $items = $result_items->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    if (empty($items)) {
        throw new Exception('Tidak ada item dalam transaksi: ' . $id_transaksi);
    }

    error_log('Items found: ' . count($items));

    // Get shipping details
    $query_shipping = "SELECT * FROM pengiriman WHERE id_pengiriman = ?";
    $stmt_shipping = $conn->prepare($query_shipping);
    if (!$stmt_shipping) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt_shipping->bind_param('i', $id_pengiriman);
    $stmt_shipping->execute();
    $result_shipping = $stmt_shipping->get_result();
    $shipping = $result_shipping->fetch_assoc();
    $stmt_shipping->close();

    if (!$shipping) {
        throw new Exception('Data pengiriman tidak ditemukan: ' . $id_pengiriman);
    }

    error_log('Shipping found: ' . json_encode($shipping));

    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += intval($item['subtotal']);
    }
    $ongkir = intval($shipping['ongkir'] ?? 0);
    $total = $subtotal + $ongkir;

    error_log('Totals - Subtotal: ' . $subtotal . ', Ongkir: ' . $ongkir . ', Total: ' . $total);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Generate order number
        $no_pesanan = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);

        // ✅ UPDATE: Sesuai dengan schema DATABASE_SCHEMA-1.md
        // ✅ FIX: Gunakan tanggal_diperabarui (bukan tanggal_diperbarui) - ada "a" di tengah!
        $query_update = "UPDATE transaksi SET 
                            metode_pembayaran = ?,
                            bukti_pembayaran = ?,
                            status_pesanan = ?,
                            tanggal_diperbarui = NOW()
                        WHERE id_transaksi = ? AND id_user = ?";

        $stmt_update = $conn->prepare($query_update);
        if (!$stmt_update) {
            throw new Exception('Prepare update error: ' . $conn->error);
        }

        $status = 'Menunggu Verifikasi';
        $stmt_update->bind_param(
            'sssii',
            $metode_pembayaran,
            $filename,
            $status,
            $id_transaksi,
            $user_id
        );

        error_log('Executing update query...');
        if (!$stmt_update->execute()) {
            throw new Exception('Update transaksi error: ' . $stmt_update->error);
        }
        
        $rows_affected = $stmt_update->affected_rows;
        error_log('Update transaksi rows affected: ' . $rows_affected);
        $stmt_update->close();

        // Clear cart
        $query_clear = "DELETE FROM keranjang WHERE id_user = ?";
        $stmt_clear = $conn->prepare($query_clear);
        if (!$stmt_clear) {
            throw new Exception('Prepare clear error: ' . $conn->error);
        }
        $stmt_clear->bind_param('i', $user_id);
        error_log('Clearing cart for user: ' . $user_id);
        if (!$stmt_clear->execute()) {
            throw new Exception('Clear cart error: ' . $stmt_clear->error);
        }
        error_log('Cart cleared. Rows deleted: ' . $stmt_clear->affected_rows);
        $stmt_clear->close();

        // Update pengiriman status - gunakan tanggal_konfirmasi sesuai schema
        $query_pengiriman = "UPDATE pengiriman SET tanggal_konfirmasi = NOW() WHERE id_pengiriman = ?";
        $stmt_pengiriman = $conn->prepare($query_pengiriman);
        if (!$stmt_pengiriman) {
            throw new Exception('Prepare pengiriman error: ' . $conn->error);
        }
        $stmt_pengiriman->bind_param('i', $id_pengiriman);
        error_log('Updating pengiriman: ' . $id_pengiriman);
        if (!$stmt_pengiriman->execute()) {
            throw new Exception('Update pengiriman error: ' . $stmt_pengiriman->error);
        }
        error_log('Pengiriman updated. Rows affected: ' . $stmt_pengiriman->affected_rows);
        $stmt_pengiriman->close();

        // Commit transaction
        $conn->commit();
        error_log('Transaction committed successfully');

        // Clear session payment data
        unset($_SESSION['id_pengiriman']);
        unset($_SESSION['ongkir']);
        unset($_SESSION['subtotal']);
        unset($_SESSION['checkout_id']);
        unset($_SESSION['checkout_code']);
        unset($_SESSION['checkout_total']);

        error_log('[SUCCESS] Payment confirmed: id_transaksi=' . $id_transaksi . ', no_pesanan=' . $no_pesanan);
        error_log('=== PAYMENT HANDLER END ===\n');

        echo json_encode([
            'success' => true,
            'message' => 'Pembayaran berhasil dikonfirmasi',
            'id_transaksi' => $id_transaksi,
            'no_pesanan' => $no_pesanan,
            'redirect' => '../user/pesanan.php'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log('Transaction rolled back: ' . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log('[ERROR] Payment handler - ' . $e->getMessage());
    error_log('=== PAYMENT HANDLER END ===\n');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>