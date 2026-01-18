<?php
/**
 * Database Schema Update Script
 * Updates the transaksi table to support payment verification status
 * Run this ONCE to fix the HTTP 500 error
 */

session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('This script must be run from POST request or manually in phpmyadmin.');
}

// Check if already updated
$check_query = "SHOW COLUMNS FROM transaksi WHERE COLUMN_NAME = 'status_pesanan' AND COLUMN_TYPE LIKE '%Menunggu Verifikasi%'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) > 0) {
    echo "Already updated!";
    exit;
}

// Update the ENUM to include verification statuses
$alter_query = "ALTER TABLE transaksi MODIFY COLUMN status_pesanan ENUM(
    'Menunggu Pembayaran',
    'Menunggu Verifikasi',
    'Verified',
    'Diproses',
    'Dikirim',
    'Selesai',
    'Dibatalkan'
) DEFAULT 'Menunggu Pembayaran'";

if (mysqli_query($conn, $alter_query)) {
    echo "✅ Database schema updated successfully!";
    // Log the update
    error_log("[" . date('Y-m-d H:i:s') . "] Database schema updated: transaksi.status_pesanan ENUM values expanded.");
} else {
    echo "❌ Error: " . mysqli_error($conn);
}

?>
