<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$username = "root";
$password = "";
$database = "ronggolawe_batik";

try {
    $conn = mysqli_connect($host, $username, $password, $database);
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Koneksi database gagal. Pastikan MySQL/Laragon aktif dan database ronggolawe_batik tersedia.");
}
?>