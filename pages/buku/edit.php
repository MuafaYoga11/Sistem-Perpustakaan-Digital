<?php
$title = 'Edit Buku';
$page  = 'buku';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db = getDB();
$id = intval($_GET['id'] ?? 0);

$buku = $db->query("SELECT * FROM buku WHERE id_buku = $id")->fetch_assoc();
if (!$buku) { echo '<div class="alert alert-danger" style="margin:28px;">Buku tidak ditemukan.</div>'; exit; }

$kategori = $db->query("SELECT * FROM kategori ORDER BY nama_kategori");
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul     = trim($_POST['judul'] ?? '');
    $pengarang = trim($_POST['pengarang'] ?? '');
    $isbn      = trim($_POST['isbn'] ?? '') ?: null;
    $penerbit  = trim($_POST['penerbit'] ?? '') ?: null;
    $tahun     = intval($_POST['tahun_terbit'] ?? 0) ?: null;
    $id_kat    = intval($_POST['id_kategori'] ?? 0);
    $stok      = intval($_POST['stok'] ?? 0);

    if (!$judul)     $errors[] = 'Judul wajib diisi.';
    if (!$pengarang) $errors[] = 'Pengarang wajib diisi.';

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE buku SET judul=?, pengarang=?, isbn=?, penerbit=?,
                              tahun_terbit=?, id_kategori=?, stok=? WHERE id_buku=?");
        $stmt->bind_param('sssssiii', $judul, $pengarang, $isbn, $penerbit, $tahun, $id_kat, $stok, $id);
        $stmt->execute();
        $_SESSION['msg'] = "Buku berhasil diperbarui!";
        header('Location: index.php');
        exit;
    }
    // Repopulate for re-display
    $buku = array_merge($buku, $_POST);
}
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div>
    <h1 class="page-title">Edit Buku</h1>
    <p class="page-subtitle">Ubah informasi buku: <strong><?= htmlspecialchars($buku['judul']) ?></strong></p>
  </div>
  <a href="index.php" class="btn btn-secondary">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali ke Daftar
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
    <span class="card-title">✏️ Informasi Buku</span>
    <span style="font-size:.78rem;color:var(--clr-muted);">ID: #<?= $id ?></span>
  </div>
  <form method="POST">
    <div class="form-grid">
      <!-- Judul – full width -->
      <div class="form-group" style="grid-column:1/-1;">
        <label class="form-label">Judul Buku <span class="required">*</span></label>
        <input type="text" name="judul" class="form-control"
               value="<?= htmlspecialchars($buku['judul']) ?>" required>
      </div>

      <!-- Pengarang -->
      <div class="form-group">
        <label class="form-label">Pengarang <span class="required">*</span></label>
        <input type="text" name="pengarang" class="form-control"
               value="<?= htmlspecialchars($buku['pengarang']) ?>" required>
      </div>

      <!-- ISBN -->
      <div class="form-group">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn" class="form-control"
               placeholder="Opsional..."
               value="<?= htmlspecialchars($buku['isbn'] ?? '') ?>">
      </div>

      <!-- Penerbit -->
      <div class="form-group">
        <label class="form-label">Penerbit</label>
        <input type="text" name="penerbit" class="form-control"
               value="<?= htmlspecialchars($buku['penerbit'] ?? '') ?>">
      </div>

      <!-- Tahun Terbit -->
      <div class="form-group">
        <label class="form-label">Tahun Terbit</label>
        <input type="number" name="tahun_terbit" class="form-control"
               min="1900" max="2099"
               value="<?= $buku['tahun_terbit'] ?>">
      </div>

      <!-- Kategori -->
      <div class="form-group">
        <label class="form-label">Kategori <span class="required">*</span></label>
        <select name="id_kategori" class="form-control" required>
          <?php while ($kat = $kategori->fetch_assoc()): ?>
            <option value="<?= $kat['id_kategori'] ?>" <?= $buku['id_kategori']==$kat['id_kategori']?'selected':'' ?>>
              <?= htmlspecialchars($kat['nama_kategori']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Stok -->
      <div class="form-group">
        <label class="form-label">Jumlah Stok</label>
        <input type="number" name="stok" class="form-control" min="0"
               value="<?= $buku['stok'] ?>">
        <p class="form-hint">Stok tersedia saat ini: <strong><?= $buku['stok_tersedia'] ?></strong></p>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        💾 Update Buku
      </button>
      <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
