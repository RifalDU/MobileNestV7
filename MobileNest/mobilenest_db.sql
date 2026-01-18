-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2026 at 02:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mobilenest_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_lengkap`, `email`, `no_telepon`, `tanggal_dibuat`) VALUES
(1, 'admin', '$2y$10$/R8C00S51h0LHoPhf8zYTOawNJAGTc/UbpWB5kv89nExbehqOA8Mi', 'Administrator MobileNest', 'admin@mobilenest.com', '081234567890', '2025-10-23 14:18:47'),
(2, 'admin1', 'admin12345', 'AdminBesar', 'AdminBesar@gmail.com', '011', '2026-01-07 17:43:54');

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int(11) NOT NULL,
  `id_transaksi` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 13, 1, 6999000.00, 6999000.00),
(2, 1, 12, 1, 3499000.00, 3499000.00),
(3, 1, 7, 1, 19999000.00, 19999000.00),
(4, 2, 13, 1, 6999000.00, 6999000.00),
(5, 3, 12, 1, 3499000.00, 3499000.00),
(6, 4, 13, 1, 6999000.00, 6999000.00),
(7, 5, 13, 1, 6999000.00, 6999000.00),
(8, 6, 13, 1, 6999000.00, 6999000.00),
(9, 7, 13, 1, 6999000.00, 6999000.00);

-- --------------------------------------------------------

--
-- Table structure for table `keranjang`
--

CREATE TABLE `keranjang` (
  `id_keranjang` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `tanggal_ditambahkan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengiriman`
--

CREATE TABLE `pengiriman` (
  `id_pengiriman` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `no_pengiriman` varchar(50) NOT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `no_telepon` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `provinsi` varchar(50) NOT NULL,
  `kota` varchar(50) NOT NULL,
  `kecamatan` varchar(50) NOT NULL,
  `kode_pos` varchar(10) NOT NULL,
  `alamat_lengkap` text NOT NULL,
  `metode_pengiriman` enum('regular','express','same_day') NOT NULL DEFAULT 'regular',
  `ongkir` int(11) NOT NULL DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `status_pengiriman` varchar(50) NOT NULL DEFAULT 'Menunggu Verifikasi Pembayaran',
  `tanggal_pengiriman` datetime NOT NULL,
  `tanggal_konfirmasi` datetime DEFAULT NULL,
  `tanggal_diterima` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengiriman`
--

