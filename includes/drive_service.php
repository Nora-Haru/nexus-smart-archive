<?php
require_once __DIR__ . '/config.php';

// ============================================================
//  GOOGLE DRIVE SERVICE
//  Menggunakan Google Drive API v3 dengan Service Account
//
//  SETUP:
//  1. Buka https://console.cloud.google.com
//  2. Buat project baru → Enable "Google Drive API"
//  3. Buat Service Account → Download credentials.json
//  4. Share folder Google Drive ke email service account
//  5. Letakkan credentials.json di folder includes/
//  6. Set DRIVE_FOLDER_ID di config.php
// ============================================================
class GoogleDriveService
{

    private string $accessToken = '';
    private int $tokenExpiry = 0;

    // --------------------------------------------------------
    //  AUTH: Generate access token dari service account JWT
    // --------------------------------------------------------
    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiry - 60) {
            return $this->accessToken;
        }

        if (!file_exists(GOOGLE_CREDENTIALS)) {
            throw new Exception(
                "File credentials.json tidak ditemukan di {GOOGLE_CREDENTIALS}. Ikuti panduan setup di README.md"
            );
        }

        $creds = json_decode(file_get_contents(GOOGLE_CREDENTIALS), true);

        if ($creds['type'] !== 'service_account') {
            throw new Exception('credentials.json harus bertipe service_account');
        }

        $now = time();
        $jwt = $this->buildJWT($creds, $now);
        $resp = $this->httpPost('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $data = json_decode($resp, true);
        if (empty($data['access_token'])) {
            throw new Exception("Gagal mendapatkan access token: {$resp}");
        }

        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = $now + ($data['expires_in'] ?? 3600);
        return $this->accessToken;
    }

    // Bangun JWT untuk service account
    private function buildJWT(array $creds, int $now): string
    {
        $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64url(json_encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive', // <--- UBAH MENJADI SEPERTI INI
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $data = "$header.$payload";
        $key = openssl_pkey_get_private($creds['private_key']);
        openssl_sign($data, $sig, $key, OPENSSL_ALGO_SHA256);

        return "$data." . $this->base64url($sig);
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // --------------------------------------------------------
    //  API: Ambil daftar file dari folder Google Drive
    // --------------------------------------------------------
    public function listFiles(string $folderId = '', ?string $pageToken = null): array
    {
        $folderId = $folderId ?: DRIVE_FOLDER_ID;
        $token = $this->getAccessToken();

        // Menggunakan rawurlencode untuk kompatibilitas standar RFC 3986 API Google
        $q = rawurlencode("'$folderId' in parents and trashed=false");
        $fields = rawurlencode('nextPageToken,files(id,name,mimeType,size,createdTime,webViewLink,thumbnailLink,parents)');

        // Penambahan supportsAllDrives dan includeItemsFromAllDrives untuk kompatibilitas Shared Drive Workspace
        $url = "https://www.googleapis.com/drive/v3/files"
            . "?q=$q&fields=$fields&pageSize=1000&orderBy=name"
            . "&includeItemsFromAllDrives=true&supportsAllDrives=true";

        if ($pageToken) {
            $url .= '&pageToken=' . rawurlencode($pageToken);
        }

        $resp = $this->httpGet($url, $token);
        $data = json_decode($resp, true);

        if (isset($data['error'])) {
            throw new Exception('Drive API error: ' . ($data['error']['message'] ?? $resp));
        }

        return $data;
    }

    // Ambil SEMUA file (handle pagination otomatis)
    public function listAllFiles(string $folderId = ''): array
    {
        $all = [];
        $pageToken = null;

        do {
            $data = $this->listFiles($folderId, $pageToken);
            $all = [...$all, ...($data['files'] ?? [])];
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        return $all;
    }

    // --------------------------------------------------------
    //  SINKRONISASI: Simpan metadata file ke database
    // --------------------------------------------------------
    public function syncToDatabase(): array
    {
        $files = $this->listAllFiles();
        $pdo = getDB();
        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach ($files as $file) {
            try {
                $meta = $this->classifyFile($file);

                // --- TAMBAHKAN KONVERSI TANGGAL DI SINI ---
                $formattedDate = null;
                if (!empty($file['createdTime'])) {
                    $formattedDate = date('Y-m-d H:i:s', strtotime($file['createdTime']));
                }
                // ------------------------------------------

                $stmt = $pdo->prepare("
                    INSERT INTO files
                        (drive_id, nama_file, jenis, kategori, tahun, mime_type,
                         ukuran, thumbnail_url, drive_link, tanggal_upload)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        nama_file     = VALUES(nama_file),
                        jenis         = VALUES(jenis),
                        kategori      = VALUES(kategori),
                        tahun         = VALUES(tahun),
                        mime_type     = VALUES(mime_type),
                        ukuran        = VALUES(ukuran),
                        thumbnail_url = VALUES(thumbnail_url),
                        drive_link    = VALUES(drive_link),
                        tanggal_upload= VALUES(tanggal_upload),
                        updated_at    = CURRENT_TIMESTAMP
                ");

                $stmt->execute([
                    $file['id'],
                    $file['name'],
                    $meta['jenis'],
                    $meta['kategori'],
                    $meta['tahun'],
                    $file['mimeType'] ?? '',
                    $file['size'] ?? 0,

                    // --- GANTI BARIS INI ---
                    // Sebelumnya: $file['thumbnailLink'] ?? null,
                    "https://drive.google.com/thumbnail?id=" . $file['id'] . "&sz=w200",
                    // -----------------------

                    $file['webViewLink'] ?? '',
                    $formattedDate, // Ini adalah variabel tanggal yang sudah kita perbaiki sebelumnya
                ]);

                $synced++;
            } catch (Exception $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
                $skipped++;
            }
        }

        return [
            'total' => count($files),
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    // Klasifikasikan file berdasarkan nama dan mime type
    private function classifyFile(array $file): array
    {
        $name = strtolower($file['name'] ?? '');
        $mime = strtolower($file['mimeType'] ?? '');
        $tahun = $this->extractYear($file['name'], $file['createdTime'] ?? '');

        // Tentukan JENIS
        $isPhoto = str_contains($mime, 'image') ||
            preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|heic)$/i', $name);

        $jenis = $isPhoto ? 'Foto' : 'Dokumen';

        // Tentukan KATEGORI
        $kategori = $this->detectKategori($name, $jenis);

        return ['jenis' => $jenis, 'kategori' => $kategori, 'tahun' => $tahun];
    }

    private function detectKategori(string $name, string $jenis): string
    {
        $fotoMap = [
            'Wisuda' => ['wisuda', 'yudisium', 'toga', 'graduation'],
            'Olahraga' => ['olahraga', 'voli', 'futsal', 'basket', 'sepak bola', 'porseni', 'lomba'],
            'Seminar' => ['seminar', 'workshop', 'webinar', 'pelatihan', 'training'],
            'Keagamaan' => ['ramadhan', 'pengajian', 'isra', 'maulid', 'idul', 'hari raya', 'keagamaan'],
            'Organisasi' => ['osis', 'organisasi', 'rapat', 'pertemuan', 'himpunan', 'komunitas'],
        ];
        $dokMap = [
            'Proposal' => ['proposal', 'pengajuan', 'permohonan'],
            'Sertifikat' => ['sertifikat', 'sertif', 'piagam', 'penghargaan', 'juara', 'award'],
            'Laporan' => ['laporan', 'lkj', 'report', 'evaluasi', 'pertanggungjawaban'],
            'Surat' => ['surat', 'undangan', 'sk', 'pemberitahuan', 'pernyataan', 'berita acara'],
        ];

        $map = $jenis === 'Foto' ? $fotoMap : $dokMap;
        foreach ($map as $kat => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($name, $kw))
                    return $kat;
            }
        }

        return $jenis === 'Foto' ? 'Umum' : 'Lainnya';
    }

    private function extractYear(string $name, string $createdTime): int
    {
        if (preg_match('/\b(20\d{2})\b/', $name, $m))
            return (int) $m[1];
        if ($createdTime && preg_match('/^(\d{4})/', $createdTime, $m))
            return (int) $m[1];
        return (int) date('Y');
    }

    // --------------------------------------------------------
    //  HTTP helpers
    // --------------------------------------------------------
    private function httpGet(string $url, string $token): string
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n",
                'timeout' => 30,
                'ignore_errors' => true,
            ]
        ]);
        $result = @file_get_contents($url, false, $ctx);
        return $result === false ? '{"error":{"message":"HTTP request failed"}}' : $result;
    }

    private function httpPost(string $url, array $params): string
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($params),
                'timeout' => 30,
                'ignore_errors' => true,
            ]
        ]);
        $result = @file_get_contents($url, false, $ctx);
        return $result === false ? '{}' : $result;
    }

    // --------------------------------------------------------
    //  API: Upload file baru ke folder Google Drive
    // --------------------------------------------------------
    public function uploadFile(array $file): array
    {
        $token = $this->getAccessToken();
        $folderId = DRIVE_FOLDER_ID;

        $boundary = '-------' . md5(time());
        $filename = $file['name'];
        $mimeType = $file['type'];
        $fileContent = file_get_contents($file['tmp_name']);

        // Menyusun Metadata JSON untuk Google Drive API
        $metadata = json_encode([
            'name' => $filename,
            'parents' => [$folderId]
        ]);

        // Menyusun Body Multipart sesuai standar Google API
        $body = "--$boundary\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= $metadata . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: $mimeType\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= "--$boundary--\r\n";

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer $token\r\n" .
                    "Content-Type: multipart/related; boundary=$boundary\r\n" .
                    "Content-Length: " . strlen($body) . "\r\n",
                'content' => $body,
                'timeout' => 60,
                'ignore_errors' => true
            ]
        ]);

        // Endpoint Google Drive Upload API v3
        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,mimeType,size,webViewLink,thumbnailLink,createdTime';
        $resp = @file_get_contents($url, false, $ctx);
        $data = json_decode($resp, true);

        if (isset($data['error'])) {
            throw new Exception('Google Upload Error: ' . ($data['error']['message'] ?? $resp));
        }

        return $data;
    }
}
