<?php
if (!isset($page_title)) {
    $page_title = 'Ronggolawe Batik';
}

start_session_once();

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = $is_logged_in && ($_SESSION['role'] ?? '') === 'admin';
$is_user = $is_logged_in && ($_SESSION['role'] ?? '') === 'user';
$user_name = $_SESSION['nama'] ?? '';
$cart_total = ($is_user && isset($conn)) ? cart_count($conn, (int)$_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title); ?> | Ronggolawe Batik</title>
    <meta name="description" content="Ronggolawe Batik - E-Commerce Produk Batik Khas Kabupaten Tuban">
    <link rel="stylesheet" href="<?= $asset_prefix ?? ''; ?>css/style.css">
</head>
<body>
<div class="site-shell">
<div class="announcement"><div class="container"><span class="announcement-dot"></span><span>Koleksi batik khas Tuban • Belanja lebih mudah • Melestarikan warisan lokal</span></div></div>
<?php
$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<header class="topbar">
    <div class="container nav-wrap">
        <a class="brand" href="<?= $home_link ?? 'index.php'; ?>" aria-label="Ronggolawe Batik - Beranda">
            <span class="brand-mark">RB</span>
            <span class="brand-copy">
                <strong>Ronggolawe</strong>
                <small>Batik Tuban</small>
            </span>
        </a>

        <nav class="main-nav" id="mainNav" aria-label="Navigasi utama">
            <a class="nav-link" href="<?= $home_link ?? 'index.php'; ?>">Beranda</a>

            <a class="nav-link" href="<?= $asset_prefix ?? ''; ?>user/index.php">Katalog</a>
            <?php if ($is_user): ?>
                <a class="nav-link" href="<?= $asset_prefix ?? ''; ?>user/pesanan.php">Pesanan</a>
                <a class="nav-link" href="<?= $asset_prefix ?? ''; ?>user/alamat.php">Alamat</a>
                <a class="nav-link" href="<?= $asset_prefix ?? ''; ?>user/profil.php">Profil</a>
                <a class="nav-link" href="<?= $asset_prefix ?? ''; ?>user/wishlist.php">Wishlist</a>
                <a class="nav-link nav-cart" href="<?= $asset_prefix ?? ''; ?>user/cart.php">
                    Keranjang <span><?= $cart_total; ?></span>
                </a>
            <?php endif; ?>

            <?php if ($is_admin): ?>
                <a class="nav-link" href="<?= $asset_prefix ?? ''; ?>admin/index.php">Dashboard Admin</a>
            <?php endif; ?>

            <div class="mobile-nav-actions">
                <?php if ($is_logged_in): ?>
                    <a class="mobile-profile" href="<?= $is_user ? (($asset_prefix ?? '') . 'user/profil.php') : (($asset_prefix ?? '') . 'admin/index.php'); ?>">
                        <span class="mobile-avatar"><?= e(strtoupper(substr($user_name, 0, 1))); ?></span>
                        <span><strong><?= e($user_name); ?></strong><small><?= $is_user ? 'Akun pelanggan' : 'Administrator'; ?></small></span>
                    </a>
                    <a class="btn btn-outline btn-block" href="<?= $asset_prefix ?? ''; ?>logout.php">Keluar</a>
                <?php else: ?>
                    <div class="mobile-auth-row">
                        <a class="btn btn-outline" href="<?= $asset_prefix ?? ''; ?>login.php">Masuk</a>
                        <a class="btn btn-primary" href="<?= $asset_prefix ?? ''; ?>register.php">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>

        <div class="nav-actions">
            <?php if ($is_logged_in): ?>
                <a class="user-chip user-chip-link" href="<?= $is_user ? (($asset_prefix ?? '') . 'user/profil.php') : (($asset_prefix ?? '') . 'admin/index.php'); ?>">
                    <span class="nav-avatar"><?= e(strtoupper(substr($user_name, 0, 1))); ?></span>
                    <span class="nav-user-label">Halo, <?= e($user_name); ?></span>
                </a>
                <a class="btn btn-outline btn-sm nav-logout" href="<?= $asset_prefix ?? ''; ?>logout.php">Keluar</a>
            <?php else: ?>
                <a class="btn btn-outline btn-sm" href="<?= $asset_prefix ?? ''; ?>login.php">Masuk</a>
                <a class="btn btn-primary btn-sm" href="<?= $asset_prefix ?? ''; ?>register.php">Daftar</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" type="button" aria-label="Buka menu" aria-controls="mainNav" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
    <div class="nav-backdrop" data-nav-close></div>
</header>

<main>