INSERT INTO `pengiriman` (`id_pengiriman`, `id_user`, `no_pengiriman`, `nama_penerima`, `no_telepon`, `email`, `provinsi`, `kota`, `kecamatan`, `kode_pos`, `alamat_lengkap`, `metode_pengiriman`, `ongkir`, `catatan`, `status_pengiriman`, `tanggal_pengiriman`, `tanggal_konfirmasi`, `tanggal_diterima`, `created_at`, `updated_at`) VALUES
(1, 5, 'SHIP-20260108094504-64468', 'salabim', '21312421412', 'salambim@example.com', 'Los angeles', 'Los santos', 'nanggulo', '65213', 'Jln.Los santos No 4B', 'regular', 20000, '', 'Menunggu Pickup', '2026-01-08 15:45:04', NULL, NULL, '2026-01-08 08:45:04', '2026-01-08 08:45:04'),
(2, 5, 'SHIP-20260108101153-60370', 'salabim', '12214124124', 'salambim@example.com', 'Los angeles', 'Los santos', 'nanggulo', '22213', 'los santos', 'same_day', 100000, '', 'Menunggu Pickup', '2026-01-08 16:11:53', NULL, NULL, '2026-01-08 09:11:53', '2026-01-08 09:11:53'),
(3, 5, 'SHIP-20260108102113-94037', 'salabim', '12214124124', 'salambim@example.com', 'Testing', 'Testing', 'Testing', '11111', 'Jln.Testing123', 'regular', 20000, '', 'Menunggu Pickup', '2026-01-08 16:21:13', NULL, NULL, '2026-01-08 09:21:13', '2026-01-08 09:21:13'),
(4, 5, 'SHIP-20260108104025-15425', 'salabim', '12214124124', 'salambim@example.com', 'Testing', 't', 't', '65213', 'Testing2', 'regular', 20000, '', 'Menunggu Pickup', '2026-01-08 16:40:25', NULL, NULL, '2026-01-08 09:40:25', '2026-01-08 09:40:25'),
(5, 5, 'SHIP-20260108110214-44252', 'salabim', '12214124124', 'salambim@example.com', 'Testing', 'Testing', 'Testing', '11111', 'Testing', 'regular', 20000, '', 'Menunggu Pickup', '2026-01-08 17:02:14', NULL, NULL, '2026-01-08 10:02:14', '2026-01-08 10:02:14'),
(6, 5, 'SHIP-20260108110909-63289', 'salabim', '12214124124', 'salambim@example.com', 'Testing', 'Testing', 'Testing', '11111', 'Testing', 'regular', 20000, '', 'Menunggu Pickup', '2026-01-08 17:09:09', NULL, NULL, '2026-01-08 10:09:09', '2026-01-08 10:09:09'),
(7, 5, 'SHIP-20260108113733-45063', 'salabim', '12214124124', 'salambim@example.com', 'Testing', 'Testing', 'Testing', '11111', 't', 'regular', 20000, '', 'Menunggu Pickup', '2026-01-08 17:37:33', '2026-01-08 19:47:52', NULL, '2026-01-08 10:37:33', '2026-01-08 12:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `merek` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `harga` decimal(12,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `status_produk` enum('Tersedia','Tidak Tersedia') DEFAULT 'Tersedia',
  `tanggal_ditambahkan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id_produk`, `nama_produk`, `merek`, `deskripsi`, `spesifikasi`, `harga`, `stok`, `gambar`, `kategori`, `status_produk`, `tanggal_ditambahkan`) VALUES
