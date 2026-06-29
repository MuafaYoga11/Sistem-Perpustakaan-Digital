<?php
$title = 'Tambah Pembayaran Denda';
$page  = 'pembayaran';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db = getDB();
$errors = [];

// Expect id_pengembalian in GET to associate payment
$id_pengembalian = intval($_GET['pengembalian'] ?? 0);
if ($id_pengembalian <= 0) {
    echo '<div class="alert alert-danger">ID pengembalian tidak valid.</div>';
    exit;
}

// Fetch related peminjaman info for display (optional)
$info = $db->query("SELECT p.tanggal_jatuh_tempo, pg.tanggal_kembali FROM pengembalian pg JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman WHERE pg.id_pengembalian = $id_pengembalian")->fetch_assoc();
if (!$info) {
    echo '<div class="alert alert-danger">Data pengembalian tidak ditemukan.</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_denda = floatval($_POST['total_denda'] ?? 0);
    $status_bayar = $_POST['status_bayar'] ?? 'belum_lunas';
    if ($total_denda < 0) $errors[] = 'Total denda tidak boleh negatif.';
    if (!in_array($status_bayar, ['belum_lunas', 'lunas'])) $errors[] = 'Status bayar tidak valid.';

    if (empty($errors)) {
        $stmt = $db->prepare('INSERT INTO pembayaran_denda (id_pengembalian, total_denda, status_bayar) VALUES (?, ?, ?)');
        $stmt->bind_param('ids', $id_pengembalian, $total_denda, $status_bayar);
        if ($stmt->execute()) {
            $_SESSION['msg'] = 'Pembayaran berhasil ditambahkan.';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Gagal menyimpan: ' . $stmt->error;
        }
    }
}
?>
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
    <div>
        <h1 class="page-title">Tambah Pembayaran Denda</h1>
        <p class="page-subtitle">Pengembalian ID: <?= $id_pengembalian ?></p>
    </div>
    <a href="index.php" class="btn btn-secondary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Kembali
    </a>
</div>
<?php if ($errors): ?>
    <div class="alert alert-danger">
        <strong>⚠️ Mohon perbaiki kesalahan berikut:</strong><br>
        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
    </div>
<?php endif; ?>
<div class="card" style="max-width:600px;">
    <div class="card-header">
        <span class="card-title">💳 Informasi Pembayaran</span>
    </div>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Total Denda (Rp)</label>
                <input type="number" step="0.01" name="total_denda" class="form-control" value="<?= htmlspecialchars($_POST['total_denda'] ?? $info['total_denda'] ?? 0) ?>" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Status Bayar <span class="required">*</span></label>
                <select name="status_bayar" class="form-control" required>
                    <option value="belum_lunas" <?= (($_POST['status_bayar'] ?? '') === 'belum_lunas') ? 'selected' : '' ?>>Belum Lunas</option>
                    <option value="lunas" <?= (($_POST['status_bayar'] ?? '') === 'lunas') ? 'selected' : '' ?>>Lunas</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>
