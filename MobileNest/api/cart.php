<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// Simple debug log function
function debug_log($message) {
    $log_file = __DIR__ . '/../logs/cart_debug.log';
    $dir = dirname($log_file);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) session_start();

debug_log('=== NEW REQUEST === ' . json_encode($_REQUEST));

// Cek User ID
function getCurrentUserId() {
    global $conn;
    
    debug_log('Getting current user ID...');
    
    // Get user info
    $user_info = get_user_info();
    debug_log('get_user_info() returned: ' . json_encode($user_info));
    
    if ($user_info && isset($user_info['id'])) {
        debug_log('Using logged-in user ID: ' . $user_info['id']);
        return $user_info['id'];
    }

    debug_log('No user found');
    return null;
}

$user_id = getCurrentUserId();
debug_log('Final user_id: ' . $user_id);

if (!$user_id) {
    debug_log('ERROR: Failed to get user_id');
    echo json_encode(['success' => false, 'message' => 'User session not found']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
debug_log('Action: ' . $action);

// --- LOGIC ADD TO CART ---
if ($action === 'add') {
    debug_log('Processing ADD action');
    
    $input = json_decode(file_get_contents('php://input'), true);
    debug_log('Raw input: ' . file_get_contents('php://input'));
    debug_log('Parsed input: ' . json_encode($input));
    
    if (!$input) {
        debug_log('ERROR: Invalid JSON input');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $id_produk = (int)($input['id_produk'] ?? 0);
    $qty = (int)($input['quantity'] ?? 1);
    
    debug_log('id_produk: ' . $id_produk . ', qty: ' . $qty . ', user_id: ' . $user_id);

    if ($id_produk <= 0 || $qty <= 0) {
        debug_log('ERROR: Invalid product ID or quantity');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
        exit;
    }

    // Cek stok
    debug_log('Checking stock for product ' . $id_produk);
    $stmt_cek = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id_produk = ?");
    if (!$stmt_cek) {
        debug_log('ERROR: Prepare stock check failed: ' . mysqli_error($conn));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt_cek, 'i', $id_produk);
    if (!mysqli_stmt_execute($stmt_cek)) {
        debug_log('ERROR: Execute stock check failed: ' . mysqli_error($conn));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt_cek);
        exit;
    }
    
    $result_cek = mysqli_stmt_get_result($stmt_cek);
    $prod = mysqli_fetch_assoc($result_cek);
    mysqli_stmt_close($stmt_cek);
    
    debug_log('Product check result: ' . json_encode($prod));

    if (!$prod) {
        debug_log('ERROR: Product not found');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    if ($qty > (int)$prod['stok']) {
        debug_log('ERROR: Insufficient stock');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Stock insufficient']);
        exit;
    }

    // Cek item di keranjang
    debug_log('Checking if item exists in cart for user ' . $user_id . ' and product ' . $id_produk);
    $stmt_check = mysqli_prepare($conn, "SELECT id_keranjang, jumlah FROM keranjang WHERE id_user = ? AND id_produk = ?");
    if (!$stmt_check) {
        debug_log('ERROR: Prepare check cart failed: ' . mysqli_error($conn));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt_check, 'ii', $user_id, $id_produk);
    if (!mysqli_stmt_execute($stmt_check)) {
        debug_log('ERROR: Execute check cart failed: ' . mysqli_error($conn));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt_check);
        exit;
    }
    
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    $item_exists = mysqli_num_rows($result_check) > 0;
    debug_log('Item exists in cart: ' . ($item_exists ? 'yes' : 'no'));
    
    if ($item_exists) {
        // Item sudah ada, update quantity
        $row = mysqli_fetch_assoc($result_check);
        $new_qty = (int)$row['jumlah'] + $qty;
        
        debug_log('Updating existing item: old_qty=' . $row['jumlah'] . ', new_qty=' . $new_qty);
        
        if ($new_qty > (int)$prod['stok']) {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Total quantity exceeds stock');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Total quantity exceeds stock']);
            exit;
        }
        
        $stmt_update = mysqli_prepare($conn, "UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ?");
        if (!$stmt_update) {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Prepare update failed: ' . mysqli_error($conn));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt_update, 'ii', $new_qty, $row['id_keranjang']);
        if (!mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Update execute failed: ' . mysqli_error($conn));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
            exit;
        }
        
        debug_log('SUCCESS: Item quantity updated');
        mysqli_stmt_close($stmt_update);
        mysqli_stmt_close($stmt_check);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Product quantity updated']);
        exit;
    } else {
        // Item baru, insert
        debug_log('Inserting new item to cart');
        
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO keranjang (id_user, id_produk, jumlah, tanggal_ditambahkan) VALUES (?, ?, ?, NOW())");
        if (!$stmt_insert) {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Prepare insert failed: ' . mysqli_error($conn));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt_insert, 'iii', $user_id, $id_produk, $qty);
        if (!mysqli_stmt_execute($stmt_insert)) {
            mysqli_stmt_close($stmt_insert);
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Insert execute failed: ' . mysqli_error($conn));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
            exit;
        }
        
        debug_log('SUCCESS: New item added to cart');
        mysqli_stmt_close($stmt_insert);
        mysqli_stmt_close($stmt_check);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Product added to cart']);
        exit;
    }
}

// --- LOGIC GET CART ---
if ($action === 'get') {
    debug_log('Getting cart items for user_id: ' . $user_id);
    
    $query = "SELECT 
                k.id_keranjang,
                k.id_user,
                k.id_produk,
                k.jumlah as quantity,
                k.tanggal_ditambahkan,
                p.nama_produk,
                p.harga,
                p.gambar,
                p.stok
              FROM keranjang k 
              JOIN produk p ON k.id_produk = p.id_produk 
              WHERE k.id_user = ?
              ORDER BY k.tanggal_ditambahkan DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        debug_log('ERROR: Prepare get cart failed: ' . mysqli_error($conn));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB Error']);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while($row = mysqli_fetch_assoc($res)) {
        $row['id_produk'] = (int)$row['id_produk'];
        $row['quantity'] = (int)$row['quantity'];
        $row['harga'] = (float)$row['harga'];
        $row['stok'] = (int)$row['stok'];
        $items[] = $row;
    }
    
    debug_log('Cart items count: ' . count($items));
    
    mysqli_stmt_close($stmt);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'items' => $items, 'count' => count($items)]);
    exit;
}

