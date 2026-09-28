<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('admin');

$error = "";
$success = "";

function unique_slug($conn, $name, $exclude_id = 0)
{
    $base = slugify($name);
    if ($base === '') $base = 'produk';

    $slug = $base;
    $counter = 1;

    while (true) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE slug=? AND id<>? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $slug, $exclude_id);
        mysqli_stmt_execute($stmt);
        $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$found) return $slug;
        $counter++;
        $slug = $base . '-' . $counter;
    }
}

function upload_image($field, &$error)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload gambar gagal.";
        return null;
    }

    if ($_FILES[$field]['size'] > 4 * 1024 * 1024) {
        $error = "Ukuran gambar maksimal 4 MB.";
        return null;
    }

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
        $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP.";
        return null;
    }

    $name = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], "../images/" . $name)) {
        $error = "Gambar tidak dapat disimpan.";
        return null;
    }

    return $name;
}

if (isset($_POST['tambah'])) {
    $sku = trim($_POST['sku'] ?? '');
    $nama = trim($_POST['nama_produk'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = max(0, (int)($_POST['stok'] ?? 0));
    $slug = unique_slug($conn, $nama);

    if ($sku === '' || $nama === '' || $category_id <= 0) {
        $error = "SKU, nama produk, dan kategori wajib diisi.";
    } elseif ($harga < 0) {
        $error = "Harga tidak boleh negatif.";
    } else {
        $gambar = upload_image('gambar', $error);

        if ($error === '') {
            $stmt = mysqli_prepare($conn, "INSERT INTO products (category_id,sku,nama_produk,slug,deskripsi,harga,stok,gambar,status) VALUES (?,?,?,?,?,?,?,?,'aktif')");
            mysqli_stmt_bind_param($stmt, "issssdis", $category_id, $sku, $nama, $slug, $deskripsi, $harga, $stok, $gambar);

            try {
                mysqli_stmt_execute($stmt);
                $success = "Produk berhasil ditambahkan.";
            } catch (mysqli_sql_exception $e) {
                $error = $e->getCode() === 1062 ? "SKU sudah digunakan." : "Produk gagal ditambahkan.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $sku = trim($_POST['sku'] ?? '');
    $nama = trim($_POST['nama_produk'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = max(0, (int)($_POST['stok'] ?? 0));
    $status = $_POST['status'] === 'nonaktif' ? 'nonaktif' : 'aktif';
    $slug = unique_slug($conn, $nama, $id);

    if ($sku === '' || $nama === '' || $category_id <= 0) {
        $error = "SKU, nama produk, dan kategori wajib diisi.";
    } else {
        $gambar = upload_image('gambar', $error);

        if ($error === '') {
            if ($gambar) {
                $stmt = mysqli_prepare($conn, "UPDATE products SET category_id=?,sku=?,nama_produk=?,slug=?,deskripsi=?,harga=?,stok=?,gambar=?,status=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "issssdissi", $category_id, $sku, $nama, $slug, $deskripsi, $harga, $stok, $gambar, $status, $id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE products SET category_id=?,sku=?,nama_produk=?,slug=?,deskripsi=?,harga=?,stok=?,status=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "issssdisi", $category_id, $sku, $nama, $slug, $deskripsi, $harga, $stok, $status, $id);
            }

            try {
                mysqli_stmt_execute($stmt);
                $success = "Produk berhasil diperbarui.";
            } catch (mysqli_sql_exception $e) {
                $error = $e->getCode() === 1062 ? "SKU sudah digunakan." : "Produk gagal diperbarui.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = mysqli_prepare($conn, "UPDATE products SET status=IF(status='aktif','nonaktif','aktif') WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    redirect('produk.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$categories = mysqli_query($conn, "SELECT id,nama_kategori FROM categories WHERE status='aktif' ORDER BY nama_kategori");
$products = mysqli_query($conn, "SELECT products.*,categories.nama_kategori FROM products INNER JOIN categories ON products.category_id=categories.id ORDER BY products.id DESC");

$page_title = "Kelola Produk";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-title">ADMIN PANEL</div>
        <a href="index.php">Dashboard</a>
        <a href="kategori.php">Kategori</a>
        <a class="active" href="produk.php">Produk</a>
        <a href="pengguna.php">Pengguna</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="laporan.php">Laporan</a>
        <a href="../index.php">Lihat Website</a>
        <a href="../logout.php">Keluar</a>
    </aside>

    <section class="admin-main">
        <div class="admin-head"><div><div class="kicker">MASTER DATA</div><h1>Produk</h1></div></div>

        <?php if ($error): ?><div class="flash error"><?= e($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="flash success"><?= e($success); ?></div><?php endif; ?>

        <div class="admin-card">
            <h2><?= $edit ? 'Edit Produk' : 'Tambah Produk'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id']; ?>"><?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" name="sku" value="<?= e($edit['sku'] ?? ''); ?>" placeholder="RB-GDG-001" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_produk" value="<?= e($edit['nama_produk'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category_id" required>
                            <option value="">Pilih kategori</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?= (int)$cat['id']; ?>" <?= isset($edit['category_id']) && (int)$edit['category_id']===(int)$cat['id']?'selected':''; ?>>
                                    <?= e($cat['nama_kategori']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" <?= !$edit?'disabled':''; ?>>
                            <option value="aktif" <?= ($edit['status'] ?? '')==='aktif'?'selected':''; ?>>Aktif</option>
                            <option value="nonaktif" <?= ($edit['status'] ?? '')==='nonaktif'?'selected':''; ?>>Nonaktif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Harga</label>
                        <input type="number" name="harga" min="0" step="0.01" value="<?= e($edit['harga'] ?? '0'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stok" min="0" value="<?= e($edit['stok'] ?? '0'); ?>" required>
                    </div>
                    <div class="form-group full">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi"><?= e($edit['deskripsi'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Gambar Produk</label>
                        <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
                        <small class="text-muted">Maksimal 4 MB. JPG, JPEG, PNG, WEBP.</small>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" name="<?= $edit?'edit':'tambah'; ?>">
                    <?= $edit?'Simpan Perubahan':'Tambah Produk'; ?>
                </button>
                <?php if ($edit): ?><a class="btn btn-outline" href="produk.php">Batal</a><?php endif; ?>
            </form>
        </div>

        <div class="admin-card">
            <h2>Daftar Produk</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Gambar</th><th>SKU</th><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($products)): ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['gambar'])): ?>
                                    <img class="thumb-sm" src="../images/<?= e($row['gambar']); ?>" alt="">
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?= e($row['sku']); ?></td>
                            <td><strong><?= e($row['nama_produk']); ?></strong></td>
                            <td><?= e($row['nama_kategori']); ?></td>
                            <td><?= rupiah($row['harga']); ?></td>
                            <td><?= (int)$row['stok']; ?></td>
                            <td><span class="status <?= $row['status']==='aktif'?'success':'danger'; ?>"><?= e($row['status']); ?></span></td>
                            <td class="actions">
                                <a class="btn btn-outline btn-sm" href="produk.php?edit=<?= (int)$row['id']; ?>">Edit</a>
                                <a class="btn btn-warning btn-sm" href="produk.php?toggle=<?= (int)$row['id']; ?>" onclick="return confirm('Ubah status produk?')"><?= $row['status']==='aktif'?'Nonaktifkan':'Aktifkan'; ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include "../includes/footer.php"; ?>