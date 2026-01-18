<?php
/**
 * Transactions API Endpoint
 * Handles: Create transaction, Get transaction, Update status, Get user transactions
 */

require_once '../config.php';
require_once 'response.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'getUserTransactions';

if ($method === 'GET') {
    if ($action === 'getUserTransactions') {
        getUserTransactions();
    } elseif ($action === 'getById' && isset($_GET['id'])) {
        getTransactionById($_GET['id']);
    } else {
        APIResponse::error('Invalid action', 400);
    }
} elseif ($method === 'POST') {
    if ($action === 'create') {
        createTransaction();
    } elseif ($action === 'updateStatus') {
        updateTransactionStatus();
    } else {
        APIResponse::error('Invalid action', 400);
    }
} else {
    APIResponse::error('Method not allowed', 405);
}

/**
 * Get user transactions
 */
function getUserTransactions() {
    if (!is_logged_in()) {
        APIResponse::unauthorized('Please login to view transactions');
    }
    
    global $conn;
    
    $user_id = $_SESSION['user'];
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $conn->prepare('SELECT COUNT(*) as total FROM transaksi WHERE id_user = ?');
    $countStmt->bind_param('i', $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get transactions
    $stmt = $conn->prepare(
        'SELECT * FROM transaksi WHERE id_user = ? ORDER BY tanggal_transaksi DESC LIMIT ? OFFSET ?'
    );
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = formatTransactionData($row);
    }
    $stmt->close();
    
    APIResponse::success([
        'transactions' => $transactions,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Transactions retrieved successfully');
}

/**
 * Get single transaction by ID
 */
function getTransactionById($id) {
    if (!is_logged_in()) {
        APIResponse::unauthorized('Please login');
    }
    
    global $conn;
    
    $user_id = $_SESSION['user'];
    $transaction_id = intval($id);
    
    // Get transaction
    $stmt = $conn->prepare('SELECT * FROM transaksi WHERE id_transaksi = ? AND id_user = ?');
    $stmt->bind_param('ii', $transaction_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        APIResponse::notFound('Transaction not found');
    }
    
    $transaction = $result->fetch_assoc();
    $stmt->close();
    
    // Get transaction details/items
    $itemStmt = $conn->prepare(
        'SELECT dt.*, p.nama_produk, p.harga as harga_produk FROM detail_transaksi dt
         LEFT JOIN produk p ON dt.id_produk = p.id_produk
         WHERE dt.id_transaksi = ?'
    );
    $itemStmt->bind_param('i', $transaction_id);
    $itemStmt->execute();
    $itemsResult = $itemStmt->get_result();
    
    $items = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $items[] = [
            'id_produk' => $row['id_produk'],
            'nama_produk' => $row['nama_produk'],
            'jumlah' => $row['jumlah'],
            'harga_satuan' => (float)$row['harga_satuan'],
            'subtotal' => (float)$row['subtotal']
        ];
    }
    $itemStmt->close();
    
    $transactionData = formatTransactionData($transaction);
    $transactionData['items'] = $items;
    
    APIResponse::success($transactionData, 'Transaction details retrieved');
}

/**
 * Create new transaction from cart
 */
function createTransaction() {
    if (!is_logged_in()) {
        APIResponse::unauthorized('Please login to create transaction');
    }
    
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $errors = [];
    if (empty($data['alamat_pengiriman'])) $errors['alamat_pengiriman'] = 'Shipping address is required';
    if (empty($data['metode_pembayaran'])) $errors['metode_pembayaran'] = 'Payment method is required';
    
    if (!empty($errors)) {
        APIResponse::validationError($errors);
    }
    
    // Check if cart is not empty
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        APIResponse::error('Cart is empty', 400);
    }
    
    $user_id = $_SESSION['user'];
    $alamat = $conn->real_escape_string($data['alamat_pengiriman']);
    $metode = $conn->real_escape_string($data['metode_pembayaran']);
    $catatan = isset($data['catatan_user']) ? $conn->real_escape_string($data['catatan_user']) : '';
    $total_harga = 0;
    
    $conn->begin_transaction();
    
    try {
        // Calculate total and check stock
        $cart_items = [];
        foreach ($_SESSION['cart'] as $item) {
            $product_id = $item['id_produk'];
            $quantity = $item['jumlah'];
            
            $stmt = $conn->prepare('SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk = ?');
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Product not found: ' . $product_id);
            }
            
            $product = $result->fetch_assoc();
            
            if ($product['stok'] < $quantity) {
                throw new Exception('Insufficient stock for: ' . $product['nama_produk']);
            }
            
            $subtotal = $product['harga'] * $quantity;
            $total_harga += $subtotal;
            
            $cart_items[] = [
                'id_produk' => $product_id,
                'jumlah' => $quantity,
                'harga_satuan' => $product['harga'],
                'subtotal' => $subtotal
            ];
            
            $stmt->close();
        }
        
        // Generate transaction code
        $kode_transaksi = 'TRX-' . date('YmdHis') . '-' . $user_id;
        
        // Insert transaction
        $stmt = $conn->prepare(
            'INSERT INTO transaksi (id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, kode_transaksi, catatan_user)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        
        $status = 'Menunggu Pembayaran';
        $stmt->bind_param('idsssss', $user_id, $total_harga, $status, $metode, $alamat, $kode_transaksi, $catatan);
        $stmt->execute();
        
        $transaction_id = $conn->insert_id;
        $stmt->close();
        
        // Insert transaction details and update stock
        foreach ($cart_items as $item) {
            // Insert detail transaksi
            $detailStmt = $conn->prepare(
                'INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga_satuan, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            );
            
            $detailStmt->bind_param(
                'iiidi',
                $transaction_id,
                $item['id_produk'],
                $item['jumlah'],
                $item['harga_satuan'],
                $item['subtotal']
            );
            $detailStmt->execute();
            $detailStmt->close();
            
            // Update stock
            $updateStmt = $conn->prepare('UPDATE produk SET stok = stok - ? WHERE id_produk = ?');
            $updateStmt->bind_param('ii', $item['jumlah'], $item['id_produk']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        $conn->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        APIResponse::success([
            'transaction_id' => $transaction_id,
            'kode_transaksi' => $kode_transaksi,
            'total' => (float)$total_harga,
            'status' => $status
        ], 'Transaction created successfully', 201);
        
    } catch (Exception $e) {
        $conn->rollback();
        APIResponse::error('Transaction failed: ' . $e->getMessage(), 400);
    }
}

/**
 * Update transaction status (Admin only)
 */
function updateTransactionStatus() {
    if (!is_admin()) {
        APIResponse::unauthorized('Admin access required');
    }
    
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $errors = [];
    if (empty($data['id_transaksi'])) $errors['id_transaksi'] = 'Transaction ID is required';
    if (empty($data['status_pesanan'])) $errors['status_pesanan'] = 'Status is required';
    
    if (!empty($errors)) {
        APIResponse::validationError($errors);
    }
    
    $transaction_id = intval($data['id_transaksi']);
    $status = $conn->real_escape_string($data['status_pesanan']);
    $no_resi = isset($data['no_resi']) ? $conn->real_escape_string($data['no_resi']) : null;
    
    $stmt = $conn->prepare('UPDATE transaksi SET status_pesanan = ? WHERE id_transaksi = ?');
    $stmt->bind_param('si', $status, $transaction_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Update no_resi if provided
        if (!empty($no_resi)) {
            $updateStmt = $conn->prepare('UPDATE transaksi SET no_resi_awal = ? WHERE id_transaksi = ?');
            $updateStmt->bind_param('si', $no_resi, $transaction_id);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        APIResponse::success(null, 'Transaction status updated successfully');
    } else {
        APIResponse::serverError('Failed to update transaction: ' . $conn->error);
    }
}

/**
 * Format transaction data
 */
function formatTransactionData($transaction) {
    return [
        'id' => $transaction['id_transaksi'],
        'kode_transaksi' => $transaction['kode_transaksi'] ?? 'N/A',
        'total' => (float)$transaction['total_harga'],
        'status' => $transaction['status_pesanan'],
        'metode_pembayaran' => $transaction['metode_pembayaran'],
        'alamat_pengiriman' => $transaction['alamat_pengiriman'],
        'no_resi' => $transaction['no_resi_awal'] ?? null,
        'catatan' => $transaction['catatan_user'] ?? '',
        'tanggal_transaksi' => $transaction['tanggal_transaksi'],
        'tanggal_diperbaharui' => $transaction['tanggal_dipebaharui']
    ];
}

?>