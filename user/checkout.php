<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');

$user_id = (int)$_SESSION['user_id'];
$error = "";
$direct_product_id = (int)($_POST['direct_product_id'] ?? $_GET['product_id'] ?? 0);
$direct_jumlah = max(1, (int)($_POST['direct_jumlah'] ?? $_GET['jumlah'] ?? 1));
$direct_mode = $direct_product_id > 0;

$items = [];
$subtotal = 0;
$ongkir = shipping_fee('');
$addresses = [];

if ($direct_mode) {
    $stmt = mysqli_prepare($conn, "SELECT id AS product_id, nama_produk, harga, stok, status, gambar FROM products WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $direct_product_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || $row['status'] !== 'aktif') {
        $error = "Produk tidak tersedia.";
    } elseif ((int)$row['stok'] < $direct_jumlah) {
        $error = "Jumlah pesanan melebihi stok yang tersedia.";
    } else {
        $row['jumlah'] = $direct_jumlah;
        $row['line_total'] = (float)$row['harga'] * $direct_jumlah;
        $items[] = $row;
        $subtotal = $row['line_total'];
    }
} else {
    $stmt = mysqli_prepare($conn, "SELECT cart.id AS cart_id, cart.jumlah, products.id AS product_id, products.nama_produk, products.harga, products.stok, products.status, products.gambar FROM cart INNER JOIN products ON cart.product_id=products.id WHERE cart.user_id=? ORDER BY cart.id");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result_cart = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result_cart)) {
        if ($row['status'] !== 'aktif' || (int)$row['stok'] < (int)$row['jumlah']) {
            $error = "Ada produk di keranjang yang stoknya berubah. Silakan periksa kembali.";
        }
        $row['line_total'] = (float)$row['harga'] * (int)$row['jumlah'];
        $subtotal += $row['line_total'];
        $items[] = $row;
    }
    mysqli_stmt_close($stmt);

    if (!$items) {
        redirect('cart.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_pesanan'])) {
    if (isset($_POST['address_id']) && (int)$_POST['address_id'] > 0) {
        $address_id = (int)$_POST['address_id'];
        $stmt_addr = mysqli_prepare($conn, "SELECT * FROM user_addresses WHERE id=? AND user_id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt_addr, 'ii', $address_id, $user_id); mysqli_stmt_execute($stmt_addr);
        $saved_address = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_addr)); mysqli_stmt_close($stmt_addr);
        $nama_penerima = $saved_address['nama_penerima'] ?? ''; $no_telepon = $saved_address['no_telepon'] ?? ''; $alamat = $saved_address['alamat'] ?? ''; $kota = $saved_address['kota'] ?? ''; $kode_pos = $saved_address['kode_pos'] ?? '';
    } else {
        $nama_penerima = trim($_POST['nama_penerima'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $kota = trim($_POST['kota'] ?? '');
        $kode_pos = trim($_POST['kode_pos'] ?? '');
    }
    $catatan = trim($_POST['catatan'] ?? '');
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? 'QRIS';
    $ongkir = shipping_fee($kota);

    if ($nama_penerima === '' || $no_telepon === '' || $alamat === '' || $kota === '') {
        $error = "Nama penerima, nomor telepon, alamat, dan kota wajib diisi.";
    } elseif (!array_key_exists($kota, shipping_city_rates())) {
        $error = "Kota tujuan tidak valid. Silakan pilih kota dari daftar.";
    } elseif (!in_array($metode_pembayaran, ['QRIS', 'DANA', 'COD'], true)) {
        $error = "Metode pembayaran tidak valid.";
    } elseif ($error === '') {
        mysqli_begin_transaction($conn);

        try {
            $checkout_items = [];
            $fresh_subtotal = 0;

            if ($direct_mode) {
                $stmt = mysqli_prepare($conn, "SELECT id AS product_id, nama_produk, harga, stok, status FROM products WHERE id=? FOR UPDATE");
                mysqli_stmt_bind_param($stmt, "i", $direct_product_id);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);

                if (!$row || $row['status'] !== 'aktif' || (int)$row['stok'] < $direct_jumlah) {
                    throw new Exception("Stok produk tidak mencukupi atau produk sudah tidak tersedia.");
                }
                $row['jumlah'] = $direct_jumlah;
                $row['subtotal'] = (float)$row['harga'] * $direct_jumlah;
                $fresh_subtotal = $row['subtotal'];
                $checkout_items[] = $row;
            } else {
                $stmt = mysqli_prepare($conn, "SELECT cart.product_id, cart.jumlah, products.nama_produk, products.harga, products.stok, products.status FROM cart INNER JOIN products ON cart.product_id=products.id WHERE cart.user_id=? FOR UPDATE");
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $locked = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($locked)) {
                    if ($row['status'] !== 'aktif' || (int)$row['stok'] < (int)$row['jumlah']) {
                        throw new Exception("Stok salah satu produk tidak mencukupi.");
                    }
                    $row['subtotal'] = (float)$row['harga'] * (int)$row['jumlah'];
                    $fresh_subtotal += $row['subtotal'];
                    $checkout_items[] = $row;
                }
                mysqli_stmt_close($stmt);
            }

            if (!$checkout_items) {
                throw new Exception("Tidak ada produk yang dipesan.");
            }

            $ongkir = shipping_fee($kota);
            $total = $fresh_subtotal + $ongkir;
            $nomor_order = 'RB-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, nomor_order, subtotal, ongkir, total_harga, nama_penerima, no_telepon, alamat, kota, kode_pos, status, catatan) VALUES (?,?,?,?,?,?,?,?,?,?, 'Menunggu', ?)");
            mysqli_stmt_bind_param($stmt, "isdddssssss", $user_id, $nomor_order, $fresh_subtotal, $ongkir, $total, $nama_penerima, $no_telepon, $alamat, $kota, $kode_pos, $catatan);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            foreach ($checkout_items as $item) {
                $stmt = mysqli_prepare($conn, "INSERT INTO order_details (order_id, product_id, nama_produk, jumlah, harga, subtotal) VALUES (?,?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, "iisidd", $order_id, $item['product_id'], $item['nama_produk'], $item['jumlah'], $item['harga'], $item['subtotal']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare($conn, "UPDATE products SET stok=stok-? WHERE id=? AND stok>=?");
                $qty = (int)$item['jumlah'];
                mysqli_stmt_bind_param($stmt, "iii", $qty, $item['product_id'], $qty);
                mysqli_stmt_execute($stmt);
                $updated_stock = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);
                if ($updated_stock !== 1) {
                    throw new Exception('Stok produk berubah. Silakan ulangi checkout.');
                }
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO payments (order_id, metode_pembayaran, jumlah_bayar, status) VALUES (?, ?, ?, 'Menunggu')");
            mysqli_stmt_bind_param($stmt, "isd", $order_id, $metode_pembayaran, $total);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (!$direct_mode) {
                $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id=?");
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($conn);
            redirect('payment.php?id=' . $order_id . '&created=1');
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Ambil alamat tersimpan setelah proses checkout selesai.
// Disimpan sebagai array agar aman dipakai oleh foreach dan json_encode().
$stmt_addr_list = mysqli_prepare($conn, "SELECT id, label, nama_penerima, no_telepon, alamat, kota, kode_pos, is_default FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC");
mysqli_stmt_bind_param($stmt_addr_list, "i", $user_id);
mysqli_stmt_execute($stmt_addr_list);
$result_addr_list = mysqli_stmt_get_result($stmt_addr_list);
while ($address_row = mysqli_fetch_assoc($result_addr_list)) {
    $addresses[] = $address_row;
}
mysqli_stmt_close($stmt_addr_list);

$page_title = "Checkout";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<section class="page-hero">
    <div class="container">
        <div class="kicker" style="color:#9fdcff">CHECKOUT</div>
        <h1>Konfirmasi Pesanan</h1>
        <p>Pilih salah satu alamat tersimpan. Kamu bisa menyimpan banyak alamat dan mengeditnya kapan saja.</p>
    </div>
</section>

<section class="content">
    <div class="container">
        <?php if ($error): ?>
            <div class="flash error"><?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="checkout-grid">
            <?php if ($direct_mode): ?>
                <input type="hidden" name="direct_product_id" value="<?= (int)$direct_product_id; ?>">
                <input type="hidden" name="direct_jumlah" value="<?= (int)$direct_jumlah; ?>">
            <?php endif; ?>
            <div class="panel">
                <h2>Informasi Pengiriman</h2>

                <div class="form-group">
                    <label>Pilih Alamat Pengiriman</label>
                    <?php if (!empty($addresses)): ?>
                    <select name="address_id" id="address_id" required>
                        <option value="">Pilih alamat tersimpan</option>
                        <?php foreach ($addresses as $a): ?>
                            <option value="<?= (int)$a['id']; ?>" data-kota="<?= e($a['kota']); ?>" <?= !empty($a['is_default']) ? 'selected' : ''; ?>><?= e($a['label']); ?> — <?= e($a['nama_penerima']); ?>, <?= e($a['kota']); ?><?= !empty($a['is_default']) ? ' (Utama)' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Alamat diambil dari menu Alamat. <a href="alamat.php" style="color:var(--blue);font-weight:800">Kelola/tambah alamat</a></small>
                    <?php else: ?>
                    <div class="flash warning" style="margin:0">Belum ada alamat tersimpan. <a href="alamat.php" style="font-weight:800;color:var(--blue)">Tambah alamat sekarang</a>.</div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="metode_pembayaran" required>
                        <option value="QRIS" selected>QRIS — Scan QRIS</option>
                        <option value="DANA">DANA — 0815-5645-0733</option>
                        <option value="COD">COD — Bayar saat barang diterima</option>
                    </select>
                    <small class="text-muted">Metode dipilih saat checkout dan langsung melekat pada pesanan.</small>
                </div>
                <div class="form-group">
                    <label>Catatan Pesanan</label>
                    <textarea name="catatan" placeholder="Catatan untuk penjual (opsional)"></textarea>
                </div>
                <div id="addressPreview" style="border:1px solid var(--line);border-radius:14px;padding:14px;background:#f8fbff">Pilih alamat untuk melihat detail penerima.</div>
            </div>

            <div class="panel">
                <h2>Ringkasan</h2>
                <div style="border:1px solid var(--line);border-radius:14px;padding:12px;margin-bottom:14px">
                    <?php foreach ($items as $item): ?>
                        <div style="padding:10px 0;border-bottom:1px solid #edf2f8">
                            <div style="font-weight:800"><?= e($item['nama_produk']); ?></div>
                            <div class="text-muted" style="font-size:13px;margin-top:4px">
                                Harga barang: <?= rupiah($item['harga']); ?> × <?= (int)$item['jumlah']; ?> pcs
                            </div>
                            <div style="display:flex;justify-content:space-between;gap:12px;margin-top:5px">
                                <span class="text-muted">Subtotal produk</span>
                                <strong><?= rupiah($item['line_total']); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-line"><span>Total harga barang</span><strong><?= rupiah($subtotal); ?></strong></div>
                <div class="summary-line"><span>Estimasi ongkir</span><strong id="ongkirPreview"><?= rupiah($ongkir); ?></strong></div>
                <div class="summary-total"><span>Total pembayaran</span><span id="totalPreview"><?= rupiah($subtotal + $ongkir); ?></span></div>
                <p class="text-muted" style="font-size:12px;margin-top:15px">Ongkir dihitung otomatis berdasarkan kota/kabupaten tujuan di Jawa Timur. Tarif ini merupakan estimasi aplikasi dan bukan tarif real-time dari kurir.</p>
                <button class="btn btn-primary btn-block" name="buat_pesanan" type="submit">Buat Pesanan</button>
            </div>
        </form>
    </div>
</section>


<script>
(function () {
    const addressSelect = document.getElementById('address_id');
    const addressPreview = document.getElementById('addressPreview');
    const shippingEl = document.getElementById('ongkirPreview');
    const totalEl = document.getElementById('totalPreview');
    const subtotal = <?= json_encode((float)$subtotal); ?>;
    const rates = <?= json_encode(shipping_city_rates(), JSON_UNESCAPED_UNICODE); ?>;
    const rupiahJs = (value) => 'Rp ' + Math.round(value).toLocaleString('id-ID');
    const addressData = <?= json_encode(array_map(function($a){ return ['id'=>(int)$a['id'],'label'=>$a['label'],'nama'=>$a['nama_penerima'],'telepon'=>$a['no_telepon'],'alamat'=>$a['alamat'],'kota'=>$a['kota'],'kode_pos'=>$a['kode_pos']]; }, $addresses), JSON_UNESCAPED_UNICODE); ?>;
    function updateAddressPreview() {
        if (!addressSelect || !addressPreview) return;
        const id = parseInt(addressSelect.value || '0', 10);
        const a = addressData.find(x => x.id === id);
        if (!a) { addressPreview.textContent = 'Pilih alamat untuk melihat detail penerima.'; return; }
        addressPreview.innerHTML = '<strong>' + a.label + '</strong><br>' + a.nama + ' · ' + a.telepon + '<br>' + a.alamat.replace(/\n/g,'<br>') + '<br>' + a.kota + ' ' + a.kode_pos;
    }
    function updateShipping() {
        const selected = addressSelect ? addressSelect.options[addressSelect.selectedIndex] : null;
        const key = selected ? selected.getAttribute('data-kota') || '' : '';
        const shipping = rates[key] || rates['Lainnya'];
        shippingEl.textContent = rupiahJs(shipping);
        totalEl.textContent = rupiahJs(subtotal + shipping);
    }
    if (addressSelect) addressSelect.addEventListener('change', updateShipping);
    if (addressSelect) addressSelect.addEventListener('change', updateAddressPreview);
    updateAddressPreview();
    updateShipping();
})();
</script>

<?php include "../includes/footer.php"; ?>