-- V24.5: pastikan database mendukung status pembayaran Dikembalikan.
USE ronggolawe_batik;

ALTER TABLE payments
    MODIFY status ENUM('Menunggu','Dibayar','Gagal','Dikembalikan') NOT NULL DEFAULT 'Menunggu';

-- Verifikasi setelah menjalankan: SHOW COLUMNS FROM payments LIKE 'status';
