-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Jan 2026 pada 02.27
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `distrozone_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `kaos_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('customer','admin') NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `attachment_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `kaos_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_jual` decimal(10,2) NOT NULL,
  `harga_modal` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `laba` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id`, `transaksi_id`, `kaos_id`, `qty`, `harga_jual`, `harga_modal`, `subtotal`, `laba`, `created_at`) VALUES
(1, 1, 144, 1, 89000.00, 84000.00, 89000.00, 5000.00, '2026-01-28 01:12:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kaos_master`
--

CREATE TABLE `kaos_master` (
  `id` int(11) NOT NULL,
  `nama_kaos` varchar(100) NOT NULL,
  `merek` varchar(50) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `type_kaos` enum('Lengan Panjang','Lengan Pendek') DEFAULT 'Lengan Pendek',
  `deskripsi` text DEFAULT NULL,
  `foto_utama` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kaos_master`
--

INSERT INTO `kaos_master` (`id`, `nama_kaos`, `merek`, `kategori_id`, `type_kaos`, `deskripsi`, `foto_utama`, `created_at`, `updated_at`) VALUES
(1, 'Vintage Classic Tee', 'DistroZone', 1, 'Lengan Pendek', 'Kaos cotton combed 30s berkualitas tinggi.', 'assets/uploads/products/696de628181c7_1768810024.png', '2026-01-19 07:55:39', '2026-01-19 08:07:04'),
(2, 'Urban Streetwear', 'Erigo', 1, 'Lengan Pendek', 'Desain urban modern untuk gaya maksimal.', 'assets/uploads/products/adidasputih.png', '2026-01-19 07:55:39', '2026-01-19 07:55:39'),
(3, 'Bomber Jacket', 'DistroZone', 3, 'Lengan Panjang', 'Jaket bomber anti angin dan air.', 'assets/uploads/products/697964e78edd6_1769563367.png', '2026-01-19 07:55:39', '2026-01-28 01:22:47'),
(4, 'Classic White Tee', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', 'assets/uploads/products/millsputih.png', '2026-01-19 07:55:39', '2026-01-19 07:55:39'),
(5, 'Midnight Black Oversize', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', 'assets/uploads/products/696deb0c298f6_1768811276.png', '2026-01-19 07:55:39', '2026-01-19 08:27:56'),
(6, 'Navy Essential Long', 'DistroZone', 1, 'Lengan Panjang', 'Produk berkualitas terbaik dari DistroZone', 'assets/uploads/products/696debdfcc1e1_1768811487.png', '2026-01-19 07:55:39', '2026-01-19 08:31:27'),
(7, 'Maron Solid Vibe', 'UrbanStyle', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari UrbanStyle', 'assets/uploads/products/696dec6a68ae1_1768811626.png', '2026-01-19 07:55:39', '2026-01-19 08:33:46'),
(8, 'Army Green Duty', 'UrbanStyle', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari UrbanStyle', 'assets/uploads/products/696dece660d74_1768811750.png', '2026-01-19 07:55:39', '2026-01-19 08:35:50'),
(9, 'Charcoal Grey Daily', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', 'assets/uploads/products/696dedd1d5d6a_1768811985.png', '2026-01-19 07:55:39', '2026-01-19 08:39:45'),
(10, 'Kemeja Workshirt Man', 'Burberry', 2, 'Lengan Panjang', 'Produk berkualitas terbaik dari Burberry', 'assets/uploads/products/696dee45f3071_1768812101.png', '2026-01-19 07:55:39', '2026-01-19 08:41:41'),
(11, 'Crimson Red Energy', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', 'assets/uploads/products/696dee9157d24_1768812177.png', '2026-01-19 07:55:39', '2026-01-19 08:42:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kaos_varian`
--

CREATE TABLE `kaos_varian` (
  `id` int(11) NOT NULL,
  `kaos_master_id` int(11) NOT NULL,
  `kode_varian` varchar(20) NOT NULL,
  `warna` varchar(50) DEFAULT NULL,
  `warna_hex` varchar(7) DEFAULT '#FFFFFF',
  `size` enum('XS','S','M','L','XL','2XL','3XL','4XL','5XL') DEFAULT 'L',
  `harga` decimal(10,2) NOT NULL,
  `harga_pokok` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `foto_varian` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_varian` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kaos_varian`
--

INSERT INTO `kaos_varian` (`id`, `kaos_master_id`, `kode_varian`, `warna`, `warna_hex`, `size`, `harga`, `harga_pokok`, `stok`, `foto_varian`, `created_at`, `updated_at`, `type_varian`) VALUES
(1, 1, 'TS-001', 'Hitam', '#000000', 'L', 85000.00, 80000.00, 4, 'assets/uploads/products/696de62818f81_1768810024.png', '2026-01-19 07:55:40', '2026-01-27 08:50:22', 'Pendek'),
(2, 2, 'TS-002', 'Putih', '#ffffff', 'XL', 95000.00, 90000.00, 49, 'assets/uploads/products/adidasputih.png', '2026-01-19 07:55:40', '2026-01-26 02:52:39', 'Pendek'),
(4, 4, 'CLA1001', 'Putih', '#ffffff', 'S', 85000.00, 80000.00, 25, 'assets/uploads/products/millsputih.png', '2026-01-19 07:55:40', '2026-01-26 08:08:48', 'Pendek'),
(5, 4, 'CLA1002', 'Putih', '#ffffff', 'M', 85000.00, 80000.00, 25, 'assets/uploads/products/millsputih.png', '2026-01-19 07:55:40', '2026-01-26 01:52:56', 'Pendek'),
(6, 4, 'CLA1003', 'Putih', '#ffffff', 'L', 85000.00, 80000.00, 25, 'assets/uploads/products/millsputih.png', '2026-01-19 07:55:40', '2026-01-26 01:52:56', 'Pendek'),
(7, 4, 'CLA1004', 'Putih', '#ffffff', 'XL', 85000.00, 80000.00, 25, 'assets/uploads/products/millsputih.png', '2026-01-19 07:55:40', '2026-01-26 01:52:56', 'Pendek'),
(8, 5, 'MID2001', 'Hitam', '#000000', 'L', 125000.00, 120000.00, 9, 'assets/uploads/products/696deb0c2a2d2_1768811276.png', '2026-01-19 07:55:40', '2026-01-26 08:09:35', 'Pendek'),
(27, 1, 'Vintage Classic Tee-', 'Putih', '#ffffff', 'S', 85000.00, 80000.00, 100, 'assets/uploads/products/696de628188f6_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(28, 1, 'Vintage Classic Tee-', 'Putih', '#ffffff', 'L', 85000.00, 80000.00, 100, 'assets/uploads/products/696de628188f6_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(29, 1, 'Vintage Classic Tee-', 'Putih', '#ffffff', 'XL', 85000.00, 80000.00, 100, 'assets/uploads/products/696de628188f6_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(30, 1, 'Vintage Classic Tee-', 'Putih', '#ffffff', '2XL', 85000.00, 80000.00, 100, 'assets/uploads/products/696de628188f6_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(31, 1, 'Vintage Classic Tee-', 'Hitam', '#000000', 'XS', 85000.00, 80000.00, 5, 'assets/uploads/products/696de62818f81_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(32, 1, 'Vintage Classic Tee-', 'Hitam', '#000000', 'XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696de62818f81_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(33, 1, 'Vintage Classic Tee-', 'Hitam', '#000000', '4XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696de62818f81_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(34, 1, 'Vintage Classic Tee-', 'Kuning', '#c6d12e', 'XS', 85000.00, 80000.00, 6, 'assets/uploads/products/696de6281a91d_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(35, 1, 'Vintage Classic Tee-', 'Kuning', '#c6d12e', 'S', 85000.00, 80000.00, 6, 'assets/uploads/products/696de6281a91d_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(36, 1, 'Vintage Classic Tee-', 'Kuning', '#c6d12e', 'L', 85000.00, 80000.00, 6, 'assets/uploads/products/696de6281a91d_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(37, 1, 'Vintage Classic Tee-', 'Kuning', '#c6d12e', 'XL', 85000.00, 80000.00, 6, 'assets/uploads/products/696de6281a91d_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(38, 1, 'Vintage Classic Tee-', 'Teal', '#3abba1', 'S', 85000.00, 80000.00, 7, 'assets/uploads/products/696de6281af88_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(39, 1, 'Vintage Classic Tee-', 'Teal', '#3abba1', 'L', 85000.00, 80000.00, 7, 'assets/uploads/products/696de6281af88_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(40, 1, 'Vintage Classic Tee-', 'Teal', '#3abba1', '2XL', 85000.00, 80000.00, 7, 'assets/uploads/products/696de6281af88_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(41, 1, 'Vintage Classic Tee-', 'Teal', '#3abba1', '5XL', 85000.00, 80000.00, 7, 'assets/uploads/products/696de6281af88_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(42, 1, 'Vintage Classic Tee-', 'Pink', '#c37f7f', 'S', 85000.00, 80000.00, 8, 'assets/uploads/products/696de6281b5cf_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', '-'),
(43, 1, 'Vintage Classic Tee-', 'Pink', '#c37f7f', 'L', 85000.00, 80000.00, 8, 'assets/uploads/products/696de6281b5cf_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(44, 1, 'Vintage Classic Tee-', 'Pink', '#c37f7f', 'XL', 85000.00, 80000.00, 8, 'assets/uploads/products/696de6281b5cf_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(45, 1, 'Vintage Classic Tee-', 'Pink', '#c37f7f', '2XL', 85000.00, 80000.00, 8, 'assets/uploads/products/696de6281b5cf_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(46, 1, 'Vintage Classic Tee-', 'Pink', '#c37f7f', '5XL', 85000.00, 80000.00, 8, 'assets/uploads/products/696de6281b5cf_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(47, 1, 'Vintage Classic Tee-', 'Aqua', '#609abe', 'L', 85000.00, 80000.00, 4, 'assets/uploads/products/696de6281bdea_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(48, 1, 'Vintage Classic Tee-', 'Aqua', '#609abe', '2XL', 85000.00, 80000.00, 4, 'assets/uploads/products/696de6281bdea_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(49, 1, 'Vintage Classic Tee-', 'Aqua', '#609abe', '5XL', 85000.00, 80000.00, 4, 'assets/uploads/products/696de6281bdea_1768810024.png', '2026-01-19 08:07:04', '2026-01-26 01:50:27', 'Pendek'),
(50, 2, 'Urban Streetwear-Put', 'Putih', '#ffffff', 'S', 95000.00, 90000.00, 50, NULL, '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(51, 2, 'Urban Streetwear-Put', 'Putih', '#ffffff', 'M', 95000.00, 90000.00, 50, NULL, '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(52, 2, 'Urban Streetwear-Put', 'Putih', '#ffffff', '3XL', 95000.00, 90000.00, 50, NULL, '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(53, 2, 'Urban Streetwear-Bir', 'Biru', '#214cb0', 'XS', 95000.00, 90000.00, 4, 'assets/uploads/products/696de6a5c3604_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(54, 2, 'Urban Streetwear-Bir', 'Biru', '#214cb0', 'M', 95000.00, 90000.00, 4, 'assets/uploads/products/696de6a5c3604_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(55, 2, 'Urban Streetwear-Bir', 'Biru', '#214cb0', 'L', 95000.00, 90000.00, 4, 'assets/uploads/products/696de6a5c3604_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(56, 2, 'Urban Streetwear-Bir', 'Biru', '#214cb0', '3XL', 95000.00, 90000.00, 4, 'assets/uploads/products/696de6a5c3604_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(57, 2, 'Urban Streetwear-Hij', 'Hijau', '#2ca039', 'XS', 95000.00, 90000.00, 15, 'assets/uploads/products/696de6a5c5098_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(58, 2, 'Urban Streetwear-Hij', 'Hijau', '#2ca039', 'L', 95000.00, 90000.00, 15, 'assets/uploads/products/696de6a5c5098_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(59, 2, 'Urban Streetwear-Hij', 'Hijau', '#2ca039', '2XL', 95000.00, 90000.00, 15, 'assets/uploads/products/696de6a5c5098_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(60, 2, 'Urban Streetwear-Hij', 'Hijau', '#2ca039', '3XL', 95000.00, 90000.00, 15, 'assets/uploads/products/696de6a5c5098_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:53', 'Pendek'),
(61, 2, 'Urban Streetwear-Abu', 'Abu Abu', '#8a8585', 'M', 95000.00, 90000.00, 6, 'assets/uploads/products/696de6a5c5700_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(62, 2, 'Urban Streetwear-Abu', 'Abu Abu', '#8a8585', 'L', 95000.00, 90000.00, 6, 'assets/uploads/products/696de6a5c5700_1768810149.png', '2026-01-19 08:09:09', '2026-01-26 01:50:45', 'Pendek'),
(63, 2, 'Urban Streetwear-Abu', 'Abu Abu', '#8a8585', '2XL', 95000.00, 90000.00, 6, 'assets/uploads/products/696de6a5c5700_1768810149.png', '2026-01-19 08:09:09', '2026-01-27 08:50:20', 'Pendek'),
(64, 3, 'Bomber Jacket-Putih-', 'Putih', '#ffffff', 'XS', 250000.00, 240000.00, 25, NULL, '2026-01-19 08:12:40', '2026-01-26 01:53:56', 'Panjang'),
(65, 3, 'Bomber Jacket-Putih-', 'Putih', '#ffffff', 'L', 250000.00, 240000.00, 25, NULL, '2026-01-19 08:12:40', '2026-01-26 01:53:56', 'Panjang'),
(66, 3, 'Bomber Jacket-Putih-', 'Putih', '#ffffff', 'XL', 250000.00, 240000.00, 25, NULL, '2026-01-19 08:12:40', '2026-01-26 01:53:56', 'Panjang'),
(67, 3, 'Bomber Jacket-Putih-', 'Putih', '#ffffff', '2XL', 250000.00, 240000.00, 25, NULL, '2026-01-19 08:12:40', '2026-01-26 01:53:56', 'Panjang'),
(72, 3, 'Bomber Jacket-Coklat', 'Coklat', '#9a794c', 'XS', 250000.00, 240000.00, 4, 'assets/uploads/products/69796353e2bca_1769562963.png', '2026-01-19 08:12:40', '2026-01-28 01:16:03', 'Panjang'),
(73, 3, 'Bomber Jacket-Coklat', 'Coklat', '#9a794c', 'L', 250000.00, 240000.00, 4, 'assets/uploads/products/69796353e2bca_1769562963.png', '2026-01-19 08:12:40', '2026-01-28 01:16:03', 'Panjang'),
(74, 3, 'Bomber Jacket-Coklat', 'Coklat', '#9a794c', '5XL', 250000.00, 240000.00, 4, 'assets/uploads/products/69796353e2bca_1769562963.png', '2026-01-19 08:12:40', '2026-01-28 01:16:03', 'Panjang'),
(75, 3, 'Bomber Jacket-Abu Ab', 'Abu Abu', '#a99e9e', 'XS', 250000.00, 240000.00, 3, 'assets/uploads/products/69796353e334a_1769562963.png', '2026-01-19 08:12:40', '2026-01-28 01:16:03', 'Panjang'),
(76, 3, 'Bomber Jacket-Abu Ab', 'Abu Abu', '#a99e9e', 'S', 250000.00, 240000.00, 3, 'assets/uploads/products/69796353e334a_1769562963.png', '2026-01-19 08:12:40', '2026-01-28 01:16:03', 'Panjang'),
(77, 3, 'Bomber Jacket-Abu Ab', 'Abu Abu', '#a99e9e', 'L', 250000.00, 240000.00, 3, 'assets/uploads/products/69796353e334a_1769562963.png', '2026-01-19 08:12:40', '2026-01-28 01:16:03', 'Panjang'),
(78, 3, 'Bomber Jacket-Abu Ab', 'Abu Abu', '#a99e9e', 'XL', 250000.00, 240000.00, 3, 'assets/uploads/products/69796353e334a_1769562963.png', '2026-01-19 08:12:40', '2026-01-28 01:16:03', 'Panjang'),
(79, 4, 'Classic White Tee-Hi', 'Hitam', '#000000', 'XS', 85000.00, 80000.00, 4, 'assets/uploads/products/696deab0734dc_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', '-'),
(80, 4, 'Classic White Tee-Hi', 'Hitam', '#000000', 'L', 85000.00, 80000.00, 4, 'assets/uploads/products/696deab0734dc_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', 'Pendek'),
(81, 4, 'Classic White Tee-Hi', 'Hitam', '#000000', '2XL', 85000.00, 80000.00, 4, 'assets/uploads/products/696deab0734dc_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', 'Pendek'),
(82, 4, 'Classic White Tee-Hi', 'Hitam', '#000000', '3XL', 85000.00, 80000.00, 4, 'assets/uploads/products/696deab0734dc_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', 'Pendek'),
(83, 4, 'Classic White Tee-Un', 'Ungu', '#572687', 'S', 85000.00, 80000.00, 5, 'assets/uploads/products/696deab07530f_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', 'Pendek'),
(84, 4, 'Classic White Tee-Un', 'Ungu', '#572687', 'L', 85000.00, 80000.00, 5, 'assets/uploads/products/696deab07530f_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', 'Pendek'),
(85, 4, 'Classic White Tee-Un', 'Ungu', '#572687', '2XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696deab07530f_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', 'Pendek'),
(86, 4, 'Classic White Tee-Un', 'Ungu', '#572687', '3XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696deab07530f_1768811184.png', '2026-01-19 08:26:24', '2026-01-26 01:52:56', 'Pendek'),
(87, 5, 'Midnight Black Overs', 'Putih', '#ffffff', 'L', 125000.00, 120000.00, 20, 'assets/uploads/products/696deb0c29d6c_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(88, 5, 'Midnight Black Overs', 'Putih', '#ffffff', 'XL', 125000.00, 120000.00, 20, 'assets/uploads/products/696deb0c29d6c_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', '-'),
(89, 5, 'Midnight Black Overs', 'Hitam', '#000000', '2XL', 125000.00, 120000.00, 9, 'assets/uploads/products/696deb0c2a2d2_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(90, 5, 'Midnight Black Overs', 'Hitam', '#000000', '5XL', 125000.00, 120000.00, 9, 'assets/uploads/products/696deb0c2a2d2_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(91, 5, 'Midnight Black Overs', 'Coklat', '#825130', 'S', 125000.00, 120000.00, 8, 'assets/uploads/products/696deb0c2bea5_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(92, 5, 'Midnight Black Overs', 'Coklat', '#825130', 'L', 125000.00, 120000.00, 8, 'assets/uploads/products/696deb0c2bea5_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(93, 5, 'Midnight Black Overs', 'Coklat', '#825130', '2XL', 125000.00, 120000.00, 8, 'assets/uploads/products/696deb0c2bea5_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(94, 5, 'Midnight Black Overs', 'Biru', '#1e72cc', 'XS', 125000.00, 120000.00, 7, 'assets/uploads/products/696deb0c2c4cd_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(95, 5, 'Midnight Black Overs', 'Biru', '#1e72cc', 'S', 125000.00, 120000.00, 7, 'assets/uploads/products/696deb0c2c4cd_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(96, 5, 'Midnight Black Overs', 'Biru', '#1e72cc', 'L', 125000.00, 120000.00, 7, 'assets/uploads/products/696deb0c2c4cd_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(97, 5, 'Midnight Black Overs', 'Merah', '#b62020', 'L', 125000.00, 120000.00, 6, 'assets/uploads/products/696deb0c2ca73_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(98, 5, 'Midnight Black Overs', 'Merah', '#b62020', '2XL', 125000.00, 120000.00, 6, 'assets/uploads/products/696deb0c2ca73_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(99, 5, 'Midnight Black Overs', 'Merah', '#b62020', '5XL', 125000.00, 120000.00, 6, 'assets/uploads/products/696deb0c2ca73_1768811276.png', '2026-01-19 08:27:56', '2026-01-26 08:09:35', 'Pendek'),
(100, 6, 'Navy Essential Long-', 'Putih', '#ffffff', 'M', 95000.00, 90000.00, 40, 'assets/uploads/products/696debdfcc741_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(101, 6, 'Navy Essential Long-', 'Putih', '#ffffff', 'L', 95000.00, 90000.00, 40, 'assets/uploads/products/696debdfcc741_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(102, 6, 'Navy Essential Long-', 'Putih', '#ffffff', 'XL', 95000.00, 90000.00, 40, 'assets/uploads/products/696debdfcc741_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(103, 6, 'Navy Essential Long-', 'Putih', '#ffffff', '2XL', 95000.00, 90000.00, 40, 'assets/uploads/products/696debdfcc741_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(104, 6, 'Navy Essential Long-', 'Putih', '#ffffff', '4XL', 95000.00, 90000.00, 40, 'assets/uploads/products/696debdfcc741_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', '-'),
(105, 6, 'Navy Essential Long-', 'Hitam', '#000000', 'S', 95000.00, 90000.00, 7, 'assets/uploads/products/696debdfcd1d7_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(106, 6, 'Navy Essential Long-', 'Hitam', '#000000', 'L', 95000.00, 90000.00, 7, 'assets/uploads/products/696debdfcd1d7_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(107, 6, 'Navy Essential Long-', 'Hitam', '#000000', 'XL', 95000.00, 90000.00, 7, 'assets/uploads/products/696debdfcd1d7_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(108, 6, 'Navy Essential Long-', 'Hitam', '#000000', '2XL', 95000.00, 90000.00, 7, 'assets/uploads/products/696debdfcd1d7_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(109, 6, 'Navy Essential Long-', 'Hitam', '#000000', '5XL', 95000.00, 90000.00, 7, 'assets/uploads/products/696debdfcd1d7_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(110, 6, 'Navy Essential Long-', 'Hijau', '#1f934c', 'XS', 95000.00, 90000.00, 6, 'assets/uploads/products/696debdfcdaf0_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(111, 6, 'Navy Essential Long-', 'Hijau', '#1f934c', 'L', 95000.00, 90000.00, 6, 'assets/uploads/products/696debdfcdaf0_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(112, 6, 'Navy Essential Long-', 'Hijau', '#1f934c', 'XL', 95000.00, 90000.00, 6, 'assets/uploads/products/696debdfcdaf0_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(113, 6, 'Navy Essential Long-', 'Hijau', '#1f934c', '4XL', 95000.00, 90000.00, 6, 'assets/uploads/products/696debdfcdaf0_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(114, 6, 'Navy Essential Long-', 'Coklat', '#915840', 'XS', 95000.00, 90000.00, 5, 'assets/uploads/products/696debdfcfacf_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Panjang'),
(115, 6, 'Navy Essential Long-', 'Coklat', '#915840', 'L', 95000.00, 90000.00, 5, 'assets/uploads/products/696debdfcfacf_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(116, 6, 'Navy Essential Long-', 'Coklat', '#915840', '4XL', 95000.00, 90000.00, 5, 'assets/uploads/products/696debdfcfacf_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(117, 6, 'Navy Essential Long-', 'Merah', '#a53131', 'S', 95000.00, 90000.00, 4, 'assets/uploads/products/696debdfd00ab_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(118, 6, 'Navy Essential Long-', 'Merah', '#a53131', 'L', 95000.00, 90000.00, 4, 'assets/uploads/products/696debdfd00ab_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(119, 6, 'Navy Essential Long-', 'Merah', '#a53131', '2XL', 95000.00, 90000.00, 4, 'assets/uploads/products/696debdfd00ab_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(120, 6, 'Navy Essential Long-', 'Merah', '#a53131', '5XL', 95000.00, 90000.00, 4, 'assets/uploads/products/696debdfd00ab_1768811487.png', '2026-01-19 08:31:27', '2026-01-26 01:53:22', 'Pendek'),
(121, 7, 'Maron Solid Vibe-Put', 'Putih', '#ffffff', 'S', 89000.00, 84000.00, 49, NULL, '2026-01-19 08:33:46', '2026-01-26 03:18:29', 'Pendek'),
(122, 7, 'Maron Solid Vibe-Put', 'Putih', '#ffffff', 'M', 89000.00, 84000.00, 50, NULL, '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(123, 7, 'Maron Solid Vibe-Put', 'Putih', '#ffffff', 'L', 89000.00, 84000.00, 50, NULL, '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(124, 7, 'Maron Solid Vibe-Put', 'Putih', '#ffffff', '5XL', 89000.00, 84000.00, 50, NULL, '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(125, 7, 'Maron Solid Vibe-Hit', 'Hitam', '#000000', 'L', 89000.00, 84000.00, 5, 'assets/uploads/products/696dec6a6a87a_1768811626.png', '2026-01-19 08:33:46', '2026-01-26 01:54:16', '-'),
(126, 7, 'Maron Solid Vibe-Hit', 'Hitam', '#000000', '4XL', 89000.00, 84000.00, 5, 'assets/uploads/products/696dec6a6a87a_1768811626.png', '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(127, 7, 'Maron Solid Vibe-Hit', 'Hitam', '#000000', '5XL', 89000.00, 84000.00, 5, 'assets/uploads/products/696dec6a6a87a_1768811626.png', '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(128, 7, 'Maron Solid Vibe-Tea', 'Teal', '#38a375', 'L', 89000.00, 84000.00, 6, 'assets/uploads/products/696dec6a6b6cf_1768811626.png', '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(129, 7, 'Maron Solid Vibe-Tea', 'Teal', '#38a375', '2XL', 89000.00, 84000.00, 6, 'assets/uploads/products/696dec6a6b6cf_1768811626.png', '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(130, 7, 'Maron Solid Vibe-Tea', 'Teal', '#38a375', '5XL', 89000.00, 84000.00, 6, 'assets/uploads/products/696dec6a6b6cf_1768811626.png', '2026-01-19 08:33:46', '2026-01-26 01:54:16', 'Pendek'),
(131, 8, 'Army Green Duty-Puti', 'Putih', '#ffffff', 'L', 89000.00, 84000.00, 30, 'assets/uploads/products/696dece6612d9_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(132, 8, 'Army Green Duty-Puti', 'Putih', '#ffffff', 'XL', 89000.00, 84000.00, 30, 'assets/uploads/products/696dece6612d9_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(133, 8, 'Army Green Duty-Puti', 'Putih', '#ffffff', '5XL', 89000.00, 84000.00, 30, 'assets/uploads/products/696dece6612d9_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(134, 8, 'Army Green Duty-Hita', 'Hitam', '#000000', 'L', 89000.00, 84000.00, 5, 'assets/uploads/products/696dece6619b4_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(135, 8, 'Army Green Duty-Hita', 'Hitam', '#000000', '2XL', 89000.00, 84000.00, 5, 'assets/uploads/products/696dece6619b4_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(136, 8, 'Army Green Duty-Hita', 'Hitam', '#000000', '5XL', 89000.00, 84000.00, 5, 'assets/uploads/products/696dece6619b4_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(137, 8, 'Army Green Duty-Hija', 'Hijau', '#378b57', 'XS', 89000.00, 84000.00, 6, 'assets/uploads/products/696dece663113_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(138, 8, 'Army Green Duty-Hija', 'Hijau', '#378b57', 'S', 89000.00, 84000.00, 6, 'assets/uploads/products/696dece663113_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(139, 8, 'Army Green Duty-Hija', 'Hijau', '#378b57', 'L', 89000.00, 84000.00, 6, 'assets/uploads/products/696dece663113_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(140, 8, 'Army Green Duty-Hija', 'Hijau', '#378b57', 'XL', 89000.00, 84000.00, 6, 'assets/uploads/products/696dece663113_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(141, 8, 'Army Green Duty-Oran', 'Oranye', '#ff8800', 'L', 89000.00, 84000.00, 7, 'assets/uploads/products/696dece6637bb_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(142, 8, 'Army Green Duty-Oran', 'Oranye', '#ff8800', '2XL', 89000.00, 84000.00, 7, 'assets/uploads/products/696dece6637bb_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(143, 8, 'Army Green Duty-Oran', 'Oranye', '#ff8800', '5XL', 89000.00, 84000.00, 7, 'assets/uploads/products/696dece6637bb_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(144, 8, 'Army Green Duty-Abu ', 'Abu Abu', '#bcb3b3', 'L', 89000.00, 84000.00, 5, 'assets/uploads/products/696dece663ca9_1768811750.png', '2026-01-19 08:35:50', '2026-01-28 01:12:13', '-'),
(145, 8, 'Army Green Duty-Abu ', 'Abu Abu', '#bcb3b3', 'XL', 89000.00, 84000.00, 8, 'assets/uploads/products/696dece663ca9_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(146, 8, 'Army Green Duty-Abu ', 'Abu Abu', '#bcb3b3', '4XL', 89000.00, 84000.00, 8, 'assets/uploads/products/696dece663ca9_1768811750.png', '2026-01-19 08:35:50', '2026-01-26 01:55:09', 'Pendek'),
(147, 9, 'Charcoal Grey Daily-', 'Putih', '#ffffff', 'M', 85000.00, 80000.00, 20, 'assets/uploads/products/696dedd1d626f_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(148, 9, 'Charcoal Grey Daily-', 'Putih', '#ffffff', 'XL', 85000.00, 80000.00, 20, 'assets/uploads/products/696dedd1d626f_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(149, 9, 'Charcoal Grey Daily-', 'Putih', '#ffffff', '2XL', 85000.00, 80000.00, 20, 'assets/uploads/products/696dedd1d626f_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(150, 9, 'Charcoal Grey Daily-', 'Putih', '#ffffff', '4XL', 85000.00, 80000.00, 20, 'assets/uploads/products/696dedd1d626f_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', '-'),
(151, 9, 'Charcoal Grey Daily-', 'Hitam', '#000000', 'L', 85000.00, 80000.00, 6, 'assets/uploads/products/696dedd1d6ae2_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(152, 9, 'Charcoal Grey Daily-', 'Hitam', '#000000', 'XL', 85000.00, 80000.00, 6, 'assets/uploads/products/696dedd1d6ae2_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(153, 9, 'Charcoal Grey Daily-', 'Hitam', '#000000', '2XL', 85000.00, 80000.00, 6, 'assets/uploads/products/696dedd1d6ae2_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(154, 9, 'Charcoal Grey Daily-', 'Hitam', '#000000', '5XL', 85000.00, 80000.00, 6, 'assets/uploads/products/696dedd1d6ae2_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(155, 9, 'Charcoal Grey Daily-', 'Hijau', '#46a05c', 'XS', 85000.00, 80000.00, 5, 'assets/uploads/products/696dedd1d7197_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(156, 9, 'Charcoal Grey Daily-', 'Hijau', '#46a05c', 'L', 85000.00, 80000.00, 5, 'assets/uploads/products/696dedd1d7197_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(157, 9, 'Charcoal Grey Daily-', 'Hijau', '#46a05c', 'XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696dedd1d7197_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(158, 9, 'Charcoal Grey Daily-', 'Hijau', '#46a05c', '5XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696dedd1d7197_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(159, 9, 'Charcoal Grey Daily-', 'Biru', '#1f63a3', 'L', 85000.00, 80000.00, 6, 'assets/uploads/products/696dedd1d77f8_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(160, 9, 'Charcoal Grey Daily-', 'Biru', '#1f63a3', '2XL', 85000.00, 80000.00, 6, 'assets/uploads/products/696dedd1d77f8_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(161, 9, 'Charcoal Grey Daily-', 'Biru', '#1f63a3', '5XL', 85000.00, 80000.00, 6, 'assets/uploads/products/696dedd1d77f8_1768811985.png', '2026-01-19 08:39:45', '2026-01-26 01:55:22', 'Pendek'),
(162, 10, 'Kemeja Workshirt Man', 'Putih', '#ffffff', 'L', 110000.00, 100000.00, 15, 'assets/uploads/products/696dee45f35a7_1768812101.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(163, 10, 'Kemeja Workshirt Man', 'Putih', '#ffffff', '2XL', 110000.00, 100000.00, 15, 'assets/uploads/products/696dee45f35a7_1768812101.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(164, 10, 'Kemeja Workshirt Man', 'Putih', '#ffffff', '5XL', 110000.00, 100000.00, 15, 'assets/uploads/products/696dee45f35a7_1768812101.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(165, 10, 'Kemeja Workshirt Man', 'Hitam', '#000000', 'L', 110000.00, 100000.00, 6, 'assets/uploads/products/696dee4600b70_1768812102.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(166, 10, 'Kemeja Workshirt Man', 'Hitam', '#000000', '5XL', 110000.00, 100000.00, 6, 'assets/uploads/products/696dee4600b70_1768812102.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(167, 10, 'Kemeja Workshirt Man', 'Biru', '#77b9da', 'L', 110000.00, 100000.00, 4, 'assets/uploads/products/696dee4601000_1768812102.jpg', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(168, 10, 'Kemeja Workshirt Man', 'Biru', '#77b9da', '3XL', 110000.00, 100000.00, 4, 'assets/uploads/products/696dee4601000_1768812102.jpg', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(169, 10, 'Kemeja Workshirt Man', 'Biru', '#77b9da', '5XL', 110000.00, 100000.00, 4, 'assets/uploads/products/696dee4601000_1768812102.jpg', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(170, 10, 'Kemeja Workshirt Man', 'Teal', '#3e8e61', 'L', 110000.00, 100000.00, 7, 'assets/uploads/products/696dee46015d8_1768812102.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(171, 10, 'Kemeja Workshirt Man', 'Teal', '#3e8e61', '2XL', 110000.00, 100000.00, 7, 'assets/uploads/products/696dee46015d8_1768812102.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(172, 10, 'Kemeja Workshirt Man', 'Teal', '#3e8e61', '5XL', 110000.00, 100000.00, 7, 'assets/uploads/products/696dee46015d8_1768812102.png', '2026-01-19 08:41:42', '2026-01-26 01:45:43', 'Panjang'),
(173, 11, 'Crimson Red Energy-P', 'Putih', '#ffffff', 'XL', 85000.00, 80000.00, 10, 'assets/uploads/products/696dee9158253_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:07', 'Pendek'),
(174, 11, 'Crimson Red Energy-P', 'Putih', '#ffffff', '3XL', 85000.00, 80000.00, 10, 'assets/uploads/products/696dee9158253_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:07', 'Pendek'),
(175, 11, 'Crimson Red Energy-P', 'Putih', '#ffffff', '5XL', 85000.00, 80000.00, 10, 'assets/uploads/products/696dee9158253_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:16', 'Pendek'),
(176, 11, 'Crimson Red Energy-H', 'Hitam', '#000000', 'L', 85000.00, 80000.00, 5, 'assets/uploads/products/696dee9159b27_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:07', 'Pendek'),
(177, 11, 'Crimson Red Energy-H', 'Hitam', '#000000', '3XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696dee9159b27_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:07', 'Pendek'),
(178, 11, 'Crimson Red Energy-H', 'Hitam', '#000000', '5XL', 85000.00, 80000.00, 5, 'assets/uploads/products/696dee9159b27_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:07', 'Pendek'),
(179, 11, 'Crimson Red Energy-A', 'Abu Abu', '#948989', 'L', 85000.00, 80000.00, 2, 'assets/uploads/products/696dee915a099_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:07', 'Pendek'),
(180, 11, 'Crimson Red Energy-A', 'Abu Abu', '#948989', '5XL', 85000.00, 80000.00, 2, 'assets/uploads/products/696dee915a099_1768812177.png', '2026-01-19 08:42:57', '2026-01-26 01:45:07', 'Pendek'),
(183, 3, 'Bomber Jacket-Hitam-', 'Hitam', '#000000', '3XL', 250000.00, 240000.00, 6, 'assets/uploads/products/697964e790b8c_1769563367.png', '2026-01-28 01:16:52', '2026-01-28 01:22:47', NULL),
(184, 3, 'Bomber Jacket-Hitam-', 'Hitam', '#000000', 'L', 250000.00, 240000.00, 6, 'assets/uploads/products/697964e790b8c_1769563367.png', '2026-01-28 01:17:33', '2026-01-28 01:22:47', NULL),
(185, 3, 'Bomber Jacket-Hitam-', 'Hitam', '#000000', '4XL', 250000.00, 240000.00, 6, 'assets/uploads/products/697964e790b8c_1769563367.png', '2026-01-28 01:17:33', '2026-01-28 01:22:47', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `slug`) VALUES
(1, 'T-Shirt', 't-shirt'),
(2, 'Kemeja', 'kemeja'),
(3, 'Jaket', 'jaket'),
(4, 'Aksesoris', 'aksesoris');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payment_proof`
--

CREATE TABLE `payment_proof` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `file_bukti` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `tanggal_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'store_name', 'DistroZone'),
(2, 'store_description', 'Pusat Fashion Distro Terkini'),
(3, 'store_address', 'Jln. Raya Pegangsaan Timur No.29H, Jakarta'),
(4, 'store_phone', '081234567890'),
(5, 'store_email', 'info@distrozone.com'),
(6, 'jam_operasional_offline', '{\"open\":\"10:00\", \"close\":\"20:00\", \"closed_days\":[1]}'),
(7, 'jam_operasional_online', '{\"open\":\"10:00\", \"close\":\"17:00\", \"closed_days\":[]}'),
(8, 'offline_operasional', 'Selasa-Jumat|10:00|20:00'),
(9, 'online_operasional', 'Setiap Hari|10:00|17:00'),
(10, 'hari_libur', 'Senin, 25 Desember, 1 Januari, 17 Agustus');

-- --------------------------------------------------------

--
-- Struktur dari tabel `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` int(11) NOT NULL,
  `wilayah` varchar(100) NOT NULL,
  `cost_per_kg` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `shipping_rates`
--

INSERT INTO `shipping_rates` (`id`, `wilayah`, `cost_per_kg`) VALUES
(1, 'Jakarta', 24000.00),
(2, 'Depok', 24000.00),
(3, 'Bekasi', 25000.00),
(4, 'Tangerang', 25000.00),
(5, 'Bogor', 27000.00),
(6, 'Jawa Barat', 31000.00),
(7, 'Jawa Tengah', 39000.00),
(8, 'Jawa Timur', 47000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `kode_transaksi` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `kasir_id` int(11) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `status` enum('pending','paid','sent','completed','cancelled') NOT NULL DEFAULT 'pending',
  `platform` enum('desktop','web') DEFAULT 'desktop',
  `no_resi` varchar(100) DEFAULT NULL,
  `cancelled_by` enum('kasir','customer') DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `kode_transaksi`, `customer_id`, `kasir_id`, `total`, `shipping_city`, `shipping_address`, `shipping_cost`, `grand_total`, `payment_method`, `status`, `platform`, `no_resi`, `cancelled_by`, `cancel_reason`, `tanggal`, `waktu`, `created_at`) VALUES
(1, 'TRX-260128-001', NULL, 2, 89000.00, NULL, NULL, 0.00, 89000.00, 'Tunai', 'completed', 'desktop', NULL, NULL, NULL, '2026-01-28', '2026-01-28 08:12:13', '2026-01-28 01:12:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_code` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `role` enum('admin','kasir','customer') NOT NULL DEFAULT 'customer',
  `shift` varchar(20) DEFAULT NULL,
  `platform` enum('desktop','web','all') DEFAULT 'all',
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `user_code`, `username`, `password`, `nama`, `email`, `alamat`, `no_telp`, `nik`, `role`, `shift`, `platform`, `foto`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ADM-001', 'admin', 'password', 'Owner DistroZone', 'admin@distrozone.com', 'Jln. Raya Pegangsaan Timur No.29H', '081234567890', '3171234567890001', 'admin', '24 Jam', 'all', 'C:\\Users\\antartika2\\Downloads\\prabowo.png', 'active', '2026-01-19 07:55:39', '2026-01-23 06:24:17'),
(2, 'KSR-001', 'kasirdekstop', 'password', 'Kasir Desktop', 'kasir@distrozone.com', 'Jakarta', '081298765432', '3171234567890002', 'kasir', 'Pagi (08:00-16:00)', 'desktop', 'assets/uploads/users/696df42284719_1768813602.png', 'active', '2026-01-19 07:55:39', '2026-01-27 03:23:53'),
(9, 'KSR-002', 'kasirweb', 'password', 'Kasir Web', 'kasirweb@distrozone.com', 'Jakarta', '081299998888', '3171234567890003', 'kasir', 'Sore (16:00-24:00)', 'web', 'assets/uploads/users/697883cf5c52d_1769505743.png', 'active', '2026-01-27 08:52:30', '2026-01-27 03:24:01');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `kaos_id` (`kaos_id`);

--
-- Indeks untuk tabel `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indeks untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indeks untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `kaos_id` (`kaos_id`);

--
-- Indeks untuk tabel `kaos_master`
--
ALTER TABLE `kaos_master`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `kaos_varian`
--
ALTER TABLE `kaos_varian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kaos_master_id` (`kaos_master_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `payment_proof`
--
ALTER TABLE `payment_proof`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeks untuk tabel `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `kasir_id` (`kasir_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_code` (`user_code`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kaos_master`
--
ALTER TABLE `kaos_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `kaos_varian`
--
ALTER TABLE `kaos_varian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=186;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `payment_proof`
--
ALTER TABLE `payment_proof`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT untuk tabel `shipping_rates`
--
ALTER TABLE `shipping_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `chat_conversations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kaos_master`
--
ALTER TABLE `kaos_master`
  ADD CONSTRAINT `kaos_master_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `kaos_varian`
--
ALTER TABLE `kaos_varian`
  ADD CONSTRAINT `kaos_varian_ibfk_1` FOREIGN KEY (`kaos_master_id`) REFERENCES `kaos_master` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `payment_proof`
--
ALTER TABLE `payment_proof`
  ADD CONSTRAINT `payment_proof_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_proof_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_proof_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
