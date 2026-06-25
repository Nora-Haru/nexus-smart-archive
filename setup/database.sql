-- ============================================================
--  Smart Media Archive — Setup Database
--  Jalankan file ini SEKALI saat pertama kali setup
--  mysql -u root -p < setup/database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS smart_archive
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE smart_archive;

-- Tabel utama penyimpanan metadata file
CREATE TABLE IF NOT EXISTS files (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    drive_id      VARCHAR(255) NOT NULL UNIQUE COMMENT 'ID file di Google Drive',
    nama_file     VARCHAR(500) NOT NULL,
    jenis         ENUM('Foto','Dokumen') NOT NULL,
    kategori      VARCHAR(100) NOT NULL,
    tahun         YEAR NOT NULL,
    mime_type     VARCHAR(100),
    ukuran        BIGINT UNSIGNED DEFAULT 0 COMMENT 'Ukuran dalam bytes',
    thumbnail_url VARCHAR(1000),
    drive_link    VARCHAR(1000) NOT NULL,
    folder_path   VARCHAR(500),
    tanggal_upload DATETIME,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jenis     (jenis),
    INDEX idx_kategori  (kategori),
    INDEX idx_tahun     (tahun),
    INDEX idx_jenis_kat (jenis, kategori),
    FULLTEXT idx_nama   (nama_file)
) ENGINE=InnoDB;

-- Tabel log percakapan chatbot
CREATE TABLE IF NOT EXISTS chat_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    pesan_user TEXT NOT NULL,
    respons    TEXT,
    jenis_pred VARCHAR(50),
    kat_pred   VARCHAR(100),
    hasil_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- Tabel konfigurasi keyword Decision Tree
CREATE TABLE IF NOT EXISTS dt_keywords (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword  VARCHAR(100) NOT NULL UNIQUE,
    jenis    ENUM('Foto','Dokumen') NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    bobot    TINYINT DEFAULT 1 COMMENT 'Bobot prioritas 1-10'
) ENGINE=InnoDB;

-- Seed data keyword Decision Tree
INSERT IGNORE INTO dt_keywords (keyword, jenis, kategori, bobot) VALUES
-- Foto kategori
('wisuda',      'Foto',    'Wisuda',      10),
('yudisium',    'Foto',    'Wisuda',       8),
('toga',        'Foto',    'Wisuda',       8),
('olahraga',    'Foto',    'Olahraga',    10),
('voli',        'Foto',    'Olahraga',     9),
('futsal',      'Foto',    'Olahraga',     9),
('basket',      'Foto',    'Olahraga',     9),
('porseni',     'Foto',    'Olahraga',     8),
('lomba',       'Foto',    'Olahraga',     6),
('seminar',     'Foto',    'Seminar',     10),
('workshop',    'Foto',    'Seminar',      9),
('webinar',     'Foto',    'Seminar',      9),
('keagamaan',   'Foto',    'Keagamaan',   10),
('ramadhan',    'Foto',    'Keagamaan',    9),
('pengajian',   'Foto',    'Keagamaan',    9),
('isra',        'Foto',    'Keagamaan',    8),
('organisasi',  'Foto',    'Organisasi',  10),
('osis',        'Foto',    'Organisasi',   9),
('rapat',       'Foto',    'Organisasi',   7),
-- Dokumen kategori
('proposal',    'Dokumen', 'Proposal',    10),
('sertifikat',  'Dokumen', 'Sertifikat',  10),
('sertif',      'Dokumen', 'Sertifikat',   8),
('piagam',      'Dokumen', 'Sertifikat',   8),
('juara',       'Dokumen', 'Sertifikat',   7),
('laporan',     'Dokumen', 'Laporan',     10),
('lkj',         'Dokumen', 'Laporan',      8),
('report',      'Dokumen', 'Laporan',      8),
('surat',       'Dokumen', 'Surat',       10),
('undangan',    'Dokumen', 'Surat',        9),
('sk',          'Dokumen', 'Surat',        7),
('berita acara','Dokumen', 'Surat',        7);

-- Tampilkan pesan sukses
SELECT 'Database berhasil dibuat!' AS status;
SELECT COUNT(*) AS total_keywords FROM dt_keywords;
