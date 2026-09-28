<?php

function start_session_once()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value)
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function shipping_city_rates()
{
    // Estimasi ongkir untuk 38 kabupaten/kota di Jawa Timur.
    // Tarif ini adalah tarif aplikasi untuk kebutuhan project, bukan tarif API kurir real-time.
    return [
        'Kabupaten Tuban' => 10000,
        'Kabupaten Lamongan' => 12000,
        'Kabupaten Bojonegoro' => 12000,
        'Kabupaten Gresik' => 15000,
        'Kota Surabaya' => 15000,
        'Kabupaten Sidoarjo' => 15000,
        'Kabupaten Mojokerto' => 17000,
        'Kota Mojokerto' => 17000,
        'Kabupaten Jombang' => 17000,
        'Kabupaten Pasuruan' => 20000,
        'Kota Pasuruan' => 20000,
        'Kabupaten Malang' => 20000,
        'Kota Malang' => 20000,
        'Kabupaten Kediri' => 20000,
        'Kota Kediri' => 20000,
        'Kabupaten Madiun' => 22000,
        'Kota Madiun' => 22000,
        'Kabupaten Probolinggo' => 22000,
        'Kota Probolinggo' => 22000,
        'Kabupaten Blitar' => 22000,
        'Kota Blitar' => 22000,
        'Kabupaten Banyuwangi' => 25000,
        'Kabupaten Bangkalan' => 22000,
        'Kabupaten Sampang' => 22000,
        'Kabupaten Pamekasan' => 24000,
        'Kabupaten Sumenep' => 26000,
        'Kabupaten Ngawi' => 23000,
        'Kabupaten Magetan' => 23000,
        'Kabupaten Ponorogo' => 23000,
        'Kabupaten Pacitan' => 25000,
        'Kabupaten Trenggalek' => 24000,
        'Kabupaten Tulungagung' => 23000,
        'Kabupaten Nganjuk' => 20000,
        'Kota Batu' => 22000,
        'Kabupaten Jember' => 25000,
        'Kabupaten Bondowoso' => 25000,
        'Kabupaten Lumajang' => 23000,
        'Kabupaten Situbondo' => 25000,
        'Lainnya' => 30000,
    ];
}

function shipping_fee($kota)
{
    $rates = shipping_city_rates();
    $kota = trim($kota);
    return $rates[$kota] ?? $rates['Lainnya'];
}

function order_status_class($status)
{
    $status = (string)$status;
    if ($status === 'Selesai') return 'success';
    if ($status === 'Dibatalkan') return 'danger';
    if ($status === 'Dikirim') return 'info';
    return 'warning';
}

function payment_status_class($status)
{
    $status = (string)$status;
    if ($status === 'Dibayar') return 'success';
    if (in_array($status, ['Gagal', 'Dikembalikan'], true)) return 'danger';
    return 'warning';
}

function default_shipping_fee()
{

    // Fallback kompatibilitas untuk halaman lama. Checkout menggunakan shipping_fee().
    return 15000;
}

function redirect($url)
{
    header("Location: " . $url);
    exit;
}

function require_login($role = null)
{
    start_session_once();

    if (!isset($_SESSION['user_id'])) {
        redirect('../login.php');
    }

    if ($role !== null && ($_SESSION['role'] ?? '') !== $role) {
        redirect('../index.php');
    }
}

function slugify($text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    return trim($text, '-');
}

function flash($type, $message)
{
    start_session_once();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash()
{
    start_session_once();

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function cart_count($conn, $user_id)
{
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(jumlah), 0) AS total FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return (int)$row['total'];
}

function restore_order_stock($conn, $order_id)
{
    $stmt = mysqli_prepare($conn, "SELECT product_id, jumlah FROM order_details WHERE order_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $order_id); mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $qty = (int)$row['jumlah']; $product_id = (int)$row['product_id'];
        $up = mysqli_prepare($conn, "UPDATE products SET stok=stok+? WHERE id=?");
        mysqli_stmt_bind_param($up, 'ii', $qty, $product_id); mysqli_stmt_execute($up); mysqli_stmt_close($up);
    }
    mysqli_stmt_close($stmt);
}

function safe_redirect_target($target)
{
    $allowed = [
        'user/index.php',
        'user/cart.php',
        'user/checkout.php',
        'user/pesanan.php'
    ];

    if (in_array($target, $allowed, true)) {
        return $target;
    }

    // Izinkan kembali ke halaman detail produk dengan ID numerik saja.
    // Query string lain tidak diterima agar target redirect tetap aman.
    if (preg_match('/^user\/detail\.php\?id=[1-9][0-9]*$/', $target)) {
        return $target;
    }

    return 'user/index.php';
}
?>