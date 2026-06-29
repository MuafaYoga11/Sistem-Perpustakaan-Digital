<?php
$title = 'Tambah Buku';
$page  = 'buku';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db         = getDB();
$kategori   = $db->query("SELECT * FROM kategori ORDER BY nama_kategori");
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul       = trim($_POST['judul']       ?? '');
    $pengarang   = trim($_POST['pengarang']   ?? '');
    $isbn        = trim($_POST['isbn']        ?? '') ?: null;
    $penerbit    = trim($_POST['penerbit']    ?? '') ?: null;
    $tahun       = intval($_POST['tahun_terbit'] ?? 0) ?: null;
    $id_kat      = intval($_POST['id_kategori'] ?? 0);
    $stok        = intval($_POST['stok']      ?? 0);

    if (!$judul)     $errors[] = 'Judul wajib diisi.';
    if (!$pengarang) $errors[] = 'Pengarang wajib diisi.';
    if (!$id_kat)    $errors[] = 'Kategori wajib dipilih.';
    if ($stok < 0)   $errors[] = 'Stok tidak boleh negatif.';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO buku
            (judul, pengarang, isbn, penerbit, tahun_terbit, id_kategori, stok, stok_tersedia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssiis', $judul, $pengarang, $isbn, $penerbit, $tahun, $id_kat, $stok, $stok);
        $stmt->execute();
        $_SESSION['msg'] = "Buku \"$judul\" berhasil ditambahkan!";
        header('Location: index.php');
        exit;
    }
}
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div>
    <h1 class="page-title">Tambah Buku Baru</h1>
    <p class="page-subtitle">Isi formulir di bawah untuk menambah koleksi buku</p>
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
    <span class="card-title">📖 Informasi Buku</span>
  </div>
  <form method="POST">
    <div class="form-grid">
      <!-- Judul – full width -->
      <div class="form-group" style="grid-column:1/-1;">
        <label class="form-label">Judul Buku <span class="required">*</span></label>
        <input type="text" name="judul" class="form-control"
               placeholder="Masukkan judul buku..."
               value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" required>
      </div>

      <!-- Pengarang -->
      <div class="form-group">
        <label class="form-label">Pengarang <span class="required">*</span></label>
        <input type="text" name="pengarang" class="form-control"
               placeholder="Nama pengarang..."
               value="<?= htmlspecialchars($_POST['pengarang'] ?? '') ?>" required>
      </div>

      <!-- ISBN -->
      <div class="form-group">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn" class="form-control"
               placeholder="Opsional..."
               value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>">
      </div>

      <!-- Penerbit -->
      <div class="form-group">
        <label class="form-label">Penerbit</label>
        <input type="text" name="penerbit" class="form-control"
               placeholder="Nama penerbit..."
               value="<?= htmlspecialchars($_POST['penerbit'] ?? '') ?>">
      </div>

      <!-- Tahun Terbit -->
      <div class="form-group">
        <label class="form-label">Tahun Terbit</label>
        <input type="number" name="tahun_terbit" class="form-control"
               min="1900" max="2099"
               value="<?= htmlspecialchars($_POST['tahun_terbit'] ?? date('Y')) ?>">
      </div>

      <!-- Kategori -->
      <div class="form-group">
        <label class="form-label">Kategori <span class="required">*</span></label>
        <select name="id_kategori" class="form-control" required>
          <option value="">-- Pilih Kategori --</option>
          <?php while ($kat = $kategori->fetch_assoc()): ?>
            <option value="<?= $kat['id_kategori'] ?>"
              <?= (($_POST['id_kategori'] ?? '') == $kat['id_kategori']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($kat['nama_kategori']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Stok -->
      <div class="form-group">
        <label class="form-label">Jumlah Stok <span class="required">*</span></label>
        <input type="number" name="stok" class="form-control" min="0"
               value="<?= htmlspecialchars($_POST['stok'] ?? '1') ?>" required>
        <p class="form-hint">Stok tersedia akan otomatis sama dengan stok saat buku baru ditambahkan.</p>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        💾 Simpan Buku
      </button>
      <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
