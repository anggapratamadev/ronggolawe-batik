<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');

$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT orders.*, payments.metode_pembayaran, payments.status AS payment_status, payments.bukti_pembayaran FROM orders LEFT JOIN payments ON orders.id=payments.order_id WHERE orders.id=? AND orders.user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);
mysqli_stmt_close($stmt);

if (!$order) redirect('pesanan.php');

$timeline_status = ['Menunggu','Diproses','Dikirim','Selesai'];
$current_index = array_search($order['status'], $timeline_status, true);
if ($current_index === false) $current_index = -1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pesanan_selesai'])) {
    if ($order['status'] !== 'Dikirim') {
        flash('error', 'Pesanan hanya dapat diselesaikan setelah statusnya Dikirim.');
        redirect('order_detail.php?id=' . $order_id);
    }

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status='Selesai' WHERE id=? AND user_id=? AND status='Dikirim'");
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
        mysqli_stmt_execute($stmt);
        $changed = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($changed !== 1) {
            throw new Exception('Pesanan tidak dapat diselesaikan.');
        }

        // Untuk COD, pembayaran dianggap selesai ketika pelanggan mengonfirmasi barang diterima.
        $stmt = mysqli_prepare($conn, "UPDATE payments SET status='Dibayar', paid_at=COALESCE(paid_at,NOW()) WHERE order_id=? AND metode_pembayaran='COD' AND status<>'Dibayar'");
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        flash('success', 'Pesanan berhasil dikonfirmasi selesai. Terima kasih sudah berbelanja di Ronggolawe Batik.');
        redirect('order_detail.php?id=' . $order_id);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        flash('error', $e->getMessage());
        redirect('order_detail.php?id=' . $order_id);
    }
}

$stmt = mysqli_prepare($conn, "SELECT * FROM order_details WHERE order_id=? ORDER BY id");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$details = mysqli_stmt_get_result($stmt);

$page_title = "Detail Pesanan";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<section class="content">
    <div class="container">
        <p><a style="color:var(--blue);font-weight:800" href="pesanan.php">← Kembali ke pesanan</a></p>
        <?php if ($flash = get_flash()): ?><div class="flash <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div><?php endif; ?>

        <div class="panel">
            <div class="order-head">
                <div>
                    <div class="kicker">PESANAN</div>
                    <h1 style="margin:5px 0"><?= e($order['nomor_order']); ?></h1>
                    <div class="text-muted"><?= date('d M Y H:i', strtotime($order['created_at'])); ?></div>
                </div>
                <span class="status <?= order_status_class($order['status']); ?>"><?= e($order['status']); ?></span>
            </div>

            <?php if ($order['status'] !== 'Dibatalkan'): ?>
                <div class="order-timeline">
                    <?php foreach ($timeline_status as $i=>$step): ?>
                        <div class="timeline-step <?= $i < $current_index ? 'done' : ($i === $current_index ? 'current done' : ''); ?>">
                            <div class="timeline-dot"><?= $i < $current_index ? '✓' : ($i+1); ?></div>
                            <div class="timeline-label"><?= e($step); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flash error" style="margin-top:20px">Pesanan ini dibatalkan.</div>
            <?php endif; ?>

            <div class="table-wrap" style="margin-top:25px">
                <table class="admin-table">
                    <thead>
                        <tr><th>Produk</th><th>Jumlah</th><th>Harga Barang</th><th>Subtotal</th><th>Review</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($detail = mysqli_fetch_assoc($details)): ?>
                        <tr>
                            <td><?= e($detail['nama_produk']); ?></td>
                            <td><?= (int)$detail['jumlah']; ?></td>
                            <td><?= rupiah($detail['harga']); ?></td>
                            <td><?= rupiah($detail['subtotal']); ?></td>
                            <td>
                                <?php if ($order['status'] === 'Selesai'): ?>
                                    <a class="btn btn-outline btn-sm" href="review.php?product_id=<?= (int)$detail['product_id']; ?>&order_id=<?= (int)$order_id; ?>">Beri Review</a>
                                <?php else: ?>
                                    <span class="text-muted">Setelah selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary">
                <div class="summary-box">
                    <div class="summary-line"><span>Total harga barang</span><strong><?= rupiah($order['subtotal']); ?></strong></div>
                    <div class="summary-line"><span>Ongkir</span><strong><?= rupiah($order['ongkir']); ?></strong></div>
                    <div class="summary-total"><span>Total pembayaran</span><span><?= rupiah($order['total_harga']); ?></span></div>
                </div>
            </div>
        </div>

        <div class="panel" style="margin-top:20px">
            <h2>Pengiriman</h2>
            <p><strong><?= e($order['nama_penerima']); ?></strong> · <?= e($order['no_telepon']); ?></p>
            <p><?= nl2br(e($order['alamat'])); ?><br><?= e($order['kota']); ?> <?= e($order['kode_pos']); ?></p>
            <div class="tracking-card">
                <div><span class="tracking-label">STATUS PENGIRIMAN</span><strong><?= e($order['status']); ?></strong></div>
                <div><span class="tracking-label">KURIR</span><strong><?= e($order['kurir'] ?: 'Belum ditentukan'); ?></strong></div>
                <div><span class="tracking-label">NOMOR RESI</span><strong><?= e($order['nomor_resi'] ?: 'Belum tersedia'); ?></strong></div>
            </div>
            <?php if (!empty($order['catatan'])): ?>
                <p class="text-muted">Catatan: <?= e($order['catatan']); ?></p>
            <?php endif; ?>

            <div id="konfirmasi" style="margin-top:20px;padding:16px;border-radius:14px;background:#f5f9ff;border:1px solid #dce8f7">
                <?php if ($order['status'] === 'Dikirim'): ?>
                    <h3 style="margin-top:0">Pesanan sudah dikirim?</h3>
                    <p class="text-muted">Jika barang sudah kamu terima dengan baik, klik tombol berikut. Setelah dikonfirmasi, status pesanan menjadi <strong>Selesai</strong> dan admin tidak dapat mengubahnya lagi.</p>
                    <form method="POST" onsubmit="return confirm('Pastikan barang sudah benar-benar kamu terima. Lanjutkan?')">
                        <button class="btn btn-primary" type="submit" name="pesanan_selesai" value="1">Pesanan Sudah Diterima — Selesaikan Pesanan</button>
                    </form>
                <?php elseif ($order['status'] === 'Selesai'): ?>
                    <div class="flash success" style="margin:0">Pesanan sudah selesai. Kamu dapat memberikan review untuk produk yang dibeli.</div>
                <?php else: ?>
                    <p class="text-muted" style="margin:0">Tombol <strong>Pesanan Sudah Diterima</strong> akan muncul setelah admin mengubah status menjadi <strong>Dikirim</strong>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php mysqli_stmt_close($stmt); include "../includes/footer.php"; ?>