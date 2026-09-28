USE ronggolawe_batik;

-- Tambahan 18 produk agar katalog terlihat lebih lengkap

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-GDG-002','Batik Pesisir Biru','batik-pesisir-biru','Batik bernuansa biru dengan karakter motif pesisir khas Tuban.',225000,16,'batik-pesisir-biru.jpg','aktif' FROM categories WHERE nama_kategori='Batik Gedog' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-GDG-002');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-GDG-003','Batik Pesisir Gold','batik-pesisir-gold','Kain batik dengan aksen warna keemasan yang elegan untuk acara khusus.',235000,14,'batik-pesisir-gold.jpg','aktif' FROM categories WHERE nama_kategori='Batik Gedog' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-GDG-003');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-TLS-002','Batik Lurik Tuban','batik-lurik-tuban','Batik tulis dengan pola berulang yang sederhana dan mudah dipadukan.',295000,13,'batik-lurik-tuban.jpg','aktif' FROM categories WHERE nama_kategori='Batik Tulis' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-TLS-002');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-TLS-003','Batik Sekar Jati','batik-sekar-jati','Batik tulis bernuansa klasik dengan komposisi motif yang lembut.',345000,9,'batik-sekar-jati.jpg','aktif' FROM categories WHERE nama_kategori='Batik Tulis' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-TLS-003');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-TLS-004','Batik Pring Tuban','batik-pring-tuban','Batik tulis dengan inspirasi motif tumbuhan dan sentuhan pesisir.',315000,11,'batik-pring-tuban.jpg','aktif' FROM categories WHERE nama_kategori='Batik Tulis' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-TLS-004');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-CAP-002','Batik Ronggolawe Navy','batik-ronggolawe-navy','Batik cap berwarna navy yang cocok untuk busana harian maupun acara formal.',175000,20,'batik-ronggolawe-navy.jpg','aktif' FROM categories WHERE nama_kategori='Batik Cap' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-CAP-002');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-CAP-003','Batik Ronggolawe Maroon','batik-ronggolawe-maroon','Batik cap dengan warna maroon yang hangat dan motif khas Tuban.',180000,17,'batik-ronggolawe-maroon.jpg','aktif' FROM categories WHERE nama_kategori='Batik Cap' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-CAP-003');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-CAP-004','Batik Cap Pesisir','batik-cap-pesisir','Batik cap praktis dengan motif pesisir yang mudah dipadukan.',155000,22,'batik-cap-pesisir.jpg','aktif' FROM categories WHERE nama_kategori='Batik Cap' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-CAP-004');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-KMJ-002','Kemeja Batik Pria Navy','kemeja-pria-navy','Kemeja batik pria dengan nuansa navy, cocok untuk kerja dan acara resmi.',245000,10,'kemeja-pria-navy.jpg','aktif' FROM categories WHERE nama_kategori='Kemeja Batik' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-KMJ-002');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-KMJ-003','Kemeja Batik Sagara','kemeja-pria-sagara','Kemeja batik bernuansa pesisir dengan potongan yang nyaman dipakai.',255000,12,'kemeja-pria-sagara.jpg','aktif' FROM categories WHERE nama_kategori='Kemeja Batik' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-KMJ-003');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-KMJ-004','Kemeja Batik Wanita Tuban','kemeja-wanita-tuban','Kemeja batik wanita dengan motif khas Tuban untuk tampilan semi-formal.',235000,14,'kemeja-wanita-tuban.jpg','aktif' FROM categories WHERE nama_kategori='Kemeja Batik' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-KMJ-004');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-KMJ-005','Kemeja Kantor Gedog','kemeja-kantor-gedog','Kemeja batik untuk kebutuhan kantor dengan motif Gedog yang khas.',265000,8,'kemeja-kantor-gedog.jpg','aktif' FROM categories WHERE nama_kategori='Kemeja Batik' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-KMJ-005');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-SLD-002','Selendang Pesisir Biru','selendang-pesisir-biru','Selendang dengan warna biru yang ringan dan cocok sebagai pelengkap busana.',155000,15,'selendang-pesisir-biru.jpg','aktif' FROM categories WHERE nama_kategori='Selendang' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SLD-002');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-SLD-003','Selendang Sekar Tuban','selendang-sekar-tuban','Selendang batik dengan motif lembut untuk acara budaya dan penggunaan harian.',165000,12,'selendang-sekar.jpg','aktif' FROM categories WHERE nama_kategori='Selendang' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SLD-003');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-SLD-004','Selendang Gedog Gold','selendang-gedog-gold','Selendang Gedog dengan aksen keemasan yang memberi kesan elegan.',175000,10,'selendang-gedog-gold.jpg','aktif' FROM categories WHERE nama_kategori='Selendang' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SLD-004');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-SRG-002','Sarung Gedog Navy','sarung-gedog-navy','Sarung batik dengan warna navy dan motif Gedog yang khas.',215000,13,'sarung-gedog-navy.jpg','aktif' FROM categories WHERE nama_kategori='Sarung' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SRG-002');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-SRG-003','Sarung Pesisir Tuban','sarung-pesisir-tuban','Sarung batik bernuansa pesisir untuk kebutuhan keluarga dan kegiatan budaya.',205000,16,'sarung-pesisir.jpg','aktif' FROM categories WHERE nama_kategori='Sarung' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SRG-003');

INSERT INTO products (category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT id,'RB-SRG-004','Sarung Ronggolawe','sarung-ronggolawe','Sarung batik dengan motif khas Ronggolawe dan warna yang mudah dipadukan.',225000,9,'sarung-ronggolawe.jpg','aktif' FROM categories WHERE nama_kategori='Sarung' AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SRG-004');
