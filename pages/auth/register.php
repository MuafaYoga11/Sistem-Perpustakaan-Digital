<?php
// register.php
session_start();
require_once '../../config/database.php';

$error = '';

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telepon  = trim($_POST['no_hp'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');

    if ($nama && $email && $password) {
        $db = getDB();

        // Check if email already exists
        $stmt = $db->prepare("SELECT id_user FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $db->begin_transaction();
            try {
                // Hashing password
                $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
                $role = 'anggota';
                $status = 'aktif';

                // Insert into users
                $stmt = $db->prepare("INSERT INTO users (nama, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssss', $nama, $email, $hashed_pass, $role, $status);
                $stmt->execute();
                $id_user = $db->insert_id;

                // Generate no_anggota
                $no_anggota = 'AG-' . date('Ymd') . '-' . sprintf("%03d", rand(1, 999));

                // Insert into anggota
                $stmt = $db->prepare("INSERT INTO anggota (id_user, no_anggota, no_hp, alamat, tanggal_daftar) VALUES (?, ?, ?, ?, CURDATE())");
                $stmt->bind_param('isss', $id_user, $no_anggota, $telepon, $alamat);
                $stmt->execute();

                $db->commit();
                $success = 'Pendaftaran berhasil! Silakan masuk.';
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Harap isi semua field wajib.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Anggota — Perpustakaan Digital</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--clr-bg); }
    .register-card { width:460px; max-width:95vw; }
    .register-logo { text-align:center; margin-bottom:24px; }
    .register-logo h1 { font-family:var(--font-display); font-size:1.8rem; color:var(--clr-sage-dark); }
    .register-logo p  { color:var(--clr-muted); font-size:.85rem; }
  </style>
</head>
<body>
<div class="register-card card">
  <div class="register-logo">
    <div style="font-size:2.5rem;">✍️</div>
    <h1>Buat Akun Anggota</h1>
    <p style="margin-top: 4px;">Daftar untuk menjadi anggota Perpustakaan Digital</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Nama Lengkap *</label>
      <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap Anda" required>
    </div>
    <div class="form-group">
      <label class="form-label">Email *</label>
      <input type="email" name="email" class="form-control" placeholder="contoh@domain.com" required>
    </div>
    <div class="form-group">
      <label class="form-label">Password *</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <div class="form-group">
      <label class="form-label">No. HP / WhatsApp</label>
      <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx">
    </div>
    <div class="form-group">
      <label class="form-label">Alamat Lengkap</label>
      <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat rumah Anda"></textarea>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
      Daftar Sekarang
    </button>
  </form>
  
  <div style="margin-top:16px;text-align:center;font-size:.88rem;color:var(--clr-muted);">
    Sudah punya akun? <a href="<?= BASE_URL ?>/pages/auth/login.php" style="font-weight:600;">Masuk di sini</a>
  </div>
</div>
</body>
</html>
