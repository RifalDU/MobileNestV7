<?php
/**
 * Authentication Check Middleware
 * Validates admin user session and sets user variables
 * Used by admin panel pages for role verification
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: ../../user/login.php');
    exit('Unauthorized: Please login first');
}

// Get user role from session
$user_role = $_SESSION['role'] ?? 'guest';

// Set user variables based on role
if ($user_role === 'admin') {
    $user_id = $_SESSION['admin_id'] ?? $_SESSION['admin'] ?? null;
    $username = $_SESSION['admin_username'] ?? 'Unknown';
    $email = $_SESSION['admin_email'] ?? 'Unknown';
    $nama_lengkap = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Admin';
    $user_role = 'admin';
} elseif ($user_role === 'user') {
    $user_id = $_SESSION['user_id'] ?? $_SESSION['user'] ?? null;
    $username = $_SESSION['username'] ?? 'Unknown';
    $email = $_SESSION['email'] ?? 'Unknown';
    $nama_lengkap = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
    $user_role = 'user';
} else {
    http_response_code(401);
    header('Location: ../../user/login.php');
    exit('Unauthorized: Invalid session');
}

// Set login time if not already set
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// For debugging (optional - remove in production)
// error_log('User authenticated: ' . $username . ' (Role: ' . $user_role . ')');

?>