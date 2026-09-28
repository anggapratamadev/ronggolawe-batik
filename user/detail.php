<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

start_session_once();

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT products.*, categories.nama_kategori FROM products INNER JOIN categories ON products.category_id=categories.id WHERE products.id=? AND products.status='aktif' AND categories.status='aktif' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    mysqli_stmt_close($stmt);
    redirect('index.php');
}

$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
$is_wish=false;
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user') {
    $wish_stmt=mysqli_prepare($conn, "SELECT id FROM wishlist WHERE user_id=? AND product_id=? LIMIT 1");
    mysqli_stmt_bind_param($wish_stmt,'ii',$_SESSION['user_id'],$id); mysqli_stmt_execute($wish_stmt); $is_wish=(bool)mysqli_fetch_assoc(mysqli_stmt_get_result($wish_stmt)); mysqli_stmt_close($wish_stmt);
}
$review_stmt=mysqli_prepare($conn, "SELECT r.rating,r.komentar,r.created_at,u.nama FROM reviews r INNER JOIN users u ON r.user_id=u.id WHERE r.product_id=? AND r.status='Tampil' ORDER BY r.created_at DESC LIMIT 8");
$rating_stmt=mysqli_prepare($conn, "SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS total_review FROM reviews WHERE product_id=? AND status='Tampil'"); mysqli_stmt_bind_param($rating_stmt,'i',$id); mysqli_stmt_execute($rating_stmt); $rating_summary=mysqli_fetch_assoc(mysqli_stmt_get_result($rating_stmt)); mysqli_stmt_close($rating_stmt);
$sales_stmt=mysqli_prepare($conn, "SELECT COALESCE(SUM(od.jumlah),0) AS total_terjual FROM order_details od INNER JOIN orders o ON o.id=od.order_id WHERE od.product_id=? AND o.status <> 'Dibatalkan'"); mysqli_stmt_bind_param($sales_stmt,'i',$id); mysqli_stmt_execute($sales_stmt); $sales_summary=mysqli_fetch_assoc(mysqli_stmt_get_result($sales_stmt)); mysqli_stmt_close($sales_stmt);
mysqli_stmt_bind_param($review_stmt,'i',$id); mysqli_stmt_execute($review_stmt); $reviews=mysqli_stmt_get_result($review_stmt);

$page_title = $product['nama_produk'];
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<section class="content">
    <div class="container">
        <p><a style="color:var(--blue);font-weight:800" href="index.php">← Kembali ke katalog</a></p>

        <div class="detail-grid">
            <div class="detail-image">
                <?php if (!empty($product['gambar'])): ?>
                    <img src="../images/<?= e($product['gambar']); ?>" alt="<?= e($product['nama_produk']); ?>">
                <?php else: ?>
                    <div class="image-placeholder">Ronggolawe Batik</div>
                <?php endif; ?>
            </div>

            <div class="detail-info">
                <div class="kicker"><?= e($product['nama_kategori']); ?></div>
                <h1><?= e($product['nama_produk']); ?></h1>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px"><div><div class="detail-price"><?= rupiah($product['harga']); ?></div><div class="text-muted" style="font-size:13px;margin-top:4px">Harga per produk</div></div><?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user'): ?><form method="POST" action="wishlist.php"><input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>"><input type="hidden" name="action" value="<?= $is_wish?'remove':'add'; ?>"><button class="wishlist-btn <?= $is_wish?'is-active':''; ?>" style="position:static;width:46px;height:46px" title="Wishlist"><?= $is_wish?'♥':'♡'; ?></button></form><?php endif; ?></div>

                <div class="detail-meta">
                    <span class="badge">SKU: <?= e($product['sku']); ?></span>
                    <span class="badge"><?= (int)$product['stok']; ?> stok</span>
                    <?php if ((int)$sales_summary['total_terjual'] > 0): ?><span class="badge badge-gold">Terjual <?= (int)$sales_summary['total_terjual']; ?></span><?php endif; ?>
                    <?php if ((int)$rating_summary['total_review'] > 0): ?><span class="badge badge-rating">★ <?= number_format((float)$rating_summary['avg_rating'],1,',','.'); ?> (<?= (int)$rating_summary['total_review']; ?> ulasan)</span><?php endif; ?>
                </div>

                <h3>Deskripsi</h3>
                <p class="text-muted">
                    <?= !empty($product['deskripsi']) ? nl2br(e($product['deskripsi'])) : 'Belum ada deskripsi produk.'; ?>
                </p>

                <?php if ((int)$product['stok'] > 0): ?>
                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user'): ?>
                        <form method="POST" action="checkout.php" class="quantity-row">
                            <input type="hidden" name="direct_product_id" value="<?= (int)$product['id']; ?>">
                            <div class="qty"><label>Jumlah</label><input type="number" name="direct_jumlah" value="1" min="1" max="<?= (int)$product['stok']; ?>" required></div>
                            <button class="btn btn-primary" type="submit" name="beli_sekarang">Beli Sekarang</button>
                        </form>
                        <div style="margin-top:10px"><form method="POST" action="cart.php" class="quantity-row"><input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>"><input type="hidden" name="jumlah" value="1"><button class="btn btn-outline" type="submit" name="tambah_keranjang">+ Tambah ke Keranjang</button></form></div>
                    <?php else: ?>
                        <div class="flash warning">Produk siap dibeli. <a href="../login.php?redirect=<?= urlencode('user/detail.php?id=' . (int)$product['id']); ?>" style="font-weight:900;color:var(--blue)">Masuk atau daftar</a> untuk melanjutkan pembelian.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="flash error">Produk sedang habis.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

        <div class="panel" style="margin-top:25px">
            <div class="section-head" style="margin-bottom:16px"><div><div class="kicker">ULASAN PELANGGAN</div><h2>Pengalaman pembeli</h2><p>Review hanya dapat diberikan setelah pesanan selesai.</p></div></div>
            <?php if (mysqli_num_rows($reviews)===0): ?><div class="empty"><p>Belum ada review untuk produk ini.</p></div><?php else: ?>
                <div style="display:grid;gap:12px">
                <?php while($r=mysqli_fetch_assoc($reviews)): ?>
                    <div style="padding:15px;border:1px solid var(--line);border-radius:14px;background:#fbfdff"><div style="display:flex;justify-content:space-between;gap:12px"><strong><?=e($r['nama']);?></strong><span style="color:#b88616;font-weight:900"><?=str_repeat('★',(int)$r['rating']);?></span></div><p style="margin:7px 0 0;color:#536179"><?=nl2br(e($r['komentar']));?></p></div>
                <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

<?php mysqli_stmt_close($review_stmt); include "../includes/footer.php"; ?>