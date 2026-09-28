RONGGOLAWE BATIK V23 — FINAL SHOPPING FLOW

Pembaruan utama:
1. Katalog memiliki pencarian, kategori, urutan harga, nama, produk terlaris, dan rating tertinggi.
2. Produk menampilkan stok, rating, jumlah ulasan, dan jumlah terjual jika tersedia.
3. Homepage memiliki bagian Produk Terlaris.
4. Detail produk menampilkan rating, jumlah ulasan, dan jumlah terjual.
5. Checkout melakukan pengecekan stok kembali di dalam transaksi dan menghentikan order jika stok berubah.
6. Admin mengelola alur Menunggu -> Diproses -> Dikirim; status Selesai hanya dikonfirmasi customer.
7. Saat status Dikirim, kurir dan nomor resi wajib diisi.
8. User dapat melihat kurir dan resi pada daftar/detail pesanan.
9. Status pembayaran dan status pesanan menggunakan label visual yang lebih jelas.
10. Database V22 sudah memiliki kolom kurir/nomor_resi. final_upgrade_v23.sql disediakan untuk database lama.

Alur final:
Pilih produk -> Beli Sekarang / Keranjang -> Checkout -> Pilih alamat -> Pembayaran -> Verifikasi admin -> Diproses -> Dikirim + Resi -> Customer menerima -> Customer klik Pesanan Diterima -> Selesai -> Review.
