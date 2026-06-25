<?php
require_once __DIR__ . '/config.php';

// ============================================================
//  DECISION TREE ENGINE
//  Mengklasifikasi input teks menjadi {jenis, kategori}
//  menggunakan pendekatan keyword-weighted tree
// ============================================================
class DecisionTree
{

    private array $keywords = [];
    private array $tree = [];

    public function __construct()
    {
        $this->loadKeywords();
        $this->buildTree();
    }

    // Muat keyword dari database
    private function loadKeywords(): void
    {
        $pdo = getDB();
        $rows = $pdo->query("SELECT keyword, jenis, kategori, bobot FROM dt_keywords ORDER BY bobot DESC")
            ->fetchAll();
        foreach ($rows as $r) {
            $this->keywords[] = $r;
        }
    }

    // Bangun struktur pohon keputusan sederhana:
    // Level 1 → tentukan JENIS (Foto / Dokumen)
    // Level 2 → tentukan KATEGORI
    private function buildTree(): void
    {
        $this->tree = [
            'Foto' => [],
            'Dokumen' => [],
        ];
        foreach ($this->keywords as $kw) {
            $this->tree[$kw['jenis']][$kw['kategori']][] = [
                'keyword' => $kw['keyword'],
                'bobot' => (int) $kw['bobot'],
            ];
        }
    }

    // Klasifikasi teks input → return ['jenis'=>..., 'kategori'=>..., 'score'=>...]
    public function classify(string $input): array
    {
        $input = mb_strtolower(trim($input));
        $tokens = $this->tokenize($input);
        $scores = [];

        // Hitung skor setiap node
        foreach ($this->tree as $jenis => $kategoriMap) {
            foreach ($kategoriMap as $kategori => $kwList) {
                foreach ($kwList as $kw) {
                    // Cek apakah keyword ada di input (exact substring)
                    foreach ($tokens as $token) {
                        similar_text($token, $kw['keyword'], $pct);
                        if ($pct >= 85 || str_contains($input, $kw['keyword'])) {
                            $key = "$jenis|$kategori";
                            $scores[$key] = ($scores[$key] ?? 0) + $kw['bobot'];
                            break;
                        }
                    }
                }
            }
        }

        if (empty($scores)) {
            return ['jenis' => null, 'kategori' => null, 'score' => 0];
        }

        // Ambil skor tertinggi
        arsort($scores);
        $best = array_key_first($scores);
        [$jenis, $kategori] = explode('|', $best);

        return [
            'jenis' => $jenis,
            'kategori' => $kategori,
            'score' => $scores[$best],
        ];
    }

    // Tokenisasi input
    private function tokenize(string $text): array
    {
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_unique($tokens);
    }

    // Ambil semua keyword untuk ditampilkan di UI
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    // Visualisasi tree untuk keperluan laporan
    public function getTreeStructure(): array
    {
        return $this->tree;
    }
}

// ============================================================
//  SEARCH ENGINE
// ============================================================
class SearchEngine
{

    private DecisionTree $dt;

    public function __construct()
    {
        $this->dt = new DecisionTree();
    }

    public function search(string $query, ?int $tahun = null, int $limit = 20): array
    {
        $result = $this->dt->classify($query);

        if (!$result['jenis']) {
            // Fallback: fulltext search di nama file
            return $this->fulltextSearch($query, $limit);
        }

        $pdo = getDB();
        $sql = "SELECT * FROM files WHERE jenis = ? AND kategori = ?";
        $params = [$result['jenis'], $result['kategori']];

        if ($tahun) {
            $sql .= " AND tahun = ?";
            $params[] = $tahun;
        }

        // Cek apakah query juga menyebut tahun secara eksplisit
        if (!$tahun && preg_match('/\b(20\d{2})\b/', $query, $m)) {
            $sql .= " AND tahun = ?";
            $params[] = (int) $m[1];
        }

        $sql .= " ORDER BY tahun DESC, nama_file ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $files = $stmt->fetchAll();

        return [
            'klasifikasi' => $result,
            'files' => $files,
            'total' => count($files),
        ];
    }

    private function fulltextSearch(string $query, int $limit): array
    {
        $pdo = getDB();
        $like = "%{$query}%";
        $stmt = $pdo->prepare(
            "SELECT * FROM files
             WHERE nama_file LIKE ? OR kategori LIKE ?
             ORDER BY tahun DESC LIMIT ?"
        );
        $stmt->execute([$like, $like, $limit]);
        return [
            'klasifikasi' => ['jenis' => null, 'kategori' => null, 'score' => 0],
            'files' => $stmt->fetchAll(),
            'total' => $stmt->rowCount(),
        ];
    }

    // Simpan log percakapan
    public function logChat(string $sessionId, string $pesan, array $hasil): void
    {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO chat_logs (session_id, pesan_user, jenis_pred, kat_pred, hasil_count)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $sessionId,
            $pesan,
            $hasil['klasifikasi']['jenis'],
            $hasil['klasifikasi']['kategori'],
            $hasil['total'],
        ]);
    }
}
