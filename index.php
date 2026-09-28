<?php
require_once "config/database.php";
require_once "includes/functions.php";

start_session_once();

$page_title = "Beranda";
$asset_prefix = "";
$home_link = "index.php";

$category_result = mysqli_query($conn, "SELECT id, nama_kategori FROM categories WHERE status='aktif' ORDER BY nama_kategori LIMIT 6");
$product_result = mysqli_query($conn, "SELECT products.*, categories.nama_kategori, COALESCE(sales.total_terjual,0) AS total_terjual, COALESCE(rating_data.avg_rating,0) AS avg_rating, COALESCE(rating_data.total_review,0) AS total_review FROM products INNER JOIN categories ON products.category_id=categories.id LEFT JOIN (SELECT od.product_id,SUM(od.jumlah) AS total_terjual FROM order_details od INNER JOIN orders o ON o.id=od.order_id WHERE o.status <> 'Dibatalkan' GROUP BY od.product_id) sales ON sales.product_id=products.id LEFT JOIN (SELECT product_id,AVG(rating) AS avg_rating,COUNT(*) AS total_review FROM reviews WHERE status='Tampil' GROUP BY product_id) rating_data ON rating_data.product_id=products.id WHERE products.status='aktif' AND categories.status='aktif' ORDER BY products.id DESC LIMIT 12");
$best_result = mysqli_query($conn, "SELECT products.*, categories.nama_kategori, COALESCE(sales.total_terjual,0) AS total_terjual, COALESCE(rating_data.avg_rating,0) AS avg_rating, COALESCE(rating_data.total_review,0) AS total_review FROM products INNER JOIN categories ON products.category_id=categories.id LEFT JOIN (SELECT od.product_id,SUM(od.jumlah) AS total_terjual FROM order_details od INNER JOIN orders o ON o.id=od.order_id WHERE o.status <> 'Dibatalkan' GROUP BY od.product_id) sales ON sales.product_id=products.id LEFT JOIN (SELECT product_id,AVG(rating) AS avg_rating,COUNT(*) AS total_review FROM reviews WHERE status='Tampil' GROUP BY product_id) rating_data ON rating_data.product_id=products.id WHERE products.status='aktif' AND categories.status='aktif' ORDER BY total_terjual DESC, products.id DESC LIMIT 4");

include "includes/header.php";
?>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="eyebrow">BATIK KHAS KABUPATEN TUBAN</span>
            <h1>Warisan Tuban,<br><span>dibawa lebih dekat.</span></h1>
            <p>Temukan batik Gedog, batik tulis, batik cap, kemeja, selendang, dan sarung khas Tuban dalam satu tempat.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#produk">Jelajahi Koleksi</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-outline" href="register.php">Buat Akun</a>
                <?php endif; ?>
                <div class="hero-copy-note"><span class="hero-photo-dot"></span><span><b>Koleksi lokal</b> • dikurasi untuk pecinta batik Tuban</span></div>
            </div>
        </div>
        <div class="hero-art">
            <div class="hero-photo-wrap">
                <img class="hero-photo" src="images/batik-gedog-tuban.jpg" alt="Batik Gedog khas Tuban">
                <div class="hero-photo-label">
                    <span class="hero-photo-dot"></span>
                    Batik Gedog • Tuban
                </div>
            </div>
        </div>
    </div>
</section>

