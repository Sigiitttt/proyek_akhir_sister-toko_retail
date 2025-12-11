-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Des 2025 pada 16.12
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
-- Database: `retail_toko_lokal`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int(11) NOT NULL,
  `id_transaksi` varchar(60) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_satuan` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL
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
(14, 'TRX-1-20251209154858-562', 2, 1, 8000.00, 8000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `kode_produk` varchar(50) DEFAULT NULL,
  `nama_produk` varchar(150) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `harga_jual` decimal(12,2) DEFAULT NULL,
  `stok_lokal` int(11) DEFAULT 0,
  `updated_at_pusat` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `kode_produk`, `nama_produk`, `satuan`, `harga_jual`, `stok_lokal`, `updated_at_pusat`) VALUES
(1, '123', 'susu ultramilk', 'botol', 7000.00, 45, '2025-12-09 21:41:45'),
(2, '124', 'oero vanila', 'pacs', 8000.00, 24, '2025-12-09 21:41:45'),
(3, '125', 'roma sari gandum', 'pcs', 12000.00, 80, '2025-12-09 21:41:45'),
(4, '126', 'aqua', 'pcs', 5000.00, 90, '2025-12-09 21:41:45'),
(5, '127', 'kopi susu', 'pcs', 5000.00, 55, '2025-12-09 21:42:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` varchar(60) NOT NULL,
  `no_struk` varchar(50) DEFAULT NULL,
  `total_transaksi` decimal(12,2) DEFAULT NULL,
  `bayar` decimal(12,2) DEFAULT NULL,
  `kembalian` decimal(12,2) DEFAULT NULL,
  `metode_pembayaran` varchar(50) DEFAULT 'Tunai',
  `id_kasir` int(11) DEFAULT NULL,
  `waktu_transaksi` datetime DEFAULT current_timestamp(),
  `kasir_id` int(11) DEFAULT NULL,
  `is_synced` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `no_struk`, `total_transaksi`, `bayar`, `kembalian`, `metode_pembayaran`, `id_kasir`, `waktu_transaksi`, `kasir_id`, `is_synced`) VALUES
('TRX-1-20251209140932-364', 'STR-2512095233', 5000.00, 5000.00, 0.00, 'Tunai', NULL, '2025-12-09 20:09:32', 2, 1),
('TRX-1-20251209141738-551', 'STR-2512099413', 12000.00, 50000.00, 38000.00, 'Tunai', NULL, '2025-12-09 20:17:38', 2, 1),
('TRX-1-20251209141750-363', 'STR-2512098052', 15000.00, 100000.00, 85000.00, 'Tunai', NULL, '2025-12-09 20:17:50', 2, 1),
('TRX-1-20251209141800-884', 'STR-2512099748', 21000.00, 21000.00, 0.00, 'Tunai', NULL, '2025-12-09 20:18:00', 2, 1),
('TRX-1-20251209143047-767', 'STR-2512099966', 12000.00, 12000.00, 0.00, 'Tunai', NULL, '2025-12-09 20:30:47', 4, 1),
('TRX-1-20251209143744-137', 'STR-2512095495', 8000.00, 8000.00, 0.00, 'QRIS', NULL, '2025-12-09 20:37:44', 4, 1),
('TRX-1-20251209143942-417', 'STR-2512096305', 14000.00, 14000.00, 0.00, 'Debit', NULL, '2025-12-09 20:39:42', 4, 1),
('TRX-1-20251209144147-388', 'STR-2512096673', 8000.00, 50000.00, 42000.00, 'Tunai', NULL, '2025-12-09 20:41:47', 4, 1),
('TRX-1-20251209144353-278', 'STR-2512092535', 48000.00, 48000.00, 0.00, 'Tunai', NULL, '2025-12-09 20:43:53', 4, 1),
('TRX-1-20251209144901-633', 'STR-2512098254', 12000.00, 12000.00, 0.00, 'Tunai', NULL, '2025-12-09 20:49:01', 4, 1),
('TRX-1-20251209150013-435', 'STR-2512094862', 12000.00, 12000.00, 0.00, 'Tunai', NULL, '2025-12-09 21:00:13', 4, 1),
('TRX-1-20251209154333-963', 'STR-2512096448', 25000.00, 25000.00, 0.00, 'Tunai', NULL, '2025-12-09 21:43:33', 4, 1),
('TRX-1-20251209154858-562', 'STR-2512097834', 8000.00, 8000.00, 0.00, 'Debit', NULL, '2025-12-09 21:48:58', 4, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role` enum('kasir','kepala_toko') DEFAULT 'kasir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(2, 'kasir1', '123456', 'Budi Santoso', 'kasir'),
(3, 'kasir2', 'kasir123', 'Siti Aminah', 'kasir'),
(4, 'spv', 'admin', 'herman spv', 'kepala_toko');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_kasir` (`id_kasir`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`),
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_kasir`) REFERENCES `users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
