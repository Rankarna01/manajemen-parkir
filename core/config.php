<?php
// core/config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Ganti dengan username DB Anda
define('DB_PASS', '');          // Ganti dengan password DB Anda
define('DB_NAME', 'db_parkir_otomatis');

// Buat koneksi
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (mysqli_connect_errno()) {
    die("Koneksi database gagal : " . mysqli_connect_error());
}
?>