<?php
// api/seed_dummy.php — isi database dengan data dummy untuk testing
// HAPUS file ini setelah data asli sudah disinkronkan dari Drive!

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$pdo = getDB();

$dummy = [
    // === FOTO WISUDA ===
    ['Wisuda2026_Batch1.jpg', 'Foto', 'Wisuda', 2026],
    ['Wisuda2026_Batch2.jpg', 'Foto', 'Wisuda', 2026],
    ['Wisuda2025_Sarjana.jpg', 'Foto', 'Wisuda', 2025],
    ['Wisuda2025_Magister.jpg', 'Foto', 'Wisuda', 2025],
    ['Yudisium2026.jpg', 'Foto', 'Wisuda', 2026],
    ['FotoToga2026.jpg', 'Foto', 'Wisuda', 2026],
    // === FOTO OLAHRAGA ===
    ['VoliPutri2026.jpg', 'Foto', 'Olahraga', 2026],
    ['FutsalPutra2026.jpg', 'Foto', 'Olahraga', 2026],
    ['BasketFinal2025.jpg', 'Foto', 'Olahraga', 2025],
    ['PORSENI2026_Opening.jpg', 'Foto', 'Olahraga', 2026],
    ['PORSENI2026_Penutupan.jpg', 'Foto', 'Olahraga', 2026],
    ['LombaLari2025.jpg', 'Foto', 'Olahraga', 2025],
    // === FOTO SEMINAR ===
    ['SeminarAI2026.jpg', 'Foto', 'Seminar', 2026],
    ['WorkshopDesain2026.jpg', 'Foto', 'Seminar', 2026],
    ['WebinarPendidikan2025.jpg', 'Foto', 'Seminar', 2025],
    ['PelatihanKepemimpinan2026.jpg', 'Foto', 'Seminar', 2026],
    // === FOTO KEAGAMAAN ===
    ['BukberRamadhan2026.jpg', 'Foto', 'Keagamaan', 2026],
    ['PengajianBulanan2026.jpg', 'Foto', 'Keagamaan', 2026],
    ['IsraMiraj2026.jpg', 'Foto', 'Keagamaan', 2026],
    ['HariRayaIdul2026.jpg', 'Foto', 'Keagamaan', 2026],
    // === FOTO ORGANISASI ===
    ['RapatOSIS2026.jpg', 'Foto', 'Organisasi', 2026],
    ['PertemuanHimpunan2025.jpg', 'Foto', 'Organisasi', 2025],
    ['MubesBEM2026.jpg', 'Foto', 'Organisasi', 2026],
    ['DiklatorOSIS2026.jpg', 'Foto', 'Organisasi', 2026],
    // === DOKUMEN PROPOSAL ===
    ['ProposalRamadhan2026.docx', 'Dokumen', 'Proposal', 2026],
    ['ProposalSeminar2026.docx', 'Dokumen', 'Proposal', 2026],
    ['ProposalLomba2025.docx', 'Dokumen', 'Proposal', 2025],
    ['ProposalWisata2026.pdf', 'Dokumen', 'Proposal', 2026],
    ['ProposalPORSENI2026.docx', 'Dokumen', 'Proposal', 2026],
    // === DOKUMEN SERTIFIKAT ===
    ['SertifikatJuaraVoli2026.pdf', 'Dokumen', 'Sertifikat', 2026],
    ['SertifikatPORSENI2025.pdf', 'Dokumen', 'Sertifikat', 2025],
    ['PiagamPenghargaan2026.pdf', 'Dokumen', 'Sertifikat', 2026],
    ['SertifikatSeminarAI2026.pdf', 'Dokumen', 'Sertifikat', 2026],
    ['AwardBestStudent2025.pdf', 'Dokumen', 'Sertifikat', 2025],
    // === DOKUMEN LAPORAN ===
    ['LaporanTahunan2026.pdf', 'Dokumen', 'Laporan', 2026],
    ['LaporanTahunan2025.pdf', 'Dokumen', 'Laporan', 2025],
    ['LKJ2026_Semester1.pdf', 'Dokumen', 'Laporan', 2026],
    ['ReportKegiatan2026.docx', 'Dokumen', 'Laporan', 2026],
    ['EvaluasiOSIS2025.pdf', 'Dokumen', 'Laporan', 2025],
    // === DOKUMEN SURAT ===
    ['SuratUndanganWisuda2026.docx', 'Dokumen', 'Surat', 2026],
    ['SKPengurus2026.pdf', 'Dokumen', 'Surat', 2026],
    ['BeritaAcara2026.docx', 'Dokumen', 'Surat', 2026],
    ['SuratPemberitahuan2025.docx', 'Dokumen', 'Surat', 2025],
    // Tambahan agar mencapai 50+
    ['FotoBazarMurah2026.jpg', 'Foto', 'Organisasi', 2026],
    ['FotoPeringatan17Agustus2026.jpg', 'Foto', 'Keagamaan', 2026],
    ['SeminarNasional2026.jpg', 'Foto', 'Seminar', 2026],
    ['Wisuda2024.jpg', 'Foto', 'Wisuda', 2024],
    ['VoliPutra2025.jpg', 'Foto', 'Olahraga', 2025],
    ['ProposalBaksos2026.docx', 'Dokumen', 'Proposal', 2026],
    ['SertifikatOSIS2026.pdf', 'Dokumen', 'Sertifikat', 2026],
    ['LaporanBulanan2026.pdf', 'Dokumen', 'Laporan', 2026],
    ['SuratKuasa2026.docx', 'Dokumen', 'Surat', 2026],
    ['SertifikatLombaDebat2026.pdf', 'Dokumen', 'Sertifikat', 2026],
];

$pdo->exec("DELETE FROM files WHERE drive_id LIKE 'dummy_%'");

$stmt = $pdo->prepare("
    INSERT IGNORE INTO files
        (drive_id, nama_file, jenis, kategori, tahun, mime_type, ukuran, drive_link, tanggal_upload)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$count = 0;
foreach ($dummy as $i => $d) {
    $mime = $d[1] === 'Foto' ? 'image/jpeg'
        : (str_ends_with($d[0], '.pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $size = rand(200000, 5000000);
    $link = "https://drive.google.com/file/d/dummy_{$i}/view";
    $date = sprintf('%04d-%02d-%02d', $d[3], rand(1, 12), rand(1, 28));

    $stmt->execute(["dummy_$i", $d[0], $d[1], $d[2], $d[3], $mime, $size, $link, $date]);
    $count++;
}

jsonResponse([
    'success' => true,
    'message' => "$count data dummy berhasil ditambahkan.",
    'catatan' => 'Hapus file api/seed_dummy.php setelah data asli dari Drive sudah tersinkronisasi.',
]);
