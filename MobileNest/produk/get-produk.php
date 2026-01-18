<?php
/**
 * ============================================
 * FILE: get-produk.php
 * PURPOSE: Get filtered products from database
 * ENHANCEMENT: Include both file path and base64 image data
 * ============================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
require_once '../config.php';

error_log('===== get-produk.php START =====' . date('Y-m-d H:i:s'));

try {
    // Get filter parameters
    $brand = isset($_GET['brand']) ? $_GET['brand'] : '';
    $min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 999999999;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

    error_log('Query params - brand: ' . $brand . ', price: ' . $min_price . '-' . $max_price . ', search: ' . $search . ', sort: ' . $sort);

    // Build WHERE clause
    $where_conditions = ["status_produk = 'Tersedia'"];

    if (!empty($brand)) {
        $brands_array = explode(',', $brand);
        $brands_safe = array_map(function($b) { 
            global $conn;
            return "'" . $conn->real_escape_string(trim($b)) . "'";
        }, $brands_array);
        $where_conditions[] = "merek IN (" . implode(',', $brands_safe) . ")";
    }

    if ($min_price > 0 || $max_price < 999999999) {
        $where_conditions[] = "harga >= $min_price AND harga <= $max_price";
    }

    if (!empty($search)) {
        $search_safe = $conn->real_escape_string($search);
        $where_conditions[] = "(nama_produk LIKE '%$search_safe%' OR merek LIKE '%$search_safe%')";
    }

    $where_clause = implode(' AND ', $where_conditions);
    
    error_log('WHERE clause: ' . $where_clause);

    // Build ORDER BY clause
    $order_by = "id_produk DESC"; // Default simple sort

    switch($sort) {
        case 'harga_rendah':
            $order_by = "harga ASC";
            break;
        case 'harga_tinggi':
            $order_by = "harga DESC";
            break;
        case 'populer':
            $order_by = "id_produk DESC";
            break;
        case 'terbaru':
        default:
            $order_by = "id_produk DESC";
    }

    error_log('ORDER BY: ' . $order_by);

    // SELECT only columns that definitely exist
    $sql = "SELECT id_produk, nama_produk, merek, harga, stok, kategori, status_produk, gambar 
            FROM produk 
            WHERE $where_clause 
            ORDER BY $order_by";

    error_log('SQL Query: ' . $sql);

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        $error_msg = 'SQL Error: ' . mysqli_error($conn);
        error_log($error_msg);
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error',
            'details' => $error_msg
        ]);
        exit;
    }

    $products = [];
    $row_count = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row_count++;
        // Convert to proper types
        $row['id_produk'] = (int)$row['id_produk'];
        $row['harga'] = (int)$row['harga'];
        $row['stok'] = (int)$row['stok'];
        
        // Add optional fields with defaults
        $row['terjual'] = 0;
        $row['rating'] = 4.5;
        
        // **NEW: Keep gambar as-is (could be base64 or filename)**
        // filter.js will handle both cases
        
        $products[] = $row;
    }

    error_log('Products found: ' . count($products) . ' rows processed: ' . $row_count);
    error_log('===== get-produk.php END (SUCCESS) =====' . date('Y-m-d H:i:s'));

    // Return successful response
    http_response_code(200);
    echo json_encode($products);

} catch (Exception $e) {
    error_log('Exception in get-produk.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
