<?php
$title = 'Data Anggota';
$page  = 'anggota';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db  = getDB();
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

$search = trim($_GET['q'] ?? '');
$sql    = "SELECT u.*, a.no_anggota, a.no_hp, a.tanggal_daftar
           FROM users u JOIN anggota a ON u.id_user = a.id_user
           WHERE u.role = 'anggota'";
if ($search) {
    $s   = $db->real_escape_string($search);
    $sql .= " AND (u.nama LIKE '%$s%' OR u.email LIKE '%$s%' OR a.no_anggota LIKE '%$s%')";
}
$sql .= " ORDER BY u.id_user DESC";
$rows = $db->query($sql);
$total = $rows->num_rows;
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
  <div>
    <h1 class="page-title">Data Anggota</h1>
    <p class="page-subtitle">Daftar anggota perpustakaan terdaftar</p>
  </div>
  <a href="create.php" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Anggota
  </a>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="GET" class="search-form">
  <div class="search-bar" style="max-width:420px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" placeholder="Cari nama, email, no. anggota..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary btn-sm">Cari</button>
    <?php if ($search): ?>
      <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
    <?php endif; ?>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      👥 Daftar Anggota
      <span style="font-weight:400;font-size:.8rem;color:var(--clr-muted);margin-left:8px;">(<?= $total ?> data<?= $search ? " untuk \"".htmlspecialchars($search)."\"" : '' ?>)</span>
    </h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No. Anggota</th>
          <th>Nama</th>
          <th>Email</th>
          <th>Telepon</th>
          <th>Tgl Daftar</th>
          <th style="text-align:center;">Status</th>
          <th style="text-align:center;width:130px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0): ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <div class="empty-state-icon">👤</div>
                <p><?= $search ? "Tidak ada anggota yang cocok dengan pencarian." : "Belum ada data anggota." ?></p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php while ($row = $rows->fetch_assoc()): ?>
          <tr>
            <td><code style="font-size:.82rem;background:var(--clr-accent-light);padding:2px 7px;border-radius:4px;"><?= htmlspecialchars($row['no_anggota']) ?></code></td>
            <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
            <td style="color:var(--clr-muted);"><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['no_hp'] ?? '-') ?></td>
            <td style="color:var(--clr-muted);font-size:.83rem;"><?= date('d M Y', strtotime($row['tanggal_daftar'])) ?></td>
            <td style="text-align:center;">
              <span class="badge <?= $row['status']==='aktif' ? 'badge-green' : 'badge-red' ?>">
                <?= ucfirst($row['status']) ?>
              </span>
            </td>
            <td style="text-align:center;">
              <div style="display:flex;gap:6px;justify-content:center;">
                <a href="edit.php?id=<?= $row['id_user'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                <a href="delete.php?id=<?= $row['id_user'] ?>"
                   onclick="return confirm('Hapus anggota ini?')"
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
