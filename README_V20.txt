RONGGOLAWE BATIK V20 — READY TO USE
====================================

PROJECT
-------
E-Commerce Produk Batik Khas Kabupaten Tuban berbasis PHP + MySQL.
Target lokal: Laragon / Apache / PHP / MySQL.

FOLDER
------
C:\laragon\www\ronggolawe-batik
URL: http://localhost/ronggolawe-batik/

AKUN ADMIN
----------
Email    : admin@ronggolawe.com
Password : admin123

FITUR V20
---------
- Beranda publik
- Katalog publik tanpa login
- Detail produk publik
- Login/register saat akan membeli
- Beli sekarang tanpa wajib memasukkan keranjang
- Keranjang belanja
- Banyak alamat pengiriman + alamat utama
- Estimasi ongkir otomatis untuk kota/kabupaten Jawa Timur
- QRIS manual
- DANA 0815-5645-0733
- COD
- Upload bukti pembayaran QRIS/DANA
- Verifikasi pembayaran oleh admin
- Alur pesanan terpusat di admin
- Admin wajib memasukkan kurir + nomor resi sebelum status Dikirim
- Customer yang mengubah status menjadi Selesai setelah barang diterima
- Review setelah pesanan selesai
- Profil pengguna + ganti password
- Wishlist
- Search + filter + sorting katalog
- Dashboard admin dengan statistik transaksi
- Laporan penjualan bulanan + cetak
- Manajemen produk/kategori/pengguna/pesanan
- 404/403
- Responsive HP/tablet/desktop
- Proteksi dasar session, prepared statement, password_hash/password_verify

DATABASE BARU
-------------
Jika membuat database dari nol, import:
    database/full_schema_v20.sql

File tersebut membuat database, tabel, admin, kategori, dan seed produk.

DATABASE YANG SUDAH ADA DARI V19
---------------------------------
Tidak perlu mengulang semua seed. Jalankan sekali:
    database/v20_upgrade.sql

Jika versi database lama belum mempunyai kolom kurir/nomor_resi, jalankan juga:
    database/admin_pesanan_gabungan.sql

Jika alamat pengguna belum ada:
    database/alamat_pengguna.sql

Jika pembayaran manual belum menggunakan enum QRIS/DANA/COD:
    database/pembayaran_manual.sql

CATATAN QRIS
------------
File QRIS berada di:
    images/qris-ronggolawe.png

Untuk penggunaan nyata, ganti file tersebut dengan QRIS merchant asli milik toko.

CATATAN ONGKIR
--------------
Ongkir dihitung otomatis berdasarkan kota/kabupaten tujuan Jawa Timur.
Tarif adalah estimasi aplikasi untuk kebutuhan project, bukan tarif API kurir real-time.

SETELAH EXTRACT
---------------
1. Pastikan Laragon Apache + MySQL menyala.
2. Pastikan folder project tepat di C:\laragon\www\ronggolawe-batik.
3. Buka http://localhost/ronggolawe-batik/
4. Jika CSS lama masih terlihat, tekan Ctrl+Shift+R.
5. Jika menggunakan database lama, import v20_upgrade.sql di phpMyAdmin.

ALUR TRANSAKSI
--------------
Pengunjung
  -> Katalog
  -> Detail produk
  -> Masuk/Daftar
  -> Beli Sekarang / Keranjang
  -> Pilih alamat
  -> Pilih QRIS/DANA/COD
  -> Buat pesanan

QRIS/DANA
  -> Bayar
  -> Upload bukti
  -> Admin verifikasi Dibayar
  -> Pesanan Diproses
  -> Admin isi kurir + resi
  -> Dikirim
  -> Customer menerima
  -> Customer klik Pesanan Sudah Diterima
  -> Selesai
  -> Review

COD
  -> Buat pesanan
  -> Diproses
  -> Admin isi kurir + resi
  -> Dikirim
  -> Customer menerima
  -> Selesai + pembayaran COD dianggap selesai
