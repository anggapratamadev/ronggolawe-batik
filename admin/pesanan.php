<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('admin');

$error = '';
$allowed_payment = ['Menunggu', 'Dibayar', 'Gagal', 'Dikembalikan'];
$order_statuses = ['Menunggu', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];
$allowed_order = ['Menunggu', 'Diproses', 'Dikirim', 'Dibatalkan'];

/*
 * Semua proses transaksi admin dipusatkan di halaman ini:
 * pembayaran + proses pesanan + pengiriman.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $order_id = (int)($_POST['order_id'] ?? 0);

    if ($order_id <= 0) {
        flash('error', 'Pesanan tidak valid.');
        redirect('pesanan.php');
    }

    if ($action === 'update_payment') {
        $payment_id = (int)($_POST['payment_id'] ?? 0);
        $payment_status = $_POST['payment_status'] ?? '';

        if (!in_array($payment_status, $allowed_payment, true) || $payment_id <= 0) {
            flash('error', 'Status pembayaran tidak valid.');
            redirect('pesanan.php?id=' . $order_id);
        }

        $stmt = mysqli_prepare($conn, "SELECT orders.status AS order_status, payments.status AS current_payment_status, payments.metode_pembayaran FROM orders INNER JOIN payments ON orders.id=payments.order_id WHERE orders.id=? AND payments.id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $payment_id);
        mysqli_stmt_execute($stmt);
        $payment_context = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$payment_context) {
            flash('error', 'Data pembayaran tidak ditemukan.');
            redirect('pesanan.php?id=' . $order_id);
        }

        $current_order_status = $payment_context['order_status'];
        $current_payment_status = $payment_context['current_payment_status'];
        $payment_method = $payment_context['metode_pembayaran'];

        if ($current_order_status === 'Selesai') {
            flash('error', 'Pesanan sudah selesai. Status pembayaran tidak dapat diubah lagi.');
            redirect('pesanan.php?id=' . $order_id);
        }

        if ($current_order_status === 'Dibatalkan') {
            flash('error', 'Pesanan sudah dibatalkan. Status pembayaran tidak dapat diubah lagi.');
            redirect('pesanan.php?id=' . $order_id);
        }

        if ($current_payment_status === $payment_status) {
            flash('error', 'Status pembayaran sudah ' . $payment_status . '. Tidak ada perubahan.');
            redirect('pesanan.php?id=' . $order_id);
        }

        // COD baru dianggap dibayar ketika pelanggan menyelesaikan pesanan.
        // Admin tidak boleh mengubah COD menjadi Dibayar/Dikembalikan/Gagal.
        if ($payment_method === 'COD') {
            flash('error', 'Pembayaran COD tetap Menunggu sampai pelanggan menerima barang. Status COD akan menjadi Dibayar otomatis saat pesanan dikonfirmasi Selesai.');
            redirect('pesanan.php?id=' . $order_id);
        }

        // Pembayaran non-COD mempunyai alur:
        // Menunggu/Gagal -> Dibayar -> (opsional) Dikembalikan sebelum dikirim.
        // Setelah pesanan Dikirim/Selesai, status pembayaran tidak boleh diturunkan.
        if ($payment_status === 'Dibayar') {
            if (!in_array($current_payment_status, ['Menunggu', 'Gagal'], true) || $current_order_status !== 'Menunggu') {
                flash('error', 'Pembayaran hanya dapat dikonfirmasi menjadi Dibayar dari Menunggu/Gagal saat pesanan masih Menunggu.');
                redirect('pesanan.php?id=' . $order_id);
            }
        } elseif ($payment_status === 'Gagal' || $payment_status === 'Menunggu') {
            // Status pembayaran tidak boleh diputar-putar oleh admin.
            // Dari Menunggu/Gagal, tindakan admin hanya boleh mengonfirmasi menjadi Dibayar.
            // Setelah Dibayar, pengembalian hanya melalui Dikembalikan sebelum dikirim.
            flash('error', 'Status pembayaran hanya boleh dikonfirmasi menjadi Dibayar. Setelah Dibayar, gunakan Dikembalikan jika memang perlu refund sebelum pesanan dikirim.');
            redirect('pesanan.php?id=' . $order_id);
        } elseif ($payment_status === 'Dikembalikan') {
            if ($current_payment_status !== 'Dibayar' || !in_array($current_order_status, ['Menunggu', 'Diproses'], true)) {
                flash('error', 'Pengembalian hanya dapat dilakukan untuk pembayaran yang sudah Dibayar sebelum pesanan dikirim.');
                redirect('pesanan.php?id=' . $order_id);
            }
        }

        mysqli_begin_transaction($conn);
        try {
            $new_paid_at = ($payment_status === 'Dibayar') ? 'NOW()' : 'NULL';
            $sql_payment = "UPDATE payments SET status=?, paid_at=" . $new_paid_at . " WHERE id=? AND order_id=?";
            $stmt = mysqli_prepare($conn, $sql_payment);
            mysqli_stmt_bind_param($stmt, "sii", $payment_status, $payment_id, $order_id);
            mysqli_stmt_execute($stmt);
            $payment_changed = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($payment_changed !== 1) {
                throw new Exception('Status pembayaran gagal diperbarui.');
            }

            if ($payment_status === 'Dibayar') {
                $stmt = mysqli_prepare($conn, "UPDATE orders SET status='Diproses' WHERE id=? AND status='Menunggu'");
                mysqli_stmt_bind_param($stmt, "i", $order_id);
                mysqli_stmt_execute($stmt);
                $order_changed = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);

                if ($order_changed !== 1) {
                    throw new Exception('Pembayaran sudah berubah, tetapi status pesanan gagal masuk ke Diproses. Transaksi dibatalkan.');
                }

                mysqli_commit($conn);
                flash('success', 'Pembayaran dikonfirmasi. Pesanan otomatis masuk ke Diproses dan siap diproses untuk pengiriman.');
            } elseif ($payment_status === 'Dikembalikan') {
                $stmt = mysqli_prepare($conn, "UPDATE orders SET status='Dibatalkan' WHERE id=? AND status IN ('Menunggu','Diproses')");
                mysqli_stmt_bind_param($stmt, "i", $order_id);
                mysqli_stmt_execute($stmt);
                $order_changed = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);

                if ($order_changed !== 1) {
                    throw new Exception('Pesanan gagal dibatalkan saat pembayaran dikembalikan.');
                }

                restore_order_stock($conn, $order_id);
                mysqli_commit($conn);
                flash('success', 'Pembayaran Dikembalikan. Pesanan dibatalkan dan stok dikembalikan.');
            } else {
                mysqli_commit($conn);
                flash('success', 'Status pembayaran diperbarui menjadi ' . $payment_status . '.');
            }
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            flash('error', $e->getMessage());
        }

        redirect('pesanan.php?id=' . $order_id);
    }

    if ($action === 'update_order') {
        $status = $_POST['order_status'] ?? '';
        $kurir = trim($_POST['kurir'] ?? '');
        $nomor_resi = trim($_POST['nomor_resi'] ?? '');

        if ($status === 'Selesai') {
            flash('error', 'Status Selesai hanya dapat dikonfirmasi oleh pelanggan setelah pesanan diterima.');
            redirect('pesanan.php?id=' . $order_id);
        }

        if (!in_array($status, $allowed_order, true)) {
            flash('error', 'Status pesanan tidak valid.');
            redirect('pesanan.php?id=' . $order_id);
        }

        if ($status === 'Dikirim' && ($kurir === '' || $nomor_resi === '')) {
            flash('error', 'Kurir dan nomor resi wajib diisi sebelum pesanan berstatus Dikirim.');
            redirect('pesanan.php?id=' . $order_id);
        }

        $check_order = mysqli_prepare($conn, "SELECT status FROM orders WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($check_order, 'i', $order_id); mysqli_stmt_execute($check_order);
        $current_order = mysqli_fetch_assoc(mysqli_stmt_get_result($check_order)); mysqli_stmt_close($check_order);
        if (!$current_order) { flash('error','Pesanan tidak ditemukan.'); redirect('pesanan.php'); }
        $current_status = $current_order['status'];

        if ($current_status === 'Selesai') {
            flash('error', 'Pesanan yang sudah selesai tidak dapat diubah lagi oleh admin.');
            redirect('pesanan.php?id=' . $order_id);
        }
        if ($current_status === 'Dibatalkan') {
            flash('error', 'Pesanan yang sudah dibatalkan tidak dapat diproses kembali.');
            redirect('pesanan.php?id=' . $order_id);
        }

        if ($status === 'Diproses' && !in_array($current_status, ['Menunggu','Diproses'], true)) {
            flash('error', 'Pesanan harus berstatus Menunggu sebelum diproses.');
            redirect('pesanan.php?id=' . $order_id);
        }
        if ($status === 'Dikirim' && !in_array($current_status, ['Diproses','Dikirim'], true)) {
            flash('error', 'Pesanan harus berstatus Diproses sebelum dikirim.');
            redirect('pesanan.php?id=' . $order_id);
        }

        if (in_array($status, ['Diproses','Dikirim'], true)) {
            $check = mysqli_prepare($conn, "SELECT metode_pembayaran, status FROM payments WHERE order_id=? LIMIT 1");
            mysqli_stmt_bind_param($check, "i", $order_id); mysqli_stmt_execute($check);
            $pay_check = mysqli_fetch_assoc(mysqli_stmt_get_result($check)); mysqli_stmt_close($check);
            if (!$pay_check) { flash('error', 'Data pembayaran pesanan tidak ditemukan.'); redirect('pesanan.php?id=' . $order_id); }
            if ($pay_check['metode_pembayaran'] !== 'COD' && $pay_check['status'] !== 'Dibayar') {
                flash('error', 'Pesanan QRIS/DANA harus berstatus Dibayar sebelum diproses atau dikirim.');
                redirect('pesanan.php?id=' . $order_id);
            }
        }

        if ($status === 'Dibatalkan' && !in_array($current_status, ['Menunggu','Diproses'], true)) {
            flash('error', 'Pesanan yang sudah dikirim atau selesai tidak dapat dibatalkan.');
            redirect('pesanan.php?id=' . $order_id);
        }

        if ($status === 'Dibatalkan') {
            $cancel_pay = mysqli_prepare($conn, "SELECT metode_pembayaran, status FROM payments WHERE order_id=? LIMIT 1");
            mysqli_stmt_bind_param($cancel_pay, 'i', $order_id);
            mysqli_stmt_execute($cancel_pay);
            $cancel_payment = mysqli_fetch_assoc(mysqli_stmt_get_result($cancel_pay));
            mysqli_stmt_close($cancel_pay);

            if ($cancel_payment && $cancel_payment['metode_pembayaran'] !== 'COD' && $cancel_payment['status'] === 'Dibayar') {
                flash('error', 'Pesanan yang sudah dibayar tidak dapat dibatalkan langsung. Ubah status pembayaran menjadi Dikembalikan terlebih dahulu.');
                redirect('pesanan.php?id=' . $order_id);
            }
        }

        // Kurir/resi disimpan pada tabel orders melalui migration V9.
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status=?, kurir=?, nomor_resi=? WHERE id=?");
        if (!$stmt) {
            flash('error', 'Kolom pengiriman belum tersedia. Import database/admin_pesanan_gabungan.sql terlebih dahulu.');
            redirect('pesanan.php?id=' . $order_id);
        }
        mysqli_stmt_bind_param($stmt, "sssi", $status, $kurir, $nomor_resi, $order_id);
        mysqli_stmt_execute($stmt);
        $changed = mysqli_stmt_affected_rows($stmt); mysqli_stmt_close($stmt);
        if ($status === 'Dibatalkan' && $changed > 0) { restore_order_stock($conn, $order_id); }

        flash('success', 'Status pesanan dan informasi pengiriman berhasil diperbarui.');
        redirect('pesanan.php?id=' . $order_id);
    }
}

$selected_id = (int)($_GET['id'] ?? 0);
$selected = null;
$details = null;
$payment = null;

if ($selected_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT orders.*, users.nama, users.email, payments.id AS payment_id, payments.metode_pembayaran, payments.status AS payment_status, payments.bukti_pembayaran, payments.jumlah_bayar, payments.paid_at FROM orders INNER JOIN users ON orders.user_id=users.id LEFT JOIN payments ON orders.id=payments.order_id WHERE orders.id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $selected_id);
    mysqli_stmt_execute($stmt);
    $selected = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($selected) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM order_details WHERE order_id=? ORDER BY id ASC");
        mysqli_stmt_bind_param($stmt, "i", $selected_id);
        mysqli_stmt_execute($stmt);
        $details = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
}

$keyword = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$where = [];
$params = [];
$types = '';

if ($keyword !== '') {
    $where[] = "(orders.nomor_order LIKE CONCAT('%', ?, '%') OR users.nama LIKE CONCAT('%', ?, '%') OR users.email LIKE CONCAT('%', ?, '%'))";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= 'sss';
}
if (in_array($status_filter, $order_statuses, true)) {
    $where[] = "orders.status=?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql = "SELECT orders.id, orders.nomor_order, orders.total_harga, orders.status, orders.created_at, users.nama, payments.metode_pembayaran, payments.status AS payment_status FROM orders INNER JOIN users ON orders.user_id=users.id LEFT JOIN payments ON orders.id=payments.order_id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY orders.id DESC';

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);

$available_statuses = [];
$available_payment_statuses = [];
if ($selected) {
    if ($selected['status'] === 'Menunggu') {
        $available_statuses = ['Menunggu','Diproses','Dibatalkan'];
    } elseif ($selected['status'] === 'Diproses') {
        $available_statuses = ['Diproses','Dikirim','Dibatalkan'];
    } elseif ($selected['status'] === 'Dikirim') {
        $available_statuses = ['Dikirim'];
    } else {
        $available_statuses = [$selected['status']];
    }

    // Pilihan pembayaran mengikuti state machine agar admin tidak dapat
    // membuat kombinasi status pembayaran/pesanan yang tidak masuk akal.
    $selected_payment_status = $selected['payment_status'] ?? 'Menunggu';
    $selected_payment_method = $selected['metode_pembayaran'] ?? '';
    if ($selected['status'] === 'Selesai' || $selected['status'] === 'Dibatalkan') {
        $available_payment_statuses = [$selected_payment_status];
    } elseif ($selected_payment_method === 'COD') {
        // COD tetap Menunggu sampai pelanggan menekan Pesanan Diterima.
        $available_payment_statuses = [$selected_payment_status];
    } elseif ($selected_payment_status === 'Menunggu') {
        $available_payment_statuses = ['Menunggu', 'Dibayar'];
    } elseif ($selected_payment_status === 'Gagal') {
        $available_payment_statuses = ['Gagal', 'Dibayar'];
    } elseif ($selected_payment_status === 'Dibayar' && in_array($selected['status'], ['Menunggu','Diproses'], true)) {
        $available_payment_statuses = ['Dibayar', 'Dikembalikan'];
    } else {
        $available_payment_statuses = [$selected_payment_status];
    }
}

$page_title = "Kelola Pesanan";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-title">ADMIN PANEL</div>
        <a href="index.php">Dashboard</a>
        <a href="kategori.php">Kategori</a>
        <a href="produk.php">Produk</a>
        <a href="pengguna.php">Pengguna</a>
        <a class="active" href="pesanan.php">Pesanan</a>
        <a href="laporan.php">Laporan</a>
        <a href="../index.php">Lihat Website</a>
        <a href="../logout.php">Keluar</a>
    </aside>

    <section class="admin-main">
        <div class="admin-head">
            <div>
                <div class="kicker">TRANSAKSI</div>
                <h1>Pesanan</h1>
                <p class="text-muted">Pembayaran, proses pesanan, dan pengiriman dikelola dari satu tempat.</p>
            </div>
        </div>

        <?php if ($flash = get_flash()): ?>
            <div class="flash <?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <form method="GET" class="form-grid" style="align-items:end">
                <div class="form-group">
                    <label>Cari Pesanan</label>
                    <input type="text" name="q" value="<?= e($keyword); ?>" placeholder="Nomor order, nama, atau email">
                </div>
                <div class="form-group">
                    <label>Status Pesanan</label>
                    <select name="status">
                        <option value="">Semua status</option>
                        <?php foreach ($order_statuses as $status): ?>
                            <option value="<?= e($status); ?>" <?= $status_filter === $status ? 'selected' : ''; ?>><?= e($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" type="submit">Filter Pesanan</button>
                    <a class="btn btn-outline" href="pesanan.php">Reset</a>
                </div>
            </form>
        </div>

        <?php if ($selected): ?>
            <div class="admin-card">
                <div class="order-head">
                    <div>
                        <div class="kicker">DETAIL PESANAN</div>
                        <h2 style="margin:5px 0"><?= e($selected['nomor_order']); ?></h2>
                        <div class="text-muted">Dibuat <?= date('d/m/Y H:i', strtotime($selected['created_at'])); ?></div>
                    </div>
                    <div style="text-align:right">
                        <span class="status <?= order_status_class($selected['status']); ?>"><?= e($selected['status']); ?></span>
                        <div style="margin-top:8px;font-weight:900;font-size:20px"><?= rupiah($selected['total_harga']); ?></div>
                    </div>
                </div>

                <div class="form-grid" style="margin-top:22px">
                    <div class="panel" style="box-shadow:none;border:1px solid var(--line)">
                        <h3>Pelanggan</h3>
                        <p><strong><?= e($selected['nama']); ?></strong><br><?= e($selected['email']); ?></p>
                        <p><strong>Penerima:</strong> <?= e($selected['nama_penerima']); ?><br>
                        <strong>Telepon:</strong> <?= e($selected['no_telepon']); ?></p>
                        <p><strong>Alamat:</strong><br><?= nl2br(e($selected['alamat'])); ?><br><?= e($selected['kota']); ?> <?= e($selected['kode_pos']); ?></p>
                        <?php if (!empty($selected['catatan'])): ?><p class="text-muted"><strong>Catatan:</strong> <?= e($selected['catatan']); ?></p><?php endif; ?>
                    </div>

                    <div class="panel" style="box-shadow:none;border:1px solid var(--line)">
                        <h3>Ringkasan Produk</h3>
                        <?php if ($details): ?>
                            <?php while ($detail = mysqli_fetch_assoc($details)): ?>
                                <div style="padding:9px 0;border-bottom:1px solid #edf2f8">
                                    <div style="font-weight:800"><?= e($detail['nama_produk']); ?></div>
                                    <div class="text-muted" style="font-size:13px;margin-top:3px">Harga barang: <?= rupiah($detail['harga']); ?> × <?= (int)$detail['jumlah']; ?> pcs</div>
                                    <div class="summary-line" style="margin-top:4px"><span>Subtotal produk</span><strong><?= rupiah($detail['subtotal']); ?></strong></div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        <div class="summary-line"><span>Total harga barang</span><strong><?= rupiah($selected['subtotal']); ?></strong></div>
                        <div class="summary-line"><span>Ongkir</span><strong><?= rupiah($selected['ongkir']); ?></strong></div>
                        <div class="summary-total"><span>Total pembayaran</span><span><?= rupiah($selected['total_harga']); ?></span></div>
                    </div>
                </div>

                <div class="form-grid" style="margin-top:20px">
                    <div class="panel" style="box-shadow:none;border:1px solid var(--line)">
                        <h3>Pembayaran</h3>
                        <p>Metode: <strong><?= e($selected['metode_pembayaran'] ?? '-'); ?></strong></p>
                        <p>Jumlah: <strong><?= rupiah($selected['jumlah_bayar'] ?? $selected['total_harga']); ?></strong></p>
                        <p>Status: <span class="status <?= payment_status_class($selected['payment_status'] ?? 'Menunggu'); ?>"><?= e($selected['payment_status'] ?? 'Menunggu'); ?></span></p>

                        <?php if (!empty($selected['bukti_pembayaran'])): ?>
                            <p><a class="btn btn-outline btn-sm" target="_blank" href="../images/payments/<?= e(basename($selected['bukti_pembayaran'])); ?>">Lihat Bukti Pembayaran</a></p>
                        <?php elseif (in_array($selected['metode_pembayaran'], ['QRIS','DANA'], true)): ?>
                            <p class="text-muted">Bukti pembayaran belum dikirim pelanggan.</p>
                        <?php endif; ?>

                        <?php if (!empty($selected['payment_id'])): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_payment">
                                <input type="hidden" name="order_id" value="<?= (int)$selected['id']; ?>">
                                <input type="hidden" name="payment_id" value="<?= (int)$selected['payment_id']; ?>">
                                <label>Status Pembayaran</label>
                                <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                                    <select name="payment_status" style="flex:1;min-width:180px" <?= count($available_payment_statuses) <= 1 ? 'disabled' : ''; ?>>
                                        <?php foreach ($available_payment_statuses as $status): ?>
                                            <option value="<?= e($status); ?>" <?= $selected['payment_status'] === $status ? 'selected' : ''; ?>><?= e($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($selected['metode_pembayaran'] === 'COD' && $selected['status'] !== 'Selesai' && $selected['status'] !== 'Dibatalkan'): ?>
                                        <small class="text-muted" style="width:100%;display:block">COD akan menjadi <strong>Dibayar</strong> otomatis setelah pelanggan mengonfirmasi pesanan diterima.</small>
                                    <?php endif; ?>
                                    <?php if (count($available_payment_statuses) > 1): ?>
                                        <button class="btn btn-primary" type="submit">Simpan Pembayaran</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="panel" style="box-shadow:none;border:1px solid var(--line)">
                        <h3>Pengiriman & Status Pesanan</h3>
                        <?php if ($selected['status'] === 'Selesai'): ?>
                            <div class="flash success" style="margin:0">Pesanan sudah <strong>Selesai</strong>. Status ini dikonfirmasi oleh pelanggan setelah barang diterima dan tidak dapat diubah oleh admin.</div>
                            <?php if (!empty($selected['kurir']) || !empty($selected['nomor_resi'])): ?>
                                <p style="margin-top:16px"><strong>Kurir:</strong> <?= e($selected['kurir'] ?: '-'); ?><br><strong>Nomor Resi:</strong> <?= e($selected['nomor_resi'] ?: '-'); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_order">
                                <input type="hidden" name="order_id" value="<?= (int)$selected['id']; ?>">
                                <div class="form-group">
                                    <label>Status Pesanan</label>
                                    <select name="order_status" required>
                                        <?php foreach ($available_statuses as $status): ?>
                                            <option value="<?= e($status); ?>" <?= $selected['status'] === $status ? 'selected' : ''; ?>><?= e($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Alur toko: Menunggu → Diproses → Dikirim. Sebelum Dikirim, kurir dan nomor resi wajib diisi. Setelah barang diterima, pelanggan yang mengubahnya menjadi Selesai.</small>
                                </div>
                                <div class="form-group">
                                    <label>Kurir</label>
                                    <input type="text" name="kurir" value="<?= e($selected['kurir'] ?? ''); ?>" placeholder="JNE, J&T, SiCepat, dll.">
                                </div>
                                <div class="form-group">
                                    <label>Nomor Resi</label>
                                    <input type="text" name="nomor_resi" value="<?= e($selected['nomor_resi'] ?? ''); ?>" placeholder="Masukkan nomor resi jika sudah dikirim">
                                </div>
                                <button class="btn btn-primary btn-block" type="submit">Simpan Pesanan & Pengiriman</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="section-head" style="margin-bottom:12px">
                <div>
                    <h2>Daftar Pesanan</h2>
                    <p>Semua transaksi pelanggan dalam satu halaman.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>Pesanan</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status Pesanan</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                        <tr>
                            <td><strong><?= e($order['nomor_order']); ?></strong><br><span class="text-muted"><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></span></td>
                            <td><?= e($order['nama']); ?></td>
                            <td><?= rupiah($order['total_harga']); ?></td>
                            <td>
                                <?= e($order['metode_pembayaran'] ?? '-'); ?><br>
                                <span class="status <?= $order['payment_status'] === 'Dibayar' ? 'success' : (($order['payment_status'] === 'Gagal' ? 'danger' : 'warning')); ?>"><?= e($order['payment_status'] ?? 'Menunggu'); ?></span>
                            </td>
                            <td><span class="status <?= order_status_class($order['status']); ?>"><?= e($order['status']); ?></span></td>
                            <td><a class="btn btn-primary btn-sm" href="pesanan.php?id=<?= (int)$order['id']; ?>">Kelola</a></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($orders) === 0): ?>
                        <tr><td colspan="6" class="text-muted" style="text-align:center;padding:30px">Belum ada pesanan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php mysqli_stmt_close($stmt); include "../includes/footer.php"; ?>
