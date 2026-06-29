<?php
$title = 'Dashboard';
$page  = 'dashboard';
require_once '../config/database.php';
require_once '../includes/header.php';

$db = getDB();

// Statistik ringkas
$total_buku     = $db->query("SELECT COUNT(*) FROM buku")->fetch_row()[0];
$total_anggota  = $db->query("SELECT COUNT(*) FROM anggota")->fetch_row()[0];
$pinjam_aktif   = $db->query("SELECT COUNT(*) FROM peminjaman WHERE status != 'dikembalikan'")->fetch_row()[0];
$pinjam_terlambat = $db->query("SELECT COUNT(*) FROM peminjaman WHERE status != 'dikembalikan' AND tanggal_jatuh_tempo < CURRENT_DATE()")->fetch_row()[0];
$total_peminjaman = $db->query("SELECT COUNT(*) AS cnt FROM peminjaman")->fetch_row()[0];
$total_denda = $db->query("SELECT IFNULL(SUM(total_denda),0) AS sum FROM pembayaran_denda WHERE LOWER(status_bayar)='lunas'")->fetch_row()[0];

// 5 peminjaman terbaru
$recent = $db->query("
    SELECT p.*, a.id_anggota, u.nama AS nama_anggota,
           GROUP_CONCAT(b.judul SEPARATOR ', ') AS judul_buku
    FROM peminjaman p
    JOIN anggota a ON p.id_anggota = a.id_anggota
    JOIN users u   ON a.id_user    = u.id_user
    JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id_peminjaman
    JOIN buku b ON dp.id_buku = b.id_buku
    GROUP BY p.id_peminjaman
    ORDER BY p.tanggal_pinjam DESC LIMIT 5
");
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Ringkasan aktivitas perpustakaan hari ini</p>
  </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card green">
    <div class="stat-icon">📚</div>
    <div class="stat-number"><?= $total_buku ?></div>
    <div class="stat-label">Total Koleksi Buku</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👤</div>
    <div class="stat-number"><?= $total_anggota ?></div>
    <div class="stat-label">Anggota Terdaftar</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">📋</div>
    <div class="stat-number"><?= $pinjam_aktif ?></div>
    <div class="stat-label">Peminjaman Aktif</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon">⚠️</div>
    <div class="stat-number"><?= $pinjam_terlambat ?></div>
    <div class="stat-label">Peminjaman Terlambat</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🔖</div>
    <div class="stat-number"><?= $total_peminjaman ?></div>
    <div class="stat-label">Total Peminjaman</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-number"><?= number_format($total_denda,0,',','.') ?></div>
    <div class="stat-label">Total Denda (Lunas)</div>
  </div>
</div>

<!-- Tabel Peminjaman Terbaru -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">📖 Peminjaman Terbaru</h2>
    <a href="<?= BASE_URL ?>/pages/peminjaman/index.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>Anggota</th><th>Buku</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if ($recent->num_rows === 0): ?>
        <tr>
          <td colspan="6">
            <div class="empty-state">
              <div class="empty-state-icon">📋</div>
              <p>Belum ada aktivitas peminjaman terbaru.</p>
            </div>
          </td>
        </tr>
        <?php else: ?>
        <?php while ($row = $recent->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id_peminjaman'] ?></td>
          <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
          <td><?= htmlspecialchars($row['judul_buku']) ?></td>
          <td><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?></td>
          <td><?= date('d M Y', strtotime($row['tanggal_jatuh_tempo'])) ?></td>
          <td>
            <?php
              $badge = ['dipinjam'=>'badge-orange','dikembalikan'=>'badge-green','terlambat'=>'badge-red'];
              $cls   = $badge[$row['status']] ?? 'badge-gray';
            ?>
            <span class="badge <?= $cls ?>"><?= ucfirst($row['status']) ?></span>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
