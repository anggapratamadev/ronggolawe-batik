<?php
require_once 'includes/functions.php';
start_session_once();
$page_title='Halaman Tidak Ditemukan'; $asset_prefix=''; $home_link='index.php';
include 'includes/header.php';
?>
<section class="content"><div class="container"><div class="empty"><div style="font-size:54px;font-weight:950;color:#0b5ed7;line-height:1">404</div><h2>Halaman tidak ditemukan</h2><p class="text-muted">Halaman yang kamu cari mungkin sudah dipindahkan atau alamatnya tidak benar.</p><a class="btn btn-primary" href="index.php">Kembali ke Beranda</a></div></div></section>
<?php include 'includes/footer.php'; ?>
