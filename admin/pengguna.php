<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('admin');

$error = "";
$success = "";

if (isset($_POST['toggle_status'])) {
    $id = (int)($_POST['id'] ?? 0);

    if ($id === (int)$_SESSION['user_id']) {
        $error = "Akun admin yang sedang digunakan tidak dapat dinonaktifkan.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET status=IF(status='aktif','nonaktif','aktif') WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $success = mysqli_stmt_affected_rows($stmt) > 0 ? "Status pengguna berhasil diperbarui." : "Pengguna tidak ditemukan.";
        mysqli_stmt_close($stmt);
    }
}

$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';

$sql = "SELECT id,nama,email,role,status,created_at FROM users WHERE 1=1";
$types = '';
$params = [];

if ($search !== '') {
    $sql .= " AND (nama LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

if ($role === 'admin' || $role === 'user') {
    $sql .= " AND role=?";
    $types .= 's';
    $params[] = $role;
}

$sql .= " ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'];
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='admin'"))['total'];
$total_customer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='user'"))['total'];
$total_active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status='aktif'"))['total'];

$page_title = "Daftar Pengguna";
$asset_prefix = "../";
$home_link = "../index.php";
include "../includes/header.php";
?>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-title">ADMIN PANEL</div>
        <a href="index.php">Dashboard</a>
        <a href="kategori.php">Kategori</a>
        <a href="produk.php">Produk</a>
        <a href="pengguna.php" class="active">Pengguna</a>
        <a href="pesanan.php">Pesanan</a>
        <a href="laporan.php">Laporan</a>
        <a href="../index.php">Lihat Website</a>
        <a href="../logout.php">Keluar</a>
    </aside>

    <section class="admin-main">
        <div class="admin-head">
            <div>
                <div class="kicker">MANAJEMEN AKUN</div>
                <h1>Daftar Pengguna</h1>
            </div>
            <span class="badge">Total <?= (int)$total_users; ?> akun</span>
        </div>

        <?php if ($error): ?><div class="flash error"><?= e($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="flash success"><?= e($success); ?></div><?php endif; ?>

        <div class="stats user-stats">
            <div class="stat"><small>Semua Akun</small><strong><?= (int)$total_users; ?></strong></div>
            <div class="stat"><small>Pelanggan</small><strong><?= (int)$total_customer; ?></strong></div>
            <div class="stat"><small>Admin</small><strong><?= (int)$total_admin; ?></strong></div>
            <div class="stat"><small>Akun Aktif</small><strong><?= (int)$total_active; ?></strong></div>
        </div>

        <div class="admin-card">
            <div class="section-head" style="margin-bottom:16px">
                <div>
                    <h2>Data Pengguna</h2>
                    <p>Kelola akun admin dan pelanggan yang terdaftar di Ronggolawe Batik.</p>
                </div>
            </div>

            <form method="GET" class="filter-bar">
                <div class="filter-search">
                    <input type="text" name="search" value="<?= e($search); ?>" placeholder="Cari nama atau email..."><!-- -->
                </div>
                <select name="role">
                    <option value="">Semua Role</option>
                    <option value="user" <?= $role === 'user' ? 'selected' : ''; ?>>Pelanggan</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                <button class="btn btn-primary" type="submit">Cari</button>
                <?php if ($search !== '' || $role !== ''): ?><a class="btn btn-outline" href="pengguna.php">Reset</a><?php endif; ?>
            </form>

            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($users) === 0): ?>
                        <tr><td colspan="7"><div class="empty">Tidak ada pengguna yang sesuai dengan pencarian.</div></td></tr>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td>#<?= (int)$row['id']; ?></td>
                                <td><strong><?= e($row['nama']); ?></strong></td>
                                <td><?= e($row['email']); ?></td>
                                <td>
                                    <span class="status <?= $row['role'] === 'admin' ? 'warning' : ''; ?>">
                                        <?= $row['role'] === 'admin' ? 'Admin' : 'Pelanggan'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status <?= $row['status'] === 'aktif' ? 'success' : 'danger'; ?>">
                                        <?= e(ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="actions">
                                    <?php if ((int)$row['id'] === (int)$_SESSION['user_id']): ?>
                                        <span class="text-muted">Akun Anda</span>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('<?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?> pengguna ini?')">
                                            <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                            <button class="btn <?= $row['status'] === 'aktif' ? 'btn-warning' : 'btn-primary'; ?> btn-sm" type="submit" name="toggle_status">
                                                <?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php
mysqli_stmt_close($stmt);
include "../includes/footer.php";
?>
