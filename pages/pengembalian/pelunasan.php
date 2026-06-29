<?php
// pelunasan.php - process payment for a returned book fine
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_pengembalian'])) {
    $_SESSION['msg'] = 'Invalid request.';
    header('Location: index.php');
    exit;
}

$id = (int)$_POST['id_pengembalian'];
$db = getDB();

// Check if a payment record already exists
$check = $db->query("SELECT id_pembayaran FROM pembayaran_denda WHERE id_pengembalian = $id");
if ($check && $check->num_rows > 0) {
    // Update existing record to lunas
    $db->query("UPDATE pembayaran_denda SET status_bayar = 'lunas' WHERE id_pengembalian = $id");
} else {
    // Insert a new record assuming no fine (or zero) – adjust as needed
    $db->query("INSERT INTO pembayaran_denda (id_pengembalian, total_denda, status_bayar) VALUES ($id, 0, 'lunas')");
}

$_SESSION['msg'] = 'Pembayaran lunas berhasil.';
header('Location: index.php');
exit;
?>
