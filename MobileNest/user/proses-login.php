<?php
/**
 * UNIFIED LOGIN PROCESSOR - Support Admin & User
 * Automatically detect user role and set appropriate session
 * 
 * Updated: Now checks BOTH admin and users table
 * Priority: Admin table first, then users table
 */

// 1. Start session
session_start();

// 2. Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 3. Get input
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// 4. Validate input
if (empty($username) || empty($password)) {
    $_SESSION['error'] = 'Username dan password harus diisi!';
    header('Location: login.php');
    exit;
}

// 5. Connect to database
$servername = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'mobilenest_db';

$conn = new mysqli($servername, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    $_SESSION['error'] = 'Database error: ' . $conn->connect_error;
    header('Location: login.php');
    exit;
}

// 6. Set charset
$conn->set_charset('utf8mb4');

// 7. TRY LOGIN AS ADMIN FIRST ========================================
$sql_admin = "SELECT id_admin, username, email, password, nama_lengkap FROM admin WHERE username = ? OR email = ? LIMIT 1";
$stmt_admin = $conn->prepare($sql_admin);

if (!$stmt_admin) {
    $_SESSION['error'] = 'Database error: ' . $conn->error;
    header('Location: login.php');
    exit;
}

$stmt_admin->bind_param('ss', $username, $username);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows === 1) {
    // ✅ ADMIN FOUND - Verify password
    $admin = $result_admin->fetch_assoc();
    $stmt_admin->close();

    if (!password_verify($password, $admin['password'])) {
        $_SESSION['error'] = 'Password salah!';
        header('Location: login.php');
        exit;
    }

    // ✅ ADMIN LOGIN SUCCESS - Set session as ADMIN
    $_SESSION['admin'] = $admin['id_admin'];
    $_SESSION['admin_id'] = $admin['id_admin'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_name'] = $admin['nama_lengkap'];
    $_SESSION['role'] = 'admin';
    $_SESSION['logged_in'] = true;

    $_SESSION['success'] = '✅ Login Admin berhasil! Selamat datang ' . $admin['nama_lengkap'];

    $conn->close();

    // Redirect ke admin dashboard
    header('Location: ../admin/index.php');
    exit;
}

$stmt_admin->close();

// 8. TRY LOGIN AS USER ================================================
$sql_user = "SELECT id_user, username, email, password, nama_lengkap FROM users WHERE username = ? OR email = ? LIMIT 1";
$stmt_user = $conn->prepare($sql_user);

if (!$stmt_user) {
    $_SESSION['error'] = 'Database error: ' . $conn->error;
    header('Location: login.php');
    exit;
}

$stmt_user->bind_param('ss', $username, $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    // ❌ USER NOT FOUND - Neither admin nor user
    $_SESSION['error'] = 'Username atau email tidak ditemukan!';
    header('Location: login.php');
    exit;
}

// ✅ USER FOUND - Verify password
$user = $result_user->fetch_assoc();
$stmt_user->close();

if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'Password salah!';
    header('Location: login.php');
    exit;
}

// ✅ USER LOGIN SUCCESS - Set session as USER
$_SESSION['user'] = $user['id_user'];
$_SESSION['user_id'] = $user['id_user'];
$_SESSION['user_name'] = $user['nama_lengkap'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = 'user';
$_SESSION['logged_in'] = true;

$_SESSION['success'] = '✅ Login berhasil! Selamat datang ' . $user['nama_lengkap'];

$conn->close();

// Redirect ke user home
header('Location: ../index.php');
exit;
?>