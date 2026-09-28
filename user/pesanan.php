<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');

$user_id = (int)$_SESSION['user_id'];

// Pembatalan dari sisi pelanggan tidak tersedia setelah barang dikirim.
// Tolak juga request manual/POST 'cancel_order' agar endpoint tidak dapat
// dimanipulasi untuk membatalkan pesanan yang sudah Dikirim/Selesai.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_order') {
    $cancel_id = (int)($_POST['order_id'] ?? 0);
    $stmt_cancel = mysqli_prepare($conn, "SELECT status FROM orders WHERE id=? AND user_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt_cancel, 'ii', $cancel_id, $user_id);
    mysqli_stmt_execute($stmt_cancel);
    $cancel_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cancel));
    mysqli_stmt_close($stmt_cancel);

    if (!$cancel_order) {
        flash('error', 'Pesanan tidak ditemukan.');
    } elseif (in_array($cancel_order['status'], ['Dikirim', 'Selesai', 'Dibatalkan'], true)) {
        flash('error', 'Pesanan yang sudah dikirim tidak dapat dibatalkan oleh pelanggan.');
    } else {
        flash('error', 'Pembatalan pesanan oleh pelanggan tidak tersedia. Hubungi admin jika ada masalah dengan pesanan.');
    }
    redirect('pesanan.php');
}

$stmt = mysqli_prepare($conn, "SELECT orders.*, payments.metode_pembayaran, payments.status AS payment_status FROM orders LEFT JOIN payments ON orders.id=payments.order_id WHERE orders.user_id=? ORDER BY orders.id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);

$page_title = "Pesanan Saya";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<section class="page-hero">
    <div class="container">
        <div class="kicker" style="color:#9fdcff">AKUN</div>
        <h1>Pesanan Saya</h1>
        <p>Riwayat dan status pesanan Ronggolawe Batik.</p>
    </div>
</section>

<section class="content">
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="flash success">Pesanan berhasil dibuat.</div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($orders) === 0): ?>
            <div class="empty">
                <h2>Belum ada pesanan</h2>
                <a class="btn btn-primary" href="index.php">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                <?php
                $status_class = 'status';
                if ($order['status'] === 'Selesai') $status_class .= ' success';
                if ($order['status'] === 'Dibatalkan') $status_class .= ' danger';
                if ($order['status'] === 'Menunggu') $status_class .= ' warning';
                ?>
                <div class="order-card">
                    <div class="order-head">
                        <div>
                            <div class="order-no"><?= e($order['nomor_order']); ?></div>
                            <div class="text-muted"><?= date('d M Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <span class="<?= $status_class; ?>"><?= e($order['status']); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:20px;margin-top:18px;flex-wrap:wrap">
                        <div>
                            <div class="text-muted">Pembayaran</div>
                            <strong><?= e($order['metode_pembayaran'] ?? '-'); ?></strong>
                            <div class="text-muted">Status pembayaran: <span class="status <?= payment_status_class($order['payment_status'] ?? 'Menunggu'); ?>"><?= e($order['payment_status'] ?? 'Menunggu'); ?></span></div>
                        </div>
                        <div>
                            <div class="text-muted">Rincian Harga</div>
                            <strong>Barang: <?= rupiah($order['subtotal']); ?></strong>
                            <div class="text-muted">Ongkir: <?= rupiah($order['ongkir']); ?></div>
                            <?php if (!empty($order['kurir']) || !empty($order['nomor_resi'])): ?><div class="tracking-mini">🚚 <?= e($order['kurir'] ?: 'Kurir'); ?> · Resi: <strong><?= e($order['nomor_resi'] ?: '-'); ?></strong></div><?php endif; ?>
                            <strong style="font-size:20px">Total: <?= rupiah($order['total_harga']); ?></strong>
                        </div>
                        <div>
                            <a class="btn btn-outline btn-sm" href="order_detail.php?id=<?= (int)$order['id']; ?>">Detail</a>
                            <?php if ($order['status'] !== 'Dibatalkan' && ($order['payment_status'] ?? '') !== 'Dibayar'): ?>
                                <a class="btn btn-primary btn-sm" href="payment.php?id=<?= (int)$order['id']; ?>">Pembayaran</a>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'Dikirim'): ?>
                                <a class="btn btn-primary btn-sm" href="order_detail.php?id=<?= (int)$order['id']; ?>#konfirmasi">Pesanan Diterima</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</section>

<?php mysqli_stmt_close($stmt); include "../includes/footer.php"; ?>