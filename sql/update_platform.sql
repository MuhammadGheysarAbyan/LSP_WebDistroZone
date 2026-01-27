-- =====================================================
-- PEMISAHAN KASIR DESKTOP DAN WEB
-- =====================================================

-- 1. Tambah kolom platform di users (untuk kasir)
ALTER TABLE users ADD COLUMN platform ENUM('desktop', 'web', 'all') DEFAULT 'all' AFTER shift;

-- 2. Tambah kolom platform di transaksi
ALTER TABLE transaksi ADD COLUMN platform ENUM('desktop', 'web') DEFAULT 'desktop' AFTER status;

-- 3. Hapus semua customer
DELETE FROM users WHERE role = 'customer';

-- 4. Update kasir existing untuk desktop
UPDATE users SET platform = 'desktop', nama = 'Kasir Desktop' WHERE user_code = 'KSR-001';

-- 5. Insert kasir web baru
INSERT INTO users (user_code, username, password, nama, email, alamat, no_telp, nik, role, shift, platform, status) 
VALUES ('KSR-002', 'kasirweb', 'password', 'Kasir Web', 'kasirweb@distrozone.com', 'Jakarta', '081299998888', '3171234567890003', 'kasir', 'Pagi', 'web', 'active');

-- 6. Update admin agar bisa akses semua
UPDATE users SET platform = 'all' WHERE role = 'admin';

-- Verifikasi
SELECT user_code, username, nama, role, platform FROM users;
