<?php
require_once "config/database.php";
require_once "includes/functions.php";

start_session_once();

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/index.php' : 'user/index.php');
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        $error = "Semua field wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sesuai.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $check = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        if (mysqli_num_rows($check) > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO users (nama,email,password,role,status) VALUES (?,?,?,'user','aktif')");
            mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $hash);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Registrasi berhasil. Silakan masuk menggunakan akun baru.";
            } else {
                $error = "Registrasi gagal.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

$page_title = "Daftar Akun";
$asset_prefix = "";
$home_link = "index.php";
include "includes/header.php";
?>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">RB</span>
            <h1>Buat akun baru</h1>
            <p>Daftar untuk menyimpan keranjang dan melakukan pembelian.</p>
        </div>

        <?php if ($error): ?>
            <div class="flash error"><?= e($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash success"><?= e($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Nama lengkap" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="nama@email.com" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" minlength="6" placeholder="Min. 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="konfirmasi_password" minlength="6" placeholder="Ulangi password" required>
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Buat Akun</button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a style="color:var(--blue);font-weight:800" href="login.php">Masuk</a>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>