// --- LOGIC COUNT ---
if ($action === 'count') {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM keranjang WHERE id_user = ?");
    if (!$stmt) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB Error']);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'count' => (int)$row['cnt']]);
    exit;
}

// --- LOGIC REMOVE ---
if ($action === 'remove') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_produk = (int)($input['id_produk'] ?? 0);
    
    if ($id_produk <= 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "DELETE FROM keranjang WHERE id_user = ? AND id_produk = ?");
    if (!$stmt) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'DB Error']);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $id_produk);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $result]);
    exit;
}

// --- LOGIC UPDATE QUANTITY ---
if ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_produk = (int)($input['id_produk'] ?? 0);
    $new_qty = (int)($input['quantity'] ?? 0);
    
    if ($id_produk <= 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    if ($new_qty <= 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM keranjang WHERE id_user = ? AND id_produk = ?");
        if (!$stmt) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB Error']);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $id_produk);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt_cek = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id_produk = ?");
        if (!$stmt_cek) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB Error']);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt_cek, 'i', $id_produk);
        mysqli_stmt_execute($stmt_cek);
        $res_cek = mysqli_stmt_get_result($stmt_cek);
        $prod = mysqli_fetch_assoc($res_cek);
        mysqli_stmt_close($stmt_cek);
        
        if (!$prod || $new_qty > (int)$prod['stok']) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Stock insufficient']);
            exit;
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE keranjang SET jumlah = ? WHERE id_user = ? AND id_produk = ?");
        if (!$stmt) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB Error']);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, 'iii', $new_qty, $user_id, $id_produk);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'message' => 'Action not found']);
?>