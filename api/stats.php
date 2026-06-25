<?php
// api/stats.php — statistik untuk dashboard
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

$pdo = getDB();

// Total file
$totals = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(jenis='Foto') as total_foto,
        SUM(jenis='Dokumen') as total_dokumen
    FROM files
")->fetch();

// Per kategori
$perKategori = $pdo->query("
    SELECT jenis, kategori, COUNT(*) as jumlah
    FROM files
    GROUP BY jenis, kategori
    ORDER BY jenis, jumlah DESC
")->fetchAll();

// Per tahun
$perTahun = $pdo->query("
    SELECT tahun, COUNT(*) as jumlah
    FROM files
    GROUP BY tahun
    ORDER BY tahun DESC
    LIMIT 10
")->fetchAll();

// --- TAMBAHKAN KODE INI ---
// Periode Waktu Jangka Pendek
$periodeRaw = $pdo->query("
    SELECT
        SUM(DATE(tanggal_upload) = CURDATE()) as hari_ini,
        SUM(YEARWEEK(tanggal_upload, 1) = YEARWEEK(CURDATE(), 1)) as minggu_ini,
        SUM(MONTH(tanggal_upload) = MONTH(CURDATE()) AND YEAR(tanggal_upload) = YEAR(CURDATE())) as bulan_ini
    FROM files
")->fetch();

$periode = [
    'hari_ini' => (int) ($periodeRaw['hari_ini'] ?? 0),
    'minggu_ini' => (int) ($periodeRaw['minggu_ini'] ?? 0),
    'bulan_ini' => (int) ($periodeRaw['bulan_ini'] ?? 0),
];
// -------------------------

// File terbaru
$terbaru = $pdo->query("
    SELECT nama_file, jenis, kategori, tahun, drive_link, thumbnail_url, tanggal_upload
    FROM files
    ORDER BY tanggal_upload DESC, created_at DESC
    LIMIT 8
")->fetchAll();

// Log statistik chatbot
$chatStats = $pdo->query("
    SELECT COUNT(*) as total_percakapan,
           AVG(hasil_count) as rata_hasil
    FROM chat_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch();

jsonResponse([
    'totals' => $totals,
    'per_kategori' => $perKategori,
    'per_tahun' => $perTahun,
    'periode' => $periode, // <--- TAMBAHKAN BARIS INI
    'terbaru' => $terbaru,
    'chat_stats' => $chatStats,
]);
