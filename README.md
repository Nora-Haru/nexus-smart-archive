# Smart Media Archive Assistant
### Proyek Pengantar Kecerdasan Buatan — PHP + MySQL + Google Drive API

---

## 📦 Struktur Proyek

```
smart-archive/
├── index.php                  ← Halaman utama (dashboard + chatbot)
├── includes/
│   ├── config.php             ← Konfigurasi database & API (WAJIB DIEDIT)
│   ├── decision_tree.php      ← Engine Decision Tree + SearchEngine
│   └── drive_service.php      ← Google Drive API service
├── api/
│   ├── search.php             ← POST endpoint chatbot pencarian
│   ├── stats.php              ← GET endpoint statistik dashboard
│   ├── sync.php               ← POST endpoint sinkronisasi Drive
│   └── seed_dummy.php         ← Isi data dummy (HAPUS saat produksi)
├── assets/
│   ├── css/style.css          ← Stylesheet utama
│   └── js/app.js              ← JavaScript frontend
├── setup/
│   └── database.sql           ← Script setup database
└── README.md
```

---

## 🚀 Cara Menjalankan

### Persyaratan
- PHP 8.1+ (dengan ekstensi: pdo_mysql, openssl, json)
- MySQL 5.7+ atau MariaDB 10.4+
- Web server: Apache/Nginx atau `php -S localhost:8000`

---

### Langkah 1 — Setup Database

```bash
# Masuk ke MySQL dan jalankan script SQL
mysql -u root -p < setup/database.sql

# Atau buka phpMyAdmin dan import file setup/database.sql
```

---

### Langkah 2 — Konfigurasi Aplikasi

Edit file `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // user MySQL kamu
define('DB_PASS', '');              // password MySQL kamu
define('DB_NAME', 'smart_archive');

define('DRIVE_FOLDER_ID', 'GANTI_INI'); // ID folder Google Drive kamu
```

---

### Langkah 3 — Jalankan Server

```bash
cd smart-archive
php -S localhost:8000
```

Buka browser: **http://localhost:8000**

---

### Langkah 4 — Isi Data Demo (Testing)

Klik tombol **+ Data Demo** di pojok kanan atas untuk mengisi 50+ data dummy.
Setelah berhasil, coba chatbot dengan mengetik: `cari foto wisuda`

---

## 🔑 Setup Google Drive API (Untuk Data Asli)

### A. Buat Project di Google Cloud Console

1. Buka https://console.cloud.google.com
2. Klik **New Project** → beri nama proyek
3. Di menu kiri: **APIs & Services** → **Library**
4. Cari **Google Drive API** → klik **Enable**

### B. Buat Service Account

1. **APIs & Services** → **Credentials**
2. Klik **Create Credentials** → **Service Account**
3. Isi nama service account → klik **Create and Continue**
4. Role: pilih **Viewer** → klik **Done**
5. Klik service account yang baru dibuat
6. Tab **Keys** → **Add Key** → **Create new key** → **JSON**
7. File `credentials.json` akan terunduh otomatis

### C. Share Folder Google Drive

1. Buka folder Google Drive yang berisi foto/dokumen kamu
2. Klik **Share**
3. Masukkan **email service account** (ada di credentials.json, field `client_email`)
   Contoh: `nama@nama-project.iam.gserviceaccount.com`
4. Berikan akses **Viewer**

### D. Ambil Folder ID

Dari URL folder Drive:
```
https://drive.google.com/drive/folders/1a4rqp5cHVOlUcyUIYIaw3yPHG0b-4ia6?usp=sharing
                                        ↑ ini adalah FOLDER_ID
```

### E. Letakkan credentials.json

```bash
cp ~/Downloads/credentials.json smart-archive/includes/credentials.json
```

### F. Sinkronisasi

Klik tombol **↻ Sinkronisasi Drive** di aplikasi, atau jalankan:

```bash
curl -X POST "http://localhost:8000/api/sync.php?key=<SYNC_KEY>"
# SYNC_KEY bisa dilihat di halaman Setup dalam aplikasi
```

---

## 🌳 Cara Kerja Decision Tree

```
Input: "cari foto wisuda 2026"
         ↓
    Tokenisasi & lowercase
         ↓
  Cocokkan dengan keyword DB
  ┌─────────────────────────────────┐
  │  "wisuda" → Foto · Wisuda (10)  │  ← skor tertinggi
  │  "foto"   → Foto · (jenis)  (5) │
  └─────────────────────────────────┘
         ↓
  Query: SELECT * FROM files
         WHERE jenis='Foto'
           AND kategori='Wisuda'
           AND tahun=2026
         ↓
  Kembalikan hasil ke chatbot
```

Kamu bisa tambah keyword baru langsung di tabel `dt_keywords` di MySQL:
```sql
INSERT INTO dt_keywords (keyword, jenis, kategori, bobot)
VALUES ('graduation', 'Foto', 'Wisuda', 9);
```

---

## 📋 Luaran yang Dikumpulkan

| No | Luaran | File |
|----|--------|------|
| 1 | Source Code | Semua file di folder ini |
| 2 | Dataset | Tabel `files` di MySQL (export CSV) |
| 3 | Dashboard | Buka `http://localhost:8000` |
| 4 | Video Demo | Rekam layar saat demo chatbot |
| 5 | Laporan | Buat dokumen Word/PDF terpisah |

---

## ❓ Troubleshooting

| Error | Solusi |
|-------|--------|
| `DB connection failed` | Periksa DB_HOST, DB_USER, DB_PASS di config.php |
| `credentials.json not found` | Letakkan file di `includes/credentials.json` |
| `Gagal mendapatkan access token` | Pastikan Google Drive API sudah di-enable |
| File tidak muncul setelah sync | Pastikan folder sudah di-share ke email service account |
| `Access denied` saat sync | Gunakan SYNC_KEY yang tertera di halaman Setup |
