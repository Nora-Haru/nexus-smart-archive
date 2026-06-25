<?php
// api/upload.php — menangani upload berkas ke Google Drive dan MySQL
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/drive_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

if (empty($_FILES['file'])) {
    jsonResponse(['error' => 'File berkas tidak ditemukan.'], 400);
}

// Ambil input jenis dan kategori dari form
$jenis = $_POST['jenis'] ?? 'Foto';
$kategori = trim($_POST['kategori'] ?? '');

// Logika penentuan nilai default jika kategori dikosongkan
if (empty($kategori)) {
    $kategori = ($jenis === 'Foto') ? 'Umum' : 'Lainnya';
}

try {
    $drive = new GoogleDriveService();

    // 1. Unggah berkas ke Google Drive
    $gFile = $drive->uploadFile($_FILES['file']);

    // 2. Format data pelengkap untuk MySQL
    $tahun = (int) date('Y');
    $formattedDate = date('Y-m-d H:i:s');

    // 3. Masukkan record metadata baru ke tabel files
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO files 
            (drive_id, nama_file, jenis, kategori, tahun, mime_type, ukuran, thumbnail_url, drive_link, tanggal_upload)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $gFile['id'],
        $gFile['name'],
        $jenis,
        $kategori,
        $tahun,
        $gFile['mimeType'] ?? $_FILES['file']['type'],
        $gFile['size'] ?? $_FILES['file']['size'],
        $gFile['thumbnailLink'] ?? null,
        $gFile['webViewLink'] ?? '',
        $formattedDate
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Berkas berhasil disimpan di Google Drive & terindeks ke database.',
        'file' => $gFile['name']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(['error' => $e->getMessage()]);
}