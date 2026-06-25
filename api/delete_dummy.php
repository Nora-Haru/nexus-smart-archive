<?php
// api/delete_dummy.php — menghapus data dummy dari database
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

// Pastikan hanya menerima request POST demi keamanan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM files WHERE drive_id LIKE 'dummy_%'");
    $stmt->execute();
    $count = $stmt->rowCount();

    jsonResponse([
        'success' => true,
        'message' => "$count data demo berhasil dihapus."
    ]);
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(['error' => $e->getMessage()]);
}