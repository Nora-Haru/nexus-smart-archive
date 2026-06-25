<?php
// api/files.php — endpoint khusus pengambilan data mentah untuk browser
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

try {
    $pdo = getDB();
    // Mengambil semua file langsung tanpa melalui filter NLP Chatbot
    $stmt = $pdo->query("
        SELECT id, nama_file, jenis, kategori, tahun, mime_type, ukuran, drive_link, thumbnail_url
        FROM files 
        ORDER BY tahun DESC, nama_file ASC 
        LIMIT 500
    ");
    $files = $stmt->fetchAll();

    $formatted = array_map(function ($f) {
        $f['ukuran_mb'] = $f['ukuran'] > 0 ? round($f['ukuran'] / 1048576, 2) : null;
        return $f;
    }, $files);

    echo json_encode(['files' => $formatted]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}