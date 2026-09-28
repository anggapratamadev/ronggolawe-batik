# Ronggolawe Batik

Ronggolawe Batik adalah website e-commerce produk batik khas Kabupaten Tuban berbasis PHP dan MySQL.

## Fitur

- Homepage dan katalog produk
- Detail produk
- Registrasi dan login pengguna
- Keranjang belanja
- Wishlist
- Checkout
- Alamat pengiriman
- Perhitungan ongkir berdasarkan kota/kabupaten Jawa Timur
- Pembayaran QRIS, DANA, dan COD
- Tracking nomor resi
- Review produk
- Dashboard admin
- Manajemen kategori
- Manajemen produk
- Manajemen pengguna
- Manajemen pesanan
- Laporan penjualan

## Teknologi

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Laragon

## Instalasi

1. Install Laragon.
2. Clone repository ke:

   `C:\laragon\www\ronggolawe-batik`

3. Jalankan Apache dan MySQL melalui Laragon.
4. Buat database:

   `ronggolawe_batik`

5. Import file database yang tersedia di folder `database`.
6. Sesuaikan konfigurasi database.
7. Buka:

   `http://localhost/ronggolawe-batik/`

## Struktur Role

### Admin

Admin dapat mengelola:

- Kategori
- Produk
- Pengguna
- Pesanan
- Pembayaran
- Pengiriman
- Laporan

### User

User dapat:

- Melihat produk
- Menambahkan produk ke keranjang
- Membeli produk
- Mengatur alamat
- Melakukan pembayaran
- Melihat pesanan
- Melakukan konfirmasi pesanan diterima
- Memberikan review

## Catatan

Project ini dibuat untuk kebutuhan pembelajaran dan pengembangan sistem e-commerce berbasis web.