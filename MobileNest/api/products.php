<?php
/**
 * Products API Endpoint
 * Handles: GET all products, GET single product, POST new product, PUT update, DELETE
 */

require_once '../config.php';
require_once 'response.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'getAll';

// GET Requests
if ($method === 'GET') {
    if ($action === 'getAll') {
        getAllProducts();
    } elseif ($action === 'getById' && isset($_GET['id'])) {
        getProductById($_GET['id']);
    } elseif ($action === 'search' && isset($_GET['q'])) {
        searchProducts($_GET['q']);
    } elseif ($action === 'getByBrand' && isset($_GET['brand'])) {
        getProductsByBrand($_GET['brand']);
    } elseif ($action === 'getByPrice' && isset($_GET['min']) && isset($_GET['max'])) {
        getProductsByPrice($_GET['min'], $_GET['max']);
    } else {
        APIResponse::error('Invalid action', 400);
    }
}
// POST Requests (Admin only)
elseif ($method === 'POST') {
    require_login(true);
    
    $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : 'create';
    
    if ($action === 'create') {
        createProduct();
    } elseif ($action === 'update') {
        updateProduct();
    } elseif ($action === 'delete') {
        deleteProduct();
    } else {
        APIResponse::error('Invalid action', 400);
    }
}
else {
    APIResponse::error('Method not allowed', 405);
}

/**
 * Get all products dengan filter
 */
function getAllProducts() {
    global $conn;
    
    // Filter dari query string
    $search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
    $brand = isset($_GET['brand']) ? sanitize_input($_GET['brand']) : '';
    $minPrice = isset($_GET['minPrice']) ? intval($_GET['minPrice']) : 0;
    $maxPrice = isset($_GET['maxPrice']) ? intval($_GET['maxPrice']) : 999999999;
    $sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'terbaru';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
    $offset = ($page - 1) * $limit;
    
    // Build where clause
    $where = ["status_produk = 'Tersedia'"];
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $where[] = "(nama_produk LIKE '%$search%' OR merek LIKE '%$search%' OR deskripsi LIKE '%$search%')";
    }
    
    if (!empty($brand)) {
        $brand = $conn->real_escape_string($brand);
        $where[] = "merek = '$brand'";
    }
    
    $where[] = "harga BETWEEN $minPrice AND $maxPrice";
    
    $whereSQL = implode(' AND ', $where);
    
    // Determine sort
    $orderSQL = 'tanggal_ditambahkan DESC';
    if ($sort === 'termurah') $orderSQL = 'harga ASC';
    elseif ($sort === 'termahal') $orderSQL = 'harga DESC';
    elseif ($sort === 'populer') $orderSQL = 'id_produk DESC';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM produk WHERE $whereSQL";
    $countResult = $conn->query($countQuery);
    $totalProducts = $countResult->fetch_assoc()['total'];
    
    // Get products
    $query = "SELECT * FROM produk WHERE $whereSQL ORDER BY $orderSQL LIMIT $limit OFFSET $offset";
    $result = $conn->query($query);
    
    if (!$result) {
        APIResponse::serverError('Database error: ' . $conn->error);
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }
    
    APIResponse::success([
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $totalProducts,
            'total_pages' => ceil($totalProducts / $limit)
        ]
    ], 'Products retrieved successfully');
}

/**
 * Get single product by ID
 */
function getProductById($id) {
    global $conn;
    
    $id = intval($id);
    $query = "SELECT * FROM produk WHERE id_produk = $id";
    $result = $conn->query($query);
    
    if (!$result || $result->num_rows === 0) {
        APIResponse::notFound('Product not found');
    }
    
    $product = $result->fetch_assoc();
    APIResponse::success(formatProductData($product), 'Product retrieved successfully');
}

/**
 * Search products
 */
function searchProducts($query) {
    global $conn;
    
    $query = $conn->real_escape_string(trim($query));
    
    if (strlen($query) < 2) {
        APIResponse::validationError(['search' => 'Query must be at least 2 characters']);
    }
    
    $sql = "SELECT * FROM produk WHERE status_produk = 'Tersedia' 
            AND (nama_produk LIKE '%$query%' OR merek LIKE '%$query%' OR deskripsi LIKE '%$query%')
            ORDER BY nama_produk ASC LIMIT 50";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        APIResponse::serverError('Database error: ' . $conn->error);
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }
    
    APIResponse::success($products, 'Search completed');
}

/**
 * Get products by brand
 */
function getProductsByBrand($brand) {
    global $conn;
    
    $brand = $conn->real_escape_string(trim($brand));
    
    $query = "SELECT * FROM produk WHERE status_produk = 'Tersedia' AND merek = '$brand' ORDER BY nama_produk ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        APIResponse::serverError('Database error: ' . $conn->error);
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }
    
    APIResponse::success($products, 'Products by brand retrieved');
}

