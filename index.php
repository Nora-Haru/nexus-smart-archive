<?php
session_start();
require_once 'includes/config.php';

// Coba ambil statistik cepat untuk server-side render awal
$stats = ['total' => 0, 'total_foto' => 0, 'total_dokumen' => 0];
try {
  $pdo = getDB();
  $stats = $pdo->query("SELECT COUNT(*) as total, SUM(jenis='Foto') as total_foto, SUM(jenis='Dokumen') as total_dokumen FROM files")->fetch();
} catch (Exception $e) {
  // DB belum di-setup, tampilkan tetap
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <div class="app-shell">

    <!-- ======== TOPBAR ======== -->
    <header class="topbar">
      <a class="topbar-brand" href="#">
        <div class="logo-icon">🗂</div>
        <span><?= APP_NAME ?></span>
      </a>
      <div class="topbar-actions">
        <div class="auto-sync-wrapper" title="Otomatis sinkronisasi setiap 1 menit">
          <label class="toggle-switch">
            <input type="checkbox" id="auto-sync-toggle">
            <span class="slider"></span>
          </label>
          <span style="font-size: 13px; color: var(--text-2); font-weight: 500;">Auto-Sync</span>
        </div>

        <button class="btn-sync" id="btn-seed" onclick="seedDummy()" title="Isi data dummy untuk testing"
          style="background:#16a34a;">
          + Data Demo
        </button>

        <button class="btn-sync" id="btn-delete-dummy" onclick="deleteDummy()" title="Hapus semua data dummy"
          style="background:#dc2626;">
          🗑️ Hapus Demo
        </button>

        <button class="btn-sync" id="btn-sync"
          style="background:var(--bg-input);border:1px solid var(--border);color:var(--text-2)" onclick="syncDrive()">
          ↻ Sinkronisasi Drive
        </button>
      </div>
    </header>

    <!-- ======== SIDEBAR ======== -->
    <nav class="sidebar">
      <div class="sidebar-label">Menu</div>
      <button class="nav-item active" data-view="dashboard" onclick="showView('dashboard')">
        <span class="nav-icon">📊</span> Dashboard
      </button>
      <button class="nav-item" data-view="chatbot" onclick="showView('chatbot')">
        <span class="nav-icon">💬</span> Chatbot Pencarian
      </button>
      <button class="nav-item" data-view="files" onclick="showView('files')">
        <span class="nav-icon">📁</span> Semua File
      </button>
      <button class="nav-item" data-view="tree" onclick="showView('tree')">
        <span class="nav-icon">🌳</span> Decision Tree
      </button>
      <button class="nav-item" data-view="setup" onclick="showView('setup')">
        <span class="nav-icon">⚙️</span> Setup & Panduan
      </button>

      <div style="margin-top:auto;padding:1rem 12px;border-top:1px solid var(--border);margin-top:2rem">
        <div style="font-size:12px;color:var(--text-3)">Versi <?= APP_VERSION ?></div>
        <div style="font-size:12px;color:var(--text-3);margin-top:4px">
          <?php
          $statusDB = true;
          try {
            getDB();
          } catch (Exception $e) {
            $statusDB = false;
          }
          ?>
          DB: <span style="color:<?= $statusDB ? '#22c55e' : '#ef4444' ?>">
            <?= $statusDB ? '● Online' : '● Offline' ?>
          </span>
        </div>
      </div>
    </nav>

    <!-- ======== MAIN ======== -->
    <main class="main-content">

      <!-- ===== VIEW: DASHBOARD ===== -->
      <div id="view-dashboard" class="view active">
        <div class="stat-grid">
          <div class="stat-card blue">
            <div class="stat-label">Total File</div>
            <div class="stat-val" id="stat-total"><?= (int) $stats['total'] ?></div>
            <div class="stat-sub">Foto &amp; Dokumen</div>
          </div>
          <div class="stat-card green">
            <div class="stat-label">Total Foto</div>
            <div class="stat-val" id="stat-foto"><?= (int) $stats['total_foto'] ?></div>
            <div class="stat-sub">5 kategori</div>
          </div>
          <div class="stat-card amber">
            <div class="stat-label">Total Dokumen</div>
            <div class="stat-val" id="stat-dok"><?= (int) $stats['total_dokumen'] ?></div>
            <div class="stat-sub">4 kategori</div>
          </div>
          <div class="stat-card red">
            <div class="stat-label">Kata Kunci DT</div>
            <div class="stat-val">
              <?php
              try {
                echo (int) getDB()->query("SELECT COUNT(*) FROM dt_keywords")->fetchColumn();
              } catch (Exception $e) {
                echo '–';
              }
              ?>
            </div>
            <div class="stat-sub">Decision Tree</div>
          </div>
        </div>

        <div class="two-col" style="margin-bottom:20px">
          <div class="card">
            <div class="section-header">
              <div class="section-title">📸 Kategori Foto</div>
            </div>
            <div id="bars-foto">
              <div style="color:var(--text-3);font-size:13px">Memuat...</div>
            </div>
          </div>
          <div class="card">
            <div class="section-header">
              <div class="section-title">📄 Kategori Dokumen</div>
            </div>
            <div id="bars-dok">
              <div style="color:var(--text-3);font-size:13px">Memuat...</div>
            </div>
          </div>
        </div>

        <div class="two-col">
          <div class="card" style="display: flex; flex-direction: column;">
            <div class="section-header" style="margin-bottom: 1rem;">
              <div class="section-title">📅 Periode Waktu</div>
            </div>

            <!-- Mini Grid untuk Hari, Minggu, Bulan -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
              <div
                style="background: var(--bg-input); padding: 14px 10px; border-radius: var(--radius-sm); text-align: center; border: 1px solid var(--border);">
                <div
                  style="font-size: 11px; color: var(--text-3); text-transform: uppercase; font-weight: 600; margin-bottom: 6px; letter-spacing: 0.05em;">
                  Hari Ini</div>
                <div id="stat-hari" style="font-size: 22px; font-weight: 700; color: var(--text); line-height: 1;">0
                </div>
              </div>
              <div
                style="background: var(--bg-input); padding: 14px 10px; border-radius: var(--radius-sm); text-align: center; border: 1px solid var(--border);">
                <div
                  style="font-size: 11px; color: var(--text-3); text-transform: uppercase; font-weight: 600; margin-bottom: 6px; letter-spacing: 0.05em;">
                  Minggu Ini</div>
                <div id="stat-minggu"
                  style="font-size: 22px; font-weight: 700; color: var(--accent-2); line-height: 1;">0</div>
              </div>
              <div
                style="background: var(--bg-input); padding: 14px 10px; border-radius: var(--radius-sm); text-align: center; border: 1px solid var(--border);">
                <div
                  style="font-size: 11px; color: var(--text-3); text-transform: uppercase; font-weight: 600; margin-bottom: 6px; letter-spacing: 0.05em;">
                  Bulan Ini</div>
                <div id="stat-bulan" style="font-size: 22px; font-weight: 700; color: var(--green); line-height: 1;">0
                </div>
              </div>
            </div>

            <div
              style="font-size: 12px; color: var(--text-3); text-transform: uppercase; font-weight: 600; margin-bottom: 12px; letter-spacing: 0.05em;">
              Distribusi Per Tahun</div>

            <!-- Grafik Bar Per Tahun -->
            <div id="bars-tahun" style="flex: 1; overflow-y: auto;">
              <div style="color:var(--text-3);font-size:13px">Memuat...</div>
            </div>
          </div>
          <div class="card" style="overflow:hidden">
            <div class="section-header">
              <div class="section-title">🕐 File Terbaru</div>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Tahun</th>
                    <th>Link</th>
                  </tr>
                </thead>
                <tbody id="recent-files"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== VIEW: CHATBOT ===== -->
      <div id="view-chatbot" class="view">
        <div class="chat-wrap">
          <div class="chat-panel">
            <div class="chat-header">
              <div class="status-dot"></div>
              <div class="chat-header-title">Nexus Archive Assistant</div>
              <div style="margin-left:auto;font-size:12px;color:var(--text-3)">Decision Tree · MySQL</div>
            </div>
            <div class="chat-messages" id="chat-messages"></div>
            <div class="chat-input-area">
              <div class="chat-input-row">
                <input type="text" id="chat-input" placeholder="Ketik pencarian... contoh: cari foto wisuda" />
                <button class="btn-send" id="btn-send" onclick="sendChat()">
                  Kirim ➤
                </button>
              </div>
              <div class="suggestions" id="chat-suggestions"></div>
            </div>
          </div>

          <!-- Panel samping: info Decision Tree -->
          <div class="info-panel">
            <div class="info-card">
              <div class="info-card-title">🌳 Struktur Decision Tree</div>
              <div class="tree-node level-1">
                <div class="tree-dot" style="background:#5b6ef5"></div> Foto
              </div>
              <?php foreach (['Wisuda', 'Olahraga', 'Seminar', 'Keagamaan', 'Organisasi'] as $k): ?>
                <div class="tree-node level-2">
                  <div class="tree-dot" style="background:#22c55e"></div> <?= $k ?>
                </div>
              <?php endforeach; ?>
              <div class="tree-node level-1" style="margin-top:8px">
                <div class="tree-dot" style="background:#f59e0b"></div> Dokumen
              </div>
              <?php foreach (['Proposal', 'Sertifikat', 'Laporan', 'Surat'] as $k): ?>
                <div class="tree-node level-2">
                  <div class="tree-dot" style="background:#ef4444"></div> <?= $k ?>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="info-card">
              <div class="info-card-title">💡 Contoh Perintah</div>
              <?php $contoh = [
                'cari foto wisuda',
                'cari foto voli putri',
                'cari sertifikat juara',
                'cari proposal ramadhan',
                'cari laporan 2026',
                'cari surat undangan',
              ];
              foreach ($contoh as $c): ?>
                <div style="font-size:13px;padding:5px 0;border-bottom:1px solid var(--border);color:var(--text-3)">
                  <span style="color:var(--accent-2)">›</span> <?= $c ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== VIEW: FILE BROWSER ===== -->
      <div id="view-files" class="view">
        <div class="section-header" style="margin-bottom:1.25rem">
          <div class="section-title">📁 Semua File</div>
        </div>

        <div class="two-col" style="margin-bottom: 20px;">

          <div class="card" style="border-color: var(--border-2); display: flex; flex-direction: column;">
            <div style="font-weight: 600; margin-bottom: 10px; font-size: 15px; color: var(--text);">
              📤 Upload Otomatis (via App)
            </div>
            <div style="font-size: 13px; color: var(--text-2); margin-bottom: 16px; line-height: 1.5;">
              Cocok untuk file berukuran kecil. Sistem akan mengunggah dan mengindeks file secara otomatis ke dalam
              aplikasi.
            </div>

            <form id="upload-form" onsubmit="handleFormUpload(event)"
              style="display: flex; flex-direction: column; gap: 10px; margin-top: auto;">
              <input type="file" id="upload-file" required
                style="background: var(--bg-input); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: var(--radius-sm); font-size: 13px; width: 100%; outline: none;">

              <div style="display: flex; gap: 10px;">
                <select id="upload-jenis"
                  style="width: 120px; background: var(--bg-input); border: 1px solid var(--border); color: var(--text); padding: 8px; border-radius: var(--radius-sm); font-size: 13px; outline: none;">
                  <option value="Foto">🖼️ Foto</option>
                  <option value="Dokumen">📄 Dokumen</option>
                </select>
                <input type="text" id="upload-kategori" placeholder="Kategori (opsional)"
                  style="flex: 1; background: var(--bg-input); border: 1px solid var(--border); color: var(--text); padding: 8px 12px; border-radius: var(--radius-sm); font-size: 13px; outline: none;">
              </div>

              <button type="submit" class="btn-sync"
                style="background: var(--accent); padding: 10px; width: 100%; justify-content: center; font-size: 14px; margin-top: 4px;">
                Unggah Berkas
              </button>
            </form>
          </div>

          <div class="card"
            style="border-color: var(--accent); background: linear-gradient(145deg, var(--bg-card), rgba(91, 110, 245, 0.05)); display: flex; flex-direction: column;">
            <div
              style="font-weight: 600; margin-bottom: 10px; font-size: 15px; color: var(--text); display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 18px;">☁️</span> Upload Manual (File Besar)
            </div>
            <div style="font-size: 13px; color: var(--text-2); margin-bottom: 16px; line-height: 1.5;">
              Solusi untuk file berukuran besar atau jika API terkena limit kuota. Silakan unggah berkas langsung ke
              Google Drive, lalu klik <strong>↻ Sinkronisasi Drive</strong> di kanan atas agar terindeks.
            </div>

            <a href="https://drive.google.com/drive/folders/<?= DRIVE_FOLDER_ID ?>" target="_blank"
              rel="noopener noreferrer" class="btn-drive"
              style="margin-top: auto; justify-content: center; width: 100%;">
              <div class="btn-drive-icon">📁</div>
              <span>Buka Google Drive ↗</span>
            </a>
          </div>

        </div>

        <div class="filter-bar">
          <input type="text" id="filter-nama" placeholder="🔍 Cari nama file..." oninput="filterFiles()">
          <select id="filter-jenis" onchange="filterFiles()">
            <option value="">Semua Jenis</option>
            <option value="Foto">Foto</option>
            <option value="Dokumen">Dokumen</option>
          </select>
          <select id="filter-kat" onchange="filterFiles()">
            <option value="">Semua Kategori</option>
            <?php foreach (['Wisuda', 'Olahraga', 'Seminar', 'Keagamaan', 'Organisasi', 'Proposal', 'Sertifikat', 'Laporan', 'Surat'] as $k): ?>
              <option value="<?= $k ?>"><?= $k ?></option>
            <?php endforeach; ?>
          </select>
          <select id="filter-tahun" onchange="filterFiles()">
            <option value="">Semua Tahun</option>
            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
              <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="card" style="padding:0;overflow:hidden">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Nama File</th>
                  <th>Jenis</th>
                  <th>Kategori</th>
                  <th>Tahun</th>
                  <th>Ukuran</th>
                  <th>Link</th>
                </tr>
              </thead>
              <tbody id="file-tbody">
                <tr>
                  <td colspan="6" style="text-align:center;padding:2rem;color:var(--text-3)">Memuat data...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== VIEW: DECISION TREE ===== -->
      <div id="view-tree" class="view">
        <div class="section-title" style="margin-bottom:1.5rem">🌳 Visualisasi Decision Tree</div>

        <!-- SVG Diagram -->
        <div class="card" style="margin-bottom:20px;text-align:center;overflow-x:auto">
          <svg viewBox="0 0 720 420" style="width:100%;max-width:720px;font-family:Inter,sans-serif">
            <!-- Root -->
            <rect x="285" y="20" width="150" height="44" rx="8" fill="#5b6ef5" />
            <text x="360" y="47" text-anchor="middle" fill="white" font-size="14" font-weight="600">Input
              Pengguna</text>

            <!-- Level 1 branches -->
            <line x1="360" y1="64" x2="180" y2="130" stroke="#3d4460" stroke-width="1.5" />
            <line x1="360" y1="64" x2="540" y2="130" stroke="#3d4460" stroke-width="1.5" />

            <!-- Foto node -->
            <rect x="100" y="130" width="160" height="44" rx="8" fill="#22c55e" />
            <text x="180" y="157" text-anchor="middle" fill="white" font-size="14" font-weight="600">📸 Foto</text>

            <!-- Dokumen node -->
            <rect x="460" y="130" width="160" height="44" rx="8" fill="#f59e0b" />
            <text x="540" y="157" text-anchor="middle" fill="white" font-size="14" font-weight="600">📄 Dokumen</text>

            <!-- Foto branches -->
            <?php
            $fotoKat = ['Wisuda', 'Olahraga', 'Seminar', 'Keagamaan', 'Organisasi'];
            $fotoX = [20, 85, 150, 215, 280];
            foreach ($fotoKat as $i => $k):
              $x = $fotoX[$i];
              ?>
              <line x1="180" y1="174" x2="<?= $x + 50 ?>" y2="270" stroke="#3d4460" stroke-width="1" />
              <rect x="<?= $x ?>" y="270" width="100" height="36" rx="6" fill="#1a2e1a" />
              <rect x="<?= $x ?>" y="270" width="100" height="3" rx="1" fill="#22c55e" />
              <text x="<?= $x + 50 ?>" y="292" text-anchor="middle" fill="#4ade80" font-size="11"><?= $k ?></text>
            <?php endforeach; ?>

            <!-- Dokumen branches -->
            <?php
            $dokKat = ['Proposal', 'Sertifikat', 'Laporan', 'Surat'];
            $dokX = [400, 470, 540, 610];
            foreach ($dokKat as $i => $k):
              $x = $dokX[$i];
              ?>
              <line x1="540" y1="174" x2="<?= $x + 40 ?>" y2="270" stroke="#3d4460" stroke-width="1" />
              <rect x="<?= $x ?>" y="270" width="90" height="36" rx="6" fill="#2a1e0a" />
              <rect x="<?= $x ?>" y="270" width="90" height="3" rx="1" fill="#f59e0b" />
              <text x="<?= $x + 45 ?>" y="292" text-anchor="middle" fill="#fbbf24" font-size="11"><?= $k ?></text>
            <?php endforeach; ?>

            <!-- Level 3: DB Query -->
            <rect x="245" y="348" width="230" height="44" rx="8" fill="#1a1d27" stroke="#3d4460" stroke-width="1" />
            <text x="360" y="370" text-anchor="middle" fill="#9aa0b8" font-size="12">Query MySQL</text>
            <text x="360" y="386" text-anchor="middle" fill="#9aa0b8" font-size="12">WHERE jenis=? AND
              kategori=?</text>
            <line x1="360" y1="306" x2="360" y2="348" stroke="#3d4460" stroke-width="1.5" stroke-dasharray="4" />
          </svg>
        </div>

        <!-- Tabel keyword -->
        <div class="card" style="padding:0;overflow:hidden">
          <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border)">
            <div class="section-title">Daftar Keyword Decision Tree</div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Keyword</th>
                  <th>Jenis</th>
                  <th>Kategori</th>
                  <th>Bobot</th>
                </tr>
              </thead>
              <tbody>
                <?php
                try {
                  $rows = getDB()->query("SELECT keyword, jenis, kategori, bobot FROM dt_keywords ORDER BY jenis, kategori, bobot DESC")->fetchAll();
                  foreach ($rows as $r):
                    ?>
                    <tr>
                      <td><code
                          style="background:var(--bg-input);padding:2px 8px;border-radius:4px;font-size:12px"><?= $r['keyword'] ?></code>
                      </td>
                      <td><span class="badge badge-<?= $r['jenis'] === 'Foto' ? 'foto' : 'dok' ?>"><?= $r['jenis'] ?></span>
                      </td>
                      <td><?= $r['kategori'] ?></td>
                      <td>
                        <div style="display:flex;align-items:center;gap:6px">
                          <div class="bar-track" style="width:60px">
                            <div class="bar-fill" style="width:<?= $r['bobot'] * 10 ?>%"></div>
                          </div>
                          <span style="font-size:12px;color:var(--text-3)"><?= $r['bobot'] ?></span>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach;
                } catch (Exception $e) {
                  echo '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-3)">Setup database dulu</td></tr>';
                } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="view-setup" class="view">
        <div class="section-title" style="margin-bottom:1.5rem">⚙️ Panduan Setup Nexus Archive</div>

        <?php
        $steps = [
          ['Setup Database MySQL', 'Jalankan perintah berikut di terminal (atau import via phpMyAdmin):', 'mysql -u root -p < setup/database.sql'],
          ['Konfigurasi Aplikasi', 'Edit file <code>includes/config.php</code> dan sesuaikan kredensial berikut:', "DB_HOST, DB_USER, DB_PASS, DB_NAME\nDRIVE_FOLDER_ID"],
          ['Setup Google Drive API', "1. Buka <a href='https://console.cloud.google.com' target='_blank' style='color:var(--accent-2)'>Google Cloud Console</a><br>2. Buat project baru → Enable <strong>Google Drive API</strong><br>3. Buat Service Account → Download <code>credentials.json</code><br>4. Share folder Drive ke email service account dengan akses <strong>Editor</strong> (agar bisa upload)<br>5. Letakkan <code>credentials.json</code> di folder <code>includes/</code>", null],
          ['Manajemen Data Demo', "Gunakan tombol <strong>+ Data Demo</strong> di pojok kanan atas untuk mengisi 50+ data simulasi, dan gunakan tombol <strong>🗑️ Hapus Demo</strong> untuk membersihkannya sebelum presentasi data asli.", null],
          ['Sinkronisasi & Auto-Sync', 'Nyalakan sakelar <strong>Auto-Sync</strong> agar sistem otomatis menyinkronkan file baru dari Google Drive setiap 1 menit, atau klik <strong>↻ Sinkronisasi Drive</strong> untuk tarikan data manual.', null],
        ];
        foreach ($steps as $i => $step):
          ?>
          <div class="card" style="margin-bottom:14px">
            <div style="display:flex;align-items:flex-start;gap:1rem">
              <div
                style="width:36px;height:36px;background:var(--accent-glow);border:1px solid var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--accent-2);flex-shrink:0">
                <?= $i + 1 ?>
              </div>
              <div style="flex:1">
                <div style="font-weight:600;margin-bottom:8px"><?= $step[0] ?></div>
                <div style="font-size:14px;color:var(--text-2);margin-bottom:<?= $step[2] ? '10px' : '0' ?>">
                  <?= $step[1] ?>
                </div>
                <?php if ($step[2]): ?>
                  <pre
                    style="background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;font-size:13px;overflow-x:auto;color:var(--text)"><?= $step[2] ?></pre>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="card" style="border-color:var(--accent);background:var(--accent-glow)">
          <div style="font-weight:600;margin-bottom:8px;color:var(--accent-2)">📁 Struktur Direktori Proyek</div>
          <pre style="font-size:13px;color:var(--text-2);line-height:1.8">nexus-archive/
