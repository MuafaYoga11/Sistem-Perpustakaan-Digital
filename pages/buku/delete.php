<?php
session_start();
require_once '../../config/database.php';

$db = getDB();
$id = intval($_GET['id'] ?? 0);

$row = $db->query("SELECT judul FROM buku WHERE id_buku = $id")->fetch_assoc();
if ($row) {
    // Cek apakah masih ada di detail_peminjaman aktif
    $cek = $db->query("
        SELECT COUNT(*) FROM detail_peminjaman dp
        JOIN peminjaman p ON dp.id_peminjaman = p.id_peminjaman
        WHERE dp.id_buku = $id AND p.status IN ('aktif','terlambat')
    ")->fetch_row()[0];

    if ($cek > 0) {
        $_SESSION['msg_error'] = 'Buku masih dalam peminjaman aktif, tidak dapat dihapus.';
    } else {
        $db->query("DELETE FROM buku WHERE id_buku = $id");
        $_SESSION['msg'] = "Buku \"{$row['judul']}\" berhasil dihapus.";
    }
}
header('Location: index.php');
exit;
