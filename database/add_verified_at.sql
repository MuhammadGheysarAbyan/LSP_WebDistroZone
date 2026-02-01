-- Tambah kolom verified_at untuk menyimpan waktu verifikasi
-- Jalankan SQL ini di phpMyAdmin

ALTER TABLE transaksi 
ADD COLUMN verified_at DATETIME NULL AFTER status;

-- Update transaksi yang sudah verified untuk set verified_at = created_at (estimasi)
UPDATE transaksi SET verified_at = created_at WHERE status = 'verified' AND verified_at IS NULL;
UPDATE transaksi SET verified_at = created_at WHERE status = 'completed' AND verified_at IS NULL;
