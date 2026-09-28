<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$order_id = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$error = "";

$stmt = mysqli_prepare($conn, "SELECT od.product_id, od.nama_produk FROM order_details od INNER JOIN orders o ON od.order_id=o.id WHERE od.order_id=? AND od.product_id=? AND o.user_id=? AND o.status='Selesai' LIMIT 1");
mysqli_stmt_bind_param($stmt, "iii", $order_id, $product_id, $user_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) redirect('pesanan.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Rating harus 1 sampai 5.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO reviews (user_id, product_id, rating, komentar, status) VALUES (?,?,?,?, 'Tampil') ON DUPLICATE KEY UPDATE rating=VALUES(rating), komentar=VALUES(komentar), status='Tampil'");
        mysqli_stmt_bind_param($stmt, "iiis", $user_id, $product_id, $rating, $komentar);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        flash('success', 'Review berhasil disimpan.');
        redirect('order_detail.php?id=' . $order_id);
    }
}

$page_title = "Review Produk";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<section class="content">
    <div class="container">
        <div class="form-card panel">
            <div class="kicker">REVIEW</div>
            <h1><?= e($product['nama_produk']); ?></h1>
            <?php if ($error): ?><div class="flash error"><?= e($error); ?></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="product_id" value="<?= $product_id; ?>">
                <input type="hidden" name="order_id" value="<?= $order_id; ?>">

                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" required>
                        <option value="">Pilih rating</option>
                        <option value="5">5 - Sangat puas</option>
                        <option value="4">4 - Puas</option>
                        <option value="3">3 - Cukup</option>
                        <option value="2">2 - Kurang</option>
                        <option value="1">1 - Tidak puas</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Komentar</label>
                    <textarea name="komentar" placeholder="Ceritakan pengalamanmu..."></textarea>
                </div>

                <button class="btn btn-primary" type="submit">Simpan Review</button>
            </form>
        </div>
    </div>
</section>

<?php include "../includes/footer.php"; ?>