<section class="signature-strip">
    <div class="container">
        <div class="signature-inner">
            <div class="signature-item"><div class="signature-icon">01</div><div><strong>Motif Tuban</strong><span>Karakter lokal dalam setiap lembar</span></div></div>
            <div class="signature-item"><div class="signature-icon">02</div><div><strong>Pilihan Lengkap</strong><span>Batik, kemeja, sarung & selendang</span></div></div>
            <div class="signature-item"><div class="signature-icon">03</div><div><strong>Belanja Terarah</strong><span>Alamat, pembayaran & pesanan lebih rapi</span></div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <div class="kicker">Mengapa Ronggolawe Batik</div>
                <h2>Tradisional, rapi, dan mudah dibeli</h2>
                <p>Pengalaman belanja batik Tuban yang sederhana dari katalog hingga pesanan.</p>
            </div>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">01</div>
                <h3>Motif Khas Tuban</h3>
                <p>Koleksi berfokus pada produk batik yang membawa identitas budaya Kabupaten Tuban.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">02</div>
                <h3>Belanja Terstruktur</h3>
                <p>Lihat produk tanpa login. Akun diperlukan ketika ingin masuk ke proses pembelian.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">03</div>
                <h3>Pesanan Terpantau</h3>
                <p>Setelah checkout, status pesanan dan pembayaran dapat dipantau dari akun pengguna.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="produk" style="padding-top:15px">
    <div class="container">
        <div class="section-head">
            <div>
                <div class="kicker">Koleksi</div>
                <h2>Produk pilihan</h2>
                <p>Pilih produk yang tersedia dan lihat detailnya.</p>
            </div>
        </div>

        <div class="product-grid">
            <?php if (mysqli_num_rows($product_result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($product_result)): ?>
                    <article class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['gambar'])): ?>
                                <img src="images/<?= e($product['gambar']); ?>" alt="<?= e($product['nama_produk']); ?>">
                            <?php else: ?>
                                <div class="image-placeholder">Ronggolawe Batik</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <div class="product-category"><?= e($product['nama_kategori']); ?></div>
                            <div class="product-title"><?= e($product['nama_produk']); ?></div>
                            <div class="product-price"><?= rupiah($product['harga']); ?></div>
                            <div class="product-meta-row"><span class="product-stock"><?= (int)$product['stok']; ?> stok</span><?php if ((int)$product['total_review'] > 0): ?><span class="product-rating">★ <?= number_format((float)$product['avg_rating'],1,',','.'); ?></span><?php endif; ?></div><div class="product-sold">Terjual <?= (int)$product['total_terjual']; ?></div>
                            <a class="btn btn-primary btn-block" href="user/detail.php?id=<?= (int)$product['id']; ?>">Lihat Detail</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty" style="grid-column:1/-1">Belum ada produk aktif.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:10px">
    <div class="container">
        <div class="section-head">
            <div>
                <div class="kicker">Paling Diminati</div>
                <h2>Produk terlaris</h2>
                <p>Produk yang paling sering dibeli pelanggan Ronggolawe Batik.</p>
            </div>
            <a class="btn btn-outline btn-sm" href="user/index.php?sort=terlaris">Lihat semua</a>
        </div>
        <div class="product-grid best-grid">
            <?php while ($best = mysqli_fetch_assoc($best_result)): ?>
                <article class="product-card compact-product">
                    <div class="product-image">
                        <?php if (!empty($best['gambar'])): ?><img src="images/<?= e($best['gambar']); ?>" alt="<?= e($best['nama_produk']); ?>"><?php else: ?><div class="image-placeholder">Ronggolawe Batik</div><?php endif; ?>
                    </div>
                    <div class="product-body">
                        <div class="product-category"><?= e($best['nama_kategori']); ?></div>
                        <div class="product-title"><?= e($best['nama_produk']); ?></div>
                        <div class="product-price"><?= rupiah($best['harga']); ?></div>
                        <div class="product-meta-row"><span class="product-sold">Terjual <?= (int)$best['total_terjual']; ?></span><?php if ((int)$best['total_review'] > 0): ?><span class="product-rating">★ <?= number_format((float)$best['avg_rating'],1,',','.'); ?></span><?php endif; ?></div>
                        <a class="btn btn-primary btn-block" href="user/detail.php?id=<?= (int)$best['id']; ?>">Lihat Detail</a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:10px">
    <div class="container">
        <div class="card" style="padding:30px;background:linear-gradient(135deg,#eaf4ff,#fff)">
            <div class="section-head" style="margin-bottom:0">
                <div>
                    <div class="kicker">Kategori</div>
                    <h2>Temukan jenis batik favoritmu</h2>
                </div>
            </div>
            <div class="detail-meta" style="margin-bottom:0">
                <?php while ($category = mysqli_fetch_assoc($category_result)): ?>
                    <span class="badge"><?= e($category['nama_kategori']); ?></span>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>