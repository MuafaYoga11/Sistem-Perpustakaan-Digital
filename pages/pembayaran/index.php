<?php
$title = 'Daftar Pembayaran Denda';
$page  = 'pembayaran';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db = getDB();
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

// Fetch all payment records with related info
$sql = "SELECT pd.id_pembayaran, pd.total_denda, pd.status_bayar,
        pg.tanggal_kembali, p.tanggal_jatuh_tempo,
        u.nama AS nama_anggota, a.no_anggota,
        GROUP_CONCAT(b.judul SEPARATOR ', ') AS buku_list
        FROM pembayaran_denda pd
        JOIN pengembalian pg ON pd.id_pengembalian = pg.id_pengembalian
        JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman
        JOIN anggota a ON p.id_anggota = a.id_anggota
        JOIN users u ON a.id_user = u.id_user
        LEFT JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id_peminjaman
        LEFT JOIN buku b ON dp.id_buku = b.id_buku
        GROUP BY pd.id_pembayaran
        ORDER BY pd.id_pembayaran DESC";
$rows = $db->query($sql);
?>
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
    <div>
        <h1 class="page-title">Daftar Pembayaran Denda</h1>
        <p class="page-subtitle">Riwayat pembayaran denda anggota</p>
    </div>
    <a href="create.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Pembayaran
    </a>
</div>
<?php if ($msg): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">💰 Riwayat Pembayaran</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Tgl Kembali</th>
                    <th>Jatuh Tempo</th>
                    <th>Denda</th>
                    <th>Status Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows->num_rows === 0): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-state-icon">💳</div>
                            <p>Belum ada data pembayaran denda.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php $i = 1; while($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['no_anggota']) . ' - ' . htmlspecialchars($row['nama_anggota']); ?></td>
                    <td><?= htmlspecialchars($row['buku_list']); ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_kembali'])); ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                    <td><?= number_format($row['total_denda'] ?? 0, 0, ',', '.'); ?> Rp</td>
                    <td>
                        <?php
                        $status = $row['status_bayar'] ?? 'belum_lunas';
                        $badgeClass = $status === 'lunas' ? 'badge-green' : 'badge-orange';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="edit.php?id=<?= $row['id_pembayaran'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                            <a href="delete.php?id=<?= $row['id_pembayaran'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus pembayaran ini?')">🗑️ Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
