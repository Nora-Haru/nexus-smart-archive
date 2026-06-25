<?php
// ============================================================
//  KONFIGURASI — sesuaikan dengan environment kamu
// ============================================================

// --- Database MySQL ---
const DB_HOST = 'localhost';
const DB_USER = 'root';         // ganti dengan user MySQL kamu
const DB_PASS = '';             // ganti dengan password MySQL kamu
const DB_NAME = 'smart_archive';

// --- Google Drive API ---
// Letakkan file credentials.json (service account) di folder ini
const GOOGLE_CREDENTIALS = __DIR__ . '/credentials.json';

// ID folder Google Drive yang akan disinkronisasi
// Contoh: https://drive.google.com/drive/folders/1ABC123XYZ  → ambil "1ABC123XYZ"
// https://drive.google.com/drive/folders/1a4rqp5cHVOlUcyUIYIaw3yPHG0b-4ia6?usp=sharing  → ambil "1a4rqp5cHVOlUcyUIYIaw3yPHG0b-4ia6"
const DRIVE_FOLDER_ID = '1a4rqp5cHVOlUcyUIYIaw3yPHG0b-4ia6';

// --- App ---
const APP_NAME = 'Nexus Archive';
const APP_VERSION = '1.0.0';

// ============================================================
//  KONEKSI DATABASE
// ============================================================
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ============================================================
//  HELPER
// ============================================================
function jsonResponse(mixed $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sanitize(string $str): string
{
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}
