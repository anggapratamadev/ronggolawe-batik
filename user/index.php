<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

start_session_once();

$page_title = "Katalog Produk";
$asset_prefix = "../";
$home_link = "../index.php";

$category_id = (int)($_GET['category'] ?? 0);
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'terbaru';
$sort_sql = match ($sort) {
    'harga_terendah' => 'products.harga ASC, products.id DESC',
    'harga_tertinggi' => 'products.harga DESC, products.id DESC',
    'nama' => 'products.nama_produk ASC',
    'terlaris' => 'COALESCE(sales.total_terjual, 0) DESC, products.id DESC',
    'rating' => 'COALESCE(rating_data.avg_rating, 0) DESC, COALESCE(rating_data.total_review, 0) DESC, products.id DESC',
    default => 'products.id DESC'
};

$sql = "SELECT products.*, categories.nama_kategori,
               COALESCE(sales.total_terjual, 0) AS total_terjual,
               COALESCE(rating_data.avg_rating, 0) AS avg_rating,
               COALESCE(rating_data.total_review, 0) AS total_review
        FROM products
        INNER JOIN categories ON products.category_id=categories.id
        LEFT JOIN (
            SELECT od.product_id, SUM(od.jumlah) AS total_terjual
            FROM order_details od
            INNER JOIN orders o ON o.id=od.order_id
            WHERE o.status <> 'Dibatalkan'
            GROUP BY od.product_id
        ) sales ON sales.product_id=products.id
        LEFT JOIN (
            SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS total_review
            FROM reviews
            WHERE status='Tampil'
            GROUP BY product_id
        ) rating_data ON rating_data.product_id=products.id
        WHERE products.status='aktif' AND categories.status='aktif'";
$params = [];
$types = "";

if ($category_id > 0) {
    $sql .= " AND products.category_id=?";
    $params[] = $category_id;
    $types .= "i";
}

if ($search !== '') {
    $sql .= " AND (products.nama_produk LIKE ? OR products.sku LIKE ?)";
    $like = "%".$search."%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$sql .= " ORDER BY " . $sort_sql;

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result_produk = mysqli_stmt_get_result($stmt);

$categories = mysqli_query($conn, "SELECT id,nama_kategori FROM categories WHERE status='aktif' ORDER BY nama_kategori");
$wish_ids=[];
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user') {
    $wish_stmt=mysqli_prepare($conn, "SELECT product_id FROM wishlist WHERE user_id=?");
    mysqli_stmt_bind_param($wish_stmt,'i',$_SESSION['user_id']); mysqli_stmt_execute($wish_stmt); $wish_res=mysqli_stmt_get_result($wish_stmt);
    while($w=mysqli_fetch_assoc($wish_res)) $wish_ids[(int)$w['product_id']]=true; mysqli_stmt_close($wish_stmt);
}
include "../includes/header.php";
?>

<section class="page-hero">
    <div class="container">
        <div class="kicker" style="color:#9fdcff">KATALOG</div>
        <h1>Koleksi Ronggolawe Batik</h1>
        <p>Pilih produk batik khas Tuban sesuai kebutuhanmu.</p>
    </div>
</section>

<section class="content">
    <div class="container">
        <form class="panel" method="GET" style="margin-bottom:22px">
            <div class="catalog-toolbar">
                <div class="form-group">
                    <label>Cari Produk</label>
                    <input type="text" name="q" value="<?= e($search); ?>" placeholder="Nama produk atau SKU">
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category">
                        <option value="0">Semua kategori</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= (int)$cat['id']; ?>" <?= $category_id === (int)$cat['id'] ? 'selected' : ''; ?>>
                                <?= e($cat['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Urutkan</label>
                    <select name="sort">
                        <option value="terbaru" <?= $sort==='terbaru'?'selected':''; ?>>Terbaru</option>
                        <option value="harga_terendah" <?= $sort==='harga_terendah'?'selected':''; ?>>Harga terendah</option>
                        <option value="harga_tertinggi" <?= $sort==='harga_tertinggi'?'selected':''; ?>>Harga tertinggi</option>
                        <option value="nama" <?= $sort==='nama'?'selected':''; ?>>Nama A–Z</option>
                        <option value="terlaris" <?= $sort==='terlaris'?'selected':''; ?>>Paling Terlaris</option>
                        <option value="rating" <?= $sort==='rating'?'selected':''; ?>>Rating Tertinggi</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Terapkan</button>
                <a class="btn btn-outline" href="index.php">Reset</a>
            </div>
        </form>

        <div class="product-grid">
            <?php if (mysqli_num_rows($result_produk) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($result_produk)): ?>
                    <article class="product-card">
                        <div class="product-image">
                            <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user'): ?>
                            <form method="POST" action="wishlist.php" class="wishlist-form">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                <input type="hidden" name="action" value="<?= isset($wish_ids[(int)$product['id']]) ? 'remove' : 'add'; ?>">
                                <button class="wishlist-btn <?= isset($wish_ids[(int)$product['id']]) ? 'is-active' : ''; ?>" title="<?= isset($wish_ids[(int)$product['id']]) ? 'Hapus wishlist' : 'Tambah wishlist'; ?>"><?= isset($wish_ids[(int)$product['id']]) ? '♥' : '♡'; ?></button>
                            </form>
                            <?php endif; ?>
                            <?php if (!empty($product['gambar'])): ?>
                                <img src="../images/<?= e($product['gambar']); ?>" alt="<?= e($product['nama_produk']); ?>">
                            <?php else: ?>
                                <div class="image-placeholder">Ronggolawe Batik</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <div class="product-category"><?= e($product['nama_kategori']); ?></div>
                            <div class="product-title"><?= e($product['nama_produk']); ?></div>
                            <div class="product-price"><?= rupiah($product['harga']); ?></div>
                            <div class="product-meta-row">
                                <span class="product-stock <?= (int)$product['stok'] <= 0 ? 'is-empty' : ''; ?>"><?= (int)$product['stok']; ?> stok</span>
                                <?php if ((int)$product['total_review'] > 0): ?><span class="product-rating">★ <?= number_format((float)$product['avg_rating'], 1, ',', '.'); ?> <small>(<?= (int)$product['total_review']; ?>)</small></span><?php else: ?><span class="product-rating muted">Belum ada ulasan</span><?php endif; ?>
                            </div>
                            <div class="product-sold">Terjual <?= (int)$product['total_terjual']; ?></div>
                            <a class="btn btn-primary btn-block" href="detail.php?id=<?= (int)$product['id']; ?>">Lihat Detail</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty" style="grid-column:1/-1">Produk tidak ditemukan. Coba kata kunci atau kategori lain.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php mysqli_stmt_close($stmt); include "../includes/footer.php"; ?>