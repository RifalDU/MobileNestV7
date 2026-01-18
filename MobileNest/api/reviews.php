<?php
/**
 * Reviews & Ratings API
 * Handles: GET reviews, POST review, UPDATE review, DELETE review
 */

require_once '../config.php';
require_once 'response.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'getByProduct';

if ($method === 'GET') {
    if ($action === 'getByProduct' && isset($_GET['product_id'])) {
        getReviewsByProduct($_GET['product_id']);
    } elseif ($action === 'getByUser') {
        getReviewsByUser();
    } elseif ($action === 'getStats' && isset($_GET['product_id'])) {
        getReviewStats($_GET['product_id']);
    } else {
        APIResponse::error('Invalid action', 400);
    }
} elseif ($method === 'POST') {
    if (!is_logged_in()) {
        APIResponse::unauthorized('Please login to post review');
    }
    
    if ($action === 'create') {
        createReview();
    } elseif ($action === 'update') {
        updateReview();
    } elseif ($action === 'delete') {
        deleteReview();
    } else {
        APIResponse::error('Invalid action', 400);
    }
} else {
    APIResponse::error('Method not allowed', 405);
}

/**
 * Get reviews by product
 */
function getReviewsByProduct($product_id) {
    global $conn;
    
    $product_id = intval($product_id);
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $conn->prepare('SELECT COUNT(*) as total FROM ulasan WHERE id_produk = ?');
    $countStmt->bind_param('i', $product_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get reviews
    $stmt = $conn->prepare(
        'SELECT u.*, us.username FROM ulasan u
         LEFT JOIN users us ON u.id_user = us.id_user
         WHERE u.id_produk = ?
         ORDER BY u.tanggal_ulasan DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->bind_param('iii', $product_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = formatReviewData($row);
    }
    $stmt->close();
    
    APIResponse::success([
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'Reviews retrieved successfully');
}

/**
 * Get user's reviews
 */
function getReviewsByUser() {
    if (!is_logged_in()) {
        APIResponse::unauthorized('Please login');
    }
    
    global $conn;
    
    $user_id = $_SESSION['user'];
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $conn->prepare('SELECT COUNT(*) as total FROM ulasan WHERE id_user = ?');
    $countStmt->bind_param('i', $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get reviews
    $stmt = $conn->prepare(
        'SELECT u.*, p.nama_produk FROM ulasan u
         LEFT JOIN produk p ON u.id_produk = p.id_produk
         WHERE u.id_user = ?
         ORDER BY u.tanggal_ulasan DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $review = formatReviewData($row);
        $review['nama_produk'] = $row['nama_produk'];
        $reviews[] = $review;
    }
    $stmt->close();
    
    APIResponse::success([
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 'User reviews retrieved');
}

/**
 * Get review statistics for a product
 */
function getReviewStats($product_id) {
    global $conn;
    
    $product_id = intval($product_id);
    
    $stmt = $conn->prepare(
        'SELECT
            COUNT(*) as total_reviews,
            AVG(rating) as average_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
         FROM ulasan
         WHERE id_produk = ?'
    );
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    if ($stats['total_reviews'] == 0) {
        APIResponse::success([
            'total_reviews' => 0,
            'average_rating' => 0,
            'distribution' => []
        ], 'No reviews yet');
    }
    
    APIResponse::success([
        'total_reviews' => (int)$stats['total_reviews'],
        'average_rating' => round((float)$stats['average_rating'], 1),
        'distribution' => [
            5 => (int)$stats['rating_5'],
            4 => (int)$stats['rating_4'],
            3 => (int)$stats['rating_3'],
            2 => (int)$stats['rating_2'],
            1 => (int)$stats['rating_1']
        ]
    ], 'Review statistics retrieved');
}

/**
 * Create review
 */
function createReview() {
    global $conn;
    
    $user_id = $_SESSION['user'];
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $errors = [];
    if (empty($data['id_produk'])) $errors['id_produk'] = 'Product ID is required';
    if (empty($data['rating']) || !in_array($data['rating'], [1, 2, 3, 4, 5])) {
        $errors['rating'] = 'Rating must be 1-5';
    }
    if (empty($data['komentar'])) $errors['komentar'] = 'Comment is required';
    
    if (!empty($errors)) {
        APIResponse::validationError($errors);
    }
    
    $product_id = intval($data['id_produk']);
    $rating = intval($data['rating']);
    $komentar = $conn->real_escape_string(trim($data['komentar']));
    
    // Check if user already reviewed this product
    $checkStmt = $conn->prepare('SELECT id_ulasan FROM ulasan WHERE id_user = ? AND id_produk = ?');
    $checkStmt->bind_param('ii', $user_id, $product_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        APIResponse::error('You already reviewed this product', 400);
    }
    $checkStmt->close();
    
    // Insert review
    $stmt = $conn->prepare('INSERT INTO ulasan (id_user, id_produk, rating, komentar) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiis', $user_id, $product_id, $rating, $komentar);
    
    if ($stmt->execute()) {
        $review_id = $conn->insert_id;
        $stmt->close();
        
        APIResponse::success(['review_id' => $review_id], 'Review posted successfully', 201);
    } else {
        APIResponse::serverError('Failed to post review: ' . $conn->error);
    }
}

/**
 * Update review
 */
function updateReview() {
    global $conn;
    
    $user_id = $_SESSION['user'];
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($data['id_ulasan'])) {
        APIResponse::validationError(['id_ulasan' => 'Review ID is required']);
    }
    
    $review_id = intval($data['id_ulasan']);
    
    // Check if review belongs to user
    $checkStmt = $conn->prepare('SELECT id_user FROM ulasan WHERE id_ulasan = ?');
    $checkStmt->bind_param('i', $review_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        APIResponse::notFound('Review not found');
    }
    
    $review = $result->fetch_assoc();
    if ($review['id_user'] != $user_id) {
        APIResponse::unauthorized('You can only edit your own reviews');
    }
    $checkStmt->close();
    
    $updates = [];
    if (!empty($data['rating']) && in_array($data['rating'], [1, 2, 3, 4, 5])) {
        $rating = intval($data['rating']);
        $updates[] = "rating = $rating";
    }
    if (!empty($data['komentar'])) {
        $komentar = $conn->real_escape_string(trim($data['komentar']));
        $updates[] = "komentar = '$komentar'";
    }
    
    if (empty($updates)) {
        APIResponse::validationError(['fields' => 'At least one field must be updated']);
    }
    
    $updateSQL = implode(', ', $updates);
    $stmt = $conn->prepare("UPDATE ulasan SET $updateSQL WHERE id_ulasan = ?");
    $stmt->bind_param('i', $review_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        APIResponse::success(null, 'Review updated successfully');
    } else {
        APIResponse::serverError('Failed to update review');
    }
}

/**
 * Delete review
 */
function deleteReview() {
    global $conn;
    
    $user_id = $_SESSION['user'];
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($data['id_ulasan'])) {
        APIResponse::validationError(['id_ulasan' => 'Review ID is required']);
    }
    
    $review_id = intval($data['id_ulasan']);
    
    // Check if review belongs to user
    $checkStmt = $conn->prepare('SELECT id_user FROM ulasan WHERE id_ulasan = ?');
    $checkStmt->bind_param('i', $review_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        APIResponse::notFound('Review not found');
    }
    
    $review = $result->fetch_assoc();
    if ($review['id_user'] != $user_id && !is_admin()) {
        APIResponse::unauthorized('You can only delete your own reviews');
    }
    $checkStmt->close();
    
    $stmt = $conn->prepare('DELETE FROM ulasan WHERE id_ulasan = ?');
    $stmt->bind_param('i', $review_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        APIResponse::success(null, 'Review deleted successfully');
    } else {
        APIResponse::serverError('Failed to delete review');
    }
}

/**
 * Format review data
 */
function formatReviewData($review) {
    return [
        'id' => $review['id_ulasan'],
        'rating' => (int)$review['rating'],
        'komentar' => $review['komentar'],
        'username' => $review['username'] ?? 'Anonymous',
        'tanggal' => $review['tanggal_ulasan']
    ];
}

?>