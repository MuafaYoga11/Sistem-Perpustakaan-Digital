<?php
$title = 'Buat Peminjaman';
$page  = 'peminjaman';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db       = getDB();
$anggota  = $db->query("SELECT a.id_anggota, u.nama, a.no_anggota FROM anggota a JOIN users u ON a.id_user = u.id_user WHERE u.status='aktif' ORDER BY u.nama");
$buku     = $db->query("SELECT id_buku, judul, pengarang, stok_tersedia FROM buku WHERE stok_tersedia > 0 ORDER BY judul");
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_anggota  = intval($_POST['id_anggota'] ?? 0);
    $buku_ids    = $_POST['buku_ids'] ?? [];
    $tgl_jatuh   = $_POST['tanggal_jatuh_tempo'] ?? '';

    if (!$id_anggota)      $errors[] = 'Pilih anggota.';
    if (empty($buku_ids))  $errors[] = 'Pilih minimal 1 buku.';
    if (!$tgl_jatuh)       $errors[] = 'Tanggal jatuh tempo wajib diisi.';

    if (empty($errors)) {
        $db->begin_transaction();
        try {
            $id_pust = $_SESSION['user']['id_user'];
            if (empty($id_pust) || ($_SESSION['user']['role'] ?? '') !== 'pustakawan') {
                $id_pust = 1;
            }
            $stmt = $db->prepare("INSERT INTO peminjaman (id_anggota, id_pustakawan, tanggal_pinjam, tanggal_jatuh_tempo, status)
                                  VALUES (?, ?, CURDATE(), ?, 'dipinjam')");
            $stmt->bind_param('iis', $id_anggota, $id_pust, $tgl_jatuh);
            $stmt->execute();
            $id_pinjam = $db->insert_id;

            foreach ($buku_ids as $id_buku) {
                $id_buku = intval($id_buku);
                $db->query("INSERT INTO detail_peminjaman (id_peminjaman, id_buku, jumlah) VALUES ($id_pinjam, $id_buku, 1)");
                $db->query("UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id_buku = $id_buku AND stok_tersedia > 0");
            }
            $db->commit();
            $_SESSION['msg'] = 'Peminjaman berhasil dibuat!';
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Gagal menyimpan: ' . $e->getMessage();
        }
    }
}
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div>
    <h1 class="page-title">Buat Peminjaman Baru</h1>
    <p class="page-subtitle">Lengkapi form berikut untuk mencatat peminjaman buku</p>
  </div>
  <a href="index.php" class="btn btn-secondary">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali
  </a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <strong>⚠️ Mohon perbaiki kesalahan berikut:</strong><br>
    <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
  </div>
<?php endif; ?>

<div class="card" style="max-width:760px;">
  <div class="card-header">
    <span class="card-title">📖 Detail Peminjaman</span>
  </div>
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Anggota *</label>
      <select name="id_anggota" class="form-control" required>
        <option value="">-- Pilih Anggota --</option>
        <?php while ($a = $anggota->fetch_assoc()): ?>
          <option value="<?= $a['id_anggota'] ?>" <?= (($_POST['id_anggota']??'')==$a['id_anggota'])?'selected':'' ?>>
            [<?= $a['no_anggota'] ?>] <?= htmlspecialchars($a['nama']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Pilih Buku (bisa lebih dari satu) *</label>
      <select name="buku_ids[]" class="form-control" multiple size="6" required>
        <?php while ($b = $buku->fetch_assoc()): ?>
          <option value="<?= $b['id_buku'] ?>">
            <?= htmlspecialchars($b['judul']) ?> — <?= htmlspecialchars($b['pengarang']) ?> (Tersedia: <?= $b['stok_tersedia'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
      <p class="form-hint">Tahan Ctrl/Cmd untuk memilih lebih dari satu buku.</p>
    </div>
    <div class="form-group">
      <label class="form-label">Tanggal Jatuh Tempo <span class="required">*</span></label>
      <input type="date"
             name="tanggal_jatuh_tempo"
             class="form-control"
             value="<?= htmlspecialchars($_POST['tanggal_jatuh_tempo'] ?? date('Y-m-d', strtotime('+7 days'))) ?>"
             min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
             required>
      <p class="form-hint">Default: 7 hari dari sekarang.</p>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">📋 Buat Peminjaman</button>
      <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
