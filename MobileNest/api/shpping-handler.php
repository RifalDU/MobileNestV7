<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';
require_once 'response.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '../logs/shipping_debug.log');

try {
    // Check session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];

    // Validate input
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat_lengkap = trim($_POST['alamat_lengkap'] ?? '');
    $kota = trim($_POST['kota'] ?? '');
    $kode_pos = trim($_POST['kode_pos'] ?? '');
    $metode_pengiriman = trim($_POST['metode_pengiriman'] ?? 'regular');
    $catatan = trim($_POST['catatan'] ?? '');

    // Validate required fields
    if (empty($nama_penerima) || empty($no_telepon) || empty($email) || empty($alamat_lengkap) || empty($kota) || empty($kode_pos)) {
        throw new Exception('Semua field harus diisi');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email tidak valid');
    }

    // Validate phone (10-13 digits)
    if (!preg_match('/^\d{10,13}$/', str_replace([' ', '-', '+'], '', $no_telepon))) {
        throw new Exception('Nomor telepon harus 10-13 digit');
    }

    // Validate postal code (5-10 digits)
    if (!preg_match('/^\d{5,10}$/', $kode_pos)) {
        throw new Exception('Kode pos harus 5-10 digit');
    }

    // Set shipping cost
    $ongkir = 0;
    switch ($metode_pengiriman) {
        case 'regular':
            $ongkir = 20000;
            break;
        case 'express':
            $ongkir = 50000;
            break;
        case 'same_day':
            $ongkir = 100000;
            break;
        default:
            throw new Exception('Metode pengiriman tidak valid');
    }

    // Generate pengiriman number
    $no_pengiriman = 'PGR-' . date('YmdHis') . '-' . rand(1000, 9999);

    // Insert into database
    $query = "INSERT INTO pengiriman (
                id_user, 
                no_pengiriman, 
                nama_penerima, 
                no_telepon, 
                email, 
                alamat_lengkap, 
                kota, 
                kode_pos, 
                metode_pengiriman, 
                ongkir, 
                status_pengiriman,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                NOW()
            )";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }

    $status = 'Menunggu Verifikasi Pembayaran';
    $stmt->bind_param(
        'issssssssi',
        $user_id,
        $no_pengiriman,
        $nama_penerima,
        $no_telepon,
        $email,
        $alamat_lengkap,
        $kota,
        $kode_pos,
        $metode_pengiriman,
        $ongkir,
        $status
    );

    if (!$stmt->execute()) {
        throw new Exception('Execute error: ' . $stmt->error);
    }

    $id_pengiriman = $conn->insert_id;

    // Store in session
    $_SESSION['id_pengiriman'] = $id_pengiriman;
    $_SESSION['ongkir'] = $ongkir;
    $_SESSION['subtotal'] = $_POST['subtotal'] ?? 0;

    error_log('[SUCCESS] Pengiriman created: id=' . $id_pengiriman . ', no=' . $no_pengiriman);

    echo json_encode([
        'success' => true,
        'message' => 'Data pengiriman berhasil disimpan',
        'id_pengiriman' => $id_pengiriman
    ]);

} catch (Exception $e) {
    error_log('[ERROR] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>