├── index.php               ← Halaman utama (UI & Dashboard)
├── php.ini                 ← Konfigurasi batas ukuran upload PHP
├── README.md               ← Dokumentasi proyek
├── includes/
│   ├── config.php          ← Konfigurasi DB & Folder ID
│   ├── credentials.json    ← Kredensial Service Account Google
│   ├── decision_tree.php   ← Engine Kecerdasan Buatan (NLP)
│   └── drive_service.php   ← Class Service Google Drive API
├── api/
│   ├── delete_dummy.php    ← Endpoint hapus data testing
│   ├── files.php           ← Endpoint tabel Semua File
│   ├── search.php          ← Endpoint pencarian Chatbot
│   ├── seed_dummy.php      ← Generator data testing
│   ├── stats.php           ← Endpoint analitik Dashboard
│   ├── sync.php            ← Endpoint sinkronisasi Drive
│   ├── test_drive.php      ← Diagnostik API Drive
│   └── upload.php          ← Endpoint unggah file ke Drive
├── assets/
│   ├── css/style.css       ← Desain antarmuka (Vanilla CSS)
│   └── js/app.js           ← Logika interaktif Frontend
└── setup/
    └── database.sql        ← Script tabel database</pre>
        </div>
      </div>

      <!-- Hidden sync key input -->
      <input type="hidden" id="sync-key" value="<?php echo md5('sync_' . DB_NAME . '_secret'); ?>">
  </div>

  </main>
  </div>

  <!-- Toast container -->
  <div class="toast-container"></div>

  <script src="assets/js/app.js"></script>
  <script>
    // Override loadFiles to use proper API with all categories
    // async function loadFiles() {
    //   const tbody = document.getElementById('file-tbody');
    //   try {
    //     const queries = ['foto','dokumen','laporan','sertifikat','proposal','wisuda','olahraga','seminar','surat'];
    //     const promises = queries.map(q =>
    //       fetch('api/search.php', {
    //         method: 'POST',
    //         headers: {'Content-Type':'application/json'},
    //         body: JSON.stringify({query: 'cari ' + q, session_id: 'browse'})
    //       }).then(r => r.json()).catch(() => ({files:[]}))
    //     );
    //     const results = await Promise.all(promises);
    //     const seen = new Set();
    //     const all  = [];
    //     results.forEach(r => (r.files||[]).forEach(f => {
    //       if (!seen.has(f.id)) { seen.add(f.id); all.push(f); }
    //     }));
    //     window._allFiles = all;
    //     filterFiles();
    //   } catch(e) {
    //     tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-3)">Gagal memuat data</td></tr>';
    //   }
    // }

    function filterFiles() {
      const nama = document.getElementById('filter-nama').value.toLowerCase();
      const jenis = document.getElementById('filter-jenis').value;
      const kat = document.getElementById('filter-kat').value;
      const tahun = document.getElementById('filter-tahun').value;
      const all = window._allFiles || [];

      const filtered = all.filter(f =>
        (!nama || f.nama_file.toLowerCase().includes(nama)) &&
        (!jenis || f.jenis === jenis) &&
        (!kat || f.kategori === kat) &&
        (!tahun || String(f.tahun) === tahun)
      );

      const tbody = document.getElementById('file-tbody');
      if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-3)">Tidak ada file yang cocok</td></tr>';
        return;
      }
      tbody.innerHTML = filtered.map(f => `
    <tr>
      <td><span class="file-name-cell" title="${f.nama_file}">${fileIcon(f.jenis, f.mime_type)} ${f.nama_file}</span></td>
      <td><span class="badge badge-${f.jenis === 'Foto' ? 'foto' : 'dok'}">${f.jenis}</span></td>
      <td>${f.kategori}</td>
      <td>${f.tahun}</td>
      <td>${f.ukuran_mb ? (f.ukuran_mb >= 1 ? f.ukuran_mb.toFixed(1) + ' MB' : Math.round(f.ukuran_mb * 1024) + ' KB') : '—'}</td>
      <td><a class="link-btn" href="${f.drive_link}" target="_blank">Buka ↗</a></td>
    </tr>
  `).join('');
    }
  </script>

</body>

</html>