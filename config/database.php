<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_peminjaman_buku');

// Simple base URL definition
define('BASE_URL', '/Database');

function getDB(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('<div class="alert alert-danger">Koneksi database gagal: ' . $conn->connect_error . '</div>');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
