<?php
$pos_id_cfg = $_SESSION['current_pos_id'];
$stmt_cfg = $db->prepare("SELECT * FROM pengaturan_sistem WHERE id = ?");
$stmt_cfg->bind_param("i", $pos_id_cfg);
$stmt_cfg->execute();
$config = $stmt_cfg->get_result()->fetch_assoc();

// Validasi jika pos dihapus owner saat sesi aktif
if (!$config) {
    unset($_SESSION['current_pos_id']);
    header("Refresh:0"); exit;
}

$page_title = "Pos: " . $config['nama_pos'];
?>