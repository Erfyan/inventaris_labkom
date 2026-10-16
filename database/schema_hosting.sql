-- Skema Database untuk Hosting (InfinityFree, cPanel, Shared Hosting)
-- PENTING: Pilih database Anda terlebih dahulu di phpMyAdmin, lalu Import file ini.
-- Jangan tambahkan perintah CREATE DATABASE atau USE karena akun hosting bersama tidak memiliki hak akses tersebut.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS perawatan;
DROP TABLE IF EXISTS mutasi;
DROP TABLE IF EXISTS peminjaman_detail;
DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS stok_log;
DROP TABLE IF EXISTS barang;
DROP TABLE IF EXISTS supplier;
DROP TABLE IF EXISTS ruangan;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','petugas','kepala_lab') NOT NULL DEFAULT 'petugas',
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(30) NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  keterangan TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ruangan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(30) NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  penanggung_jawab VARCHAR(120) NULL,
  lokasi VARCHAR(160) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE supplier (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(160) NOT NULL,
  kontak VARCHAR(120) NULL,
  telepon VARCHAR(60) NULL,
  alamat TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_barang VARCHAR(60) NOT NULL UNIQUE,
  nama_barang VARCHAR(180) NOT NULL,
  kategori_id INT NULL,
  ruangan_id INT NULL,
  supplier_id INT NULL,
  merk VARCHAR(120) NULL,
  spesifikasi TEXT NULL,
  tahun_pengadaan YEAR NULL,
  sumber_dana VARCHAR(120) NULL,
  kondisi ENUM('baik','rusak_ringan','rusak_berat','hilang') NOT NULL DEFAULT 'baik',
  jumlah INT NOT NULL DEFAULT 0,
  satuan VARCHAR(30) NOT NULL DEFAULT 'unit',
  stok_minimum INT NOT NULL DEFAULT 1,
  harga DECIMAL(15,2) NOT NULL DEFAULT 0,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  keterangan TEXT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_barang_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
  CONSTRAINT fk_barang_ruangan FOREIGN KEY (ruangan_id) REFERENCES ruangan(id) ON DELETE SET NULL,
  CONSTRAINT fk_barang_supplier FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE SET NULL,
  CONSTRAINT fk_barang_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE stok_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  barang_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jenis ENUM('masuk','keluar','penyesuaian','mutasi') NOT NULL,
  uraian TEXT NULL,
  jumlah_masuk INT NOT NULL DEFAULT 0,
  jumlah_keluar INT NOT NULL DEFAULT 0,
  user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_stok_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
  CONSTRAINT fk_stok_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE peminjaman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_pinjam VARCHAR(60) NOT NULL UNIQUE,
  tanggal_pinjam DATE NOT NULL,
  tanggal_rencana_kembali DATE NOT NULL,
  tanggal_kembali DATE NULL,
  nama_peminjam VARCHAR(160) NOT NULL,
  kelas_jurusan VARCHAR(120) NULL,
  no_hp VARCHAR(60) NULL,
  keperluan VARCHAR(255) NULL,
  status ENUM('dipinjam','dikembalikan','terlambat','dibatalkan') NOT NULL DEFAULT 'dipinjam',
  catatan TEXT NULL,
  created_by INT NULL,
  approved_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_peminjaman_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_peminjaman_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE peminjaman_detail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  peminjaman_id INT NOT NULL,
  barang_id INT NOT NULL,
  jumlah INT NOT NULL DEFAULT 1,
  kondisi_pinjam ENUM('baik','rusak_ringan','rusak_berat','hilang') NOT NULL DEFAULT 'baik',
  kondisi_kembali ENUM('baik','rusak_ringan','rusak_berat','hilang') NULL,
  CONSTRAINT fk_peminjaman_detail_peminjaman FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id) ON DELETE CASCADE,
  CONSTRAINT fk_peminjaman_detail_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE mutasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  barang_id INT NOT NULL,
  ruangan_asal_id INT NULL,
  ruangan_tujuan_id INT NOT NULL,
  tanggal DATE NOT NULL,
  keterangan TEXT NULL,
  user_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mutasi_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
  CONSTRAINT fk_mutasi_ruangan_asal FOREIGN KEY (ruangan_asal_id) REFERENCES ruangan(id) ON DELETE SET NULL,
  CONSTRAINT fk_mutasi_ruangan_tujuan FOREIGN KEY (ruangan_tujuan_id) REFERENCES ruangan(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mutasi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE perawatan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  barang_id INT NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NULL,
  jenis VARCHAR(120) NOT NULL,
  deskripsi TEXT NULL,
  biaya DECIMAL(15,2) NOT NULL DEFAULT 0,
  teknisi VARCHAR(120) NULL,
  status ENUM('proses','selesai','batal') NOT NULL DEFAULT 'proses',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_perawatan_barang FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
  CONSTRAINT fk_perawatan_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  aksi VARCHAR(60) NOT NULL,
  tabel VARCHAR(80) NOT NULL,
  record_id INT NULL,
  keterangan TEXT NULL,
  ip_address VARCHAR(80) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Data Awal Pengguna (Password: admin123)
INSERT INTO users (nama, username, password, role, status) VALUES
('Administrator', 'admin', '$2y$12$niJHxZeQo4CoFFnB.lXkIea7uMkL.TxQFwcR9cMGX38Kmkn6tfA0S', 'admin', 'aktif'),
('Petugas Lab', 'petugas', '$2y$12$niJHxZeQo4CoFFnB.lXkIea7uMkL.TxQFwcR9cMGX38Kmkn6tfA0S', 'petugas', 'aktif'),
('Kepala Lab', 'kepalalab', '$2y$12$niJHxZeQo4CoFFnB.lXkIea7uMkL.TxQFwcR9cMGX38Kmkn6tfA0S', 'kepala_lab', 'aktif');

-- Data Master
INSERT INTO kategori (kode, nama, keterangan) VALUES
('KOM', 'Komputer dan Jaringan', 'Perangkat komputer, jaringan, dan periferal'),
('ELK', 'Elektronik', 'Peralatan elektronik laboratorium'),
('ATK', 'Alat Tulis dan Bahan Praktik', 'ATK dan bahan habis pakai'),
('MEB', 'Meubel', 'Meja, kursi, lemari, rak');

INSERT INTO ruangan (kode, nama, penanggung_jawab, lokasi) VALUES
('LAB-KOM-1', 'Laboratorium Komputer 1', 'Kepala Lab Komputer', 'Gedung Praktik'),
('LAB-KOM-2', 'Laboratorium Komputer 2', 'Kepala Lab Komputer', 'Gedung Praktik'),
('GUD-LAB', 'Gudang Laboratorium', 'Petugas Sarpras', 'Area Laboratorium');

INSERT INTO supplier (nama, kontak, telepon, alamat) VALUES
('Toko Komputer Soppeng', 'Admin Toko', '08xxxxxxxxxx', 'Kabupaten Soppeng'),
('Supplier Umum Sekolah', 'Bagian Penjualan', '08xxxxxxxxxx', 'Sulawesi Selatan');

INSERT INTO barang (kode_barang, nama_barang, kategori_id, ruangan_id, supplier_id, merk, spesifikasi, tahun_pengadaan, sumber_dana, kondisi, jumlah, satuan, stok_minimum, harga, status, keterangan, created_by) VALUES
('BRG202604280001', 'PC Desktop Praktikum', 1, 1, 1, 'Rakitan', 'Core i5, RAM 8GB, SSD 256GB, Monitor 19 inch', 2024, 'BOS', 'baik', 20, 'unit', 2, 6500000, 'aktif', 'Komputer untuk praktik siswa', 1),
('BRG202604280002', 'Router Mikrotik', 1, 1, 1, 'Mikrotik', 'RouterBoard untuk praktik jaringan', 2023, 'DAK', 'baik', 8, 'unit', 1, 950000, 'aktif', '', 1),
('BRG202604280003', 'Proyektor', 2, 2, 2, 'Epson', 'Proyektor kelas/lab', 2022, 'Komite', 'rusak_ringan', 2, 'unit', 1, 7200000, 'aktif', 'Satu unit perlu servis lampu', 1),
('BRG202604280004', 'Kabel UTP Cat6', 3, 3, 2, 'Belden', 'Kabel jaringan untuk praktik', 2025, 'BOS', 'baik', 12, 'roll', 2, 850000, 'aktif', '', 1);

INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id) VALUES
(1, CURDATE(), 'masuk', 'Stok awal', 20, 0, 1),
(2, CURDATE(), 'masuk', 'Stok awal', 8, 0, 1),
(3, CURDATE(), 'masuk', 'Stok awal', 2, 0, 1),
(4, CURDATE(), 'masuk', 'Stok awal', 12, 0, 1);
