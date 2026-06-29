<?php
$title = 'Daftar Peminjaman';
$page  = 'peminjaman';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db = getDB();
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

// Fetch peminjaman with anggota and buku list
$sql = "SELECT p.id_peminjaman, u.nama AS nama_anggota, a.no_anggota,
        GROUP_CONCAT(b.judul SEPARATOR ', ') AS buku_list,
        p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.status
        FROM peminjaman p
        JOIN anggota a ON p.id_anggota = a.id_anggota
        JOIN users u ON a.id_user = u.id_user
        LEFT JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id_peminjaman
        LEFT JOIN buku b ON dp.id_buku = b.id_buku
        GROUP BY p.id_peminjaman
        ORDER BY p.tanggal_pinjam DESC";
$rows = $db->query($sql);
?>
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
  <div>
    <h1 class="page-title">Daftar Peminjaman</h1>
    <p class="page-subtitle">Semua peminjaman buku perpustakaan</p>
  </div>
  <a href="create.php" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Pinjam Buku
  </a>
</div>
<?php if ($msg): ?>
  <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<div class="card">
  <div class="card-header">
    <h2 class="card-title">📖 Riwayat Peminjaman</h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No.</th>
          <th>Anggota</th>
          <th>Buku</th>
          <th>Pinjam</th>
          <th>Jatuh Tempo</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; while($row=$rows->fetch_assoc()): ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><?php echo htmlspecialchars($row['no_anggota']) . ' - ' . htmlspecialchars($row['nama_anggota']); ?></td>
          <td><?php echo htmlspecialchars($row['buku_list'] ?? ''); ?></td>
          <td><?php echo date('d M Y', strtotime($row['tanggal_pinjam'])); ?></td>
          <td><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
          <td>
        <?php
          $now = new DateTime();
          $jt  = new DateTime($row['tanggal_jatuh_tempo']);
          $statusStr = $row['status'];
          if ($statusStr === 'dipinjam') {
            $cls = $now > $jt ? 'badge-red' : 'badge-orange';
          } elseif ($statusStr === 'terlambat') {
            $cls = 'badge-red';
          } else {
            $cls = 'badge-green';
          }
          $label = ucfirst($row['status']);
        ?>
            <span class="badge <?php echo $cls; ?>"><?php echo $label; ?></span>
          </td>
          <td>
            <?php if($row['status']==='dipinjam' || $row['status']==='terlambat'): ?>
              <a href="../pengembalian/proses.php?id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-secondary btn-sm">Pengembalian</a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
