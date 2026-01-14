-- Database: DISTROZONE_DB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_code` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `role` enum('admin','kasir','customer') NOT NULL DEFAULT 'customer',
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `user_code` (`user_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_code`, `username`, `password`, `nama`, `email`, `alamat`, `no_telp`, `nik`, `role`, `status`) VALUES
('ADM-001', 'admin', 'password', 'Owner DistroZone', 'admin@distrozone.com', 'Jln. Raya Pegangsaan Timur No.29H', '081234567890', '3171234567890001', 'admin', 'active'),
('KSR-001', 'kasir1', 'password', 'Kasir Utama', 'kasir@distrozone.com', 'Jakarta', '081298765432', '3171234567890002', 'kasir', 'active'),
('CST-001', 'customer', 'password', 'Pelanggan Setia', 'customer@distrozone.com', 'Surabaya', '081211112222', NULL, 'customer', 'active');
-- Password default: 'password'

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`nama_kategori`, `slug`) VALUES
('T-Shirt', 't-shirt'),
('Kemeja', 'kemeja'),
('Jaket', 'jaket'),
('Aksesoris', 'aksesoris');

-- --------------------------------------------------------

--
-- Table structure for table `kaos_master`
--

CREATE TABLE `kaos_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kaos` varchar(100) NOT NULL,
  `merek` varchar(50) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `type_kaos` enum('Lengan Panjang','Lengan Pendek') DEFAULT 'Lengan Pendek',
  `deskripsi` text DEFAULT NULL,
  `foto_utama` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`),
  CONSTRAINT `kaos_master_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kaos_master`
--

