<?php
// core/config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      
define('DB_PASS', '');          
define('DB_NAME', 'parkir-testing');


$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (mysqli_connect_errno()) {
    die("Koneksi database gagal : " . mysqli_connect_error());
}

define('JENIS_POS_INI', 'motor');
?>