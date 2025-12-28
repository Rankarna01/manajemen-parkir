<?php
// File: api/ajax_handler_absen.php

// Aktifkan error reporting untuk debugging sementara
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Pastikan path ke init.php benar
// Karena file ini ada di folder 'api', maka naik satu level (..) untuk cari folder 'core'
require_once '../core/init.php';

// Cek Sesi
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login habis. Silakan login ulang.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['action'])) {
    
    // ==========================================
    // 1. ABSEN MASUK
    // ==========================================
    if ($_POST['action'] == 'absen_masuk') {
        $pos_id = $_POST['pos_id'];
        $lat    = $_POST['lat'];
        $long   = $_POST['long'];
        
        // Gabungkan koordinat
        $coords = $lat . "," . $long;

        // Query Insert
        $stmt = $db->prepare("INSERT INTO absensi (user_id, pos_id, koordinat_masuk, waktu_masuk, status) VALUES (?, ?, ?, NOW(), 'hadir')");
        
        if ($stmt) {
            $stmt->bind_param("iis", $user_id, $pos_id, $coords);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Absen masuk berhasil']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Query Error: ' . $db->error]);
        }
    }
    
    // ==========================================
    // 2. ABSEN KELUAR (PULANG)
    // ==========================================
    elseif ($_POST['action'] == 'absen_keluar') {
        $lat    = $_POST['lat'];
        $long   = $_POST['long'];
        $coords = $lat . "," . $long;
        
        // Update data terakhir yang statusnya 'hadir'
        $stmt = $db->prepare("UPDATE absensi SET waktu_keluar = NOW(), koordinat_keluar = ?, status = 'pulang' WHERE user_id = ? AND status = 'hadir'");
        
        if ($stmt) {
            $stmt->bind_param("si", $coords, $user_id);
            
            if ($stmt->execute()) {
                // Hapus sesi pos agar besok bisa pilih pos lagi
                unset($_SESSION['current_pos_id']);
                echo json_encode(['status' => 'success', 'message' => 'Hati-hati di jalan!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal update absen: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Query Error: ' . $db->error]);
        }
    }
    
    else {
        echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada data post']);
}
?>