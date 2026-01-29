ALTER TABLE shipping_rates ADD COLUMN estimasi VARCHAR(50) DEFAULT '2-3 Hari';

UPDATE shipping_rates SET estimasi = '1-2 Hari' WHERE wilayah IN ('Jakarta', 'Depok', 'Bekasi', 'Tangerang', 'Bogor');
UPDATE shipping_rates SET estimasi = '2-4 Hari' WHERE wilayah = 'Jawa Barat';
UPDATE shipping_rates SET estimasi = '3-5 Hari' WHERE wilayah = 'Jawa Tengah';
UPDATE shipping_rates SET estimasi = '3-6 Hari' WHERE wilayah = 'Jawa Timur';
