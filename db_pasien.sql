-- =========================================================
-- DATABASE SISTEM INFORMASI PASIEN / KLINIK
-- File: db_pasien.sql
-- Target: MySQL 8.0+
-- =========================================================

CREATE DATABASE IF NOT EXISTS db_pasien
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_pasien;

-- =========================================================
-- 1. MASTER DATA
-- =========================================================

CREATE TABLE IF NOT EXISTS poli (
    id_poli BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_poli VARCHAR(20) NOT NULL UNIQUE,
    nama_poli VARCHAR(100) NOT NULL,
    keterangan TEXT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS asuransi (
    id_asuransi BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_asuransi VARCHAR(120) NOT NULL,
    jenis_asuransi ENUM('BPJS', 'swasta', 'lainnya') NOT NULL DEFAULT 'lainnya',
    nomor_kontak VARCHAR(30) NULL,
    alamat TEXT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asuransi_nama_jenis (nama_asuransi, jenis_asuransi)
) ENGINE=InnoDB;

-- =========================================================
-- 2. PENGGUNA DAN RBAC
-- =========================================================

CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(120) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    no_hp VARCHAR(20) NULL,
    status ENUM('aktif', 'nonaktif', 'terkunci') NOT NULL DEFAULT 'aktif',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roles (
    id_role BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(50) NOT NULL UNIQUE,
    deskripsi VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    id_permission BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_permission VARCHAR(100) NOT NULL UNIQUE,
    nama_permission VARCHAR(120) NOT NULL,
    deskripsi VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengguna_roles (
    id_pengguna BIGINT UNSIGNED NOT NULL,
    id_role BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pengguna, id_role),
    CONSTRAINT fk_pengguna_roles_pengguna
      FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pengguna_roles_role
      FOREIGN KEY (id_role) REFERENCES roles(id_role)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    id_role BIGINT UNSIGNED NOT NULL,
    id_permission BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_role, id_permission),
    CONSTRAINT fk_role_permissions_role
      FOREIGN KEY (id_role) REFERENCES roles(id_role)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission
      FOREIGN KEY (id_permission) REFERENCES permissions(id_permission)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 3. DOKTER DAN JADWAL
-- =========================================================

CREATE TABLE IF NOT EXISTS dokter (
    id_dokter BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT UNSIGNED NULL UNIQUE,
    id_poli BIGINT UNSIGNED NOT NULL,
    kode_dokter VARCHAR(30) NOT NULL UNIQUE,
    nama_dokter VARCHAR(120) NOT NULL,
    no_sip VARCHAR(80) NULL UNIQUE,
    spesialisasi VARCHAR(100) NULL,
    no_hp VARCHAR(20) NULL,
    alamat TEXT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dokter_pengguna
      FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
      ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_dokter_poli
      FOREIGN KEY (id_poli) REFERENCES poli(id_poli)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal_dokter (
    id_jadwal BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_dokter BIGINT UNSIGNED NOT NULL,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    kuota_pasien SMALLINT UNSIGNED NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    CONSTRAINT fk_jadwal_dokter
      FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_jadwal_jam CHECK (jam_selesai > jam_mulai),
    UNIQUE KEY uq_jadwal_dokter_hari_jam (id_dokter, hari, jam_mulai, jam_selesai)
) ENGINE=InnoDB;

-- =========================================================
-- 4. PASIEN
-- =========================================================

CREATE TABLE IF NOT EXISTS pasien (
    nik CHAR(16) NOT NULL PRIMARY KEY,
    id_asuransi BIGINT UNSIGNED NULL,
    nomor_peserta_asuransi VARCHAR(60) NULL,
    nama_lengkap VARCHAR(120) NOT NULL,
    tempat_lahir VARCHAR(80) NULL,
    tanggal_lahir DATE NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    golongan_darah ENUM('A', 'B', 'AB', 'O', 'Tidak diketahui') NULL,
    no_hp VARCHAR(20) NULL,
    email VARCHAR(120) NULL,
    alamat TEXT NOT NULL,
    kelurahan VARCHAR(100) NULL,
    kecamatan VARCHAR(100) NULL,
    kota_kabupaten VARCHAR(100) NULL,
    provinsi VARCHAR(100) NULL,
    kode_pos VARCHAR(10) NULL,
    nama_kontak_darurat VARCHAR(120) NULL,
    no_kontak_darurat VARCHAR(20) NULL,
    hubungan_kontak_darurat VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_pasien_nik CHECK (nik REGEXP '^[0-9]{16}$'),
    CONSTRAINT fk_pasien_asuransi
      FOREIGN KEY (id_asuransi) REFERENCES asuransi(id_asuransi)
      ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_pasien_nama (nama_lengkap),
    INDEX idx_pasien_asuransi (id_asuransi)
) ENGINE=InnoDB;

-- =========================================================
-- 5. AKUN PASIEN
-- =========================================================

CREATE TABLE IF NOT EXISTS akun_pasien (
    nik CHAR(16) NOT NULL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email_login VARCHAR(120) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email_terverifikasi_at DATETIME NULL,
    no_hp_terverifikasi_at DATETIME NULL,
    status ENUM('aktif', 'nonaktif', 'terkunci') NOT NULL DEFAULT 'aktif',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_akun_pasien_pasien
      FOREIGN KEY (nik) REFERENCES pasien(nik)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 6. KUNJUNGAN / PENDAFTARAN ONLINE DAN LOKET
-- =========================================================

CREATE TABLE IF NOT EXISTS kunjungan (
    id_kunjungan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_kunjungan VARCHAR(40) NOT NULL UNIQUE,
    nik CHAR(16) NOT NULL,
    id_poli BIGINT UNSIGNED NOT NULL,
    id_dokter BIGINT UNSIGNED NULL,
    id_jadwal BIGINT UNSIGNED NULL,
    id_pengguna_pendaftaran BIGINT UNSIGNED NULL,
    tanggal_kunjungan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tanggal_antrean DATE NULL,
    sumber_pendaftaran ENUM('loket', 'online') NOT NULL DEFAULT 'loket',
    kode_booking VARCHAR(40) NULL UNIQUE,
    nomor_antrean SMALLINT UNSIGNED NULL,
    status_verifikasi ENUM('tidak_perlu', 'menunggu', 'terverifikasi', 'ditolak') NOT NULL DEFAULT 'tidak_perlu',
    catatan_verifikasi VARCHAR(255) NULL,
    jenis_kunjungan ENUM('baru', 'lama', 'kontrol') NOT NULL DEFAULT 'baru',
    keluhan TEXT NULL,
    status ENUM('menunggu', 'diperiksa', 'selesai', 'batal') NOT NULL DEFAULT 'menunggu',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_kunjungan_pasien
      FOREIGN KEY (nik) REFERENCES pasien(nik)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_kunjungan_poli
      FOREIGN KEY (id_poli) REFERENCES poli(id_poli)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_kunjungan_dokter
      FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
      ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_kunjungan_jadwal
      FOREIGN KEY (id_jadwal) REFERENCES jadwal_dokter(id_jadwal)
      ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_kunjungan_pengguna
      FOREIGN KEY (id_pengguna_pendaftaran) REFERENCES pengguna(id_pengguna)
      ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_kunjungan_tanggal (tanggal_kunjungan),
    INDEX idx_kunjungan_pasien_tanggal (nik, tanggal_kunjungan),
    INDEX idx_kunjungan_status (status),
    INDEX idx_kunjungan_antrean_harian (tanggal_antrean, id_poli, status),
    INDEX idx_kunjungan_online (sumber_pendaftaran, status_verifikasi, tanggal_kunjungan),
    UNIQUE KEY uq_antrean_jadwal_tanggal (id_jadwal, tanggal_antrean, nomor_antrean),
    CONSTRAINT chk_booking_online_lengkap CHECK (
      sumber_pendaftaran <> 'online' OR
      (kode_booking IS NOT NULL AND nomor_antrean IS NOT NULL AND tanggal_antrean IS NOT NULL)
    )
) ENGINE=InnoDB;

-- =========================================================
-- 7. REKAM MEDIS
-- =========================================================

CREATE TABLE IF NOT EXISTS rekam_medis (
    id_rekam_medis BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kunjungan BIGINT UNSIGNED NOT NULL UNIQUE,
    id_dokter BIGINT UNSIGNED NOT NULL,
    tekanan_darah VARCHAR(20) NULL,
    suhu_celsius DECIMAL(4,1) NULL,
    berat_kg DECIMAL(5,2) NULL,
    tinggi_cm DECIMAL(5,2) NULL,
    diagnosis TEXT NOT NULL,
    catatan_pemeriksaan TEXT NULL,
    tindakan_dan_terapi TEXT NULL,
    resep_obat TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rekam_medis_kunjungan
      FOREIGN KEY (id_kunjungan) REFERENCES kunjungan(id_kunjungan)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rekam_medis_dokter
      FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- 8. TINDAKAN / LAYANAN KLINIK
-- =========================================================

CREATE TABLE IF NOT EXISTS tindakan (
    id_tindakan BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_tindakan VARCHAR(30) NOT NULL UNIQUE,
    nama_tindakan VARCHAR(120) NOT NULL,
    keterangan TEXT NULL,
    tarif DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    CONSTRAINT chk_tindakan_tarif CHECK (tarif >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kunjungan_tindakan (
    id_kunjungan BIGINT UNSIGNED NOT NULL,
    id_tindakan BIGINT UNSIGNED NOT NULL,
    jumlah SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    tarif_saat_transaksi DECIMAL(12,2) NOT NULL,
    catatan TEXT NULL,
    PRIMARY KEY (id_kunjungan, id_tindakan),
    CONSTRAINT fk_kt_kunjungan
      FOREIGN KEY (id_kunjungan) REFERENCES kunjungan(id_kunjungan)
      ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_kt_tindakan
      FOREIGN KEY (id_tindakan) REFERENCES tindakan(id_tindakan)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_kt_jumlah CHECK (jumlah > 0),
    CONSTRAINT chk_kt_tarif CHECK (tarif_saat_transaksi >= 0)
) ENGINE=InnoDB;

-- =========================================================
-- 9. PEMBAYARAN
-- =========================================================

CREATE TABLE IF NOT EXISTS pembayaran (
    id_pembayaran BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kunjungan BIGINT UNSIGNED NOT NULL,
    id_pengguna_kasir BIGINT UNSIGNED NULL,
    nomor_pembayaran VARCHAR(40) NOT NULL UNIQUE,
    tanggal_pembayaran DATETIME NULL,
    total_tagihan DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    jumlah_dibayar DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    metode_pembayaran ENUM('tunai', 'transfer', 'asuransi', 'lainnya') NOT NULL DEFAULT 'tunai',
    status ENUM('belum_bayar', 'sebagian', 'lunas', 'dibatalkan') NOT NULL DEFAULT 'belum_bayar',
    catatan TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pembayaran_kunjungan
      FOREIGN KEY (id_kunjungan) REFERENCES kunjungan(id_kunjungan)
      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pembayaran_pengguna
      FOREIGN KEY (id_pengguna_kasir) REFERENCES pengguna(id_pengguna)
      ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_pembayaran_total CHECK (total_tagihan >= 0),
    CONSTRAINT chk_pembayaran_dibayar CHECK (jumlah_dibayar >= 0),
    INDEX idx_pembayaran_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 10. AUDIT LOG
-- =========================================================

CREATE TABLE IF NOT EXISTS audit_log (
    id_log BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT UNSIGNED NULL,
    aktivitas VARCHAR(100) NOT NULL,
    nama_tabel VARCHAR(64) NULL,
    id_data VARCHAR(100) NULL,
    detail TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_pengguna
      FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
      ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_pengguna_waktu (id_pengguna, created_at),
    INDEX idx_audit_tabel_data (nama_tabel, id_data)
) ENGINE=InnoDB;

-- =========================================================
-- 11. DATA AWAL RBAC
-- =========================================================

INSERT IGNORE INTO roles (nama_role, deskripsi) VALUES
('super_admin', 'Akses penuh seluruh sistem'),
('admin', 'Pengelolaan operasional klinik'),
('pendaftaran', 'Mengelola pasien dan antrean kunjungan'),
('dokter', 'Pemeriksaan dan rekam medis'),
('kasir', 'Mengelola tagihan dan pembayaran'),
('pasien', 'Pendaftaran online dan riwayat sendiri');

-- Samakan deskripsi role yang sudah ada sebelumnya (INSERT IGNORE tidak menimpa)
UPDATE roles SET deskripsi = 'Akses penuh seluruh sistem'    WHERE nama_role = 'super_admin';
UPDATE roles SET deskripsi = 'Pengelolaan operasional klinik' WHERE nama_role = 'admin';
UPDATE roles SET deskripsi = 'Mengelola pasien dan antrean kunjungan' WHERE nama_role = 'pendaftaran';
UPDATE roles SET deskripsi = 'Pemeriksaan dan rekam medis'    WHERE nama_role = 'dokter';
UPDATE roles SET deskripsi = 'Mengelola tagihan dan pembayaran' WHERE nama_role = 'kasir';

INSERT IGNORE INTO permissions (kode_permission, nama_permission, deskripsi) VALUES
('dashboard.view', 'Lihat dashboard', 'Melihat halaman dashboard'),
('poli.view', 'Lihat poli', 'Melihat data poli'),
('poli.manage', 'Kelola poli', 'Tambah, ubah, dan nonaktifkan poli'),
('pasien.view', 'Lihat pasien', 'Melihat data pasien'),
('pasien.create', 'Tambah pasien', 'Mendaftarkan pasien baru'),
('pasien.update', 'Ubah pasien', 'Mengubah data pasien'),
('pasien.delete', 'Hapus pasien', 'Menghapus data pasien sesuai kebijakan'),
('dokter.view', 'Lihat dokter', 'Melihat data dokter'),
('dokter.manage', 'Kelola dokter', 'Mengelola data dokter'),
('kunjungan.view', 'Lihat kunjungan', 'Melihat data kunjungan'),
('kunjungan.manage', 'Kelola kunjungan', 'Membuat dan mengubah pendaftaran'),
('pendaftaran_online.view', 'Lihat pendaftaran online', 'Melihat booking online'),
('pendaftaran_online.verify', 'Verifikasi pendaftaran online', 'Menerima atau menolak booking online'),
('pendaftaran_online.cancel', 'Batalkan pendaftaran online', 'Membatalkan booking online'),
('rekam_medis.view', 'Lihat rekam medis', 'Melihat rekam medis sesuai kewenangan'),
('rekam_medis.manage', 'Kelola rekam medis', 'Membuat dan mengubah rekam medis'),
('pembayaran.view', 'Lihat pembayaran', 'Melihat tagihan dan pembayaran'),
('pembayaran.manage', 'Kelola pembayaran', 'Mencatat dan mengelola pembayaran'),
('pengguna.manage', 'Kelola pengguna', 'Mengelola akun pengguna'),
('rbac.manage', 'Kelola RBAC', 'Mengelola role dan permission'),
('audit.view', 'Lihat audit log', 'Melihat catatan aktivitas sistem');

INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM roles r
CROSS JOIN permissions p
WHERE r.nama_role = 'super_admin';

INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM roles r
CROSS JOIN permissions p
WHERE r.nama_role = 'admin'
  AND p.kode_permission IN (
    'dashboard.view','poli.view','poli.manage',
    'pasien.view','pasien.create','pasien.update',
    'dokter.view','dokter.manage',
    'kunjungan.view','kunjungan.manage',
    'pendaftaran_online.view','pendaftaran_online.verify','pendaftaran_online.cancel',
    'rekam_medis.view','pembayaran.view'
  );

INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM roles r
CROSS JOIN permissions p
WHERE r.nama_role = 'pendaftaran'
  AND p.kode_permission IN (
    'dashboard.view','poli.view','pasien.view','pasien.create',
    'pasien.update','dokter.view','kunjungan.view','kunjungan.manage',
    'pendaftaran_online.view','pendaftaran_online.verify','pendaftaran_online.cancel'
  );

INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM roles r
CROSS JOIN permissions p
WHERE r.nama_role = 'dokter'
  AND p.kode_permission IN (
    'dashboard.view','poli.view','pasien.view',
    'kunjungan.view','rekam_medis.view','rekam_medis.manage'
  );

INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM roles r
CROSS JOIN permissions p
WHERE r.nama_role = 'kasir'
  AND p.kode_permission IN (
    'dashboard.view','pasien.view','kunjungan.view',
    'pembayaran.view','pembayaran.manage'
  );

INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
FROM roles r
CROSS JOIN permissions p
WHERE r.nama_role = 'pasien'
  AND p.kode_permission IN (
    'dashboard.view','pasien.view',
    'kunjungan.view','kunjungan.manage',
    'rekam_medis.view','pembayaran.view'
  );