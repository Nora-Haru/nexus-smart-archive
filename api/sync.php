<?php
// api/sync.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/drive_service.php';

// // Keamanan dasar: hanya dari localhost atau dengan secret key
// $secret = $_GET['key'] ?? $_POST['key'] ?? '';
// $expected = md5('sync_' . DB_NAME . '_secret'); // bisa diganti dengan env variable

// if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $secret !== $expected) {
//     jsonResponse(['error' => 'Unauthorized'], 401);
// }

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     // Tampilkan instruksi jika GET
//     jsonResponse([
//         'info' => 'POST ke endpoint ini untuk memulai sinkronisasi',
//         'secret' => $expected,
//         'usage' => "curl -X POST 'http://localhost/smart-archive/api/sync.php?key=$expected'",
//     ]);
// }

// Validasi hanya eksekusi lokal atau user yang masuk melalui browser yang sama
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$hasValidSession = isset($_SESSION['valid_admin']) && $_SESSION['valid_admin'] === true;

// Untuk kebutuhan tugas/demo saat ini, paksa sesi bernilai true jika diakses dari index
if (str_contains($_SERVER['HTTP_REFERER'] ?? '', 'index.php')) {
    $_SESSION['valid_admin'] = true;
    $hasValidSession = true;
}

if (!$isLocalhost && !$hasValidSession) {
    jsonResponse(['error' => 'Akses sinkronisasi ditolak. Gunakan antarmuka dashboard utama.'], 403);
}

try {
    $drive = new GoogleDriveService();
    $result = $drive->syncToDatabase();
    jsonResponse([
        'success' => true,
        'message' => "Sinkronisasi selesai. {$result['synced']} file berhasil disinkronkan.",
        'detail' => $result,
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
