<?php
$pos_id_absen = $_SESSION['current_pos_id'];
$show_modal_absensi = false;

// Cek apakah sudah absen hari ini
$stmt_absen = $db->prepare("SELECT id FROM absensi WHERE user_id = ? AND pos_id = ? AND status = 'hadir' AND DATE(waktu_masuk) = CURDATE()");
$stmt_absen->bind_param("ii", $user_id, $pos_id_absen);
$stmt_absen->execute();

if ($stmt_absen->get_result()->num_rows == 0) {
    $show_modal_absensi = true; // Munculkan Modal Absen
}
?>