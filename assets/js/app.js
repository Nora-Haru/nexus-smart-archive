/* ============================================================
   Smart Media Archive — Main JS
   ============================================================ */

// ---- Navigasi view ----
function showView(id) {
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const view = document.getElementById('view-' + id);
  if (view) view.classList.add('active');
  const nav = document.querySelector(`[data-view="${id}"]`);
  if (nav) nav.classList.add('active');
  if (id === 'dashboard') loadDashboard();
  if (id === 'files')     loadFiles();
}

// ---- Toast ----
function toast(msg, type = 'info') {
  const icons = { success: '✓', error: '✕', info: 'ℹ' };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span>${icons[type]}</span> ${msg}`;
  document.querySelector('.toast-container').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ============================================================
//  CHATBOT
// ============================================================
const SUGGESTIONS = [
  'cari foto wisuda', 'cari sertifikat juara', 'cari proposal',
  'cari foto olahraga', 'cari laporan 2026', 'cari surat undangan',
  'cari foto seminar', 'cari foto ramadhan',
];

let sessionId = 'sess_' + Date.now();
let isTyping  = false;

function appendMsg(role, html) {
  const box  = document.getElementById('chat-messages');
  const wrap = document.createElement('div');
  wrap.className = 'msg-row ' + role;

  const icon = role === 'bot' ? '🗂️' : 'U';
  wrap.innerHTML = `
    <div class="avatar ${role}">${icon}</div>
    <div class="bubble ${role}">${html}</div>
  `;
  box.appendChild(wrap);
  box.scrollTop = box.scrollHeight;
}

function showTyping() {
  const box  = document.getElementById('chat-messages');
  const wrap = document.createElement('div');
  wrap.className = 'msg-row bot';
  wrap.id = 'typing-indicator';
  wrap.innerHTML = `
    <div class="avatar bot">🗂️</div>
    <div class="bubble bot" style="padding:14px 18px">
      <div style="display:flex;gap:5px;align-items:center">
        <span style="width:6px;height:6px;background:var(--text-3);border-radius:50%;animation:bounce .8s .0s infinite alternate"></span>
        <span style="width:6px;height:6px;background:var(--text-3);border-radius:50%;animation:bounce .8s .2s infinite alternate"></span>
        <span style="width:6px;height:6px;background:var(--text-3);border-radius:50%;animation:bounce .8s .4s infinite alternate"></span>
      </div>
    </div>
  `;
  box.appendChild(wrap);
  box.scrollTop = box.scrollHeight;
}

function hideTyping() {
  const el = document.getElementById('typing-indicator');
  if (el) el.remove();
}

function fileIcon(jenis, mime) {
  if (jenis === 'Foto') return '🖼️';
  if (mime && mime.includes('pdf')) return '📄';
  if (mime && mime.includes('word')) return '📝';
  if (mime && mime.includes('sheet')) return '📊';
  return '📁';
}

function formatSize(mb) {
  if (!mb) return '';
  return mb >= 1 ? mb.toFixed(1) + ' MB' : Math.round(mb * 1024) + ' KB';
}

async function sendChat(queryOverride) {
  const input = document.getElementById('chat-input');
  const query = (queryOverride || input.value).trim();
  if (!query || isTyping) return;

  input.value = '';
  appendMsg('user', query);
  isTyping = true;
  document.getElementById('btn-send').disabled = true;
  showTyping();

  try {
    const res  = await fetch('api/search.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query, session_id: sessionId }),
    });
    const data = await res.json();
    hideTyping();

    if (data.error) {
      appendMsg('bot', `⚠️ Error: ${data.error}`);
    } else if (data.total === 0) {
      appendMsg('bot', `
        <div>Tidak ada file ditemukan untuk <strong>"${query}"</strong>.</div>
        <div style="margin-top:8px;font-size:13px;color:var(--text-3)">
          Coba kata kunci: wisuda, olahraga, seminar, keagamaan, proposal, sertifikat, laporan, surat
        </div>
      `);
    } else {
      const cls  = data.klasifikasi;
      const clsHtml = cls.jenis
        ? `<span class="badge badge-${cls.jenis==='Foto'?'foto':'dok'}">${cls.jenis} · ${cls.kategori}</span>`
        : '';

      let filesHtml = `
        <div style="margin-bottom:8px;display:flex;align-items:center;gap:8px">
          ${clsHtml}
          <span style="font-size:12px;color:var(--text-3)">${data.total} file ditemukan</span>
        </div>
        <div class="result-list">
      `;
      data.files.forEach(f => {
        // Ukuran diperbesar dari 36px menjadi 80px khusus untuk Chatbot
        const thumbHtml = f.thumbnail_url 
          ? `<img src="${f.thumbnail_url}" style="width:180px; height:180px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1px solid var(--border);" loading="lazy">`
          : `<div style="width:180px; height:180px; border-radius:8px; background:var(--bg-input); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:36px;">${fileIcon(f.jenis, f.mime_type)}</div>`;

        filesHtml += `
          <a class="result-item" href="${f.drive_link}" target="_blank" rel="noopener" style="align-items:flex-start; gap:14px; padding:12px;">
            ${thumbHtml}
            <div style="display:flex; flex-direction:column; overflow:hidden; flex:1;">
              <!-- Nama file dibuat bisa turun ke bawah (wrap) agar teks panjang tidak terpotong -->
              <span class="result-item-name" style="font-weight: 600; font-size: 13.5px; white-space: normal; line-height: 1.4; margin-bottom: 8px;">${f.nama_file}</span>
              
              <!-- Informasi meta file dibuat lebih detail -->
              <span class="result-item-meta" style="font-size: 11.5px;">
                <span class="badge badge-${f.jenis==='Foto'?'foto':'dok'}" style="margin-right:4px;">${f.jenis}</span>
                ${f.kategori} · ${f.tahun} ${f.ukuran_mb ? ' · ' + formatSize(f.ukuran_mb) : ''}
              </span>
            </div>
          </a>
        `;
      });
      filesHtml += '</div>';
      appendMsg('bot', filesHtml);
    }
  } catch (e) {
    hideTyping();
    appendMsg('bot', '⚠️ Koneksi ke server gagal. Pastikan server PHP sudah berjalan.');
  }

  isTyping = false;
  document.getElementById('btn-send').disabled = false;
}

function initChat() {
  // Render suggestion buttons
  const sugs = document.getElementById('chat-suggestions');
  SUGGESTIONS.forEach(s => {
    const btn = document.createElement('button');
    btn.className = 'sug-btn';
    btn.textContent = s;
    btn.onclick = () => sendChat(s);
    sugs.appendChild(btn);
  });

  document.getElementById('chat-input').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); }
  });

  // Welcome message setelah page load
  setTimeout(() => {
    appendMsg('bot', `
      Halo! Saya <strong>Nexus Archive Assistant</strong> 🗂️<br>
      Saya bisa membantu kamu menemukan foto dan dokumen yang tersimpan.<br>
      <span style="font-size:13px;color:var(--text-3)">Contoh: <em>cari foto wisuda</em> atau <em>cari sertifikat juara</em></span>
    `);
  }, 300);
}

// ============================================================
//  DASHBOARD
// ============================================================
async function loadDashboard() {
  try {
    const res  = await fetch('api/stats.php');
    const data = await res.json();

    // Stat cards utama
    const t = data.totals;
    document.getElementById('stat-total').textContent = (+t.total).toLocaleString();
    document.getElementById('stat-foto').textContent  = (+t.total_foto).toLocaleString();
    document.getElementById('stat-dok').textContent   = (+t.total_dokumen).toLocaleString();

    // --- TAMBAHKAN BAGIAN INI ---
    // Stat Periode Waktu
    if (data.periode) {
      document.getElementById('stat-hari').textContent   = data.periode.hari_ini.toLocaleString();
      document.getElementById('stat-minggu').textContent = data.periode.minggu_ini.toLocaleString();
      document.getElementById('stat-bulan').textContent  = data.periode.bulan_ini.toLocaleString();
    }
    // ----------------------------

    // Bars per kategori
    renderCategoryBars(data.per_kategori);
    renderTahunBars(data.per_tahun);
    renderRecentFiles(data.terbaru);
  } catch (e) {
    console.error('Dashboard load failed:', e);
  }
}

function renderCategoryBars(rows) {
  const foto  = rows.filter(r => r.jenis === 'Foto');
  const dok   = rows.filter(r => r.jenis === 'Dokumen');
  const maxF  = Math.max(...foto.map(r => +r.jumlah), 1);
  const maxD  = Math.max(...dok.map(r => +r.jumlah), 1);

  document.getElementById('bars-foto').innerHTML = foto.map(r => `
    <div class="bar-item">
      <div class="bar-item-label">${r.kategori}</div>
      <div class="bar-track"><div class="bar-fill green" style="width:${Math.round(+r.jumlah/maxF*100)}%"></div></div>
      <div class="bar-count">${r.jumlah}</div>
    </div>
  `).join('');

  document.getElementById('bars-dok').innerHTML = dok.map(r => `
    <div class="bar-item">
      <div class="bar-item-label">${r.kategori}</div>
      <div class="bar-track"><div class="bar-fill" style="width:${Math.round(+r.jumlah/maxD*100)}%"></div></div>
      <div class="bar-count">${r.jumlah}</div>
    </div>
  `).join('');
}

function renderTahunBars(rows) {
  const max = Math.max(...rows.map(r => +r.jumlah), 1);
  document.getElementById('bars-tahun').innerHTML = rows.map(r => `
    <div class="bar-item">
      <div class="bar-item-label">${r.tahun}</div>
      <div class="bar-track"><div class="bar-fill" style="width:${Math.round(+r.jumlah/max*100)}%;background:var(--amber)"></div></div>
      <div class="bar-count">${r.jumlah}</div>
    </div>
  `).join('');
}

function renderRecentFiles(files) {
  const el = document.getElementById('recent-files');
  if (!files || !files.length) {
    el.innerHTML = '<div class="empty-state"><div class="icon">📭</div><p>Belum ada file</p></div>';
    return;
  }
  el.innerHTML = files.map(f => {
    const thumbHtml = f.thumbnail_url 
      ? `<img src="${f.thumbnail_url}" style="width:32px; height:32px; border-radius:6px; object-fit:cover; border:1px solid var(--border);" loading="lazy">`
      : `<div style="width:32px; height:32px; border-radius:6px; background:var(--bg-input); display:flex; align-items:center; justify-content:center; font-size:14px;">${fileIcon(f.jenis, f.mime_type)}</div>`;

    return `
    <tr>
      <td>
        <div style="display:flex; align-items:center; gap:12px;">
          ${thumbHtml}
          <span class="file-name-cell" title="${f.nama_file}">${f.nama_file}</span>
        </div>
      </td>
      <td><span class="badge badge-${f.jenis==='Foto'?'foto':'dok'}">${f.jenis}</span></td>
      <td>${f.kategori}</td>
      <td>${f.tahun}</td>
      <td><a class="link-btn" href="${f.drive_link}" target="_blank">Buka ↗</a></td>
    </tr>
  `}).join('');
}

// ============================================================
//  FILE BROWSER (Refactored)
// ============================================================
// let _allFiles = [];

async function loadFiles() {
  const tbody = document.getElementById('file-tbody');
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-3)"><span class="spinner"></span> Memuat data...</td></tr>';
  
  try {
    const res  = await fetch('api/files.php');
    const data = await res.json();
    
    if (data.error) throw new Error(data.error);
    
    // Simpan ke variabel global window agar terbaca oleh fungsi filterFiles
    window._allFiles = data.files || [];
    filterFiles(); 
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-3)">⚠️ Gagal memuat data arsip.</td></tr>';
  }
}

function filterFiles() {
  const nama  = document.getElementById('filter-nama').value.toLowerCase();
  const jenis = document.getElementById('filter-jenis').value;
  const kat   = document.getElementById('filter-kat').value;
  const tahun = document.getElementById('filter-tahun').value;
  const all   = window._allFiles || [];

  const filtered = all.filter(f =>
    (!nama  || f.nama_file.toLowerCase().includes(nama)) &&
    (!jenis || f.jenis     === jenis) &&
    (!kat   || f.kategori  === kat)   &&
    (!tahun || String(f.tahun) === tahun)
  );

  const tbody = document.getElementById('file-tbody');
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-3)">Tidak ada file yang cocok</td></tr>';
    return;
  }
  
  tbody.innerHTML = filtered.map(f => {
    const thumbHtml = f.thumbnail_url 
      ? `<img src="${f.thumbnail_url}" style="width:36px; height:36px; border-radius:6px; object-fit:cover; border:1px solid var(--border);" loading="lazy">`
      : `<div style="width:36px; height:36px; border-radius:6px; background:var(--bg-input); display:flex; align-items:center; justify-content:center; font-size:16px;">${fileIcon(f.jenis, f.mime_type)}</div>`;

    return `
    <tr>
      <td>
        <div style="display:flex; align-items:center; gap:12px;">
          ${thumbHtml}
          <span class="file-name-cell" title="${f.nama_file}">${f.nama_file}</span>
        </div>
      </td>
      <td><span class="badge badge-${f.jenis==='Foto'?'foto':'dok'}">${f.jenis}</span></td>
      <td>${f.kategori}</td>
      <td>${f.tahun}</td>
      <td>${f.ukuran_mb ? (f.ukuran_mb>=1 ? f.ukuran_mb.toFixed(1)+' MB' : Math.round(f.ukuran_mb*1024)+' KB') : '—'}</td>
      <td><a class="link-btn" href="${f.drive_link}" target="_blank">Buka ↗</a></td>
    </tr>
  `}).join('');
}

// ============================================================
//  SINKRONISASI GOOGLE DRIVE
// ============================================================
// async function syncDrive() {
//   const btn = document.getElementById('btn-sync');
//   btn.classList.add('loading');
//   btn.innerHTML = '<span class="spinner"></span> Menyinkronkan...';
//   toast('Memulai sinkronisasi Google Drive...', 'info');

//   try {
//     const res  = await fetch('api/sync.php', { method: 'POST' });
//     const data = await res.json();

//     if (data.error) {
//       toast('Error: ' + data.error, 'error');
//     } else {
//       toast(`✓ ${data.detail.synced} file berhasil disinkronkan`, 'success');
//       loadDashboard();
//     }
//   } catch (e) {
//     toast('Koneksi gagal saat sinkronisasi', 'error');
//   }

//   btn.classList.remove('loading');
//   btn.innerHTML = '↻ Sinkronisasi Drive';
// }

// Menggunakan parameter isAuto agar fungsi tahu kapan harus diam
async function syncDrive(isAuto = false) {
  const btn = document.getElementById('btn-sync');
  if (btn.classList.contains('loading')) return; // Cegah penumpukan request

  btn.classList.add('loading');
  btn.innerHTML = '<span class="spinner"></span> Menyinkronkan...';
  
  // Jika manual (diklik), munculkan notif mulai
  if (!isAuto) toast('Memulai sinkronisasi Google Drive...', 'info');

  try {
    const res  = await fetch('api/sync.php', { method: 'POST' });
    const data = await res.json();

    if (data.error) {
      if (!isAuto) toast('Error: ' + data.error, 'error');
    } else {
      // Hanya munculkan notif sukses JIKA diklik manual ATAU jika Auto-Sync mendeteksi ada file baru
      if (!isAuto || data.detail.synced > 0) {
        toast(`✓ ${data.detail.synced} file baru disinkronkan`, 'success');
      }
      
      // Jika ada data yang tersinkron, langsung update tampilan otomatis
      if (data.detail.synced > 0 || data.detail.skipped > 0) {
        loadDashboard();
        if (typeof window.loadFiles === 'function') loadFiles();
      }
    }
  } catch (e) {
    if (!isAuto) toast('Koneksi gagal saat sinkronisasi', 'error');
  }

  btn.classList.remove('loading');
  btn.innerHTML = '↻ Sinkronisasi Drive';
}

// Seed data dummy untuk testing
async function seedDummy() {
  toast('Mengisi data dummy...', 'info');
  try {
    const res  = await fetch('api/seed_dummy.php');
    const data = await res.json();
    toast(data.message, 'success');
    
    // Sinkronisasi view setelah data demo masuk
    setTimeout(() => {
      loadDashboard();
      if (typeof loadFiles === 'function') loadFiles();
    }, 500);
  } catch (e) {
    toast('Gagal mengisi data dummy', 'error');
  }
}

function syncKey() {
  // Harus sama dengan yang digenerate di api/sync.php
  return document.getElementById('sync-key')?.value || '';
}

// ============================================================
//  INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
  initChat();
  showView('dashboard');
  
  initAutoSync(); // <--- TAMBAHKAN BARIS INI

  // Inject bounce animation untuk typing indicator
  const style = document.createElement('style');
  style.textContent = `@keyframes bounce { to { transform: translateY(-6px); } }`;
  document.head.appendChild(style);
})


// ============================================================
// Fungsi untuk menghapus semua data demo
// ============================================================
async function deleteDummy() {
  // Beri konfirmasi pop-up agar tidak sengaja terklik
  if (!confirm('Apakah Anda yakin ingin menghapus semua data demo?')) return;

  toast('Menghapus data demo...', 'info');
  try {
    const res  = await fetch('api/delete_dummy.php', { method: 'POST' });
    const data = await res.json();

    if (data.error) {
      toast('Error: ' + data.error, 'error');
    } else {
      toast(`✓ ${data.message}`, 'success');
      
      // Refresh statistik di dashboard secara otomatis
      loadDashboard();
      
      // Jika user sedang membuka menu Semua File, refresh tabelnya juga
      if (typeof loadFiles === 'function') {
        loadFiles();
      }
    }
  } catch (e) {
    toast('Koneksi gagal saat menghapus data demo', 'error');
  }
}

// ============================================================
//  AUTO SINKRONISASI
// ============================================================
let autoSyncInterval;
const SYNC_INTERVAL_MS = 60000; // 60.000 ms = 1 menit. (Silakan ubah jika butuh lebih lama)

function initAutoSync() {
  const toggle = document.getElementById('auto-sync-toggle');
  if (!toggle) return;

  // Cek status terakhir dari LocalStorage (agar setelah direfresh tidak mati)
  const savedState = localStorage.getItem('autoSync') === 'true';
  toggle.checked = savedState;

  if (savedState) startAutoSync();

  toggle.addEventListener('change', (e) => {
    const isChecked = e.target.checked;
    localStorage.setItem('autoSync', isChecked);
    
    if (isChecked) {
      startAutoSync();
      toast('Auto-Sync AKTIF (Berjalan tiap 1 menit)', 'success');
    } else {
      stopAutoSync();
      toast('Auto-Sync NONAKTIF', 'info');
    }
  });
}

function startAutoSync() {
  stopAutoSync(); // Bersihkan memori interval yang lama
  autoSyncInterval = setInterval(() => {
    syncDrive(true); // Lempar nilai "true" menandakan ini otomatis
  }, SYNC_INTERVAL_MS);
}

function stopAutoSync() {
  if (autoSyncInterval) clearInterval(autoSyncInterval);
}

// ============================================================
//  FORM UPLOAD HANDLER
// ============================================================
async function handleFormUpload(event) {
  event.preventDefault();
  
  const fileInput = document.getElementById('upload-file');
  const jenisSelect = document.getElementById('upload-jenis');
  const kategoriInput = document.getElementById('upload-kategori');
  const form = event.target;
  const btn = form.querySelector('button[type="submit"]');

  if (!fileInput.files.length) return;

  // Bungkus data ke dalam objek FormData untuk mendukung transmisi binary stream
  const formData = new FormData();
  formData.append('file', fileInput.files[0]);
  formData.append('jenis', jenisSelect.value);
  formData.append('kategori', kategoriInput.value.trim());

  // Kunci tombol UI agar mencegah double click
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Mengunggah...';
  toast('Sedang mengunggah file ke Google Drive...', 'info');

  try {
    const res = await fetch('api/upload.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.error) {
      toast('Gagal: ' + data.error, 'error');
    } else {
      toast('✓ ' + data.message, 'success');
      
      // Reset form input setelah sukses
      form.reset();
      
      // Refresh statistik dashboard & reload tabel browser secara dinamis
      loadDashboard();
      if (typeof loadFiles === 'function') loadFiles();
    }
  } catch (e) {
    toast('Terjadi kegagalan koneksi ke server saat mengunggah.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Unggah Berkas';
  }
}