INSERT INTO `kaos_master` (`id`, `nama_kaos`, `merek`, `kategori_id`, `type_kaos`, `deskripsi`, `foto_utama`) VALUES
(1, 'Vintage Classic Tee', 'DistroZone', 1, 'Lengan Pendek', 'Kaos cotton combed 30s berkualitas tinggi.', NULL),
(2, 'Urban Streetwear', 'Erigo', 1, 'Lengan Pendek', 'Desain urban modern untuk gaya maksimal.', NULL),
(3, 'Bomber Jacket', 'DistroZone', 3, 'Lengan Panjang', 'Jaket bomber anti angin dan air.', NULL),
(4, 'Classic White Tee', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(5, 'Midnight Black Oversize', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(6, 'Navy Essential Long', 'DistroZone', 1, 'Lengan Panjang', 'Produk berkualitas terbaik dari DistroZone', NULL),
(7, 'Maron Solid Vibe', 'UrbanStyle', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari UrbanStyle', NULL),
(8, 'Army Green Duty', 'UrbanStyle', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari UrbanStyle', NULL),
(9, 'Charcoal Grey Daily', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(10, 'Sandy Beige Relax', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(11, 'Crimson Red Energy', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(12, 'Royal Blue Bold', 'UrbanStyle', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari UrbanStyle', NULL),
(13, 'Sunshine Yellow Lite', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(14, 'Forest Green Nature', 'UrbanStyle', 1, 'Lengan Panjang', 'Produk berkualitas terbaik dari UrbanStyle', NULL),
(15, 'Coffee Brown Warm', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(16, 'Lavender Soft Look', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL),
(17, 'Deep Purple Urban', 'UrbanStyle', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari UrbanStyle', NULL),
(18, 'Teal Ocean breeze', 'DistroZone', 1, 'Lengan Pendek', 'Produk berkualitas terbaik dari DistroZone', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kaos_varian`
--

CREATE TABLE `kaos_varian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `kaos_master_id` (`kaos_master_id`),
  CONSTRAINT `kaos_varian_ibfk_1` FOREIGN KEY (`kaos_master_id`) REFERENCES `kaos_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kaos_varian`
--

INSERT INTO `kaos_varian` (`kaos_master_id`, `kode_varian`, `warna`, `warna_hex`, `size`, `harga`, `harga_pokok`, `stok`) VALUES
(1, 'TS-001', 'Hitam', '#000000', 'L', 85000.00, 50000.00, 100),
(2, 'TS-002', 'Putih', '#FFFFFF', 'XL', 95000.00, 60000.00, 50),
(3, 'JK-001', 'Navy', '#000080', 'L', 250000.00, 180000.00, 25),
(4, 'CLA1001', 'Putih', '#FFFFFF', 'S', 85000.00, 0, 25),
(4, 'CLA1002', 'Putih', '#FFFFFF', 'M', 85000.00, 0, 30),
(4, 'CLA1003', 'Putih', '#FFFFFF', 'L', 85000.00, 0, 45),
(4, 'CLA1004', 'Putih', '#FFFFFF', 'XL', 85000.00, 0, 12),
(5, 'MID2001', 'Hitam', '#000000', 'L', 125000.00, 0, 20),
(5, 'MID2002', 'Hitam', '#000000', 'XL', 125000.00, 0, 15),
(6, 'NAV3001', 'Navy', '#000080', 'M', 95000.00, 0, 40),
(6, 'NAV3002', 'Navy', '#000080', 'L', 95000.00, 0, 35),
(7, 'MAR4001', 'Maron', '#800000', 'S', 89000.00, 0, 50),
(7, 'MAR4002', 'Maron', '#800000', 'M', 89000.00, 0, 45),
(7, 'MAR4003', 'Maron', '#800000', 'L', 89000.00, 0, 40),
(8, 'ARM5001', 'Army', '#4B5320', 'L', 89000.00, 0, 30),
(8, 'ARM5002', 'Army', '#4B5320', 'XL', 89000.00, 0, 25),
(9, 'CHA6001', 'Grey', '#333333', 'M', 85000.00, 0, 20),
(10, 'SAN7001', 'Beige', '#F5F5DC', 'L', 110000.00, 0, 15),
(11, 'CRI8001', 'Merah', '#DC143C', 'XL', 85000.00, 0, 10),
(12, 'ROY9001', 'Biru', '#4169E1', 'M', 89000.00, 0, 30),
(13, 'SUN1001', 'Kuning', '#FFD700', 'L', 85000.00, 0, 25),
(14, 'FOR1101', 'Hijau', '#228B22', 'XL', 99000.00, 0, 20),
(15, 'COF1201', 'Coklat', '#6F4E37', 'M', 115000.00, 0, 15),
(16, 'LAV1301', 'Lavender', '#E6E6FA', 'L', 85000.00, 0, 10),
(17, 'DEE1401', 'Ungu', '#301934', 'XL', 89000.00, 0, 5),
(18, 'TEA1501', 'Teal', '#008080', 'M', 85000.00, 0, 30);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `kaos_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `kaos_id` (`kaos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `kasir_id` int(11) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `status` enum('pending','paid','sent','completed','cancelled') NOT NULL DEFAULT 'pending',
  `tanggal` date NOT NULL,
  `waktu` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `customer_id` (`customer_id`),
  KEY `kasir_id` (`kasir_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaksi_id` int(11) NOT NULL,
  `kaos_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga_jual` decimal(10,2) NOT NULL,
  `harga_modal` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `laba` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `transaksi_id` (`transaksi_id`),
  KEY `kaos_id` (`kaos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wilayah` varchar(100) NOT NULL,
  `cost_per_kg` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `shipping_rates`
--

INSERT INTO `shipping_rates` (`wilayah`, `cost_per_kg`) VALUES
('Jakarta', 24000.00),
('Depok', 24000.00),
('Bekasi', 25000.00),
('Tangerang', 25000.00),
('Bogor', 27000.00),
('Jawa Barat', 31000.00),
('Jawa Tengah', 39000.00),
('Jawa Timur', 47000.00);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_setting` varchar(50) NOT NULL,
  `isi_setting` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_setting` (`nama_setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`nama_setting`, `isi_setting`) VALUES
('jam_operasional_offline', '{\"open\":\"10:00\", \"close\":\"20:00\", \"closed_days\":[1]}'),
('jam_operasional_online', '{\"open\":\"10:00\", \"close\":\"17:00\", \"closed_days\":[]}');

-- --------------------------------------------------------

--
-- Table structure for table `payment_proof`
--

CREATE TABLE `payment_proof` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaksi_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