/**
 * Get products by price range
 */
function getProductsByPrice($min, $max) {
    global $conn;
    
    $min = intval($min);
    $max = intval($max);
    
    if ($min > $max) {
        APIResponse::validationError(['price' => 'Min price cannot be greater than max price']);
    }
    
    $query = "SELECT * FROM produk WHERE status_produk = 'Tersedia' AND harga BETWEEN $min AND $max ORDER BY harga ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        APIResponse::serverError('Database error: ' . $conn->error);
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = formatProductData($row);
    }
    
    APIResponse::success($products, 'Products in price range retrieved');
}

/**
 * Create new product (Admin only)
 */
function createProduct() {
    global $conn;
    
    $errors = [];
    
    // Validate input
    if (empty($_POST['nama_produk'])) $errors['nama_produk'] = 'Product name is required';
    if (empty($_POST['merek'])) $errors['merek'] = 'Brand is required';
    if (empty($_POST['harga']) || !is_numeric($_POST['harga'])) $errors['harga'] = 'Valid price is required';
    if (empty($_POST['stok']) || !is_numeric($_POST['stok'])) $errors['stok'] = 'Valid stock is required';
    
    if (!empty($errors)) {
        APIResponse::validationError($errors);
    }
    
    $nama = $conn->real_escape_string($_POST['nama_produk']);
    $merek = $conn->real_escape_string($_POST['merek']);
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);
    $deskripsi = isset($_POST['deskripsi']) ? $conn->real_escape_string($_POST['deskripsi']) : '';
    $spesifikasi = isset($_POST['spesifikasi']) ? $conn->real_escape_string($_POST['spesifikasi']) : '';
    $kategori = isset($_POST['kategori']) ? $conn->real_escape_string($_POST['kategori']) : 'Tersedia';
    
    $query = "INSERT INTO produk (nama_produk, merek, harga, stok, deskripsi, spesifikasi, kategori, status_produk)
              VALUES ('$nama', '$merek', $harga, $stok, '$deskripsi', '$spesifikasi', '$kategori', 'Tersedia')";
    
    if ($conn->query($query)) {
        $product_id = $conn->insert_id;
        APIResponse::success(['id' => $product_id], 'Product created successfully', 201);
    } else {
        APIResponse::serverError('Failed to create product: ' . $conn->error);
    }
}

/**
 * Update product (Admin only)
 */
function updateProduct() {
    global $conn;
    
    if (empty($_POST['id_produk'])) {
        APIResponse::validationError(['id_produk' => 'Product ID is required']);
    }
    
    $id = intval($_POST['id_produk']);
    $updates = [];
    
    if (!empty($_POST['nama_produk'])) {
        $nama = $conn->real_escape_string($_POST['nama_produk']);
        $updates[] = "nama_produk = '$nama'";
    }
    if (!empty($_POST['harga'])) {
        $harga = floatval($_POST['harga']);
        $updates[] = "harga = $harga";
    }
    if (isset($_POST['stok'])) {
        $stok = intval($_POST['stok']);
        $updates[] = "stok = $stok";
    }
    
    if (empty($updates)) {
        APIResponse::validationError(['fields' => 'At least one field must be updated']);
    }
    
    $updateSQL = implode(', ', $updates);
    $query = "UPDATE produk SET $updateSQL WHERE id_produk = $id";
    
    if ($conn->query($query)) {
        APIResponse::success(null, 'Product updated successfully');
    } else {
        APIResponse::serverError('Failed to update product: ' . $conn->error);
    }
}

/**
 * Delete product (Admin only)
 */
function deleteProduct() {
    global $conn;
    
    if (empty($_POST['id_produk'])) {
        APIResponse::validationError(['id_produk' => 'Product ID is required']);
    }
    
    $id = intval($_POST['id_produk']);
    
    // Soft delete - update status instead
    $query = "UPDATE produk SET status_produk = 'Dihapus' WHERE id_produk = $id";
    
    if ($conn->query($query)) {
        APIResponse::success(null, 'Product deleted successfully');
    } else {
        APIResponse::serverError('Failed to delete product: ' . $conn->error);
    }
}

/**
 * Format product data for API response
 */
function formatProductData($product) {
    return [
        'id' => $product['id_produk'],
        'nama' => $product['nama_produk'],
        'merek' => $product['merek'],
        'harga' => (float)$product['harga'],
        'stok' => (int)$product['stok'],
        'kategori' => $product['kategori'],
        'deskripsi' => $product['deskripsi'],
        'spesifikasi' => $product['spesifikasi'],
        'status' => $product['status_produk'],
        'gambar' => $product['gambar'] ?? null,
        'tanggal_ditambahkan' => $product['tanggal_ditambahkan']
    ];
}

?>