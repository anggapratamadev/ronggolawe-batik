-- Ronggolawe Batik V23
-- Database sudah mendukung kurir + nomor resi pada tabel orders.
-- File ini bersifat dokumentasi/cek kompatibilitas untuk instalasi lama.
-- Jalankan hanya jika database lama belum mempunyai kedua kolom berikut.

USE ronggolawe_batik;

-- Cek dengan:
-- SHOW COLUMNS FROM orders LIKE 'kurir';
-- SHOW COLUMNS FROM orders LIKE 'nomor_resi';

-- Untuk MySQL versi yang mendukung ADD COLUMN IF NOT EXISTS:
ALTER TABLE orders ADD COLUMN IF NOT EXISTS kurir VARCHAR(100) NULL AFTER kode_pos;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS nomor_resi VARCHAR(100) NULL AFTER kurir;

-- Indeks membantu pencarian nomor resi pada data besar.
CREATE INDEX IF NOT EXISTS idx_orders_resi ON orders(nomor_resi);
