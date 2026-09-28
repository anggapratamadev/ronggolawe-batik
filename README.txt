RONGGOLAWE BATIK - V8 PEMBAYARAN MANUAL QRIS + DANA
=====================================================

Versi ini tidak menggunakan Midtrans.

Metode pembayaran:
1. QRIS
2. DANA - 081556450733
3. COD

ALUR PEMBAYARAN:
Checkout -> pilih metode -> buat pesanan -> halaman pembayaran -> lakukan pembayaran -> upload bukti -> admin verifikasi -> status Dibayar -> pesanan otomatis Diproses.

QRIS:
- Sistem membaca file: images/qris-ronggolawe.png
- WAJIB mengganti file tersebut dengan QRIS merchant ASLI milik toko.
- Jangan membuat QR code dari nomor DANA lalu menyebutnya QRIS karena itu bukan QRIS merchant.

DANA:
- Nomor tujuan: 081556450733
- Pengguna mengirim sesuai total pesanan lalu upload screenshot/struk.

UPLOAD BUKTI:
- Format JPG, PNG, WEBP
- Maksimal 3 MB
- Disimpan di images/payments/

DATABASE:
Import database/pembayaran_manual.sql ke database ronggolawe_batik.
Migration hanya mengubah enum metode pembayaran; tabel dan data lain tetap.

ADMIN:
Admin -> Pembayaran dapat melihat bukti dan mengubah status.
Jika status diubah menjadi Dibayar, pesanan yang masih Menunggu otomatis menjadi Diproses.

Catatan keamanan:
QRIS harus berupa QRIS merchant yang benar-benar diterbitkan oleh penyedia QRIS. Jangan menggunakan QR contoh untuk transaksi nyata.

PEMBARUAN V9 - ADMIN PESANAN TERPADU
------------------------------------
Menu Pembayaran pada admin sudah digabung ke menu Pesanan.
Admin dapat mengelola dalam satu halaman:
- Detail pelanggan dan produk
- Verifikasi pembayaran QRIS/DANA
- Status pembayaran
- Status pesanan
- Kurir
- Nomor resi

Sebelum memakai fitur pengiriman, import:
database/admin_pesanan_gabungan.sql
ke database ronggolawe_batik melalui phpMyAdmin.


Pembaruan V10:
- Ongkir standar Rp 15.000 ditambahkan ke checkout dan total pesanan.
- Rincian harga barang per unit, jumlah, subtotal produk, ongkir, dan total ditampilkan.
- Status Selesai hanya dapat dikonfirmasi customer setelah pesanan berstatus Dikirim.
- Untuk COD, pembayaran otomatis ditandai Dibayar saat customer mengonfirmasi pesanan diterima.


FITUR ONGKIR OTOMATIS
- Checkout menghitung ongkir berdasarkan kota tujuan dan total berat produk.
- Berat produk disimpan di products.berat_gram.
- Tarif dasar per kota berlaku untuk 1 kg; setiap tambahan 1 kg menambah Rp5.000.
- Jalankan database/ongkir_otomatis.sql sekali pada database ronggolawe_batik sebelum menggunakan fitur ini.
- Tarif merupakan simulasi untuk project/tugas kampus, bukan tarif kurir real-time.
