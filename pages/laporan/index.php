<?php
$title = 'Laporan Perpustakaan';
$page  = 'laporan';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db = getDB();
// Statistik utama
$totalBuku = $db->query('SELECT COUNT(*) AS cnt FROM buku')->fetch_assoc()['cnt'];
$totalAnggota = $db->query('SELECT COUNT(*) AS cnt FROM anggota')->fetch_assoc()['cnt'];
$totalPeminjaman = $db->query('SELECT COUNT(*) AS cnt FROM peminjaman')->fetch_assoc()['cnt'];
$aktifPinjam = $db->query("SELECT COUNT(*) AS cnt FROM peminjaman WHERE status != 'dikembalikan'")->fetch_assoc()['cnt'];
$terlambat = $db->query("SELECT COUNT(*) AS cnt FROM peminjaman WHERE status != 'dikembalikan' AND tanggal_jatuh_tempo < CURRENT_DATE()")->fetch_assoc()['cnt'];
$totalDenda = $db->query("SELECT IFNULL(SUM(total_denda),0) AS sum FROM pembayaran_denda WHERE LOWER(status_bayar)='lunas'")->fetch_assoc()['sum'];
?>
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
  <div>
    <h1 class="page-title">Laporan Perpustakaan</h1>
    <p class="page-subtitle">Ringkasan statistik dan kinerja sistem</p>
  </div>
</div>
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📚</div>
    <div class="stat-number"><?= number_format($totalBuku) ?></div>
    <div class="stat-label">Total Buku</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👥</div>
    <div class="stat-number"><?= number_format($totalAnggota) ?></div>
    <div class="stat-label">Total Anggota</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🔖</div>
    <div class="stat-number"><?= number_format($totalPeminjaman) ?></div>
    <div class="stat-label">Total Peminjaman</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🟢</div>
    <div class="stat-number"><?= number_format($aktifPinjam) ?></div>
    <div class="stat-label">Pinjam Aktif</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⚠️</div>
    <div class="stat-number"><?= number_format($terlambat) ?></div>
    <div class="stat-label">Terlambat</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-number"><?= number_format($totalDenda,0,',','.') ?></div>
    <div class="stat-label">Total Denda (Lunas)</div>
  </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
