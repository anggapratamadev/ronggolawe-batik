<?php
require_once "config/database.php";
require_once "includes/functions.php";

start_session_once();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') redirect('admin/index.php');
    redirect('user/index.php');
}

$error = "";
$redirect_target = safe_redirect_target($_GET['redirect'] ?? $_POST['redirect'] ?? 'user/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Email dan password wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, nama, email, password, role, status FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if ($user['status'] !== 'aktif') {
                $error = "Akun Anda sedang dinonaktifkan.";
            } elseif (password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                }

                redirect($redirect_target);
            } else {
                $error = "Email atau password salah.";
            }
        } else {
            $error = "Email atau password salah.";
        }

        mysqli_stmt_close($stmt);
    }
}

$page_title = "Login";
$asset_prefix = "";
$home_link = "index.php";
include "includes/header.php";
?>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">RB</span>
            <h1>Selamat datang kembali</h1>
            <p>Masuk untuk melanjutkan belanja di Ronggolawe Batik.</p>
        </div>

        <?php if ($error): ?>
            <div class="flash error"><?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="redirect" value="<?= e($redirect_target); ?>">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="nama@email.com" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Masuk ke Akun</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a style="color:var(--blue);font-weight:800" href="register.php">Daftar sekarang</a>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>