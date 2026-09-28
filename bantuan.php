<?php
require_once "config/database.php";
require_once "includes/functions.php";

start_session_once();
$page_title = "Bantuan";
$asset_prefix = "";
$home_link = "index.php";
include "includes/header.php";
?>

<section class="page-hero help-hero">
    <div class="container">
        <div class="kicker" style="color:#9fdcff">PUSAT BANTUAN</div>
        <h1>Butuh bantuan?</h1>
        <p>Temukan jawaban singkat tentang belanja, pembayaran, pengiriman, dan pesanan di Ronggolawe Batik.</p>
    </div>
</section>

<section class="content">
    <div class="container">
        <div class="help-grid">
            <div class="panel help-card">
                <span class="help-number">01</span>
                <h2>Belanja tanpa login</h2>
                <p class="text-muted">Kamu dapat melihat katalog dan detail produk tanpa membuat akun. Login atau daftar diperlukan saat ingin membeli produk.</p>
            </div>
            <div class="panel help-card">
                <span class="help-number">02</span>
                <h2>Alamat tersimpan</h2>
                <p class="text-muted">Simpan beberapa alamat dari menu Alamat. Kamu dapat mengedit, menghapus, atau memilih salah satunya sebagai alamat utama.</p>
            </div>
            <div class="panel help-card">
                <span class="help-number">03</span>
                <h2>Ongkir otomatis</h2>
                <p class="text-muted">Ongkir dihitung otomatis berdasarkan kota atau kabupaten tujuan di Jawa Timur. Nilai yang tampil merupakan estimasi aplikasi.</p>
            </div>
            <div class="panel help-card">
                <span class="help-number">04</span>
                <h2>Pembayaran</h2>
                <p class="text-muted">Metode pembayaran dipilih saat checkout. Untuk QRIS atau DANA, bukti pembayaran dikirim melalui halaman pembayaran untuk diverifikasi admin.</p>
            </div>
            <div class="panel help-card">
                <span class="help-number">05</span>
                <h2>Pengiriman</h2>
                <p class="text-muted">Setelah pembayaran diverifikasi, admin memproses pesanan. Saat dikirim, kurir dan nomor resi akan ditampilkan pada detail pesanan.</p>
            </div>
            <div class="panel help-card">
                <span class="help-number">06</span>
                <h2>Konfirmasi pesanan</h2>
                <p class="text-muted">Setelah barang benar-benar diterima, pelanggan menekan tombol <strong>Pesanan Sudah Diterima</strong>. Status kemudian berubah menjadi Selesai.</p>
            </div>
        </div>

        <div class="panel help-contact">
            <div>
                <div class="kicker">MASIH BINGUNG?</div>
                <h2>Lihat pesanan atau akunmu</h2>
                <p class="text-muted">Gunakan menu Profil, Alamat, Pesanan, dan Keranjang untuk mengelola aktivitas belanja.</p>
            </div>
            <div class="help-actions">
                <?php if ($is_user): ?>
                    <a class="btn btn-primary" href="user/pesanan.php">Lihat Pesanan</a>
                    <a class="btn btn-outline" href="user/profil.php">Buka Profil</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="user/index.php">Buka Katalog</a>
                    <a class="btn btn-outline" href="login.php">Masuk</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
