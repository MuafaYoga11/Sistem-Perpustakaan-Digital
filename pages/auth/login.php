<?php
// login.php
session_start();
require_once '../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit;
        }
        $error = 'Email atau password salah!';
    } else {
        $error = 'Harap isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login — Perpustakaan Digital</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--clr-bg); }
    .login-card { width:420px; max-width:95vw; }
    .login-logo { text-align:center; margin-bottom:28px; }
    .login-logo h1 { font-family:var(--font-display); font-size:2rem; color:var(--clr-sage-dark); }
    .login-logo p  { color:var(--clr-muted); font-size:.9rem; }
  </style>
</head>
<body>
<div class="login-card card">
  <div class="login-logo">
    <div style="font-size:2.5rem;">📚</div>
    <h1>Perpustakaan Digital</h1>
    <p>Sistem Pengolahan Data Peminjaman Buku</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" placeholder="admin@perpustakaan.id" required>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
      Masuk ke Sistem
    </button>
  </form>
  
  <div style="margin-top:16px;text-align:center;font-size:.88rem;color:var(--clr-muted);">
    Belum punya akun? <a href="<?= BASE_URL ?>/pages/auth/register.php" style="font-weight:600;">Daftar Anggota Baru</a>
  </div>
</div>
</body>
</html>
