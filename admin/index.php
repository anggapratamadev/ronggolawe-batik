<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('admin');

$page_title = "Dashboard Admin";
$asset_prefix = "../";
$home_link = "../index.php";

$stats = [];
$stats['produk'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"))['total'];
$stats['kategori'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM categories"))['total'];
$stats['pesanan'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders"))['total'];
$stats['user'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='user'"))['total'];
$stats['menunggu'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Menunggu'"))['total'];
$stats['diproses'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Diproses'"))['total'];
$stats['dikirim'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Dikirim'"))['total'];
$stats['omzet'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_harga),0) AS total FROM orders WHERE status<>'Dibatalkan' AND MONTH(created_at)=MONTH(CURRENT_DATE()) AND YEAR(created_at)=YEAR(CURRENT_DATE())"))['total'];

$recent = mysqli_query($conn, "SELECT orders.*, users.nama, payments.metode_pembayaran, payments.status AS payment_status FROM orders INNER JOIN users ON orders.user_id=users.id LEFT JOIN payments ON orders.id=payments.order_id ORDER BY orders.id DESC LIMIT 8");

include "../includes/header.php";
?>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-title">ADMIN PANEL</div>
        <a class="active" href="index.php">Dashboard</a>
        <a href="kategori.php">Kategori</a>
        <a href="produk.php">Produk</a>
        <a href="pengguna.php">Pengguna</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="laporan.php">Laporan</a>
        <a href="../index.php">Lihat Website</a>
        <a href="../logout.php">Keluar</a>
    </aside>

    <section class="admin-main">
        <div class="admin-head">
            <div>
                <div class="kicker">RONGGOLAWE BATIK</div>
                <h1>Dashboard Admin</h1>
            </div>
            <span class="badge">Administrator</span>
        </div>

        <div class="stats">
            <div class="stat"><div class="stat-label"><small>Total Produk</small><span class="stat-icon">01</span></div><strong><?= (int)$stats['produk']; ?></strong><span class="stat-note">Total produk terdaftar</span></div>
            <div class="stat"><div class="stat-label"><small>Kategori</small><span class="stat-icon">02</span></div><strong><?= (int)$stats['kategori']; ?></strong><span class="stat-note">Kategori tersedia</span></div>
            <div class="stat"><div class="stat-label"><small>Total Pesanan</small><span class="stat-icon">03</span></div><strong><?= (int)$stats['pesanan']; ?></strong><span class="stat-note"><?= (int)$stats['menunggu']; ?> menunggu diproses</span></div>
            <div class="stat"><div class="stat-label"><small>Pelanggan</small><span class="stat-icon">04</span></div><strong><?= (int)$stats['user']; ?></strong><span class="stat-note">Akun pelanggan</span></div>
        </div>
        <div class="stats" style="margin-top:-6px">
            <div class="stat"><div class="stat-label"><small>Pendapatan Bulan Ini</small><span class="stat-icon">Rp</span></div><strong style="font-size:23px"><?= rupiah($stats['omzet']); ?></strong><span class="stat-note">Tidak termasuk pesanan yang dibatalkan</span></div>
            <div class="stat"><div class="stat-label"><small>Menunggu</small><span class="stat-icon">05</span></div><strong><?= (int)$stats['menunggu']; ?></strong><span class="stat-note">Perlu diperiksa</span></div>
            <div class="stat"><div class="stat-label"><small>Diproses</small><span class="stat-icon">06</span></div><strong><?= (int)$stats['diproses']; ?></strong><span class="stat-note">Sedang disiapkan</span></div>
            <div class="stat"><div class="stat-label"><small>Dikirim</small><span class="stat-icon">07</span></div><strong><?= (int)$stats['dikirim']; ?></strong><span class="stat-note">Menunggu konfirmasi pelanggan</span></div>
        </div>

        <div class="admin-card">
            <div class="section-head" style="margin-bottom:12px">
                <div>
                    <h2>Pesanan Terbaru</h2>
                    <p>Aktivitas transaksi terbaru di toko.</p>
                </div>
                <a class="btn btn-outline btn-sm" href="pesanan.php">Lihat Semua</a>
            </div>

            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Nomor Pesanan</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td><strong><?= e($row['nomor_order']); ?></strong></td>
                            <td><?= e($row['nama']); ?></td>
                            <td><?= rupiah($row['total_harga']); ?></td>
                            <td><span class="status <?= ($row['payment_status'] ?? "")==='Dibayar'?'success':'warning'; ?>"><?= e($row['payment_status'] ?? 'Menunggu'); ?></span></td>
                            <td><span class="status <?= $row['status']==='Selesai'?'success':($row['status']==='Dibatalkan'?'danger':'warning'); ?>"><?= e($row['status']); ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include "../includes/footer.php"; ?>