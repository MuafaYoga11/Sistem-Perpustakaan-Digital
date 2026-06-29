<?php
$title = 'Data Buku';
$page  = 'buku';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db  = getDB();
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

// Pencarian
$search = trim($_GET['q'] ?? '');
$sql    = "SELECT b.*, k.nama_kategori FROM buku b
           JOIN kategori k ON b.id_kategori = k.id_kategori";
if ($search) {
    $s   = $db->real_escape_string($search);
    $sql .= " WHERE b.judul LIKE '%$s%' OR b.pengarang LIKE '%$s%' OR b.isbn LIKE '%$s%'";
}
$sql  .= " ORDER BY b.id_buku DESC";
$rows  = $db->query($sql);
$total = $rows->num_rows;
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div>
    <h1 class="page-title">Data Buku</h1>
    <p class="page-subtitle">Kelola koleksi buku perpustakaan</p>
  </div>
  <a href="create.php" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Buku
  </a>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Search -->
<form method="GET" class="search-form">
  <div class="search-bar" style="max-width:420px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" placeholder="Cari judul, pengarang, ISBN..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary btn-sm">Cari</button>
    <?php if ($search): ?>
      <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
    <?php endif; ?>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      📚 Daftar Buku
      <span style="font-weight:400;font-size:.8rem;color:var(--clr-muted);margin-left:8px;">(<?= $total ?> data<?= $search ? " untuk \"".htmlspecialchars($search)."\"" : '' ?>)</span>
    </h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:48px;">#</th>
          <th>Judul Buku</th>
          <th>Pengarang</th>
          <th>Kategori</th>
          <th style="width:70px;text-align:center;">Tahun</th>
          <th style="width:60px;text-align:center;">Stok</th>
          <th style="width:80px;text-align:center;">Tersedia</th>
          <th style="width:130px;text-align:center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p><?= $search ? "Tidak ada buku yang cocok dengan pencarian." : "Belum ada data buku. Klik <strong>Tambah Buku</strong> untuk mulai." ?></p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php while ($row = $rows->fetch_assoc()): ?>
          <tr>
            <td style="color:var(--clr-muted);font-size:.8rem;"><?= $row['id_buku'] ?></td>
            <td>
              <strong><?= htmlspecialchars($row['judul']) ?></strong>
              <?php if ($row['isbn']): ?>
                <div style="font-size:.75rem;color:var(--clr-muted);margin-top:2px;">ISBN: <?= htmlspecialchars($row['isbn']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['pengarang']) ?></td>
            <td><span class="badge badge-green"><?= htmlspecialchars($row['nama_kategori']) ?></span></td>
            <td style="text-align:center;"><?= $row['tahun_terbit'] ?? '-' ?></td>
            <td style="text-align:center;font-weight:600;"><?= $row['stok'] ?></td>
            <td style="text-align:center;">
              <?php $cls = $row['stok_tersedia'] > 0 ? 'badge-green' : 'badge-red'; ?>
              <span class="badge <?= $cls ?>"><?= $row['stok_tersedia'] ?></span>
            </td>
            <td style="text-align:center;">
              <div style="display:flex;gap:6px;justify-content:center;">
                <a href="edit.php?id=<?= $row['id_buku'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                <a href="delete.php?id=<?= $row['id_buku'] ?>"
                   onclick="return confirm('Hapus buku ini?')"
                   class="btn btn-danger btn-sm">🗑️ Hapus</a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
