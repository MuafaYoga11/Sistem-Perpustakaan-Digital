<?php
$title = 'Hapus Anggota';
$page  = 'anggota';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db = getDB();
$id_user = intval($_GET['id'] ?? 0);
if ($id_user <= 0) {
    echo '<div class="alert alert-danger">ID anggota tidak valid.</div>';
    exit;
}

// Check for related peminjaman records (active or any)
$stmt = $db->prepare('SELECT a.id_anggota FROM anggota a WHERE a.id_user = ?');
$stmt->bind_param('i', $id_user);
$stmt->execute();
$res = $stmt->get_result();
$rowAnggota = $res->fetch_assoc();
if (!$rowAnggota) {
    echo '<div class="alert alert-danger">Data anggota tidak ditemukan.</div>';
    exit;
}
$id_anggota = $rowAnggota['id_anggota'];

// Optional confirmation step
if (!isset($_GET['confirm'])) {
    echo '<div class="page-header"><h1 class="page-title">Konfirmasi Hapus Anggota</h1></div>';
    echo '<p>Apakah Anda yakin ingin menghapus anggota ini? Tindakan ini tidak dapat dibatalkan.</p>';
    echo '<a href="delete.php?id=' . $id_user . '&confirm=1" class="btn btn-danger">Ya, Hapus</a> ';
    echo '<a href="index.php" class="btn btn-secondary">Batal</a>';
    exit;
}

// Begin cascade deletion inside a transaction
$db->begin_transaction();
try {
    // 1. Find all peminjaman IDs for this anggota
    $peminjamanRes = $db->query("SELECT id_peminjaman FROM peminjaman WHERE id_anggota = $id_anggota");
    $peminjamanIds = [];
    while ($row = $peminjamanRes->fetch_assoc()) {
        $peminjamanIds[] = $row['id_peminjaman'];
    }
    // 2. Delete related pembayaran_denda and pengembalian for each peminjaman
    foreach ($peminjamanIds as $pid) {
        // Delete pembayaran_denda linked via pengembalian
        $db->query("DELETE pd FROM pembayaran_denda pd JOIN pengembalian pk ON pd.id_pengembalian = pk.id_pengembalian WHERE pk.id_peminjaman = $pid");
        // Delete pengembalian
        $db->query("DELETE FROM pengembalian WHERE id_peminjaman = $pid");
    }
    // 3. Delete peminjaman records
    $db->query("DELETE FROM peminjaman WHERE id_anggota = $id_anggota");
    // 4. Delete from anggota and users
    $stmt = $db->prepare('DELETE FROM anggota WHERE id_user = ?');
    $stmt->bind_param('i', $id_user);
    $stmt->execute();

    $stmt = $db->prepare('DELETE FROM users WHERE id_user = ?');
    $stmt->bind_param('i', $id_user);
    $stmt->execute();

    $db->commit();
    $_SESSION['msg'] = 'Anggota berhasil dihapus beserta semua data terkait.';
    header('Location: index.php');
    exit;
} catch (Exception $e) {
    $db->rollback();
    echo '<div class="alert alert-danger">Gagal menghapus: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
<?php require_once '../../includes/footer.php'; ?>
