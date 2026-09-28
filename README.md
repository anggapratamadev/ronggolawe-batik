# Ronggolawe Batik

**Ronggolawe Batik** adalah website e-commerce untuk penjualan produk batik khas Kabupaten Tuban berbasis PHP dan MySQL. Project ini dibuat sebagai project pembelajaran dan pengembangan sistem e-commerce berbasis web.

## Fitur

### Pengunjung
- Melihat homepage dan katalog produk
- Mencari dan memfilter produk
- Melihat detail produk
- Melihat kategori dan informasi produk

### User / Pelanggan
- Registrasi dan login
- Keranjang belanja
- Beli langsung
- Wishlist
- Checkout
- Menyimpan beberapa alamat pengiriman
- Ongkir otomatis berdasarkan kota/kabupaten di Jawa Timur
- Pembayaran manual melalui QRIS, DANA, atau COD
- Melihat status pembayaran dan pesanan
- Melihat kurir dan nomor resi
- Konfirmasi pesanan sudah diterima
- Memberikan review produk
- Mengelola profil dan password

### Admin
- Dashboard
- Kelola kategori
- Kelola produk dan stok
- Kelola pengguna/pelanggan
- Kelola pesanan
- Konfirmasi pembayaran
- Kelola pengiriman, kurir, dan nomor resi
- Laporan penjualan

## Teknologi

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- Laragon / Apache

## Struktur Folder

```text
ronggolawe-batik/
├── admin/                 # Halaman dan proses admin
├── config/                # Konfigurasi database
├── css/                   # Stylesheet
├── database/              # Installer database utama
├── images/                # Gambar produk dan aset
├── includes/              # Komponen PHP bersama
├── user/                  # Halaman pelanggan
├── .gitignore
├── .htaccess
├── index.php              # Homepage
├── login.php
├── register.php
└── README.md
```

## Instalasi di Laragon

### 1. Clone repository

Clone repository ke folder `www` Laragon:

```bash
git clone https://github.com/anggapratamadev/ronggolawe-batik.git
```

Atau jika repository sudah berada di komputer:

```text
C:\laragon\www\ronggolawe-batik
```

### 2. Jalankan Laragon

Jalankan **Apache** dan **MySQL/MariaDB** melalui Laragon.

### 3. Buat database

Buka phpMyAdmin melalui Laragon, kemudian import file:

```text
database/ronggolawe_batik.sql
```

File tersebut sudah berisi struktur tabel, akun admin, kategori, dan data produk awal sehingga tidak perlu menjalankan file SQL versi lama satu per satu.

### 4. Periksa konfigurasi database

Konfigurasi default project menggunakan database:

```text
Database : ronggolawe_batik
Host     : localhost
Username : root
Password : kosong
```

Jika konfigurasi MySQL/MariaDB di komputer berbeda, sesuaikan file konfigurasi database di folder `config/`.

### 5. Jalankan website

Buka:

```text
http://localhost/ronggolawe-batik/
```

## Akun Demo Admin

Untuk instalasi database bawaan project:

```text
Email    : admin@ronggolawe.com
Password : admin123
```

**Catatan:** akun tersebut ditujukan untuk demo/pengembangan lokal. Jika project digunakan di lingkungan nyata, segera ganti password admin.

## Pembayaran

Project menggunakan pembayaran manual:

- QRIS
- DANA
- COD

Pembayaran QRIS dan DANA diproses melalui konfirmasi admin. Pesanan COD dapat diproses sesuai alur pesanan dan pembayaran pada sistem.

## Pengiriman

Ongkir dihitung berdasarkan kota/kabupaten tujuan di Jawa Timur. Sistem menggunakan tarif yang tersimpan di aplikasi dan **bukan tarif real-time dari API ekspedisi**.

Untuk pesanan yang sudah dikirim, admin mengisi:

- Kurir
- Nomor resi

Pelanggan kemudian dapat melihat informasi pengiriman dan melakukan konfirmasi bahwa pesanan telah diterima.

## Upload Bukti Pembayaran

File bukti pembayaran yang diunggah saat penggunaan aplikasi disimpan secara lokal pada:

```text
images/payments/
```

Folder tersebut diabaikan oleh Git melalui `.gitignore`, sehingga bukti pembayaran pelanggan tidak ikut ter-upload ke repository. File `.gitkeep` digunakan agar struktur folder tetap tersedia.

## Catatan Pengembangan

- Project menggunakan prepared statement untuk query database pada bagian yang membutuhkan input pengguna.
- Password pengguna disimpan menggunakan hashing PHP.
- Repository tidak menyimpan file `.env` atau file upload pembayaran pengguna.
- File SQL lama/upgrade tidak diperlukan untuk instalasi baru. Gunakan `database/ronggolawe_batik.sql` sebagai installer utama.

## Lisensi

Project ini dibuat untuk kebutuhan pembelajaran dan pengembangan project e-commerce Ronggolawe Batik.
