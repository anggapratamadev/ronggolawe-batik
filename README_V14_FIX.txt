Ronggolawe Batik V14 - Perbaikan Checkout

Perbaikan:
1. Warning "Undefined variable $addresses" pada user/checkout.php diperbaiki.
2. $addresses sekarang selalu diinisialisasi dan diambil dari database sebagai array.
3. JSON alamat checkout tidak lagi memanggil array_map() pada mysqli_result.
4. Seluruh file PHP sudah dicek dengan php -l dan tidak ada syntax error.
5. Fitur alamat tersimpan, ongkir Jawa Timur, QRIS/DANA/COD, pesanan terpadu tetap dipertahankan.

Tidak perlu import database baru jika database V13 sudah terpasang.
Pastikan tabel user_addresses sudah ada (gunakan database/alamat_pengguna.sql jika belum pernah dibuat).
