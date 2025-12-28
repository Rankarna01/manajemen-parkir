<?php
// Cek Penugasan User (Assignment dari Owner)
$cek_assign = $db->query("SELECT assigned_pos_id FROM users WHERE id = $user_id")->fetch_assoc();

if (!empty($cek_assign['assigned_pos_id'])) {
    $_SESSION['current_pos_id'] = $cek_assign['assigned_pos_id'];
}

// Cek Inputan Modal (Jika User Memilih Sendiri)
if (isset($_POST['pilih_pos_id'])) {
    $_SESSION['current_pos_id'] = $_POST['pilih_pos_id'];
    header("Refresh:0"); // Reload agar config termuat
    exit;
}

// Cek Status Session
$show_modal_pilih_pos = false;
if (!isset($_SESSION['current_pos_id'])) {
    $daftar_pos = $db->query("SELECT * FROM pengaturan_sistem");
    $show_modal_pilih_pos = true;
}
?>