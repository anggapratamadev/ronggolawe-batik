<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');

$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);
$error = "";

$stmt = mysqli_prepare($conn, "SELECT orders.*, payments.id AS payment_id, payments.metode_pembayaran, payments.status AS payment_status, payments.bukti_pembayaran, payments.jumlah_bayar, payments.paid_at FROM orders INNER JOIN payments ON orders.id=payments.order_id WHERE orders.id=? AND orders.user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) redirect('pesanan.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_bukti'])) {
        if ($order['payment_status'] === 'Dibayar') {
            $error = 'Pembayaran sudah dikonfirmasi.';
        } elseif ($order['payment_status'] === 'Dikembalikan') {
            $error = 'Pembayaran sudah dikembalikan dan pesanan telah dibatalkan.';
        } elseif ($order['status'] === 'Dibatalkan') {
            $error = 'Pesanan sudah dibatalkan.';
        } elseif (!in_array($order['metode_pembayaran'], ['QRIS', 'DANA'], true)) {
            $error = 'Bukti pembayaran hanya diperlukan untuk QRIS atau DANA.';
        } elseif (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Silakan pilih bukti pembayaran terlebih dahulu.';
        } else {
            $file = $_FILES['bukti'];
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];
            $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']) : mime_content_type($file['tmp_name']);
            $max_size = 3 * 1024 * 1024;

            if (!isset($allowed[$mime])) {
                $error = 'Format bukti harus JPG, PNG, atau WEBP.';
            } elseif ((int)$file['size'] > $max_size) {
                $error = 'Ukuran bukti maksimal 3 MB.';
            } else {
                $upload_dir = __DIR__ . '/../images/payments';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = 'bukti-' . $order_id . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                $destination = $upload_dir . '/' . $filename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error = 'Bukti pembayaran gagal disimpan.';
                } else {
                    if (!empty($order['bukti_pembayaran'])) {
                        $old = $upload_dir . '/' . basename($order['bukti_pembayaran']);
                        if (is_file($old)) @unlink($old);
                    }

                    $stmt = mysqli_prepare($conn, "UPDATE payments SET bukti_pembayaran=?, status='Menunggu', paid_at=NULL WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "si", $filename, $order['payment_id']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    flash('success', 'Bukti pembayaran berhasil dikirim. Admin akan memverifikasinya.');
                    redirect('payment.php?id=' . $order_id);
                }
            }
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT payments.metode_pembayaran, payments.status AS payment_status, payments.bukti_pembayaran, payments.jumlah_bayar, payments.paid_at FROM payments WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $order['payment_id']);
mysqli_stmt_execute($stmt);
$payment_latest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if ($payment_latest) {
    $order['metode_pembayaran'] = $payment_latest['metode_pembayaran'];
    $order['payment_status'] = $payment_latest['payment_status'];
    $order['bukti_pembayaran'] = $payment_latest['bukti_pembayaran'];
    $order['jumlah_bayar'] = $payment_latest['jumlah_bayar'];
    $order['paid_at'] = $payment_latest['paid_at'];
}

