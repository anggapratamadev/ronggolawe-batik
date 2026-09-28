V24.6 - LOGIKA PEMBAYARAN & PENGIRIMAN

ALUR QRIS/DANA
1. Checkout: order Menunggu + payment Menunggu.
2. User upload bukti: payment tetap Menunggu.
3. Admin konfirmasi Dibayar: payment Dibayar + order otomatis Diproses.
4. Admin isi kurir + resi dan ubah Diproses -> Dikirim.
5. User konfirmasi diterima: order Selesai.
6. Jika perlu refund sebelum dikirim: admin Dibayar -> Dikembalikan, order otomatis Dibatalkan, stok dikembalikan.

ALUR COD
1. Checkout: order Menunggu + payment Menunggu.
2. Admin dapat mengubah order Menunggu -> Diproses tanpa mengubah payment.
3. Admin isi kurir + resi lalu Diproses -> Dikirim.
4. User menerima barang lalu klik Pesanan Sudah Diterima: order Selesai + payment otomatis Dibayar.
5. Admin tidak boleh menandai COD sebagai Dibayar/Gagal/Dikembalikan sebelum selesai.

ATURAN PENTING
- Pesanan Dikirim tidak dapat dibatalkan oleh admin.
- Pesanan Selesai/Dibatalkan tidak dapat diubah admin.
- Pembayaran Dikembalikan hanya boleh dari status Dibayar dan sebelum Dikirim.
- Pembayaran Dibayar tidak dapat diturunkan setelah pesanan Diproses/Dikirim.
- Detail status pembayaran di halaman Pesanan Saya dibaca langsung dari tabel payments.
