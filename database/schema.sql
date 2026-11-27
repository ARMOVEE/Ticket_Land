CREATE DATABASE IF NOT EXISTS db_ticketland
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_ticketland;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  peran ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
  status_aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS akun_user (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(50) NOT NULL UNIQUE,
  deskripsi VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(150) NOT NULL,
  category_id INT NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE DEFAULT NULL,
  tanggal_label VARCHAR(100) NOT NULL,
  jam_label VARCHAR(50) NOT NULL DEFAULT '19:00 - 22:00 WIB',
  venue VARCHAR(150) NOT NULL,
  kota VARCHAR(80) NOT NULL,
  penyelenggara VARCHAR(120) NOT NULL,
  label_sesi VARCHAR(50) DEFAULT NULL,
  instagram_url VARCHAR(255) DEFAULT NULL,
  tiktok_url VARCHAR(255) DEFAULT NULL,
  deskripsi TEXT NOT NULL,
  syarat_ketentuan TEXT NOT NULL,
  poster VARCHAR(255) DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_hero TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  deleted_at DATETIME DEFAULT NULL,
  deleted_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_category FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  nama VARCHAR(100) NOT NULL,
  harga INT NOT NULL DEFAULT 0,
  kuota_awal INT NOT NULL DEFAULT 0,
  stok_sisa INT NOT NULL DEFAULT 0,
  status ENUM('On Sale', 'Habis') NOT NULL DEFAULT 'On Sale',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_event_ticket_cat (event_id, nama),
  CONSTRAINT fk_tc_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor_invoice VARCHAR(40) NOT NULL UNIQUE,
  akun_user_id INT DEFAULT NULL,
  event_id INT NOT NULL,
  ticket_category_id INT NOT NULL,
  jumlah INT NOT NULL,
  subtotal INT NOT NULL DEFAULT 0,
  biaya_layanan INT NOT NULL DEFAULT 0,
  total INT NOT NULL DEFAULT 0,
  metode_pembayaran VARCHAR(60) NOT NULL,
  status ENUM('Menunggu Pembayaran', 'Lunas', 'Kedaluwarsa', 'Gagal', 'Dibatalkan') NOT NULL DEFAULT 'Lunas',
  cancel_code VARCHAR(6) NULL UNIQUE,
  cancelled_at DATETIME NULL,
  cancellation_ref VARCHAR(20) NULL,
  cancellation_reason TEXT NULL,
  pemesan_nama VARCHAR(120) NOT NULL,
  pemesan_tipe_identitas VARCHAR(20) NOT NULL DEFAULT 'KTP',
  pemesan_nomor_identitas VARCHAR(100) NOT NULL,
  pemesan_email VARCHAR(120) NOT NULL,
  pemesan_wa VARCHAR(40) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_event FOREIGN KEY (event_id) REFERENCES events(id) ON UPDATE CASCADE,
  CONSTRAINT fk_orders_tc FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  event_id INT NOT NULL,
  ticket_category_id INT NOT NULL,
  kode_tiket VARCHAR(20) NOT NULL UNIQUE,
  nama_pemegang VARCHAR(120) NOT NULL,
  tipe_identitas VARCHAR(20) NOT NULL DEFAULT 'KTP',
  nomor_identitas VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL,
  jenis_kelamin VARCHAR(20) NOT NULL DEFAULT 'Laki-laki',
  wa VARCHAR(40) NOT NULL,
  status_checkin ENUM('Belum Check-in', 'Sudah Check-in', 'Dibatalkan') NOT NULL DEFAULT 'Belum Check-in',
  waktu_checkin DATETIME DEFAULT NULL,
  checkin_staff_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tickets_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_tickets_event FOREIGN KEY (event_id) REFERENCES events(id) ON UPDATE CASCADE,
  CONSTRAINT fk_tickets_tc FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  metode VARCHAR(60) NOT NULL,
  referensi VARCHAR(80) NOT NULL,
  status ENUM('Pending', 'Berhasil', 'Gagal') NOT NULL DEFAULT 'Berhasil',
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  ticket_category_id INT NOT NULL,
  jenis VARCHAR(20) NOT NULL,
  jumlah INT NOT NULL,
  sisa_sesudah INT NOT NULL,
  pelaku VARCHAR(60) NOT NULL,
  catatan VARCHAR(255) NOT NULL DEFAULT '',
  order_id INT DEFAULT NULL,
  checkout_token VARCHAR(64) DEFAULT NULL,
  status_hold ENUM('HOLD', 'PAID', 'RELEASED') DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_st_hold (ticket_category_id, status_hold, expires_at),
  INDEX idx_st_token (checkout_token),
  CONSTRAINT fk_st_event FOREIGN KEY (event_id) REFERENCES events(id) ON UPDATE CASCADE,
  CONSTRAINT fk_st_tc FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checkin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT DEFAULT NULL,
  kode_dipindai VARCHAR(40) NOT NULL,
  nama_pemesan VARCHAR(120) NOT NULL DEFAULT '-',
  nama_event VARCHAR(150) NOT NULL DEFAULT '-',
  hasil ENUM('Check-in', 'Ditolak') NOT NULL,
  alasan VARCHAR(255) NOT NULL DEFAULT '',
  staff_id INT DEFAULT NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id INT DEFAULT NULL,
  username VARCHAR(60) NOT NULL DEFAULT 'sistem',
  aksi VARCHAR(40) NOT NULL,
  entitas VARCHAR(60) NOT NULL,
  deskripsi TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  subject VARCHAR(100) NOT NULL,
  pesan TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
