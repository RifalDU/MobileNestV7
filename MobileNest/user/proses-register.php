<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap      = mysqli_real_escape_string($conn, $_POST['nama_lengkap'] ?? '');
    $email             = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $password          = $_POST['password'] ?? '';
    $password_confirm  = $_POST['password_confirm'] ?? '';

    if ($nama_lengkap === '' || $email === '' || $password === '' || $password_confirm === '') {
        $_SESSION['error'] = "Semua field wajib diisi.";
        header('Location: register.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid.";
        header('Location: register.php');
        exit;
    }

    if ($password !== $password_confirm) {
        $_SESSION['error'] = "Password dan konfirmasi password tidak sama.";
        header('Location: register.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password minimal 6 karakter.";
        header('Location: register.php');
        exit;
    }

    $cekEmail = mysqli_query($conn, "SELECT id_user FROM users WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
    if (mysqli_num_rows($cekEmail) > 0) {
        $_SESSION['error'] = "Email sudah terdaftar.";
        header('Location: register.php');
        exit;
    }

    $username = explode('@', $email)[0];
    $cekUser  = mysqli_query($conn, "SELECT id_user FROM users WHERE username='" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1");
    if (mysqli_num_rows($cekUser) > 0) {
        $username .= rand(100, 999);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT); // praktik aman[web:190][web:193]

    $sql = "INSERT INTO users (nama_lengkap, email, username, password) 
            VALUES ('" . mysqli_real_escape_string($conn, $nama_lengkap) . "', '" . mysqli_real_escape_string($conn, $email) . "', '" . mysqli_real_escape_string($conn, $username) . "', '$hash')";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Registrasi berhasil. Silakan login.";
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat menyimpan data.";
        header('Location: register.php');
        exit;
    }
} else {
    header('Location: register.php');
    exit;
}
