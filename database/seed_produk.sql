USE ronggolawe_batik;

INSERT INTO products
(category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT c.id, 'RB-GDG-001', 'Batik Gedog Tuban', 'batik-gedog-tuban',
'Batik Gedog khas Tuban dengan karakter kain yang kuat dan motif bernuansa tradisional. Cocok untuk koleksi maupun kebutuhan busana.',
185000, 20, 'batik-gedog-tuban.jpg', 'aktif'
FROM categories c WHERE c.nama_kategori='Batik Gedog'
AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-GDG-001');

INSERT INTO products
(category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT c.id, 'RB-TLS-001', 'Batik Tulis Sekar Tuban', 'batik-tulis-sekar-tuban',
'Batik tulis dengan komposisi motif yang elegan dan sentuhan warna khas pesisir Tuban.',
325000, 12, 'batik-tulis-tuban.jpg', 'aktif'
FROM categories c WHERE c.nama_kategori='Batik Tulis'
AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-TLS-001');

INSERT INTO products
(category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT c.id, 'RB-CAP-001', 'Batik Cap Ronggolawe', 'batik-cap-ronggolawe',
'Batik cap dengan pola geometris yang rapi, nyaman dipadukan untuk berbagai gaya busana.',
165000, 18, 'batik-cap-ronggolawe.jpg', 'aktif'
FROM categories c WHERE c.nama_kategori='Batik Cap'
AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-CAP-001');

INSERT INTO products
(category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT c.id, 'RB-KMJ-001', 'Kemeja Batik Tuban', 'kemeja-batik-tuban',
'Kemeja batik siap pakai dengan motif khas Tuban, cocok untuk acara formal maupun semi-formal.',
215000, 15, 'kemeja-batik-tuban.jpg', 'aktif'
FROM categories c WHERE c.nama_kategori='Kemeja Batik'
AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-KMJ-001');

INSERT INTO products
(category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT c.id, 'RB-SLD-001', 'Selendang Gedog Tuban', 'selendang-gedog-tuban',
'Selendang bernuansa batik Gedog dengan tampilan anggun untuk pelengkap busana tradisional.',
145000, 14, 'selendang-gedog.jpg', 'aktif'
FROM categories c WHERE c.nama_kategori='Selendang'
AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SLD-001');

INSERT INTO products
(category_id, sku, nama_produk, slug, deskripsi, harga, stok, gambar, status)
SELECT c.id, 'RB-SRG-001', 'Sarung Batik Tuban', 'sarung-batik-tuban',
'Sarung batik dengan pola khas yang dapat digunakan untuk acara keluarga, ibadah, maupun kegiatan budaya.',
195000, 10, 'sarung-batik-tuban.jpg', 'aktif'
FROM categories c WHERE c.nama_kategori='Sarung'
AND NOT EXISTS (SELECT 1 FROM products WHERE sku='RB-SRG-001');


-- Memperbaiki nama file gambar pada data produk yang sudah ada
UPDATE products SET gambar='batik-gedog-tuban.jpg' WHERE sku='RB-GDG-001';
UPDATE products SET gambar='batik-tulis-tuban.jpg' WHERE sku='RB-TLS-001';
UPDATE products SET gambar='batik-cap-ronggolawe.jpg' WHERE sku='RB-CAP-001';
UPDATE products SET gambar='kemeja-batik-tuban.jpg' WHERE sku='RB-KMJ-001';
UPDATE products SET gambar='selendang-gedog.jpg' WHERE sku='RB-SLD-001';
UPDATE products SET gambar='sarung-batik-tuban.jpg' WHERE sku='RB-SRG-001';
