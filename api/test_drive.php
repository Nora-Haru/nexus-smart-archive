<?php
// api/test_drive.php
// Diagnostik Raw Google Drive API
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/drive_service.php';

try {
    $drive = new GoogleDriveService();

    // Test 1: Cek identitas kredensial
    $creds = json_decode(file_get_contents(GOOGLE_CREDENTIALS), true);

    // Test 2: Ambil satu halaman raw data tanpa filter klasifikasi PHP
    $rawData = $drive->listFiles(DRIVE_FOLDER_ID);

    echo json_encode([
        'diagnostics_info' => [
            'target_folder_id' => DRIVE_FOLDER_ID,
            'active_service_account' => $creds['client_email'],
            'total_items_detected' => count($rawData['files'] ?? []),
        ],
        'raw_api_response' => $rawData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['fatal_error' => $e->getMessage()]);
}