$page_title = "Pembayaran";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<section class="content">
    <div class="container">
        <p><a style="color:var(--blue);font-weight:800" href="pesanan.php">← Kembali ke Pesanan</a></p>

        <?php if ($error): ?><div class="flash error"><?= e($error); ?></div><?php endif; ?>
        <?php if ($flash = get_flash()): ?><div class="flash <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div><?php endif; ?>

        <?php if (isset($_GET['created'])): ?>
            <div class="flash success">Pesanan berhasil dibuat. Silakan lakukan pembayaran sesuai metode di bawah.</div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="panel">
                <div class="kicker">PEMBAYARAN</div>
                <h1><?= e($order['nomor_order']); ?></h1>
                <p class="text-muted">Rincian pembayaran</p>
                <div style="border:1px solid var(--line);border-radius:14px;padding:14px;margin:10px 0 18px">
                    <div class="summary-line"><span>Harga barang</span><strong><?= rupiah($order['subtotal']); ?></strong></div>
                    <div class="summary-line"><span>Ongkir</span><strong><?= rupiah($order['ongkir']); ?></strong></div>
                    <div class="summary-total"><span>Total yang perlu dibayar</span><span><?= rupiah($order['total_harga']); ?></span></div>
                </div>

                <div class="payment-current" style="margin-top:22px;padding:15px;border:1px solid var(--line);border-radius:14px;background:#f8fbff">
                    <div class="text-muted" style="font-size:13px">Metode pembayaran yang dipilih saat checkout</div>
                    <strong style="font-size:20px"><?= e($order['metode_pembayaran']); ?></strong>
                    <?php if ($order['metode_pembayaran'] === 'DANA'): ?><div class="text-muted">DANA: 0815-5645-0733</div><?php elseif ($order['metode_pembayaran'] === 'QRIS'): ?><div class="text-muted">Scan QRIS merchant Ronggolawe Batik</div><?php else: ?><div class="text-muted">Bayar saat barang diterima</div><?php endif; ?>
                </div>

                <?php if ($order['payment_status'] === 'Dibayar'): ?>
                    <div class="flash success" style="margin-top:18px">Pembayaran sudah dikonfirmasi oleh admin<?= $order['paid_at'] ? ' pada ' . e(date('d/m/Y H:i', strtotime($order['paid_at']))) : ''; ?>.</div>
                <?php elseif ($order['payment_status'] === 'Dikembalikan'): ?>
                    <div class="flash error" style="margin-top:18px">Pembayaran sudah <strong>Dikembalikan</strong>. Pesanan ini dibatalkan oleh admin dan tidak perlu melakukan pembayaran lagi.</div>
                <?php elseif ($order['status'] === 'Dibatalkan'): ?>
                    <div class="flash error" style="margin-top:18px">Pesanan sudah <strong>Dibatalkan</strong>. Tidak ada pembayaran yang perlu dilakukan.</div>
                <?php elseif ($order['metode_pembayaran'] === 'QRIS'): ?>
                    <div class="payment-method-box" style="margin-top:18px">
                        <h2>Bayar dengan QRIS</h2>
                        <p class="text-muted">Scan QRIS merchant Ronggolawe Batik menggunakan aplikasi pembayaran yang mendukung QRIS.</p>
                        <div class="qris-box">
                            <?php if (is_file(__DIR__ . '/../images/qris-ronggolawe.png')): ?>
                                <img src="../images/qris-ronggolawe.png" alt="QRIS Ronggolawe Batik" style="max-width:280px;width:100%;display:block;margin:auto;border-radius:12px">
                            <?php else: ?>
                                <div class="flash warning">QRIS merchant belum dipasang. Letakkan QRIS asli toko dengan nama <strong>images/qris-ronggolawe.png</strong>.</div>
                            <?php endif; ?>
                        </div>
                        <p style="margin-top:14px"><strong>Total yang harus dibayar:</strong> <?= rupiah($order['total_harga']); ?></p>
                        <p class="text-muted" style="font-size:13px">Setelah membayar, simpan screenshot/struk transaksi lalu upload sebagai bukti di bawah.</p>
                    </div>
                <?php elseif ($order['metode_pembayaran'] === 'DANA'): ?>
                    <div class="payment-method-box" style="margin-top:18px">
                        <h2>Bayar melalui DANA</h2>
                        <p class="text-muted">Kirim pembayaran sesuai total pesanan ke nomor DANA berikut:</p>
                        <div style="font-size:30px;font-weight:900;letter-spacing:1px;margin:12px 0;color:var(--blue)">0815-5645-0733</div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText('081556450733').then(()=>this.textContent='Nomor tersalin')">Salin Nomor DANA</button>
                        <p style="margin-top:16px"><strong>Total yang harus dibayar:</strong> <?= rupiah($order['total_harga']); ?></p>
                        <p class="text-muted" style="font-size:13px">Setelah transfer, simpan screenshot/struk transaksi lalu upload sebagai bukti di bawah.</p>
                    </div>
                <?php else: ?>
                    <div class="flash success" style="margin-top:18px">Pesanan menggunakan COD. Pembayaran dilakukan saat barang diterima.</div>
                <?php endif; ?>

                <?php if (in_array($order['metode_pembayaran'], ['QRIS', 'DANA'], true) && in_array($order['payment_status'], ['Menunggu', 'Gagal'], true) && $order['status'] !== 'Dibatalkan'): ?>
                    <div class="payment-method-box" style="margin-top:18px">
                        <h2>Kirim Bukti Pembayaran</h2>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Screenshot / Foto Bukti</label>
                                <input type="file" name="bukti" accept="image/jpeg,image/png,image/webp" required>
                                <small class="text-muted">JPG, PNG, atau WEBP. Maksimal 3 MB.</small>
                            </div>
                            <button class="btn btn-primary btn-block" name="upload_bukti" type="submit">Kirim Bukti Pembayaran</button>
                        </form>
                        <?php if (!empty($order['bukti_pembayaran'])): ?>
                            <div class="flash warning" style="margin-top:12px">Bukti pembayaran sudah dikirim dan sedang menunggu pemeriksaan admin.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h2>Status Pembayaran</h2>
                <p>Metode: <strong><?= e($order['metode_pembayaran']); ?></strong></p>
                <p>Status: <span class="status <?= $order['payment_status']==='Dibayar'?'success':'warning'; ?>"><?= e($order['payment_status']); ?></span></p>
                <p>Total pembayaran: <strong><?= rupiah($order['jumlah_bayar']); ?></strong></p>
                <?php if (!empty($order['bukti_pembayaran'])): ?>
                    <p><a class="btn btn-outline btn-sm" target="_blank" href="../images/payments/<?= e(basename($order['bukti_pembayaran'])); ?>">Lihat Bukti yang Dikirim</a></p>
                <?php endif; ?>
                <hr style="border:0;border-top:1px solid #e5edf7;margin:20px 0">
                <p class="text-muted">Untuk QRIS dan DANA, pesanan akan diproses setelah admin memeriksa dan mengonfirmasi bukti pembayaran.</p>
            </div>
        </div>
    </div>
</section>

<?php include "../includes/footer.php"; ?>
