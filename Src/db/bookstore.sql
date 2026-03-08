-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 11, 2026 at 01:19 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int NOT NULL,
  `sender_id` varchar(100) NOT NULL,
  `receiver_id` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `notification` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `message`, `timestamp`, `is_read`, `notification`) VALUES
(1, 'maryam@gmail.com', 'raihan@gmail.com', 'hai', '2026-01-28 01:26:43', 0, 1),
(2, 'maryam@gmail.com', 'efan@gmail.com', 'hai', '2026-01-28 01:31:18', 1, 1),
(3, 'efan@gmail.com', 'maryam@gmail.com', 'halo', '2026-01-28 01:51:36', 1, 1),
(4, 'efan@gmail.com', 'maryam@gmail.com', 'saya mau tanya, bisa kayang ga', '2026-01-28 01:52:05', 1, 1),
(5, 'maryam@gmail.com', 'efan@gmail.com', 'saya bisanya terbang', '2026-01-28 01:53:18', 1, 1),
(6, 'efan@gmail.com', 'maryam@gmail.com', 'Halo', '2026-01-28 14:52:27', 1, 1),
(7, 'maryam@gmail.com', 'efan@gmail.com', 'ya?', '2026-01-28 14:52:35', 1, 1),
(8, 'jovandwilly28@gmail.com', 'efan@gmail.com', 'assalmuaikum', '2026-01-29 06:43:23', 1, 1),
(9, 'efan@gmail.com', 'jovandwilly28@gmail.com', 'hai', '2026-01-29 06:43:32', 1, 1),
(10, 'jovandwilly28@gmail.com', 'royyanarga29@gmail.com', 'jovan', '2026-01-29 06:43:51', 0, 1),
(11, 'jovandwilly28@gmail.com', 'husni@gmail.com', 'p', '2026-01-30 02:10:29', 1, 1),
(12, 'husni@gmail.com', 'jovandwilly28@gmail.com', 'ya?', '2026-01-30 02:10:45', 1, 1),
(13, 'jovandwilly28@gmail.com', 'husni@gmail.com', 'hai', '2026-01-30 02:13:55', 1, 1),
(14, 'jovandwilly28@gmail.com', 'husni@gmail.com', 'hai', '2026-01-30 02:13:57', 1, 1),
(15, 'husni@gmail.com', 'jovandwilly28@gmail.com', 'ya \\', '2026-01-30 02:14:10', 1, 1),
(16, 'husni@gmail.com', 'jovandwilly28@gmail.com', 'ya', '2026-01-30 02:14:11', 1, 1),
(17, 'jovandwilly28@gmail.com', 'husni@gmail.com', 'hai', '2026-01-30 02:31:27', 1, 1),
(18, 'jovandwilly28@gmail.com', 'raka@gmail.com', 'test', '2026-02-09 01:40:31', 1, 1),
(19, 'raka@gmail.com', 'jovandwilly28@gmail.com', 'hallo', '2026-02-09 01:40:44', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `help_requests`
--

CREATE TABLE `help_requests` (
  `id` int NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `user_phone` varchar(20) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text,
  `status` enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  `admin_notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori_produk`
--

INSERT INTO `kategori_produk` (`id`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Fiksi', 'Buku yang berisi cerita imajinatif, seperti novel, cerpen, dan romance. '),
(3, 'Pendidikan', 'Buku pelajaran, referensi, dan materi akademik untuk sekolah.'),
(5, 'Hobi & Gaya Hidup', 'Buku tentang aktivitas sehari-hari dan minat khusus, seperti memasak, traveling, dll.'),
(6, 'Pelajaran', 'Buku bergambar dengan alur cerita visual, seperti manga dan novel.'),
(7, 'Agama & Spiritual', 'Cocok untuk mendalami ilmu spiritual');

-- --------------------------------------------------------

--
-- Table structure for table `keranjang`
--

CREATE TABLE `keranjang` (
  `id_keranjang` int NOT NULL,
  `id_buku` varchar(100) DEFAULT NULL,
  `judul_buku` varchar(100) NOT NULL,
  `harga` varchar(100) NOT NULL,
  `qty` varchar(100) NOT NULL,
  `total_harga` varchar(100) NOT NULL,
  `email_pembeli` varchar(100) NOT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `email_penjual` varchar(100) NOT NULL,
  `nama_penjual` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `keranjang`
--

INSERT INTO `keranjang` (`id_keranjang`, `id_buku`, `judul_buku`, `harga`, `qty`, `total_harga`, `email_pembeli`, `nama_pembeli`, `email_penjual`, `nama_penjual`) VALUES
(12, '7', 'IPAS', '20000', '2', '40000', 'ipuy@gmail.com', 'ipuy', 'jojodkijr28@gmail.com', 'jojo');

-- --------------------------------------------------------

--
-- Table structure for table `pembeli`
--

CREATE TABLE `pembeli` (
  `nik_pembeli` varchar(100) NOT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `email_pembeli` varchar(100) NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat_pembeli` varchar(100) NOT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `status` enum('Online','Offline') DEFAULT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembeli`
--

INSERT INTO `pembeli` (`nik_pembeli`, `nama_pembeli`, `email_pembeli`, `password`, `alamat_pembeli`, `foto`, `status`, `created_at`) VALUES
('13115652782625342', 'Maryam Hanifah', 'jovandwilly28@gmail.com', '$2y$10$n0SQUSuXdcaEOC0RQswGyuaFuoktE2AA2A7ElguORuX05bEfUi0fu', 'jl. Bandengan Utara I, RT 004 RW 011', 'foto_1768835229_630.png', 'Online', '2026-01-24'),
('5237726278530000', 'Queen', 'marsya@gmail.com', '$2y$10$zeQCd7OPgBDVPTnGQyepiu7DOkv7BatOY45faxWY77EGniE71KHzy', 'jl. Bandengan Utara I, RT 004 RW 011', NULL, 'Offline', '2026-01-24'),
('7168478478274848', 'Nabila Hajizah', 'nabila@gmail.com', '$2y$10$h.aqMizQYAsRh/Wc7r4JTu4ksryfSI2QS16QWCZkBL88.VRBykCb2', 'jl. Bandengan Utara I, RT 004 RW 011', NULL, 'Offline', '2026-01-24'),
('7182687162817627', 'Reysya Faragista', 'reysya@gmail.com', '$2y$10$LhPeuxIM.5kOnaXzmDNt3.rkTtQO8u5rUQIL3Vk0Ep1rg4y9q1Pce', 'jl. Bandengan Utara I, RT 004 RW 011', NULL, 'Offline', '2026-01-24'),
('7872846272687632', 'Khansa Nur', 'khansa@gmail.com', '$2y$10$hU5k5L1M66vS4zX0PoDDWOeYJ5j65vwawDl8zfNjP9pEgUddUwaGK', 'jl. Bandengan Utara I, RT 004 RW 011', NULL, 'Offline', '2026-01-24');

-- --------------------------------------------------------

--
-- Table structure for table `penjual`
--

CREATE TABLE `penjual` (
  `nik_penjual` varchar(100) NOT NULL,
  `nama_penjual` varchar(100) NOT NULL,
  `email_penjual` varchar(100) NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat_penjual` varchar(100) NOT NULL,
  `no_rekening` varchar(100) DEFAULT NULL,
  `debit` varchar(100) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `status` enum('Online','Offline') DEFAULT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penjual`
--

INSERT INTO `penjual` (`nik_penjual`, `nama_penjual`, `email_penjual`, `password`, `alamat_penjual`, `no_rekening`, `debit`, `foto`, `status`, `created_at`) VALUES
('5555555515111100', 'Raka Anugrah Satya', 'raka@gmail.com', '$2y$10$fpLNdSATXZ.GMsIs/gwH4.StgUN4KA8k.iOQeE4O/wxXqSfLPkQUu', 'jl. Bandengan Utara I, RT 004 RW 011', '81728172812712811781221', 'BCA', NULL, 'Online', '2026-01-24'),
('8278917981728917', 'Bramasta Raditya', 'bramasta@gmail.com', '$2y$10$2IuPOCPY/ryig.tl6HIml.GScoIIwPdAiZKg/6oLtx2JHS8MSDOlC', 'jl. Bandengan Utara I, RT 004 RW 011', '001987657272727277', 'BCA', NULL, 'Offline', '2026-02-09');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `kode_pesanan` varchar(100) NOT NULL,
  `id_buku` varchar(100) DEFAULT NULL,
  `judul_buku` varchar(100) NOT NULL,
  `harga_satuan` varchar(100) DEFAULT NULL,
  `qty` varchar(100) NOT NULL,
  `total_harga` varchar(100) DEFAULT NULL,
  `metode_bayar` enum('QRIS','Transfer') DEFAULT NULL,
  `approve` enum('Disetujui','Ditolak') DEFAULT NULL,
  `waktu_tolak` datetime DEFAULT NULL,
  `status` enum('Refund','Dikirim','Diterima') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ekspedisi` varchar(100) DEFAULT NULL,
  `no_resi` varchar(100) DEFAULT NULL,
  `email_penjual` varchar(100) DEFAULT NULL,
  `nama_penjual` varchar(100) DEFAULT NULL,
  `alamat_pembeli` varchar(100) DEFAULT NULL,
  `email_pembeli` varchar(100) DEFAULT NULL,
  `nama_pembeli` varchar(100) DEFAULT NULL,
  `bukti_pembayaran` varchar(100) DEFAULT NULL,
  `tanggal_pesanan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`kode_pesanan`, `id_buku`, `judul_buku`, `harga_satuan`, `qty`, `total_harga`, `metode_bayar`, `approve`, `waktu_tolak`, `status`, `ekspedisi`, `no_resi`, `email_penjual`, `nama_penjual`, `alamat_pembeli`, `email_pembeli`, `nama_pembeli`, `bukti_pembayaran`, `tanggal_pesanan`) VALUES
('ORD20260209014416162499b97', '21', 'PAI', '100000', '1', '100000', 'Transfer', 'Disetujui', NULL, 'Diterima', 'JET Express', '1818181871gaga', 'bramasta@gmail.com', 'Bramasta Raditya', 'jl. Bandengan Utara I, RT 004 RW 011', 'jovandwilly28@gmail.com', 'Maryam Hanifah', 'bukti_1770601456_69893bf0bcd48_499b9783.png', '2026-02-09 08:44:16');

-- --------------------------------------------------------

--
-- Table structure for table `produk_buku`
--

CREATE TABLE `produk_buku` (
  `id_buku` int NOT NULL,
  `kategori_buku` varchar(100) NOT NULL,
  `judul_buku` varchar(100) NOT NULL,
  `harga_buku` varchar(100) NOT NULL,
  `modal` varchar(100) DEFAULT NULL,
  `stok` varchar(100) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `status` enum('Aktif','Habis') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Aktif',
  `email_penjual` varchar(100) NOT NULL,
  `nama_penjual` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `produk_buku`
--

INSERT INTO `produk_buku` (`id_buku`, `kategori_buku`, `judul_buku`, `harga_buku`, `modal`, `stok`, `foto`, `status`, `email_penjual`, `nama_penjual`, `created_at`) VALUES
(20, 'Fiksi', 'Komik', '100000', '50000', '98', '1769762568_Screenshot (27).png', 'Aktif', 'raka@gmail.com', 'Raka Anugrah Satya', NULL),
(21, 'Agama & Spiritual', 'PAI', '100000', '50000', '39', '1770601392_Screenshot (27).png', 'Aktif', 'bramasta@gmail.com', 'Bramasta Raditya', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_hapus`
--

CREATE TABLE `riwayat_hapus` (
  `id` int NOT NULL,
  `kode_pesanan` varchar(50) NOT NULL,
  `email_pembeli` varchar(100) NOT NULL,
  `judul_buku` varchar(255) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `alasan` varchar(50) DEFAULT 'refund_selesai',
  `tanggal_hapus` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `riwayat_hapus`
--

INSERT INTO `riwayat_hapus` (`id`, `kode_pesanan`, `email_pembeli`, `judul_buku`, `total_harga`, `alasan`, `tanggal_hapus`) VALUES
(1, 'ORD20260129103349889', 'jovandwilly28@gmail.com', 'MTK', '300000.00', 'refund_selesai', '2026-01-29 18:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `super_admin`
--

CREATE TABLE `super_admin` (
  `id_admin` int NOT NULL,
  `email_admin` varchar(100) NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `foto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `super_admin`
--

INSERT INTO `super_admin` (`id_admin`, `email_admin`, `password`, `nama_admin`, `foto`) VALUES
(1, 'jovandkijr28@gmail.com', '$2y$10$YwFzTDJ5wwma8Q/C7IywkOBZ1xkwM.WG6bIg1WcCnWLjMGWGCEgpG', 'Jovan Djiauwilly', 'foto_1769667943_112.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `help_requests`
--
ALTER TABLE `help_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id_keranjang`);

--
-- Indexes for table `pembeli`
--
ALTER TABLE `pembeli`
  ADD PRIMARY KEY (`nik_pembeli`);

--
-- Indexes for table `penjual`
--
ALTER TABLE `penjual`
  ADD PRIMARY KEY (`nik_penjual`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`kode_pesanan`);

--
-- Indexes for table `produk_buku`
--
ALTER TABLE `produk_buku`
  ADD PRIMARY KEY (`id_buku`);

--
-- Indexes for table `riwayat_hapus`
--
ALTER TABLE `riwayat_hapus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kode` (`kode_pesanan`),
  ADD KEY `idx_email` (`email_pembeli`),
  ADD KEY `idx_tanggal` (`tanggal_hapus`);

--
-- Indexes for table `super_admin`
--
ALTER TABLE `super_admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `help_requests`
--
ALTER TABLE `help_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id_keranjang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `produk_buku`
--
ALTER TABLE `produk_buku`
  MODIFY `id_buku` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `riwayat_hapus`
--
ALTER TABLE `riwayat_hapus`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `super_admin`
--
ALTER TABLE `super_admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
