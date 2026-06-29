<?php
// includes/header.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}
$user   = $_SESSION['user'];
$page   = $page  ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Perpustakaan Digital') ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="wrapper">

  <!-- ===== SIDEBAR ===== -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h1>📚 Perpustakaan<span>Sistem Digital</span></h1>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">Utama</div>
      <a href="<?= BASE_URL ?>/pages/dashboard.php" class="nav-item <?= $page==='dashboard'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>

      <div class="nav-section">Koleksi</div>
      <a href="<?= BASE_URL ?>/pages/buku/index.php" class="nav-item <?= $page==='buku'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Data Buku
      </a>

      <div class="nav-section">Keanggotaan</div>
      <a href="<?= BASE_URL ?>/pages/anggota/index.php" class="nav-item <?= $page==='anggota'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Data Anggota
      </a>

      <div class="nav-section">Transaksi</div>
      <a href="<?= BASE_URL ?>/pages/peminjaman/index.php" class="nav-item <?= $page==='peminjaman'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Peminjaman
      </a>
      <a href="<?= BASE_URL ?>/pages/pengembalian/index.php" class="nav-item <?= $page==='pengembalian'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.61"/></svg>
        Pengembalian
      </a>
      <a href="<?= BASE_URL ?>/pages/pembayaran/index.php" class="nav-item <?= $page==='pembayaran'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Pembayaran Denda
      </a>

    </nav>
  </aside>

  <!-- ===== MAIN CONTENT ===== -->
  <main class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <div style="font-weight:600;font-size:.95rem;"><?= htmlspecialchars($title ?? 'Dashboard') ?></div>
      <div class="topbar-user">
        <div class="avatar"><?= strtoupper(substr($user['nama'],0,2)) ?></div>
        <div>
          <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($user['nama']) ?></div>
          <div style="font-size:.75rem;color:var(--clr-muted);"><?= ucfirst($user['role']) ?></div>
        </div>
        <a href="<?= BASE_URL ?>/pages/auth/logout.php" class="btn btn-secondary btn-sm">Keluar</a>
      </div>
    </div>
