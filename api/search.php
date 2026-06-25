<?php
// api/search.php — endpoint chatbot pencarian
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/decision_tree.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$query = trim($body['query'] ?? $_POST['query'] ?? '');
$tahun = isset($body['tahun']) ? (int) $body['tahun'] : null;
$sid = $body['session_id'] ?? session_id();

if (!$query) {
    jsonResponse(['error' => 'Query tidak boleh kosong'], 400);
}

$engine = new SearchEngine();
$result = $engine->search($query, $tahun);
$engine->logChat($sid, $query, $result);

// Format response untuk chatbot
$resp = [
    'query' => $query,
    'klasifikasi' => $result['klasifikasi'],
    'total' => $result['total'],
    'files' => array_map(fn($f) => [
        'id' => $f['id'],
        'nama_file' => $f['nama_file'],
        'jenis' => $f['jenis'],
        'kategori' => $f['kategori'],
        'tahun' => $f['tahun'],
        'drive_link' => $f['drive_link'],
        'thumbnail_url' => $f['thumbnail_url'],
        'ukuran_mb' => $f['ukuran'] > 0
            ? round($f['ukuran'] / 1048576, 2)
            : null,
    ], $result['files']),
    'pesan' => $result['total'] > 0
        ? "Ditemukan {$result['total']} file"
        . ($result['klasifikasi']['jenis'] ? " · {$result['klasifikasi']['jenis']}" : '')
        . ($result['klasifikasi']['kategori'] ? " · {$result['klasifikasi']['kategori']}" : '')
        : 'Tidak ada file yang ditemukan. Coba kata kunci lain.',
];

jsonResponse($resp);
