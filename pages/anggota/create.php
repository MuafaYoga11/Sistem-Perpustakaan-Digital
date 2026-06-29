<?php
$title = 'Tambah Anggota';
$page  = 'anggota';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db     = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';
    $no_hp    = trim($_POST['no_hp']    ?? '');
    $alamat   = trim($_POST['alamat']   ?? '');

    if (!$nama)     $errors[] = 'Nama lengkap wajib diisi.';
    if (!$email)    $errors[] = 'Email wajib diisi.';
    if (!$password) $errors[] = 'Password wajib diisi.';

    if (empty($errors)) {
        // Cek apakah email sudah terdaftar
        $stmt = $db->prepare("SELECT id_user FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email sudah terdaftar!';
        } else {
            $db->begin_transaction();
            try {
                // Hash password
                $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
                $role = 'anggota';
                $status = 'aktif';

                // Insert ke table users
                $stmt = $db->prepare("INSERT INTO users (nama, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssss', $nama, $email, $hashed_pass, $role, $status);
                $stmt->execute();
                $id_user = $db->insert_id;

                // Generate no_anggota yang unik
                $no_anggota = '';
                $is_unique = false;
                while (!$is_unique) {
                    $no_anggota = 'AG-' . date('Ymd') . '-' . sprintf("%03d", rand(1, 999));
                    $check_stmt = $db->prepare("SELECT id_anggota FROM anggota WHERE no_anggota = ? LIMIT 1");
                    $check_stmt->bind_param('s', $no_anggota);
                    $check_stmt->execute();
                    if ($check_stmt->get_result()->num_rows === 0) {
                        $is_unique = true;
                    }
                }

                // Insert ke table anggota
                $stmt = $db->prepare("INSERT INTO anggota (id_user, no_anggota, no_hp, alamat, tanggal_daftar) VALUES (?, ?, ?, ?, CURDATE())");
                $stmt->bind_param('isss', $id_user, $no_anggota, $no_hp, $alamat);
                $stmt->execute();

                $db->commit();
                $_SESSION['msg'] = "Anggota \"$nama\" berhasil ditambahkan dengan nomor anggota $no_anggota!";
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
  <div>
    <h1 class="page-title">Tambah Anggota Baru</h1>
    <p class="page-subtitle">Isi form di bawah untuk mendaftarkan anggota baru</p>
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
    <span class="card-title">👤 Informasi Anggota</span>
  </div>
  <form method="POST">
    <div class="form-grid">
      <div class="form-group" style="grid-column: 1 / -1;">
        <label class="form-label">Nama Lengkap *</label>
        <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap Anggota"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" placeholder="contoh@domain.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <div class="form-group" style="grid-column: 1 / -1;">
        <label class="form-label">No. HP / WhatsApp</label>
        <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx"
               value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
      </div>
      <div class="form-group" style="grid-column: 1 / -1;">
        <label class="form-label">Alamat Lengkap</label>
        <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat rumah anggota"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">💾 Simpan Anggota</button>
      <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
