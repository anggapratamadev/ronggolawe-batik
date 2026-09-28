USE ronggolawe_batik;

-- Tambahan data pengiriman agar pembayaran + pengiriman dapat dikelola
-- dalam satu menu Pesanan di panel admin.
ALTER TABLE orders
    ADD COLUMN kurir VARCHAR(100) NULL AFTER kode_pos,
    ADD COLUMN nomor_resi VARCHAR(100) NULL AFTER kurir;
