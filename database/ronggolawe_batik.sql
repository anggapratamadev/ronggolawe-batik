-- Ronggolawe Batik - Database Installer
-- Satu file untuk instalasi database baru.
-- Import file ini melalui phpMyAdmin/MySQL.

-- ============================================================
-- full_schema_v20.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS ronggolawe_batik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ronggolawe_batik;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role), INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi VARCHAR(255),
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categories_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    nama_produk VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    deskripsi TEXT,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    gambar VARCHAR(255),
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_category (category_id), INDEX idx_products_status (status), INDEX idx_products_nama (nama_produk),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_product (user_id,product_id), INDEX idx_cart_user(user_id), INDEX idx_cart_product(product_id),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nomor_order VARCHAR(40) NOT NULL UNIQUE,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ongkir DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    nama_penerima VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    kota VARCHAR(100) NOT NULL,
    kode_pos VARCHAR(10),
    kurir VARCHAR(100) NULL,
    nomor_resi VARCHAR(100) NULL,
    status ENUM('Menunggu','Diproses','Dikirim','Selesai','Dibatalkan') NOT NULL DEFAULT 'Menunggu',
    catatan TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_user(user_id), INDEX idx_orders_status(status), INDEX idx_orders_created(created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_details_order(order_id), INDEX idx_order_details_product(product_id),
    CONSTRAINT fk_order_details_order FOREIGN KEY (order_id) REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_details_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    metode_pembayaran ENUM('QRIS','DANA','COD') NOT NULL,
    jumlah_bayar DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('Menunggu','Dibayar','Gagal','Dikembalikan') NOT NULL DEFAULT 'Menunggu',
    bukti_pembayaran VARCHAR(255),
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_payment_order(order_id), INDEX idx_payments_status(status),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    komentar TEXT,
    status ENUM('Tampil','Disembunyikan') NOT NULL DEFAULT 'Tampil',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product_review(user_id,product_id), INDEX idx_reviews_product(product_id), INDEX idx_reviews_user(user_id),
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(50) NOT NULL DEFAULT 'Rumah',
    nama_penerima VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    kota VARCHAR(100) NOT NULL,
    kode_pos VARCHAR(10),
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_addresses_user(user_id), INDEX idx_addresses_default(user_id,is_default),
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist(user_id,product_id), INDEX idx_wishlist_user(user_id), INDEX idx_wishlist_product(product_id),
    CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (nama,email,password,role,status) VALUES ('Administrator','admin@ronggolawe.com','$2y$12$DzGhhOoMA3OdpYQg4ewAd.R5E4RYhNWIxQYcmN0BdpaXt452/ZOoO','admin','aktif')
ON DUPLICATE KEY UPDATE nama=VALUES(nama), role='admin', status='aktif';

INSERT INTO categories (nama_kategori,deskripsi) VALUES
('Batik Gedog','Batik khas Tuban yang menggunakan kain Gedog.'),
('Batik Tulis','Produk batik tulis khas Kabupaten Tuban.'),
('Batik Cap','Produk batik cap dengan motif khas Tuban.'),
('Kemeja Batik','Kemeja dengan motif batik khas Tuban.'),
('Selendang','Selendang batik khas Tuban.'),
('Sarung','Sarung batik khas Tuban.')
ON DUPLICATE KEY UPDATE deskripsi=VALUES(deskripsi), status='aktif';


-- Seed produk lengkap
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


-- ============================================================
-- seed_produk_banyak.sql
-- ============================================================
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


-- ============================================================
-- deskripsi_produk_v23.sql
-- ============================================================
USE ronggolawe_batik;

-- Memastikan seluruh 24 produk memiliki deskripsi yang tampil di katalog dan detail produk.
UPDATE products SET deskripsi='Batik Gedog khas Tuban dengan karakter kain yang kuat dan motif bernuansa tradisional. Cocok untuk koleksi maupun kebutuhan busana.' WHERE sku='RB-GDG-001';
UPDATE products SET deskripsi='Batik bernuansa biru dengan karakter motif pesisir khas Tuban. Cocok untuk busana santai maupun acara keluarga.' WHERE sku='RB-GDG-002';
UPDATE products SET deskripsi='Kain batik dengan aksen warna keemasan yang elegan untuk acara khusus dan koleksi pribadi.' WHERE sku='RB-GDG-003';
UPDATE products SET deskripsi='Batik tulis dengan komposisi motif yang elegan dan sentuhan warna khas pesisir Tuban.' WHERE sku='RB-TLS-001';
UPDATE products SET deskripsi='Batik tulis dengan pola berulang yang sederhana dan mudah dipadukan untuk berbagai kesempatan.' WHERE sku='RB-TLS-002';
UPDATE products SET deskripsi='Batik tulis bernuansa klasik dengan komposisi motif yang lembut dan detail pengerjaan yang khas.' WHERE sku='RB-TLS-003';
UPDATE products SET deskripsi='Batik tulis dengan inspirasi motif tumbuhan dan sentuhan pesisir yang memberikan karakter khas Tuban.' WHERE sku='RB-TLS-004';
UPDATE products SET deskripsi='Batik cap dengan pola geometris yang rapi, nyaman dipadukan untuk berbagai gaya busana.' WHERE sku='RB-CAP-001';
UPDATE products SET deskripsi='Batik cap berwarna navy yang cocok untuk busana harian maupun acara formal.' WHERE sku='RB-CAP-002';
UPDATE products SET deskripsi='Batik cap dengan warna maroon yang hangat dan motif khas Tuban untuk tampilan yang berkarakter.' WHERE sku='RB-CAP-003';
UPDATE products SET deskripsi='Batik cap praktis dengan motif pesisir yang mudah dipadukan untuk kebutuhan sehari-hari.' WHERE sku='RB-CAP-004';
UPDATE products SET deskripsi='Kemeja batik siap pakai dengan motif khas Tuban, cocok untuk acara formal maupun semi-formal.' WHERE sku='RB-KMJ-001';
UPDATE products SET deskripsi='Kemeja batik pria dengan nuansa navy, cocok untuk kerja, acara resmi, maupun kegiatan keluarga.' WHERE sku='RB-KMJ-002';
UPDATE products SET deskripsi='Kemeja batik bernuansa pesisir dengan potongan yang nyaman dipakai untuk aktivitas sehari-hari.' WHERE sku='RB-KMJ-003';
UPDATE products SET deskripsi='Kemeja batik wanita dengan motif khas Tuban untuk tampilan semi-formal yang rapi dan elegan.' WHERE sku='RB-KMJ-004';
UPDATE products SET deskripsi='Kemeja batik untuk kebutuhan kantor dengan motif Gedog yang khas dan mudah dipadukan.' WHERE sku='RB-KMJ-005';
UPDATE products SET deskripsi='Selendang bernuansa batik Gedog dengan tampilan anggun untuk pelengkap busana tradisional.' WHERE sku='RB-SLD-001';
UPDATE products SET deskripsi='Selendang dengan warna biru yang ringan dan cocok sebagai pelengkap busana sehari-hari.' WHERE sku='RB-SLD-002';
UPDATE products SET deskripsi='Selendang batik dengan motif lembut untuk acara budaya, keluarga, maupun penggunaan harian.' WHERE sku='RB-SLD-003';
UPDATE products SET deskripsi='Selendang Gedog dengan aksen keemasan yang memberi kesan elegan untuk acara spesial.' WHERE sku='RB-SLD-004';
UPDATE products SET deskripsi='Sarung batik dengan pola khas yang dapat digunakan untuk acara keluarga, ibadah, maupun kegiatan budaya.' WHERE sku='RB-SRG-001';
UPDATE products SET deskripsi='Sarung batik dengan warna navy dan motif Gedog yang khas untuk penggunaan sehari-hari.' WHERE sku='RB-SRG-002';
UPDATE products SET deskripsi='Sarung batik bernuansa pesisir untuk kebutuhan keluarga dan kegiatan budaya.' WHERE sku='RB-SRG-003';
UPDATE products SET deskripsi='Sarung batik dengan motif khas Ronggolawe dan warna yang mudah dipadukan.' WHERE sku='RB-SRG-004';


