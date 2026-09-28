<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');

$user_id = (int)$_SESSION['user_id'];
$error = "";

if (isset($_POST['tambah_keranjang'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));

    $stmt = mysqli_prepare($conn, "SELECT id, stok, status FROM products WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $product_result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($product_result);
    mysqli_stmt_close($stmt);

    if (!$product || $product['status'] !== 'aktif') {
        $error = "Produk tidak tersedia.";
    } elseif ((int)$product['stok'] <= 0) {
        $error = "Stok produk habis.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, jumlah FROM cart WHERE user_id=? AND product_id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $product_id);
        mysqli_stmt_execute($stmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($existing) {
            $new_qty = min((int)$product['stok'], (int)$existing['jumlah'] + $jumlah);
            $stmt = mysqli_prepare($conn, "UPDATE cart SET jumlah=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ii", $new_qty, $existing['id']);
        } else {
            $new_qty = min((int)$product['stok'], $jumlah);
            $stmt = mysqli_prepare($conn, "INSERT INTO cart (user_id,product_id,jumlah) VALUES (?,?,?)");
            mysqli_stmt_bind_param($stmt, "iii", $user_id, $product_id, $new_qty);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        flash('success', 'Produk berhasil ditambahkan ke keranjang.');
        redirect('cart.php');
    }
}

if (isset($_POST['update_cart'])) {
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));

    $stmt = mysqli_prepare($conn, "SELECT cart.id, products.stok FROM cart INNER JOIN products ON cart.product_id=products.id WHERE cart.id=? AND cart.user_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row) {
        $jumlah = min($jumlah, (int)$row['stok']);
        $stmt = mysqli_prepare($conn, "UPDATE cart SET jumlah=? WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "iii", $jumlah, $cart_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    redirect('cart.php');
}

if (isset($_POST['hapus_cart'])) {
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    redirect('cart.php');
}

$flash_message = get_flash();

$stmt = mysqli_prepare($conn, "SELECT cart.id AS cart_id, cart.jumlah, products.*, categories.nama_kategori FROM cart INNER JOIN products ON cart.product_id=products.id INNER JOIN categories ON products.category_id=categories.id WHERE cart.user_id=? ORDER BY cart.id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0;

while ($row = mysqli_fetch_assoc($cart_result)) {
    $row['subtotal'] = $row['harga'] * $row['jumlah'];
    $total += $row['subtotal'];
    $items[] = $row;
}
mysqli_stmt_close($stmt);

$page_title = "Keranjang";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<section class="page-hero">
    <div class="container">
        <div class="kicker" style="color:#9fdcff">KERANJANG</div>
        <h1>Keranjang Belanja</h1>
        <p>Periksa produk dan jumlah sebelum checkout.</p>
    </div>
</section>

<section class="content">
    <div class="container">
        <?php if ($flash_message): ?>
            <div class="flash <?= e($flash_message['type']); ?>"><?= e($flash_message['message']); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="flash error"><?= e($error); ?></div>
        <?php endif; ?>

        <?php if (!$items): ?>
            <div class="empty">
                <h2>Keranjang masih kosong</h2>
                <p>Yuk pilih batik yang kamu suka.</p>
                <a class="btn btn-primary" href="index.php">Lihat Katalog</a>
            </div>
        <?php else: ?>
            <div class="panel">
                <div class="table-wrap">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="cart-product">
                                            <div class="cart-thumb">
                                                <?php if (!empty($item['gambar'])): ?>
                                                    <img src="../images/<?= e($item['gambar']); ?>" alt="">
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?= e($item['nama_produk']); ?></strong>
                                                <div class="text-muted"><?= e($item['nama_kategori']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= rupiah($item['harga']); ?></td>
                                    <td>
                                        <form method="POST" style="display:flex;gap:7px">
                                            <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id']; ?>">
                                            <input style="width:80px" type="number" name="jumlah" min="1" max="<?= (int)$item['stok']; ?>" value="<?= (int)$item['jumlah']; ?>">
                                            <button class="btn btn-outline btn-sm" name="update_cart">Update</button>
                                        </form>
                                    </td>
                                    <td><strong><?= rupiah($item['subtotal']); ?></strong></td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id']; ?>">
                                            <button class="btn btn-danger btn-sm" name="hapus_cart" onclick="return confirm('Hapus produk dari keranjang?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cart-summary">
                <div class="summary-box">
                    <div class="summary-line"><span>Subtotal</span><strong><?= rupiah($total); ?></strong></div>
                    <div class="summary-line"><span>Ongkir</span><span>Dihitung saat checkout</span></div>
                    <div class="summary-total"><span>Total sementara</span><span><?= rupiah($total); ?></span></div>
                    <a class="btn btn-primary btn-block mt-20" href="checkout.php">Lanjut Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include "../includes/footer.php"; ?>