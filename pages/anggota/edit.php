<?php
$title = 'Edit Anggota';
$page  = 'anggota';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db = getDB();
$id_user = intval($_GET['id'] ?? 0);
if ($id_user <= 0) {
    echo '<div class="alert alert-danger">ID anggota tidak valid.</div>';
    exit;
}

// Ambil data gabungan dari tabel users dan anggota
$user = $db->query("SELECT u.*, a.no_anggota, a.no_hp, a.alamat, a.tanggal_daftar FROM users u JOIN anggota a ON u.id_user = a.id_user WHERE u.id_user = $id_user")
            ->fetch_assoc();
if (!$user) {
    echo '<div class="alert alert-danger">Data anggota tidak ditemukan.</div>';
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $no_hp   = trim($_POST['no_hp'] ?? '');
    $alamat  = trim($_POST['alamat'] ?? '');
    $status  = $_POST['status'] ?? 'aktif'; // ENUM('aktif','nonaktif')

    if (!$nama) $errors[] = 'Nama wajib diisi.';
    if (!$email) $errors[] = 'Email wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!in_array($status, ['aktif','nonaktif'])) $errors[] = 'Status tidak valid.';

    if (empty($errors)) {
        $db->begin_transaction();
        try {
            // Update tabel users
            if ($password) {
                $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET nama=?, email=?, password=?, status=? WHERE id_user=?");
                $stmt->bind_param('ssssi', $nama, $email, $hashed_pass, $status, $id_user);
            } else {
                $stmt = $db->prepare("UPDATE users SET nama=?, email=?, status=? WHERE id_user=?");
                $stmt->bind_param('sssi', $nama, $email, $status, $id_user);
            }
            $stmt->execute();

            // Update tabel anggota (tidak mengubah no_anggota)
            $stmt = $db->prepare("UPDATE anggota SET no_hp=?, alamat=? WHERE id_user=?");
            $stmt->bind_param('ssi', $no_hp, $alamat, $id_user);
            $stmt->execute();

            $db->commit();
            $_SESSION['msg'] = "Data anggota berhasil diperbarui.";
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
        <h1 class="page-title">Edit Anggota</h1>
        <p class="page-subtitle">Perbarui data: <strong><?= htmlspecialchars($user['nama']) ?></strong></p>
    </div>
    <a href="index.php" class="btn btn-secondary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Kembali ke Daftar
    </a>
</div>
<?php if ($errors): ?>
    <div class="alert alert-danger">
      <strong>⚠️ Mohon perbaiki kesalahan berikut:</strong><br>
      <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
    </div>
<?php endif; ?>
<div class="card" style="max-width:760px;">
    <div class="card-header">
        <span class="card-title">✏️ Informasi Anggota</span>
        <span style="font-size:.78rem;color:var(--clr-muted);"><?= htmlspecialchars($user['no_anggota']) ?></span>
    </div>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" value="<?=htmlspecialchars($_POST['nama'] ?? $user['nama'])?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($_POST['email'] ?? $user['email'])?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password (kosong = tidak diubah)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label class="form-label">No. HP / WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" value="<?=htmlspecialchars($_POST['no_hp'] ?? $user['no_hp'])?>">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="3"><?=htmlspecialchars($_POST['alamat'] ?? $user['alamat'])?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-control" required>
                    <option value="aktif" <?=(($_POST['status'] ?? $user['status'])==='aktif')?'selected':''?>>Aktif</option>
                    <option value="nonaktif" <?=(($_POST['status'] ?? $user['status'])==='nonaktif')?'selected':''?>>Nonaktif</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>
