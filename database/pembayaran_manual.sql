USE ronggolawe_batik;

ALTER TABLE payments
    MODIFY metode_pembayaran ENUM('Transfer Bank','E-Wallet','QRIS','DANA','COD') NOT NULL;

-- Kolom bukti_pembayaran sudah tersedia pada struktur database sebelumnya.
