<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('admin');

$month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : date('Y-m');
$start = $month . '-01 00:00:00';
$end = date('Y-m-d H:i:s', strtotime($start . ' +1 month'));

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) total_order, COALESCE(SUM(CASE WHEN status<>'Dibatalkan' THEN subtotal ELSE 0 END),0) omzet_barang, COALESCE(SUM(CASE WHEN status<>'Dibatalkan' THEN ongkir ELSE 0 END),0) total_ongkir, COALESCE(SUM(CASE WHEN status<>'Dibatalkan' THEN total_harga ELSE 0 END),0) omzet_total FROM orders WHERE created_at>=? AND created_at<?");
mysqli_stmt_bind_param($stmt, 'ss', $start, $end);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Selesai' AND created_at>=? AND created_at<?");
mysqli_stmt_bind_param($stmt, 'ss', $start, $end); mysqli_stmt_execute($stmt);
$completed = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0); mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT od.nama_produk, SUM(od.jumlah) qty, SUM(od.subtotal) revenue FROM order_details od INNER JOIN orders o ON od.order_id=o.id WHERE o.status<>'Dibatalkan' AND o.created_at>=? AND o.created_at<? GROUP BY od.product_id, od.nama_produk ORDER BY qty DESC, revenue DESC LIMIT 8");
mysqli_stmt_bind_param($stmt, 'ss', $start, $end); mysqli_stmt_execute($stmt); $top_products = mysqli_stmt_get_result($stmt);

$stmt2 = mysqli_prepare($conn, "SELECT status, COUNT(*) total FROM orders WHERE created_at>=? AND created_at<? GROUP BY status ORDER BY total DESC");
mysqli_stmt_bind_param($stmt2, 'ss', $start, $end); mysqli_stmt_execute($stmt2); $status_rows = mysqli_stmt_get_result($stmt2);

$page_title = 'Laporan Penjualan';
$asset_prefix = '../'; $home_link = '../index.php';
include '../includes/header.php';
?>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-title">ADMIN PANEL</div>
        <a href="index.php">Dashboard</a><a href="kategori.php">Kategori</a><a href="produk.php">Produk</a><a href="pengguna.php">Pengguna</a><a href="pesanan.php">Pesanan</a><a class="active" href="laporan.php">Laporan</a><a href="../index.php">Lihat Website</a><a href="../logout.php">Keluar</a>
    </aside>
    <section class="admin-main">
        <div class="admin-head">
            <div><div class="kicker">ANALITIK TOKO</div><h1>Laporan Penjualan</h1><p class="text-muted">Ringkasan transaksi berdasarkan bulan yang dipilih.</p></div>
            <span class="badge">Periode <?= e(date('F Y', strtotime($start))); ?></span>
        </div>

        <form method="GET" class="filter-bar">
            <div class="filter-search"><label>Bulan laporan</label><input type="month" name="month" value="<?= e($month); ?>"></div>
            <button class="btn btn-primary" type="submit">Tampilkan</button>
            <button class="btn btn-outline" type="button" onclick="window.print()">Cetak</button>
        </form>

        <div class="stats">
            <div class="stat"><div class="stat-label"><small>Total Pesanan</small><span class="stat-icon">01</span></div><strong><?= (int)$summary['total_order']; ?></strong><span class="stat-note"><?= $completed; ?> pesanan selesai</span></div>
            <div class="stat"><div class="stat-label"><small>Penjualan Barang</small><span class="stat-icon">02</span></div><strong><?= rupiah($summary['omzet_barang']); ?></strong><span class="stat-note">Sebelum ongkir</span></div>
            <div class="stat"><div class="stat-label"><small>Total Ongkir</small><span class="stat-icon">03</span></div><strong><?= rupiah($summary['total_ongkir']); ?></strong><span class="stat-note">Estimasi aplikasi</span></div>
            <div class="stat"><div class="stat-label"><small>Total Transaksi</small><span class="stat-icon">04</span></div><strong><?= rupiah($summary['omzet_total']); ?></strong><span class="stat-note">Tidak termasuk pesanan dibatalkan</span></div>
        </div>

        <div class="form-grid">
            <div class="admin-card">
                <div class="admin-toolbar"><div><h2>Produk Terlaris</h2><p>Produk dengan jumlah terjual terbanyak.</p></div></div>
                <div class="table-wrap"><table class="admin-table"><thead><tr><th>Produk</th><th>Terjual</th><th>Nilai</th></tr></thead><tbody>
                <?php if (mysqli_num_rows($top_products) === 0): ?><tr><td colspan="3" class="text-muted" style="text-align:center;padding:30px">Belum ada transaksi pada periode ini.</td></tr><?php else: ?>
                <?php while($p=mysqli_fetch_assoc($top_products)): ?><tr><td><strong><?= e($p['nama_produk']); ?></strong></td><td><?= (int)$p['qty']; ?> pcs</td><td><?= rupiah($p['revenue']); ?></td></tr><?php endwhile; ?>
                <?php endif; ?></tbody></table></div>
            </div>
            <div class="admin-card">
                <div class="admin-toolbar"><div><h2>Status Pesanan</h2><p>Distribusi pesanan pada periode ini.</p></div></div>
                <div class="table-wrap"><table class="admin-table"><thead><tr><th>Status</th><th>Jumlah</th></tr></thead><tbody>
                <?php if (mysqli_num_rows($status_rows) === 0): ?><tr><td colspan="2" class="text-muted" style="text-align:center;padding:30px">Belum ada data.</td></tr><?php else: ?>
                <?php while($r=mysqli_fetch_assoc($status_rows)): ?><tr><td><span class="status <?= $r['status']==='Selesai'?'success':($r['status']==='Dibatalkan'?'danger':'warning'); ?>"><?= e($r['status']); ?></span></td><td><strong><?= (int)$r['total']; ?></strong></td></tr><?php endwhile; ?>
                <?php endif; ?></tbody></table></div>
            </div>
        </div>

        <div class="admin-card print-note"><h2>Catatan</h2><p class="text-muted">Laporan menghitung pesanan selain status Dibatalkan. Ongkir merupakan estimasi aplikasi berdasarkan kota/kabupaten tujuan Jawa Timur, bukan tarif real-time kurir.</p></div>
    </section>
</div>
<style>@media print{.topbar,.announcement,.sidebar,.filter-bar,.footer{display:none!important}.admin-layout{display:block!important}.admin-main{padding:0!important;background:#fff}.admin-card,.stat{box-shadow:none!important;break-inside:avoid}.print-note{display:block}}</style>
<?php mysqli_stmt_close($stmt); mysqli_stmt_close($stmt2); include '../includes/footer.php'; ?>
