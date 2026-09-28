<?php
require_once "../config/database.php";
require_once "../includes/functions.php";
require_login('user');

$user_id=(int)$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $product_id=(int)($_POST['product_id']??0);
    $action=$_POST['action']??'';
    if ($product_id>0 && in_array($action,['add','remove'],true)) {
        if ($action==='add') {
            $stmt=mysqli_prepare($conn,"INSERT IGNORE INTO wishlist (user_id,product_id) VALUES (?,?)");
            mysqli_stmt_bind_param($stmt,'ii',$user_id,$product_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            flash('success','Produk ditambahkan ke wishlist.');
        } else {
            $stmt=mysqli_prepare($conn,"DELETE FROM wishlist WHERE user_id=? AND product_id=?");
            mysqli_stmt_bind_param($stmt,'ii',$user_id,$product_id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            flash('success','Produk dihapus dari wishlist.');
        }
    }
    redirect('wishlist.php');
}

$stmt=mysqli_prepare($conn,"SELECT p.*, c.nama_kategori FROM wishlist w INNER JOIN products p ON w.product_id=p.id INNER JOIN categories c ON p.category_id=c.id WHERE w.user_id=? ORDER BY w.id DESC");
mysqli_stmt_bind_param($stmt,'i',$user_id); mysqli_stmt_execute($stmt); $items=mysqli_stmt_get_result($stmt);
$page_title='Wishlist'; $asset_prefix='../'; $home_link='../index.php'; include '../includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="kicker" style="color:#9fdcff">KOLEKSI PRIBADI</div><h1>Wishlist</h1><p>Simpan produk yang kamu suka agar mudah ditemukan lagi.</p></div></section>
<section class="content"><div class="container">
<?php if($flash=get_flash()): ?><div class="flash <?=e($flash['type']);?>"><?=e($flash['message']);?></div><?php endif; ?>
<div class="product-grid">
<?php if(mysqli_num_rows($items)===0): ?><div class="empty" style="grid-column:1/-1"><h2>Wishlist masih kosong</h2><p class="text-muted">Simpan produk favoritmu dari katalog.</p><a class="btn btn-primary" href="index.php">Jelajahi Katalog</a></div>
<?php else: while($p=mysqli_fetch_assoc($items)): ?>
<article class="product-card"><div class="product-image"><form method="POST"><input type="hidden" name="product_id" value="<?= (int)$p['id'];?>"><input type="hidden" name="action" value="remove"><button class="wishlist-btn is-active" title="Hapus dari wishlist">♥</button></form><?php if($p['gambar']): ?><img src="../images/<?=e($p['gambar']);?>" alt="<?=e($p['nama_produk']);?>"><?php endif;?></div><div class="product-body"><div class="product-category"><?=e($p['nama_kategori']);?></div><div class="product-title"><?=e($p['nama_produk']);?></div><div class="product-price"><?=rupiah($p['harga']);?></div><div class="product-stock <?=((int)$p['stok']<1)?'stock-out':'';?>"><?=((int)$p['stok']>0)?((int)$p['stok'].' stok tersedia'):'Stok habis';?></div><a class="btn btn-primary btn-block" href="detail.php?id=<?= (int)$p['id'];?>">Lihat Detail</a></div></article>
<?php endwhile; endif; ?></div></div></section>
<?php mysqli_stmt_close($stmt); include '../includes/footer.php'; ?>
