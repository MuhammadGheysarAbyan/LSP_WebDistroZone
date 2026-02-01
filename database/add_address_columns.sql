-- Script untuk menambahkan kolom alamat yang terpisah
-- Jalankan ini di phpMyAdmin

-- Tambah kolom baru untuk alamat terpisah
ALTER TABLE users 
ADD COLUMN desa VARCHAR(100) NULL AFTER alamat,
ADD COLUMN kecamatan VARCHAR(100) NULL AFTER desa,
ADD COLUMN kabupaten VARCHAR(100) NULL AFTER kecamatan,
ADD COLUMN kodepos VARCHAR(10) NULL AFTER kabupaten;

-- (Opsional) Migrasi data dari kolom alamat lama
-- Data lama tetap ada di kolom 'alamat' sebagai backup
