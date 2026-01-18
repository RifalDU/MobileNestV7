<?php
/**
 * Checkout Handler API
 * 
 * Purpose: Process checkout transaction
 * - Create transaction record in transaksi table
 * - Create shipping record in pengiriman table
 * - Create transaction details from cart
 * - Clear cart
 * 
 * Endpoint: POST /api/checkout-handler.php
 * 
 * Required Fields:
 * - nama_penerima (string)
 * - no_telepon (string)
 * - email (string)
 * - provinsi (string)
 * - kota (string)
 * - kecamatan (string)
 * - kode_pos (string)
 * - alamat_lengkap (text)
 * - metode_pengiriman (enum: regular|express|same_day)
 * - catatan (text, optional)
 * 
 * Response:
 * {
 *   "success": true|false,
 *   "id_transaksi": number (if success),
 *   "kode_transaksi": string (if success),
 *   "total_harga": number (if success),
 *   "message": string
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once '../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Check user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Required fields validation
$required_fields = [
    'nama_penerima',
    'no_telepon',
    'email',
    'provinsi',
    'kota',
    'kecamatan',
    'kode_pos',
    'alamat_lengkap',
    'metode_pengiriman'
];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Field '$field' is required"
        ]);
        exit;
    }
}

// Validate shipping method
$valid_methods = ['regular', 'express', 'same_day'];
$metode_pengiriman = $_POST['metode_pengiriman'];
if (!in_array($metode_pengiriman, $valid_methods)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid shipping method'
    ]);
    exit;
}

// Validate email format
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Sanitize inputs
$nama_penerima = $conn->real_escape_string(trim($_POST['nama_penerima']));
$no_telepon = $conn->real_escape_string(trim($_POST['no_telepon']));
$email = $conn->real_escape_string(trim($_POST['email']));
$provinsi = $conn->real_escape_string(trim($_POST['provinsi']));
$kota = $conn->real_escape_string(trim($_POST['kota']));
$kecamatan = $conn->real_escape_string(trim($_POST['kecamatan']));
$kode_pos = $conn->real_escape_string(trim($_POST['kode_pos']));
$alamat_lengkap = $conn->real_escape_string(trim($_POST['alamat_lengkap']));
$catatan = isset($_POST['catatan']) ? $conn->real_escape_string(trim($_POST['catatan'])) : '';

try {
    // START DATABASE TRANSACTION
    $conn->begin_transaction();

    // ============================================
    // STEP 1: Get cart items and calculate totals
    // ============================================
    $query_cart = "SELECT k.*, p.nama_produk, p.harga, p.stok 
                   FROM keranjang k 
                   JOIN produk p ON k.id_produk = p.id_produk 
                   WHERE k.id_user = ?";
    
    $stmt_cart = $conn->prepare($query_cart);
    if (!$stmt_cart) {
        throw new Exception('Prepare cart failed: ' . $conn->error);
    }
    
    $stmt_cart->bind_param('i', $user_id);
    $stmt_cart->execute();
    $cart_result = $stmt_cart->get_result();
    $cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);

    // Check if cart is empty
    if (empty($cart_items)) {
        throw new Exception('Cart is empty');
    }

    // ============================================
    // STEP 2: Validate stock and calculate subtotal
    // ============================================
    $subtotal = 0;
    foreach ($cart_items as $item) {
        // Check if requested quantity is available
        if ($item['stok'] < $item['jumlah']) {
            throw new Exception(
                "Stock insufficient for '{$item['nama_produk']}'. "
                . "Available: {$item['stok']}, Requested: {$item['jumlah']}"
            );
        }
        $subtotal += (int)($item['harga']) * (int)($item['jumlah']);
    }

    // ============================================
    // STEP 3: Calculate shipping cost (ongkir)
    // ============================================
    $shipping_costs = [
        'regular' => 20000,
        'express' => 50000,
        'same_day' => 100000
    ];
    $ongkir = $shipping_costs[$metode_pengiriman] ?? 20000;

    // ============================================
    // STEP 4: Calculate total (dengan ongkir)
    // Total = Subtotal + Ongkir (diskon belum diimplementasikan)
    // ============================================
    $total_harga = $subtotal + $ongkir;

    // ============================================
    // STEP 5: Create transaction record in transaksi
    // Use kode_transaksi as unique reference
    // ============================================
    $kode_transaksi = 'TRX-' . date('YmdHis') . '-' . rand(10000, 99999);
    $status_pesanan = 'Menunggu Verifikasi';
    $metode_pembayaran = ''; // Will be set during payment step
    
    // Alamat pengiriman disimpan di transaksi untuk referensi
    $alamat_ref = "$alamat_lengkap, $kecamatan, $kota, $provinsi $kode_pos";
    
    $query_transaksi = "INSERT INTO transaksi 
                        (id_user, kode_transaksi, total_harga, status_pesanan, 
                         metode_pembayaran, alamat_pengiriman, tanggal_transaksi, catatan_user)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    $stmt_transaksi = $conn->prepare($query_transaksi);
    if (!$stmt_transaksi) {
        throw new Exception('Prepare transaksi failed: ' . $conn->error);
    }
    
    // Bind parameters: i=integer, s=string
    $stmt_transaksi->bind_param(
        'isidsss',
        $user_id,
        $kode_transaksi,
        $total_harga,
        $status_pesanan,
        $metode_pembayaran,
        $alamat_ref,
        $catatan
    );
    
    if (!$stmt_transaksi->execute()) {
        throw new Exception('Execute transaksi failed: ' . $stmt_transaksi->error);
    }
    
    $id_transaksi = $conn->insert_id;
    if ($id_transaksi === 0) {
        throw new Exception('Failed to create transaction');
    }

    // ============================================
    // STEP 6: Create detail_transaksi for each item
    // ============================================
    $query_detail = "INSERT INTO detail_transaksi 
                     (id_transaksi, id_produk, jumlah, harga_satuan, subtotal)
                     VALUES (?, ?, ?, ?, ?)";
    
    $stmt_detail = $conn->prepare($query_detail);
    if (!$stmt_detail) {
        throw new Exception('Prepare detail failed: ' . $conn->error);
    }
    
    foreach ($cart_items as $item) {
        $item_subtotal = (int)($item['harga']) * (int)($item['jumlah']);
        
        $stmt_detail->bind_param(
            'iiiii',
            $id_transaksi,
            $item['id_produk'],
            $item['jumlah'],
            $item['harga'],
            $item_subtotal
        );
        
        if (!$stmt_detail->execute()) {
            throw new Exception('Execute detail failed: ' . $stmt_detail->error);
        }
    }

    // ============================================
    // STEP 7: Create pengiriman record
    // Store shipping address and ongkir separately
    // ============================================
    $no_pengiriman = 'SHIP-' . date('YmdHis') . '-' . rand(10000, 99999);
    $status_pengiriman = 'Menunggu Pickup';
    
    $query_pengiriman = "INSERT INTO pengiriman 
                         (id_user, no_pengiriman, nama_penerima, no_telepon, email, 
                          provinsi, kota, kecamatan, kode_pos, alamat_lengkap, 
                          metode_pengiriman, ongkir, catatan, status_pengiriman, 
                          tanggal_pengiriman)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt_pengiriman = $conn->prepare($query_pengiriman);
    if (!$stmt_pengiriman) {
        throw new Exception('Prepare pengiriman failed: ' . $conn->error);
    }
    
    // Bind parameters for pengiriman
    // i = integer, s = string
    $stmt_pengiriman->bind_param(
        'issssssssssiss',
        $user_id,
        $no_pengiriman,
        $nama_penerima,
        $no_telepon,
        $email,
        $provinsi,
        $kota,
        $kecamatan,
        $kode_pos,
        $alamat_lengkap,
        $metode_pengiriman,
        $ongkir,
        $catatan,
        $status_pengiriman
    );
    
    if (!$stmt_pengiriman->execute()) {
        throw new Exception('Execute pengiriman failed: ' . $stmt_pengiriman->error);
    }

    // ✅ FIX: Get the id_pengiriman that was just created and set in session
    $id_pengiriman = $conn->insert_id;
    if ($id_pengiriman === 0) {
        throw new Exception('Failed to get pengiriman ID');
    }

    // ============================================
    // STEP 8: Clear cart
    // ============================================
    $query_clear = "DELETE FROM keranjang WHERE id_user = ?";
    $stmt_clear = $conn->prepare($query_clear);
    if (!$stmt_clear) {
        throw new Exception('Prepare clear failed: ' . $conn->error);
    }
    
    $stmt_clear->bind_param('i', $user_id);
    if (!$stmt_clear->execute()) {
        throw new Exception('Execute clear failed: ' . $stmt_clear->error);
    }

    // ============================================
    // COMMIT TRANSACTION
    // ============================================
    $conn->commit();

    // ✅ FIX: Set ALL required session data
    $_SESSION['checkout_id'] = $id_transaksi;
    $_SESSION['checkout_code'] = $kode_transaksi;
    $_SESSION['checkout_total'] = $total_harga;
    $_SESSION['id_pengiriman'] = $id_pengiriman;  // ✅ SET id_pengiriman
    $_SESSION['ongkir'] = $ongkir;                 // ✅ SET ongkir
    $_SESSION['subtotal'] = $subtotal;             // ✅ SET subtotal

    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id_transaksi' => $id_transaksi,
        'kode_transaksi' => $kode_transaksi,
        'total_harga' => $total_harga,
        'subtotal' => $subtotal,
        'ongkir' => $ongkir,
        'message' => 'Checkout successful, proceeding to payment'
    ]);

} catch (Exception $e) {
    // ROLLBACK TRANSACTION on error
    $conn->rollback();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Checkout failed: ' . $e->getMessage()
    ]);

} finally {
    // Close statements
    if (isset($stmt_cart)) $stmt_cart->close();
    if (isset($stmt_transaksi)) $stmt_transaksi->close();
    if (isset($stmt_detail)) $stmt_detail->close();
    if (isset($stmt_pengiriman)) $stmt_pengiriman->close();
    if (isset($stmt_clear)) $stmt_clear->close();
    
    // Close connection
    $conn->close();
}

?>