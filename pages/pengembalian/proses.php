<?php
$title = 'Proses Pengembalian';
$page  = 'pengembalian';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$db        = getDB();
$id_pinjam = intval($_GET['id'] ?? 0);
$errors    = [];

// Ambil data peminjaman
$pinjam = $db->query("
    SELECT p.*, u.nama AS nama_anggota, a.no_anggota,
           GROUP_CONCAT(b.judul SEPARATOR ', ') AS buku_list,
           GROUP_CONCAT(b.id_buku)              AS buku_ids,
           COUNT(dp.id_buku)                    AS jumlah_buku
    FROM peminjaman p
    JOIN anggota a          ON p.id_anggota    = a.id_anggota
    JOIN users u            ON a.id_user       = u.id_user
    JOIN detail_peminjaman dp ON dp.id_peminjaman = p.id_peminjaman
    JOIN buku b             ON dp.id_buku      = b.id_buku
    WHERE p.id_peminjaman = $id_pinjam AND p.status IN ('dipinjam','terlambat')
    GROUP BY p.id_peminjaman
")->fetch_assoc();

if (!$pinjam) {
    echo '<div class="alert alert-danger">Data peminjaman tidak ditemukan atau sudah dikembalikan.</div>';
    echo '<a href="index.php" class="btn btn-secondary">← Kembali</a>';
    require_once '../../includes/footer.php';
    exit;
}

// ── Konfigurasi Denda ───────────────────────────────────────────────
$TARIF_PER_HARI = 1000; // Rp 1.000 per hari per buku

// ── Hitung denda dari tanggal yang dipilih ─────────────────────────
$tgl_kembali_input = $_POST['tanggal_kembali'] ?? date('Y-m-d');
$jatuh_tempo       = $pinjam['tanggal_jatuh_tempo'];

// Selisih hari (jika tgl kembali > jatuh tempo = terlambat)
$dt_kembali     = new DateTime($tgl_kembali_input);
$dt_jatuh       = new DateTime($jatuh_tempo);
$hari_terlambat = max(0, (int)$dt_kembali->diff($dt_jatuh)->format('%r%a'));
// Negatif berarti terlambat, positif/0 berarti tepat/lebih awal
$selisih_raw    = (int)$dt_kembali->diff($dt_jatuh)->format('%r%a');
$hari_terlambat = max(0, -$selisih_raw); // terlambat jika negatif

$jumlah_buku    = (int)($pinjam['jumlah_buku'] ?? 1);
$total_denda    = $hari_terlambat * $TARIF_PER_HARI; // per buku bisa dikali $jumlah_buku

// ── Proses POST ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl_kembali  = trim($_POST['tanggal_kembali'] ?? date('Y-m-d'));
    $kondisi      = $_POST['kondisi_buku']  ?? 'baik';
    $catatan      = trim($_POST['catatan']  ?? '');
    $status_bayar = $_POST['status_bayar']  ?? 'belum_lunas';

    // Hitung ulang denda dari nilai POST (validasi server-side)
    $dt_k2         = new DateTime($tgl_kembali);
    $dt_j2         = new DateTime($jatuh_tempo);
    $selisih2      = (int)$dt_k2->diff($dt_j2)->format('%r%a');
    $hari_terlambat = max(0, -$selisih2);
    $total_denda   = $hari_terlambat * $TARIF_PER_HARI;
    $status_kembali = $hari_terlambat > 0 ? 'terlambat' : 'tepat_waktu';

    if (empty($tgl_kembali)) {
        $errors[] = 'Tanggal kembali wajib diisi.';
    }

    if (empty($errors)) {
        $db->begin_transaction();
        try {
            // Cek kolom tabel pengembalian (fallback jika kolom tidak ada)
            $cols = [];
            $colRes = $db->query("DESCRIBE pengembalian");
            while ($c = $colRes->fetch_assoc()) $cols[] = $c['Field'];

            if (in_array('terlambat_hari', $cols) && in_array('status_pengembalian', $cols) && in_array('kondisi_buku', $cols)) {
                $stmt = $db->prepare("INSERT INTO pengembalian
                    (id_peminjaman, tanggal_kembali, terlambat_hari, status_pengembalian, kondisi_buku, catatan)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('isisss', $id_pinjam, $tgl_kembali, $hari_terlambat, $status_kembali, $kondisi, $catatan);
            } elseif (in_array('kondisi_buku', $cols)) {
                $stmt = $db->prepare("INSERT INTO pengembalian
                    (id_peminjaman, tanggal_kembali, kondisi_buku, catatan)
                    VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isss', $id_pinjam, $tgl_kembali, $kondisi, $catatan);
            } else {
                $stmt = $db->prepare("INSERT INTO pengembalian
                    (id_peminjaman, tanggal_kembali, catatan)
                    VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $id_pinjam, $tgl_kembali, $catatan);
            }
            $stmt->execute();
            $id_kembalian = $db->insert_id;

            // Insert / update denda (cek apakah tabel pembayaran_denda ada)
            $tblRes = $db->query("SHOW TABLES LIKE 'pembayaran_denda'");
            if ($tblRes && $tblRes->num_rows > 0) {
                // Cek kolom pembayaran_denda
                $dcols = [];
                $dcolRes = $db->query("DESCRIBE pembayaran_denda");
                while ($dc = $dcolRes->fetch_assoc()) $dcols[] = $dc['Field'];

                if (in_array('tarif_per_hari', $dcols)) {
                    $stmt2 = $db->prepare("INSERT INTO pembayaran_denda
                        (id_pengembalian, tarif_per_hari, hari_terlambat, total_denda, status_bayar)
                        VALUES (?, ?, ?, ?, ?)");
                    $stmt2->bind_param('iiids', $id_kembalian, $TARIF_PER_HARI, $hari_terlambat, $total_denda, $status_bayar);
                } else {
                    $stmt2 = $db->prepare("INSERT INTO pembayaran_denda
                        (id_pengembalian, total_denda, status_bayar)
                        VALUES (?, ?, ?)");
                    $stmt2->bind_param('ids', $id_kembalian, $total_denda, $status_bayar);
                }
                $stmt2->execute();
            }

            // Update status peminjaman → dikembalikan
            $db->query("UPDATE peminjaman SET status='dikembalikan' WHERE id_peminjaman=$id_pinjam");

            // Kembalikan stok buku
            foreach (explode(',', $pinjam['buku_ids']) as $bid) {
                $db->query("UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id_buku = " . intval($bid));
            }

            $db->commit();
            $_SESSION['msg'] = 'Pengembalian berhasil! Denda: Rp ' . number_format($total_denda, 0, ',', '.');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Gagal menyimpan: ' . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
  <h1 class="page-title">Proses Pengembalian Buku</h1>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<div class="card" style="max-width:640px;">

  <!-- Info Peminjaman -->
  <div style="background:var(--clr-bg);border-radius:var(--radius-sm);padding:16px;margin-bottom:24px;border-left:4px solid var(--clr-sage);">
    <p><strong>Anggota:</strong> <?= htmlspecialchars($pinjam['no_anggota'] . ' — ' . $pinjam['nama_anggota']) ?></p>
    <p><strong>Buku:</strong> <?= htmlspecialchars($pinjam['buku_list']) ?></p>
    <p><strong>Tanggal Pinjam:</strong> <?= date('d M Y', strtotime($pinjam['tanggal_pinjam'])) ?></p>
    <p><strong>Jatuh Tempo:</strong> <span style="color:<?= strtotime('today') > strtotime($jatuh_tempo) ? '#e74c3c' : 'inherit' ?>;font-weight:600;"><?= date('d M Y', strtotime($jatuh_tempo)) ?></span></p>
  </div>

  <form method="POST" id="formKembali">
    <!-- Tanggal Kembali -->
    <div class="form-group">
      <label class="form-label">Tanggal Pengembalian *</label>
      <input type="date" name="tanggal_kembali" id="tgl_kembali" class="form-control"
             value="<?= htmlspecialchars($tgl_kembali_input) ?>"
             onchange="hitungDenda(this.value)" required>
      <p class="form-hint">Pilih tanggal bebas — denda dihitung otomatis jika melewati jatuh tempo.</p>
    </div>

    <!-- Kalkulasi Denda — Real-time -->
    <div id="denda-box" style="border-radius:var(--radius-sm);padding:18px;margin-bottom:20px;transition:background .3s;
         background:<?= $hari_terlambat > 0 ? '#fde8e0' : '#d4edda' ?>;">
      <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
        <div>
          <div style="font-size:.78rem;color:#666;margin-bottom:2px;">Hari Terlambat</div>
          <div id="val-hari" style="font-size:1.6rem;font-weight:700;color:<?= $hari_terlambat > 0 ? '#e74c3c' : '#27ae60' ?>">
            <?= $hari_terlambat ?> hari
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:.78rem;color:#666;margin-bottom:2px;">Tarif / hari</div>
          <div style="font-size:1.2rem;font-weight:600;">Rp <?= number_format($TARIF_PER_HARI, 0, ',', '.') ?></div>
        </div>
      </div>

      <!-- Rumus denda -->
      <div id="rumus-denda" style="font-size:.9rem;color:#555;margin-bottom:10px;font-family:monospace;background:rgba(0,0,0,.05);padding:6px 10px;border-radius:6px;">
        <?= $hari_terlambat ?> hari × Rp <?= number_format($TARIF_PER_HARI, 0, ',', '.') ?> = Rp <?= number_format($total_denda, 0, ',', '.') ?>
      </div>

      <div style="border-top:1px solid rgba(0,0,0,.08);padding-top:10px;">
        <span style="font-size:.85rem;color:#555;">Total Denda:</span>
        <span id="val-denda" style="font-size:1.5rem;font-weight:700;margin-left:8px;color:<?= $hari_terlambat > 0 ? '#e74c3c' : '#27ae60' ?>">
          Rp <?= number_format($total_denda, 0, ',', '.') ?>
        </span>
      </div>
    </div>

    <!-- Kondisi Buku -->
    <div class="form-group">
      <label class="form-label">Kondisi Buku</label>
      <select name="kondisi_buku" class="form-control">
        <option value="baik"         <?= ($_POST['kondisi_buku'] ?? '') === 'baik'         ? 'selected' : '' ?>>Baik</option>
        <option value="rusak ringan" <?= ($_POST['kondisi_buku'] ?? '') === 'rusak ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
        <option value="rusak berat"  <?= ($_POST['kondisi_buku'] ?? '') === 'rusak berat'  ? 'selected' : '' ?>>Rusak Berat</option>
      </select>
    </div>

    <!-- Status Bayar Denda -->
    <div class="form-group">
      <label class="form-label">Status Pembayaran Denda</label>
      <select name="status_bayar" class="form-control">
        <option value="belum_lunas" <?= ($_POST['status_bayar'] ?? '') === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
        <option value="lunas"       <?= ($_POST['status_bayar'] ?? '') === 'lunas'       ? 'selected' : '' ?>>Lunas</option>
      </select>
    </div>

    <!-- Catatan -->
    <div class="form-group">
      <label class="form-label">Catatan</label>
      <textarea name="catatan" class="form-control" rows="3"><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
    </div>

    <div style="display:flex;gap:12px;">
      <button type="submit" class="btn btn-primary">✅ Konfirmasi Pengembalian</button>
      <a href="index.php" class="btn btn-secondary">Batal</a>
    </div>
  </form>
</div>

<script>
const JATUH_TEMPO  = '<?= $jatuh_tempo ?>';   // format YYYY-MM-DD
const TARIF        = <?= $TARIF_PER_HARI ?>;  // per hari

function hitungDenda(tglKembali) {
  if (!tglKembali) return;

  const dtKembali  = new Date(tglKembali + 'T00:00:00');
  const dtJatuh    = new Date(JATUH_TEMPO + 'T00:00:00');
  const diffMs     = dtKembali - dtJatuh;
  const hari       = Math.max(0, Math.floor(diffMs / 86400000));
  const totalDenda = hari * TARIF;

  // Update elemen
  const warna = hari > 0 ? '#e74c3c' : '#27ae60';
  document.getElementById('val-hari').textContent   = hari + ' hari';
  document.getElementById('val-hari').style.color   = warna;
  document.getElementById('val-denda').textContent  = 'Rp ' + totalDenda.toLocaleString('id-ID');
  document.getElementById('val-denda').style.color  = warna;
  document.getElementById('denda-box').style.background = hari > 0 ? '#fde8e0' : '#d4edda';

  // Update rumus
  document.getElementById('rumus-denda').textContent =
    hari + ' hari × Rp ' + TARIF.toLocaleString('id-ID') +
    ' = Rp ' + totalDenda.toLocaleString('id-ID');
}

// Jalankan sekali saat load
hitungDenda(document.getElementById('tgl_kembali').value);
</script>

<?php require_once '../../includes/footer.php'; ?>
