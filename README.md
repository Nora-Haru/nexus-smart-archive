# Nexus Archive
### Sistem Manajemen Arsip Media Cerdas — PHP + MySQL + Google Drive API

Nexus Archive adalah sistem manajemen aset digital dua arah (*Two-Way System*) yang mengintegrasikan penyimpanan cloud Google Drive dengan Kecerdasan Buatan (Natural Language Processing & Decision Tree) untuk mengklasifikasikan dan mencari dokumen secara cerdas melalui antarmuka chatbot.

---

## 📦 Struktur Proyek

```text
nexus-archive/
├── index.php               ← Halaman utama (UI, Dashboard, Chatbot)
├── php.ini                 ← Konfigurasi batas ukuran upload file PHP
├── README.md               ← Dokumentasi proyek ini
├── includes/
│   ├── config.php          ← Konfigurasi database & ID Folder (WAJIB DIEDIT)
│   ├── credentials.json    ← File Kredensial Service Account Google
│   ├── decision_tree.php   ← Engine AI NLP & Decision Tree
│   └── drive_service.php   ← Class penghubung Google Drive API v3
├── api/
│   ├── delete_dummy.php    ← Endpoint hapus data testing
│   ├── files.php           ← Endpoint data tabel Semua File
│   ├── search.php          ← POST endpoint chatbot pencarian
│   ├── seed_dummy.php      ← Generator data testing (dummy)
│   ├── stats.php           ← GET endpoint statistik & analitik dashboard
│   ├── sync.php            ← POST endpoint sinkronisasi otomatis
│   ├── test_drive.php      ← Diagnostik API Google Drive
│   └── upload.php          ← POST endpoint unggah file ke Drive
├── assets/
│   ├── css/style.css       ← Stylesheet utama (Vanilla CSS)
│   └── js/app.js           ← Logika interaktif Frontend (AJAX/Fetch)
└── setup/
    └── database.sql        ← Script setup struktur database