(1, 'Samsung Galaxy S23', 'Samsung', 'Smartphone flagship dengan kamera 50MP dan layar Dynamic AMOLED', 'RAM 8GB, Storage 256GB, Battery 3900mAh', 12999000.00, 15, NULL, 'Flagship', 'Tersedia', '2025-10-23 14:18:47'),
(2, 'iPhone 14 Pro', 'Apple', 'iPhone terbaru dengan chip A16 Bionic dan Dynamic Island', 'RAM 6GB, Storage 128GB, iOS 16', 16999000.00, 10, NULL, 'Flagship', 'Tersedia', '2025-10-23 14:18:47'),
(3, 'Xiaomi Redmi Note 12', 'Xiaomi', 'Smartphone mid-range dengan performa tinggi', 'RAM 6GB, Storage 128GB, Battery 5000mAh', 2999000.00, 25, NULL, 'Mid-Range', 'Tersedia', '2025-10-23 14:18:47'),
(4, 'OPPO Reno 8', 'OPPO', 'Smartphone dengan kamera portrait terbaik', 'RAM 8GB, Storage 256GB, Battery 4500mAh', 5999000.00, 20, NULL, 'Mid-Range', 'Tersedia', '2025-10-23 14:18:47'),
(5, 'Vivo V27 5G', 'Vivo', 'Smartphone 5G dengan layar AMOLED 120Hz', 'RAM 12GB, Storage 256GB, Battery 4600mAh', 4999000.00, 18, NULL, 'Mid-Range', 'Tersedia', '2025-10-23 14:18:47'),
(6, 'Samsung Galaxy S23', 'Samsung', 'Smartphone flagship Samsung dengan performa tinggi dan kamera canggih', 'Processor: Snapdragon 8 Gen 2, RAM: 8GB, Storage: 256GB, Display: 6.1 inch AMOLED 120Hz', 12999000.00, 13, 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxIQEhUSEhAVFRUVFRcVFRUVFxUVFRcVFhUWFhUVFRUYHSggGBolHRUWITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAPFi0dHR0tLS0uKy0tKystLSsrLS0tLS0vKy0rLS0tKy0tKy0tLS0tLS0tLS0tLS03KystLSstK//AABEIAOEA4AMBIgACEQEDEQH/', 'Flagship', 'Tersedia', '2025-10-30 15:57:10'),
(7, 'iPhone 15 Pro', 'Apple', 'iPhone terbaru dengan chip A17 Pro dan fitur kamera revolusioner', 'Processor: A17 Pro, RAM: 8GB, Storage: 256GB, Display: 6.1 inch Super Retina XDR', 19999000.00, 8, NULL, 'Flagship', 'Tersedia', '2025-10-30 15:57:10'),
(8, 'Xiaomi 13', 'Xiaomi', 'Smartphone Xiaomi dengan Snapdragon terbaru dan layar AMOLED', 'Processor: Snapdragon 8 Gen 2, RAM: 12GB, Storage: 256GB, Display: 6.36 inch AMOLED', 7999000.00, 20, NULL, 'Mid-Range', 'Tersedia', '2025-10-30 15:57:10'),
(9, 'Oppo A57', 'Oppo', 'Smartphone budget dengan baterai besar dan performa standar', 'Processor: Snapdragon 680, RAM: 4GB, Storage: 64GB, Display: 6.56 inch LCD', 3999000.00, 7, '', 'Budget', 'Tersedia', '2025-10-30 15:57:10'),
(10, 'Vivo Y77', 'Vivo', 'Smartphone Vivo dengan kamera bagus dan desain modern', 'Processor: Snapdragon 680, RAM: 8GB, Storage: 128GB, Display: 6.64 inch LCD', 5999000.00, 18, NULL, 'Mid-Range', 'Tersedia', '2025-10-30 15:57:10'),
(11, 'Samsung Galaxy A54', 'Samsung', 'Galaxy A series dengan fitur lengkap dan harga terjangkau', 'Processor: Exynos 1280, RAM: 6GB, Storage: 128GB, Display: 6.5 inch AMOLED', 5999000.00, 22, NULL, 'Mid-Range', 'Tersedia', '2025-10-30 15:57:10'),
(12, 'Xiaomi Redmi Note 13', 'Xiaomi', 'Redmi Note dengan spesifikasi tangguh dan baterai besar', 'Processor: Snapdragon 685, RAM: 4GB, Storage: 128GB, Display: 6.67 inch IPS LCD', 3499000.00, 30, NULL, 'Budget', 'Tersedia', '2025-10-30 15:57:10'),
(13, 'Oppo Reno 8', 'Oppo', 'Reno series dengan kamera portrait dan design premium', 'Processor: Snapdragon 778G+, RAM: 8GB, Storage: 256GB, Display: 6.43 inch AMOLED', 6999000.00, 16, NULL, 'Mid-Range', 'Tersedia', '2025-10-30 15:57:10');

-- --------------------------------------------------------

--
-- Table structure for table `promo`
--

CREATE TABLE `promo` (
  `id_promo` int(11) NOT NULL,
  `nama_promo` varchar(100) NOT NULL,
  `jenis_promo` enum('Diskon Persentase','Diskon Nominal','Flash Sale') NOT NULL,
  `nilai_diskon` decimal(10,2) DEFAULT NULL,
  `persentase_diskon` int(11) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status_promo` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promo`
--

INSERT INTO `promo` (`id_promo`, `nama_promo`, `jenis_promo`, `nilai_diskon`, `persentase_diskon`, `tanggal_mulai`, `tanggal_selesai`, `status_promo`, `deskripsi`) VALUES
(1, 'Diskon Akhir Tahun', 'Diskon Persentase', NULL, 15, '2025-10-20', '2025-12-31', 'Aktif', 'Diskon 15% untuk semua produk smartphone'),
(2, 'Flash Sale Midnight', 'Flash Sale', 1000000.00, NULL, '2025-10-23', '2025-10-24', 'Aktif', 'Potongan langsung Rp 1.000.000 untuk pembelian di atas Rp 10.000.000');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `status_pesanan` enum('Menunggu Konfirmasi','Diproses','Dikirim','Selesai','Dibatalkan') DEFAULT 'Menunggu Konfirmasi',
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `alamat_pengiriman` text DEFAULT NULL,
  `no_resi` varchar(100) DEFAULT NULL,
  `tanggal_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_diperbarui` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `kode_transaksi` varchar(50) DEFAULT NULL,
  `catatan_user` text DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `ekspedisi` varchar(100) DEFAULT NULL,
  `no_resi_awal` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_user`, `total_harga`, `status_pesanan`, `metode_pembayaran`, `alamat_pengiriman`, `no_resi`, `tanggal_transaksi`, `tanggal_diperbarui`, `kode_transaksi`, `catatan_user`, `bukti_pembayaran`, `ekspedisi`, `no_resi_awal`) VALUES
(1, 5, 30517000.00, '', '', 'Jln.Los santos No 4B, nanggulo, Los santos, Los angeles 65213', NULL, '2026-01-08 08:45:04', '2026-01-08 08:45:04', 'TRX-20260108094504-45880', '', NULL, NULL, NULL),
(2, 5, 7099000.00, '', '', 'los santos, nanggulo, Los santos, Los angeles 22213', NULL, '2026-01-08 09:11:53', '2026-01-08 09:11:53', 'TRX-20260108101153-92998', '', NULL, NULL, NULL),
(3, 5, 3519000.00, '', '', 'Jln.Testing123, Testing, Testing, Testing 11111', NULL, '2026-01-08 09:21:13', '2026-01-08 09:21:13', 'TRX-20260108102113-61438', '', NULL, NULL, NULL),
(4, 5, 7019000.00, '', '', 'Testing2, t, t, Testing 65213', NULL, '2026-01-08 09:40:25', '2026-01-08 09:40:25', 'TRX-20260108104025-79844', '', NULL, NULL, NULL),
(5, 5, 7019000.00, '', '', 'Testing, Testing, Testing, Testing 11111', NULL, '2026-01-08 10:02:14', '2026-01-08 10:02:14', 'TRX-20260108110214-88958', '', NULL, NULL, NULL),
(6, 5, 7019000.00, '', '', 'Testing, Testing, Testing, Testing 11111', NULL, '2026-01-08 10:09:09', '2026-01-08 10:09:09', 'TRX-20260108110909-33389', '', NULL, NULL, NULL),
(7, 5, 7019000.00, '', 'bank_transfer', 't, Testing, Testing, Testing 11111', NULL, '2026-01-08 10:37:33', '2026-01-08 12:47:52', 'TRX-20260108113733-72620', '', 'pembayaran_5_1767876472.png', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ulasan`
--

CREATE TABLE `ulasan` (
  `id_ulasan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `komentar` text DEFAULT NULL,
  `tanggal_ulasan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_akun` enum('Aktif','Nonaktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `email`, `no_telepon`, `alamat`, `tanggal_daftar`, `status_akun`) VALUES
(1, 'user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'budi@email.com', '081234567891', 'Jl. Merdeka No. 10, Jakarta', '2025-10-23 14:18:47', 'Aktif'),
(2, 'user2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Nurhaliza', 'siti@email.com', '081234567892', 'Jl. Sudirman No. 25, Bandung', '2025-10-23 14:18:47', 'Aktif'),
(3, 'testing', '$2y$10$j5YeypK1H9vJ3O.Goot0ZOOrT9/ipmcAlTIzLTLIDg174DPs/dbG2', 'Testing 1', 'testing@gmail.com', NULL, NULL, '2025-12-11 12:24:47', 'Aktif'),
(4, 'testing2', '$2y$10$gFRcVSGgJWC51N3w0J9h0uhAJc/DI30fyAWyB9kqiapNvzVbZeR.a', 'Testing 2', 'testing2@email.com', NULL, NULL, '2025-12-11 12:26:21', 'Aktif'),
(5, 'salambim', '$2y$10$NpcevXkrdPI9AzQvJRonguIC2xV3sjgPR1K0HpPlPG7GkYTLmeyTy', 'salabim', 'salambim@example.com', NULL, NULL, '2025-12-11 12:35:23', 'Aktif'),
(6, 'salambim2', '$2y$10$EqwQUEZzlVwzvTjG7CAwM.TDsz.eOkbk3ccwjxh02lc/fuL6tgQ9y', 'salabim2', 'salambim2@example.com', NULL, NULL, '2025-12-11 13:19:54', 'Aktif'),
(7, '', '', '', 'temp_1766077976@mobilenest.local', NULL, NULL, '2025-12-18 17:12:56', 'Aktif'),
(47, 'Buriram', '$2y$10$u7vCXKWuvYV4td0XJbZB/uxssgDgatsQooNPGwf1QeU9kNfcMAZzG', 'Buriram', 'Buriram@example.com', NULL, NULL, '2025-12-18 17:18:42', 'Aktif'),
(80, 'Cobain', '$2y$10$48.nKphDR/.0GCNh89yTHuIa5f1Tibn/vf2A5pa1kqqwnlIP8c9.C', 'Cobain', 'Cobain@gmail.com', NULL, NULL, '2025-12-29 06:53:34', 'Aktif'),
(86, 'Zyruss', '$2y$10$qCivZeRtoo//YBR1RG7ISOyKb/dEEZwSw2jJO5T4YaMApWxB3fEe2', 'Zyrus', 'Zyruss@gmail.com', NULL, NULL, '2025-12-30 22:09:41', 'Aktif'),
(94, 'guest_1767698518_322', '$2y$10$q9CJjuOA1d9GqIZ7XGj83uVDHbti1GLQds4Qs87OnfhPemv.B7Sta', 'Guest Shopper', 'guest_1767698518_322@temp.local', NULL, NULL, '2026-01-06 11:21:58', 'Aktif'),
(95, 'guest_1767807645_389', '$2y$10$3nV0xuVjiXiEfQ6lj45lZOS3CgTF5f0k9ogdagmTqRJyZ2j4qflyG', 'Guest Shopper', 'guest_1767807645_389@temp.local', NULL, NULL, '2026-01-07 17:40:46', 'Aktif'),
(96, 'guest_1767812495_349', '$2y$10$34Y62yMia5B5.cLfRaBv/uaHjfb2rzJU6CpBnN3g0vipXjwtrJ1QC', 'Guest Shopper', 'guest_1767812495_349@temp.local', NULL, NULL, '2026-01-07 19:01:35', 'Aktif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `idx_transaksi` (`id_transaksi`),
  ADD KEY `idx_produk` (`id_produk`);

--
-- Indexes for table `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id_keranjang`),
  ADD UNIQUE KEY `unique_user_produk` (`id_user`,`id_produk`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `idx_user` (`id_user`);

--
-- Indexes for table `pengiriman`
--
ALTER TABLE `pengiriman`
  ADD PRIMARY KEY (`id_pengiriman`),
  ADD UNIQUE KEY `no_pengiriman` (`no_pengiriman`),
  ADD KEY `idx_id_user` (`id_user`),
  ADD KEY `idx_no_pengiriman` (`no_pengiriman`),
  ADD KEY `idx_status` (`status_pengiriman`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `idx_merek` (`merek`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_harga` (`harga`);

--
-- Indexes for table `promo`
--
ALTER TABLE `promo`
  ADD PRIMARY KEY (`id_promo`),
  ADD KEY `idx_status` (`status_promo`),
  ADD KEY `idx_tanggal` (`tanggal_mulai`,`tanggal_selesai`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_status` (`status_pesanan`),
  ADD KEY `idx_tanggal` (`tanggal_transaksi`);

--
-- Indexes for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id_ulasan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_produk` (`id_produk`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id_keranjang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `pengiriman`
--
ALTER TABLE `pengiriman`
  MODIFY `id_pengiriman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `promo`
--
ALTER TABLE `promo`
  MODIFY `id_promo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id_ulasan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE;

--
-- Constraints for table `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD CONSTRAINT `ulasan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ulasan_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
