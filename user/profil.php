<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

require_login('user');
$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

$stmt = mysqli_prepare($conn, "SELECT id, nama, email, role, status, created_at FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nama === '' || strlen($nama) < 2) {
            $error = 'Nama minimal 2 karakter.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
            mysqli_stmt_execute($stmt);
            $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if ($exists) {
                $error = 'Email tersebut sudah digunakan oleh akun lain.';
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET nama=?, email=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'ssi', $nama, $email, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $_SESSION['nama'] = $nama;
                $user['nama'] = $nama;
                $user['email'] = $email;
                $success = 'Profil berhasil diperbarui.';
            }
        }
    } elseif ($action === 'password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            $error = 'Semua kolom password wajib diisi.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$row || !password_verify($current_password, $row['password'])) {
                $error = 'Password saat ini salah.';
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'si', $hashed, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success = 'Password berhasil diubah. Gunakan password baru saat login berikutnya.';
            }
        }
    }
}

$page_title = 'Profil Saya';
$asset_prefix = '../';
$home_link = '../index.php';
include '../includes/header.php';
?>

<section class="page-hero profile-hero">
    <div class="container">
        <div class="kicker" style="color:#9fdcff">AKUN SAYA</div>
        <h1>Profil Pengguna</h1>
        <p>Kelola identitas akun dan password dengan mudah dari satu tempat.</p>
    </div>
</section>

<section class="content">
    <div class="container">
        <?php if ($error): ?>
            <div class="flash error profile-alert"><?= e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="flash success profile-alert"><?= e($success); ?></div>
        <?php endif; ?>

        <div class="profile-layout">
            <aside class="profile-summary panel">
                <div class="profile-avatar"><?= e(strtoupper(substr($user['nama'], 0, 1))); ?></div>
                <span class="profile-label">AKUN PELANGGAN</span>
                <h2><?= e($user['nama']); ?></h2>
                <p><?= e($user['email']); ?></p>
                <div class="profile-meta">
                    <div><span>Status</span><strong class="status success">Aktif</strong></div>
                    <div><span>Bergabung</span><strong><?= date('d M Y', strtotime($user['created_at'])); ?></strong></div>
                </div>
                <div class="profile-links">
                    <a class="profile-link active" href="profil.php">Profil Saya <span>→</span></a>
                    <a class="profile-link" href="alamat.php">Alamat Saya <span>→</span></a>
                    <a class="profile-link" href="pesanan.php">Riwayat Pesanan <span>→</span></a>
                    <a class="profile-link" href="wishlist.php">Wishlist <span>→</span></a>
                    <a class="profile-link" href="../bantuan.php">Pusat Bantuan <span>→</span></a>
                </div>
            </aside>

            <div class="profile-main">
                <div class="panel profile-card">
                    <div class="profile-card-heading">
                        <div>
                            <div class="kicker">INFORMASI AKUN</div>
                            <h2>Data Profil</h2>
                            <p class="text-muted">Perbarui nama atau email yang digunakan pada akunmu.</p>
                        </div>
                        <div class="profile-icon">✦</div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="profile">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" value="<?= e($user['nama']); ?>" required autocomplete="name">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= e($user['email']); ?>" required autocomplete="email">
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                    </form>
                </div>

                <div class="panel profile-card">
                    <div class="profile-card-heading">
                        <div>
                            <div class="kicker">KEAMANAN</div>
                            <h2>Ubah Password</h2>
                            <p class="text-muted">Gunakan password yang kuat dan jangan membagikannya kepada orang lain.</p>
                        </div>
                        <div class="profile-icon profile-icon-gold">🔒</div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="password">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Password Saat Ini</label>
                                <input type="password" name="current_password" required autocomplete="current-password" placeholder="Masukkan password saat ini">
                            </div>
                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="new_password" required minlength="6" autocomplete="new-password" placeholder="Minimal 6 karakter">
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password" placeholder="Ulangi password baru">
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Ubah Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
