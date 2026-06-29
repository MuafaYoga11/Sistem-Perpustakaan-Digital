<?php
$title = 'Daftar Pengembalian';
$page  = 'pengembalian';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db  = getDB();
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

$TARIF_PER_HARI = 1000;

// Cek kolom yang tersedia di pengembalian
$pgCols = [];
$pgColRes = $db->query("DESCRIBE pengembalian");
while ($c = $pgColRes->fetch_assoc()) $pgCols[] = $c['Field'];
$hasTerlambat = in_array('terlambat_hari', $pgCols);

// Query utama — ambil semua pengembalian beserta info denda
$sql = "
    SELECT pg.id_pengembalian,
           p.id_peminjaman,
           p.tanggal_pinjam,
           p.tanggal_jatuh_tempo,
           u.nama AS nama_anggota,
           a.no_anggota,
           GROUP_CONCAT(b.judul SEPARATOR ', ') AS buku_list,
           pg.tanggal_kembali,
           pg.kondisi_buku,
           pg.catatan,
           " . ($hasTerlambat ? "pg.terlambat_hari," : "DATEDIFF(pg.tanggal_kembali, p.tanggal_jatuh_tempo) AS terlambat_hari,") . "
           pd.total_denda,
           pd.status_bayar
    FROM pengembalian pg
    JOIN peminjaman p           ON pg.id_peminjaman  = p.id_peminjaman
    JOIN anggota a              ON p.id_anggota      = a.id_anggota
    JOIN users u                ON a.id_user         = u.id_user
    LEFT JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id_peminjaman
    LEFT JOIN buku b            ON dp.id_buku        = b.id_buku
    LEFT JOIN pembayaran_denda pd ON pd.id_pengembalian = pg.id_pengembalian
    GROUP BY pg.id_pengembalian
    ORDER BY pg.tanggal_kembali DESC
";
$rows = $db->query($sql);
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
  <div>
    <h1 class="page-title">Daftar Pengembalian</h1>
    <p class="page-subtitle">Riwayat pengembalian buku &amp; perhitungan denda</p>
  </div>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">🔁 Riwayat Pengembalian</h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Anggota</th>
          <th>Buku</th>
          <th>Jatuh Tempo</th>
          <th>Tgl Kembali</th>
          <th>Hari Terlambat</th>
          <th>Tarif/Hari</th>
          <th>Total Denda</th>
          <th>Status Bayar</th><th>Aksi</th>
          <th>Kondisi</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; while ($row = $rows->fetch_assoc()):
          $hariTerlambat = max(0, (int)$row['terlambat_hari']);
          // Gunakan denda dari DB jika ada, fallback hitung manual
          $totalDenda = ($row['total_denda'] !== null)
              ? (float)$row['total_denda']
              : ($hariTerlambat * $TARIF_PER_HARI);
          $status     = ($row['status_bayar'] && $row['status_bayar'] !== '')
              ? $row['status_bayar'] : 'belum_lunas';
          $badgeClass = $status === 'lunas' ? 'badge-green' : 'badge-orange';
          $terlambatColor = $hariTerlambat > 0 ? '#e74c3c' : '#27ae60';
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars(($row['no_anggota'] ?? '') . ' - ' . ($row['nama_anggota'] ?? '')) ?></td>
          <td style="max-width:220px;word-break:break-word;"><?= htmlspecialchars($row['buku_list'] ?? '') ?></td>
          <td><?= date('d M Y', strtotime($row['tanggal_jatuh_tempo'])) ?></td>
          <td><?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></td>
          <td style="font-weight:700;color:<?= $terlambatColor ?>;">
            <?= $hariTerlambat ?> hari
          </td>
          <td>Rp <?= number_format($TARIF_PER_HARI, 0, ',', '.') ?>/hari</td>
          <td style="font-weight:700;color:<?= $totalDenda > 0 ? '#e74c3c' : '#27ae60' ?>;">
            Rp <?= number_format($totalDenda, 0, ',', '.') ?>
          </td>
          <td>
            <span class="badge <?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
          </td>
  <td>
<?php if ($status !== 'lunas'): ?>
    <form method="post" action="pelunasan.php" style="display:inline;">
        <input type="hidden" name="id_pengembalian" value="<?= $row['id_pengembalian'] ?>">
        <button type="submit" class="btn btn-primary btn-sm">Lunaskan</button>
    </form>
<?php else: ?>
    <span class="badge badge-green">✔ Lunas</span>
<?php endif; ?>
  </td>
          <td><?= htmlspecialchars(ucfirst($row['kondisi_buku'] ?? '-')) ?></td>
          <td><?= nl2br(htmlspecialchars($row['catatan'] ?? '')) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
