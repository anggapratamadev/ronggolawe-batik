<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('admin');

$error = "";
$success = "";

if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') {
        $error = "Nama kategori wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO categories (nama_kategori,deskripsi,status) VALUES (?,?, 'aktif')");
        mysqli_stmt_bind_param($stmt, "ss", $nama, $deskripsi);
        try {
            mysqli_stmt_execute($stmt);
            $success = "Kategori berhasil ditambahkan.";
        } catch (mysqli_sql_exception $e) {
            $error = $e->getCode() === 1062 ? "Nama kategori sudah digunakan." : "Kategori gagal ditambahkan.";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = $_POST['status'] === 'nonaktif' ? 'nonaktif' : 'aktif';

    if ($nama === '') {
        $error = "Nama kategori wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE categories SET nama_kategori=?, deskripsi=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $nama, $deskripsi, $status, $id);
        try {
            mysqli_stmt_execute($stmt);
            $success = "Kategori berhasil diperbarui.";
        } catch (mysqli_sql_exception $e) {
            $error = $e->getCode() === 1062 ? "Nama kategori sudah digunakan." : "Kategori gagal diperbarui.";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = mysqli_prepare($conn, "UPDATE categories SET status=IF(status='aktif','nonaktif','aktif') WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    redirect('kategori.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$list = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");

$page_title = "Kelola Kategori";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-title">ADMIN PANEL</div>
        <a href="index.php">Dashboard</a>
        <a class="active" href="kategori.php">Kategori</a>
        <a href="produk.php">Produk</a>
        <a href="pengguna.php">Pengguna</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="laporan.php">Laporan</a>
        <a href="../index.php">Lihat Website</a>
        <a href="../logout.php">Keluar</a>
    </aside>

    <section class="admin-main">
        <div class="admin-head"><div><div class="kicker">MASTER DATA</div><h1>Kategori</h1></div></div>

        <?php if ($error): ?><div class="flash error"><?= e($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="flash success"><?= e($success); ?></div><?php endif; ?>

        <div class="admin-card">
            <h2><?= $edit ? 'Edit Kategori' : 'Tambah Kategori'; ?></h2>
            <form method="POST">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id']; ?>"><?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama_kategori" value="<?= e($edit['nama_kategori'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" <?= !$edit ? 'disabled' : ''; ?>>
                            <option value="aktif" <?= ($edit['status'] ?? '') === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?= ($edit['status'] ?? '') === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi"><?= e($edit['deskripsi'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" name="<?= $edit ? 'edit' : 'tambah'; ?>">
                    <?= $edit ? 'Simpan Perubahan' : 'Tambah Kategori'; ?>
                </button>
                <?php if ($edit): ?><a class="btn btn-outline" href="kategori.php">Batal</a><?php endif; ?>
            </form>
        </div>

        <div class="admin-card">
            <h2>Daftar Kategori</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>ID</th><th>Nama</th><th>Deskripsi</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($list)): ?>
                        <tr>
                            <td><?= (int)$row['id']; ?></td>
                            <td><strong><?= e($row['nama_kategori']); ?></strong></td>
                            <td><?= e($row['deskripsi'] ?? '-'); ?></td>
                            <td><span class="status <?= $row['status']==='aktif'?'success':'danger'; ?>"><?= e($row['status']); ?></span></td>
                            <td class="actions">
                                <a class="btn btn-outline btn-sm" href="kategori.php?edit=<?= (int)$row['id']; ?>">Edit</a>
                                <a class="btn btn-warning btn-sm" href="kategori.php?toggle=<?= (int)$row['id']; ?>" onclick="return confirm('Ubah status kategori?')">
                                    <?= $row['status']==='aktif'?'Nonaktifkan':'Aktifkan'; ?>
                                </a>
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