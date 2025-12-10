-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Des 2025 pada 10.16
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `retail_pusat`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_admin`, `created_at`) VALUES
(2, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner sigit', '2025-12-09 09:13:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` bigint(20) NOT NULL,
  `id_transaksi` varchar(60) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `qty`, `harga_satuan`, `subtotal`) VALUES
(1, 'TRX-1-20251209140932-364', 4, 1, 5000.00, 5000.00),
(2, 'TRX-1-20251209141738-551', 3, 1, 12000.00, 12000.00),
(3, 'TRX-1-20251209141750-363', 2, 1, 8000.00, 8000.00),
(4, 'TRX-1-20251209141750-363', 1, 1, 7000.00, 7000.00),
(5, 'TRX-1-20251209141800-884', 1, 3, 7000.00, 21000.00),
(6, 'TRX-1-20251209143047-767', 3, 1, 12000.00, 12000.00),
(7, 'TRX-1-20251209143744-137', 2, 1, 8000.00, 8000.00),
(8, 'TRX-1-20251209143942-417', 1, 2, 7000.00, 14000.00),
(9, 'TRX-1-20251209144147-388', 2, 1, 8000.00, 8000.00),
(10, 'TRX-1-20251209144353-278', 2, 6, 8000.00, 48000.00),
(11, 'TRX-1-20251209144901-633', 3, 1, 12000.00, 12000.00),
(12, 'TRX-1-20251209150013-435', 3, 1, 12000.00, 12000.00),
(13, 'TRX-1-20251209154333-963', 5, 5, 5000.00, 25000.00),
(14, 'TRX-1-20251209154858-562', 2, 1, 8000.00, 8000.00),
(15, 'TRX-2-20251209161613-155', 5, 1, 5000.00, 5000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `harga`
--

CREATE TABLE `harga` (
  `id_harga` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_toko` int(11) DEFAULT NULL,
  `harga_jual` decimal(12,2) NOT NULL,
  `tgl_berlaku` datetime DEFAULT current_timestamp(),
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `harga`
--

INSERT INTO `harga` (`id_harga`, `id_produk`, `id_toko`, `harga_jual`, `tgl_berlaku`, `aktif`) VALUES
(1, 1, NULL, 15000.00, '2025-12-07 12:38:02', 0),
(2, 2, NULL, 5000.00, '2025-12-07 15:01:24', 0),
(3, 1, NULL, 15000.00, '2025-12-09 14:40:50', 0),
(4, 1, NULL, 15000.00, '2025-12-09 14:40:58', 0),
(5, 1, NULL, 7000.00, '2025-12-09 14:41:56', 0),
(6, 1, NULL, 7000.00, '2025-12-09 14:42:12', 0),
(7, 2, NULL, 8000.00, '2025-12-09 14:43:31', 0),
(8, 2, NULL, 8000.00, '2025-12-09 14:44:59', 0),
(9, 2, NULL, 8000.00, '2025-12-09 14:45:38', 0),
(10, 3, NULL, 9000.00, '2025-12-09 14:45:48', 0),
(11, 2, NULL, 8000.00, '2025-12-09 14:52:20', 1),
(12, 3, NULL, 9000.00, '2025-12-09 14:52:34', 1),
(13, 1, NULL, 7000.00, '2025-12-09 14:52:41', 1),
(14, 3, 1, 9000.00, '2025-12-09 15:01:40', 0),
(15, 3, 1, 9000.00, '2025-12-09 15:02:25', 0),
(16, 3, 1, 12000.00, '2025-12-09 15:05:08', 1),
(17, 4, NULL, 3000.00, '2025-12-09 16:06:45', 1),
(18, 4, 1, 5000.00, '2025-12-09 16:07:05', 1),
(19, 5, NULL, 5000.00, '2025-12-09 21:33:58', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT 'pcs',
  `gambar` varchar(255) DEFAULT NULL,
  `stok_global` int(11) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `kode_produk`, `nama_produk`, `kategori`, `satuan`, `gambar`, `stok_global`, `updated_at`, `status`, `created_at`) VALUES
(1, '123', 'susu ultramilk', 'Minuman', 'botol', '1765266116_Screenshot 2025-12-09 144135.png', 5, '2025-12-09 21:41:45', 'aktif', '2025-12-09 14:45:32'),
(2, '124', 'oero vanila', 'Makanan', 'pacs', '1765266338_Screenshot 2025-12-09 144247.png', 20, '2025-12-09 21:41:45', 'aktif', '2025-12-09 14:45:32'),
(3, '125', 'roma sari gandum', 'Minuman', 'pcs', '1765266348_Screenshot 2025-12-09 144353.png', 10, '2025-12-09 21:41:45', 'aktif', '2025-12-09 14:45:48'),
(4, '126', 'aqua', 'Minuman', 'pcs', '1765271205_Screenshot 2025-12-09 160633.png', 5, '2025-12-09 21:41:45', 'aktif', '2025-12-09 16:06:45'),
(5, '127', 'kopi susu', 'Minuman', 'pcs', '1765290838_Screenshot 2025-12-09 213347.png', 20, '2025-12-09 22:15:38', 'aktif', '2025-12-09 21:33:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_distribusi`
--

CREATE TABLE `riwayat_distribusi` (
  `id_distribusi` int(11) NOT NULL,
  `id_toko` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `status` enum('pending','terkirim') DEFAULT 'terkirim'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_distribusi`
--

INSERT INTO `riwayat_distribusi` (`id_distribusi`, `id_toko`, `id_produk`, `jumlah`, `tanggal`, `status`) VALUES
(4, 1, 2, 10, '2025-12-09 15:53:07', 'terkirim'),
(5, 1, 3, 10, '2025-12-09 15:53:21', 'terkirim'),
(6, 1, 1, 10, '2025-12-09 15:53:33', 'terkirim'),
(7, 1, 4, 45, '2025-12-09 16:07:23', 'terkirim'),
(8, 1, 1, 10, '2025-12-09 20:54:11', 'terkirim'),
(9, 1, 1, 10, '2025-12-09 20:58:43', 'terkirim'),
(10, 1, 3, 15, '2025-12-09 20:59:08', 'terkirim'),
(11, 1, 3, 5, '2025-12-09 21:03:00', 'terkirim'),
(12, 1, 1, 5, '2025-12-09 21:07:00', 'terkirim'),
(13, 1, 2, 10, '2025-12-09 21:08:41', 'terkirim'),
(14, 1, 2, 10, '2025-12-09 21:17:38', 'terkirim'),
(15, 1, 3, 10, '2025-12-09 21:17:47', 'terkirim'),
(16, 1, 1, 5, '2025-12-09 21:22:44', 'terkirim'),
(17, 1, 1, 5, '2025-12-09 21:28:23', 'terkirim'),
(18, 1, 5, 20, '2025-12-09 21:34:25', 'terkirim'),
(19, 1, 5, 40, '2025-12-09 21:42:05', 'terkirim'),
(20, 2, 5, 20, '2025-12-09 22:15:38', 'terkirim');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_toko`
--

CREATE TABLE `stok_toko` (
  `id_stok` int(11) NOT NULL,
  `id_toko` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) DEFAULT 0,
  `last_update` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stok_toko`
--

INSERT INTO `stok_toko` (`id_stok`, `id_toko`, `id_produk`, `jumlah`, `last_update`) VALUES
(1, 1, 1, 45, '2025-12-09 21:28:23'),
(2, 1, 2, 25, '2025-12-09 21:17:38'),
(3, 1, 3, 40, '2025-12-09 21:17:47'),
(4, 1, 4, 45, '2025-12-09 16:07:23'),
(5, 1, 5, 60, '2025-12-09 21:42:05'),
(6, 2, 5, 20, '2025-12-09 22:15:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `toko`
--

CREATE TABLE `toko` (
  `id_toko` int(11) NOT NULL,
  `kode_toko` varchar(10) NOT NULL,
  `nama_toko` varchar(100) NOT NULL,
  `kepala_toko` varchar(100) DEFAULT '-',
  `kontak_hp` varchar(20) DEFAULT '-',
  `alamat` text DEFAULT NULL,
  `api_key` varchar(150) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `toko`
--

INSERT INTO `toko` (`id_toko`, `kode_toko`, `nama_toko`, `kepala_toko`, `kontak_hp`, `alamat`, `api_key`, `is_active`) VALUES
(1, 'JKT01', 'Toko Jakarta Pusat', '- herman', '084564788566', 'jakarta', 'e52610451ccea924ef56fb4ff70ae427', 1),
(2, 'SBY01', 'Surabaya', 'andik', '085454878845 ', 'gubeng surabaya\r\n', '030303ca251290599f55a793e1268f5c', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` varchar(60) NOT NULL,
  `id_toko` int(11) NOT NULL,
  `no_struk` varchar(50) DEFAULT NULL,
  `metode_pembayaran` varchar(50) DEFAULT 'Tunai',
  `total_transaksi` decimal(12,2) NOT NULL,
  `bayar` decimal(12,2) NOT NULL,
  `kembalian` decimal(12,2) NOT NULL,
  `waktu_transaksi` datetime NOT NULL,
  `kasir_id` int(11) DEFAULT NULL,
  `nama_kasir` varchar(100) DEFAULT '-',
  `waktu_sync` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_toko`, `no_struk`, `metode_pembayaran`, `total_transaksi`, `bayar`, `kembalian`, `waktu_transaksi`, `kasir_id`, `nama_kasir`, `waktu_sync`) VALUES
('TRX-1-20251209140932-364', 1, 'STR-2512095233', 'Tunai', 5000.00, 5000.00, 0.00, '2025-12-09 20:09:32', NULL, '-', '2025-12-09 13:17:02'),
('TRX-1-20251209141738-551', 1, 'STR-2512099413', 'Tunai', 12000.00, 50000.00, 38000.00, '2025-12-09 20:17:38', NULL, '-', '2025-12-09 13:18:37'),
('TRX-1-20251209141750-363', 1, 'STR-2512098052', 'Tunai', 15000.00, 100000.00, 85000.00, '2025-12-09 20:17:50', NULL, '-', '2025-12-09 13:18:37'),
('TRX-1-20251209141800-884', 1, 'STR-2512099748', 'Tunai', 21000.00, 21000.00, 0.00, '2025-12-09 20:18:00', NULL, '-', '2025-12-09 13:18:37'),
('TRX-1-20251209143047-767', 1, 'STR-2512099966', 'Tunai', 12000.00, 12000.00, 0.00, '2025-12-09 20:30:47', 4, '-', '2025-12-09 13:30:54'),
('TRX-1-20251209143744-137', 1, 'STR-2512095495', 'QRIS', 8000.00, 8000.00, 0.00, '2025-12-09 20:37:44', 4, '-', '2025-12-09 13:37:51'),
('TRX-1-20251209143942-417', 1, 'STR-2512096305', 'Debit', 14000.00, 14000.00, 0.00, '2025-12-09 20:39:42', 4, '-', '2025-12-09 13:39:48'),
('TRX-1-20251209144147-388', 1, 'STR-2512096673', 'Tunai', 8000.00, 50000.00, 42000.00, '2025-12-09 20:41:47', 4, '-', '2025-12-09 13:41:54'),
('TRX-1-20251209144353-278', 1, 'STR-2512092535', 'Tunai', 48000.00, 48000.00, 0.00, '2025-12-09 20:43:53', 4, 'Bambang Supervisor', '2025-12-09 13:47:35'),
('TRX-1-20251209144901-633', 1, 'STR-2512098254', 'Tunai', 12000.00, 12000.00, 0.00, '2025-12-09 20:49:01', 4, 'Bambang herman spv', '2025-12-09 13:49:08'),
('TRX-1-20251209150013-435', 1, 'STR-2512094862', 'Tunai', 12000.00, 12000.00, 0.00, '2025-12-09 21:00:13', 4, 'herman spv', '2025-12-09 14:03:21'),
('TRX-1-20251209154333-963', 1, 'STR-2512096448', 'Tunai', 25000.00, 25000.00, 0.00, '2025-12-09 21:43:33', 4, 'herman spv', '2025-12-09 14:43:38'),
('TRX-1-20251209154858-562', 1, 'STR-2512097834', 'Debit', 8000.00, 8000.00, 0.00, '2025-12-09 21:48:58', 4, 'herman spv', '2025-12-09 14:51:39'),
('TRX-2-20251209161613-155', 2, 'STR-2512099614', 'Tunai', 5000.00, 5000.00, 0.00, '2025-12-09 22:16:13', 4, 'andik spv', '2025-12-09 15:16:21');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `harga`
--
ALTER TABLE `harga`
  ADD PRIMARY KEY (`id_harga`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `id_toko` (`id_toko`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`);

--
-- Indeks untuk tabel `riwayat_distribusi`
--
ALTER TABLE `riwayat_distribusi`
  ADD PRIMARY KEY (`id_distribusi`),
  ADD KEY `id_toko` (`id_toko`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `stok_toko`
--
ALTER TABLE `stok_toko`
  ADD PRIMARY KEY (`id_stok`),
  ADD KEY `id_toko` (`id_toko`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `toko`
--
ALTER TABLE `toko`
  ADD PRIMARY KEY (`id_toko`),
  ADD UNIQUE KEY `kode_toko` (`kode_toko`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_toko` (`id_toko`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `harga`
--
ALTER TABLE `harga`
  MODIFY `id_harga` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `riwayat_distribusi`
--
ALTER TABLE `riwayat_distribusi`
  MODIFY `id_distribusi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `stok_toko`
--
ALTER TABLE `stok_toko`
  MODIFY `id_stok` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `toko`
--
ALTER TABLE `toko`
  MODIFY `id_toko` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `harga`
--
ALTER TABLE `harga`
  ADD CONSTRAINT `harga_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `riwayat_distribusi`
--
ALTER TABLE `riwayat_distribusi`
  ADD CONSTRAINT `riwayat_distribusi_ibfk_1` FOREIGN KEY (`id_toko`) REFERENCES `toko` (`id_toko`),
  ADD CONSTRAINT `riwayat_distribusi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `stok_toko`
--
ALTER TABLE `stok_toko`
  ADD CONSTRAINT `stok_toko_ibfk_1` FOREIGN KEY (`id_toko`) REFERENCES `toko` (`id_toko`) ON DELETE CASCADE,
  ADD CONSTRAINT `stok_toko_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_toko`) REFERENCES `toko` (`id_toko`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
