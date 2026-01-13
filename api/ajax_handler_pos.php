<?php
//============================================================
// AJAX HANDLER POS PARKIR - FINAL COMPLETE VERSION
// Fitur: Manual Entry, Manless, RFID Member, QRIS, Exit Logic
//============================================================

// 1. Konfigurasi Header & Error Handling
header('Content-Type: application/json');
// Matikan tampilan error HTML agar respon JSON tidak rusak (Clean JSON)
ini_set('display_errors', 0); 
error_reporting(0);

require_once '../core/init.php'; 

$response = [
    'status' => 'error',
    'message' => 'Aksi tidak dikenal.'
];

// 2. Cek Sesi Login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Sesi habis. Silakan login ulang.';
    echo json_encode($response); exit;
}

// 3. Ambil ID Pos Petugas saat ini (Untuk data pos_masuk_id)
$id_petugas = $_SESSION['user_id'];
$pos_masuk_id = 0; // Default

// Cek di database user ini ditugaskan di pos mana
$q_cek_pos = $db->query("SELECT assigned_pos_id FROM users WHERE id = $id_petugas");
if ($q_cek_pos && $row = $q_cek_pos->fetch_assoc()) {
    $pos_masuk_id = $row['assigned_pos_id'];
}

if (isset($_POST['action'])) {
    
    //================================================================
    // 1. AKSI: KENDARAAN MASUK MANUAL (Handled by Petugas)
    //================================================================
    if ($_POST['action'] == 'kendaraan_masuk_manual') {
        // Validasi Parameter
        if (!isset($_POST['plat_nomor'])) {
            echo json_encode(['status'=>'error', 'message'=>'Parameter plat_nomor hilang!']); exit;
        }

        $plat_nomor = strtoupper(trim($_POST['plat_nomor']));
        $jenis_kendaraan = $_POST['jenis_kendaraan'] ?? 'motor';
        
        if (empty($plat_nomor)) {
            echo json_encode(['status'=>'error', 'message'=>'Plat nomor wajib diisi.']); exit;
        }

        // A. Cek Duplikasi (Apakah plat ini statusnya masih 'masuk'?)
        $stmt_cek = $db->prepare("SELECT t.id FROM transaksi_parkir t JOIN kendaraan k ON t.id_kendaraan = k.id WHERE k.plat_nomor = ? AND t.status = 'masuk'");
        if (!$stmt_cek) { echo json_encode(['status'=>'error', 'message'=>'SQL Error (Cek): '.$db->error]); exit; }
        
        $stmt_cek->bind_param("s", $plat_nomor); 
        $stmt_cek->execute();
        if ($stmt_cek->get_result()->num_rows > 0) {
            echo json_encode(['status'=>'error', 'message'=>"Gagal! Kendaraan '$plat_nomor' masih ada di dalam (belum keluar)."]); exit;
        }
        $stmt_cek->close();

        // B. Cari atau Buat Data Kendaraan (Master Data)
        $id_kendaraan = null;
        $stmt_find = $db->prepare("SELECT id FROM kendaraan WHERE plat_nomor = ?");
        $stmt_find->bind_param("s", $plat_nomor); 
        $stmt_find->execute();
        $res_kend = $stmt_find->get_result();
        
        if ($res_kend->num_rows > 0) {
            // Plat lama
            $id_kendaraan = $res_kend->fetch_assoc()['id'];
        } else {
            // Plat baru
            $stmt_ins = $db->prepare("INSERT INTO kendaraan (plat_nomor, jenis) VALUES (?, ?)");
            $stmt_ins->bind_param("ss", $plat_nomor, $jenis_kendaraan);
            if (!$stmt_ins->execute()) {
                echo json_encode(['status'=>'error', 'message'=>'Gagal Simpan Kendaraan: '.$stmt_ins->error]); exit;
            }
            $id_kendaraan = $stmt_ins->insert_id;
            $stmt_ins->close();
        }
        $stmt_find->close();

        // C. Simpan Transaksi Masuk
        $waktu_masuk = date('Y-m-d H:i:s');
        
        // Pastikan tabel transaksi_parkir memiliki kolom 'pos_masuk_id'
        $query_trx = "INSERT INTO transaksi_parkir (id_kendaraan, kode_barcode, waktu_masuk, status, id_petugas_masuk, pos_masuk_id) VALUES (?, '', ?, 'masuk', ?, ?)";
        $stmt_trx = $db->prepare($query_trx);
        
        if (!$stmt_trx) {
            echo json_encode(['status'=>'error', 'message'=>'SQL Error (Insert Transaksi). Cek kolom pos_masuk_id di database! Error: '.$db->error]); exit;
        }

        $stmt_trx->bind_param("isii", $id_kendaraan, $waktu_masuk, $id_petugas, $pos_masuk_id);
        
        if ($stmt_trx->execute()) {
            $trx_id = $stmt_trx->insert_id;
            
            // Generate Barcode Unik
            $kode_prefix = 'PK-' . strtoupper(substr($jenis_kendaraan, 0, 3)) . '-' . date('Ymd') . '-';
            $barcode_final = $kode_prefix . str_pad($trx_id, 5, '0', STR_PAD_LEFT);
            
            $db->query("UPDATE transaksi_parkir SET kode_barcode = '$barcode_final' WHERE id = $trx_id");

            $response['status'] = 'success';
            $response['message'] = 'Kendaraan berhasil dicatat.';
            $response['data'] = [
                'transaksi_id' => $trx_id,
                'kode_barcode' => $barcode_final,
                'plat_nomor' => $plat_nomor,
                'jenis' => ucfirst($jenis_kendaraan),
                'waktu_masuk' => $waktu_masuk
            ];
        } else {
            $response['message'] = 'Gagal Execute Transaksi: ' . $stmt_trx->error;
        }
        $stmt_trx->close();
    }
    
    //================================================================
    // 2. AKSI: AMBIL TIKET OTOMATIS (Manless)
    //================================================================
    elseif ($_POST['action'] == 'ambil_tiket_otomatis') {
        $jenis_kendaraan = $_POST['jenis_kendaraan'] ?? 'motor';
        $plat_sementara = strtoupper(substr($jenis_kendaraan, 0, 3)) . "-" . date('Hi') . "-" . rand(10,99);

        // Insert Kendaraan Sementara
        $stmt_kend = $db->prepare("INSERT INTO kendaraan (plat_nomor, jenis) VALUES (?, ?)");
        $stmt_kend->bind_param("ss", $plat_sementara, $jenis_kendaraan);
        if (!$stmt_kend->execute()) {
             echo json_encode(['status'=>'error', 'message'=>'Gagal Insert Kendaraan Manless: '.$stmt_kend->error]); exit;
        }
        $id_kendaraan = $stmt_kend->insert_id;
        $stmt_kend->close();

        // Insert Transaksi
        $waktu_masuk = date('Y-m-d H:i:s');
        $stmt_trx = $db->prepare("INSERT INTO transaksi_parkir (id_kendaraan, kode_barcode, waktu_masuk, status, id_petugas_masuk, pos_masuk_id) VALUES (?, '', ?, 'masuk', ?, ?)");
        $stmt_trx->bind_param("isii", $id_kendaraan, $waktu_masuk, $id_petugas, $pos_masuk_id);
        
        if ($stmt_trx->execute()) {
            $trx_id = $stmt_trx->insert_id;
            $kode_prefix = 'PK-' . strtoupper(substr($jenis_kendaraan, 0, 3)) . '-' . date('Ymd') . '-';
            $barcode_final = $kode_prefix . str_pad($trx_id, 5, '0', STR_PAD_LEFT);
            $db->query("UPDATE transaksi_parkir SET kode_barcode = '$barcode_final' WHERE id = $trx_id");

            $response['status'] = 'success';
            $response['message'] = 'Tiket Keluar.';
            $response['data'] = [
                'transaksi_id' => $trx_id,
                'kode_barcode' => $barcode_final,
                'plat_nomor'   => $plat_sementara,
                'waktu_masuk'  => $waktu_masuk
            ];
        } else {
            $response['message'] = 'Gagal Generate Tiket: ' . $stmt_trx->error;
        }
        $stmt_trx->close();
    }

    //================================================================
    // 3. AKSI: CARI KENDARAAN (KELUAR) - CORE LOGIC RFID MEMBER
    //================================================================
    elseif ($_POST['action'] == 'cari_tiket_atau_plat') {
        $kode_input = trim($_POST['kode_input']);
        if (empty($kode_input)) { echo json_encode(['status'=>'error', 'message'=>'Input kosong.']); exit; }

        // --- A. LOGIKA DETEKSI RFID MEMBER ---
        // Kita cek dulu: Apakah kode yg diinput/scan ini ada di tabel members?
        
        $search_key = $kode_input; // Default: cari berdasarkan input mentah (barcode tiket/plat)
        $is_detected_via_rfid = false;

        $stmt_member = $db->prepare("SELECT plat_nomor FROM members WHERE rfid_uid = ? AND status = 'aktif' LIMIT 1");
        $stmt_member->bind_param("s", $kode_input);
        $stmt_member->execute();
        $res_member = $stmt_member->get_result();
        
        if ($res_member->num_rows > 0) {
            // YES! Ini adalah kartu RFID Member
            $m = $res_member->fetch_assoc();
            
            // KUNCI PERBAIKAN: 
            // Ganti kunci pencarian menjadi PLAT NOMOR MEMBER.
            // Karena di tabel transaksi, yg disimpan adalah Plat Nomor, bukan UID Kartu.
            $search_key = $m['plat_nomor']; 
            $is_detected_via_rfid = true;
        }
        $stmt_member->close();

        // --- B. CARI TRANSAKSI PARKIR YANG AKTIF ('masuk') ---
        $stmt = $db->prepare("SELECT t.id AS transaksi_id, t.waktu_masuk, k.plat_nomor, k.jenis, tar.tarif_per_jam 
                              FROM transaksi_parkir t 
                              JOIN kendaraan k ON t.id_kendaraan = k.id 
                              LEFT JOIN tarif_parkir tar ON k.jenis = tar.jenis_kendaraan 
                              WHERE (t.kode_barcode = ? OR k.plat_nomor = ?) AND t.status = 'masuk'");
        
        // Cari menggunakan $search_key (bisa Barcode Tiket ATAU Plat Nomor Member)
        $stmt->bind_param("ss", $search_key, $search_key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            if ($is_detected_via_rfid) {
                // Pesan Spesifik jika Kartu Valid tapi Mobil Gak Ada
                $response['message'] = "Kartu Member Valid ($search_key), tapi kendaraan tidak tercatat masuk (Wajib Check-In dulu).";
            } else {
                $response['message'] = "Tiket/Plat tidak ditemukan atau sudah keluar.";
            }
            echo json_encode($response); exit;
        }

        $data = $result->fetch_assoc();
        
        // --- C. HITUNG BIAYA ---
        $waktu_masuk = new DateTime($data['waktu_masuk']);
        $waktu_sekarang = new DateTime();
        $durasi_detik = $waktu_sekarang->getTimestamp() - $waktu_masuk->getTimestamp();
        $total_jam = ceil($durasi_detik / 3600);
        if ($total_jam <= 0) $total_jam = 1;

        $tarif_per_jam = (float) $data['tarif_per_jam'];
        $total_biaya = $total_jam * $tarif_per_jam;
        
        // --- D. CEK ULANG MEMBER (UNTUK DISKON) ---
        // Memastikan kendaraan yang keluar memang milik member aktif
        $is_member = false;
        $member_name = "";
        
        $stmt_mem_check = $db->prepare("SELECT nama, status, tanggal_expired FROM members WHERE plat_nomor = ? LIMIT 1");
        $stmt_mem_check->bind_param("s", $data['plat_nomor']);
        $stmt_mem_check->execute();
        $res_mem_check = $stmt_mem_check->get_result();

        if ($res_mem_check->num_rows > 0) {
            $member_data = $res_mem_check->fetch_assoc();
            $today = date('Y-m-d');
            
            // Validasi Masa Aktif
            if ($member_data['status'] == 'aktif' && $member_data['tanggal_expired'] >= $today) {
                $is_member = true;
                $member_name = $member_data['nama'];
                $total_biaya = 0; // GRATIS
            }
        }
        $stmt_mem_check->close();

        // Format Output untuk Frontend
        $durasi = $waktu_sekarang->diff($waktu_masuk);
        $durasi_format = $durasi->d . ' hari, ' . $durasi->h . ' jam, ' . $durasi->i . ' mnt';
        
        $response['status'] = 'success';
        $response['data'] = [
            'transaksi_id' => $data['transaksi_id'],
            'plat_nomor' => $data['plat_nomor'],
            'jenis' => ucfirst($data['jenis']),
            'waktu_masuk_format' => date('d M Y, H:i:s', strtotime($data['waktu_masuk'])),
            'durasi_format' => $durasi_format,
            'total_biaya' => $total_biaya,
            'total_biaya_format' => number_format($total_biaya, 0, ',', '.'),
            'is_member' => $is_member,
            'member_nama' => $member_name
        ];
        $stmt->close();
    }
    
    //================================================================
    // 4. AKSI: PROSES KELUAR (FINISH)
    //================================================================
    elseif ($_POST['action'] == 'proses_keluar') {
        $transaksi_id = $_POST['transaksi_id'];
        $total_biaya = $_POST['total_biaya'];
        $id_petugas_keluar = $_SESSION['user_id'];
        $waktu_keluar = date('Y-m-d H:i:s');
        
        // Deteksi Member dari biaya
        $is_member_trx = ($total_biaya == 0) ? 1 : 0;

        // Pastikan kolom 'pos_masuk_id' dan 'is_member' ada di DB
        $stmt = $db->prepare("UPDATE transaksi_parkir SET status = 'keluar', waktu_keluar = ?, biaya = ?, id_petugas_keluar = ?, is_member = ? WHERE id = ? AND status = 'masuk'");
        
        if (!$stmt) { echo json_encode(['status'=>'error', 'message'=>'SQL Error (Update Keluar): '.$db->error]); exit; }

        $stmt->bind_param("sdiii", $waktu_keluar, $total_biaya, $id_petugas_keluar, $is_member_trx, $transaksi_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['status'] = 'success';
                $response['transaksi_id'] = $transaksi_id;
            } else {
                $response['message'] = 'Gagal update. Transaksi mungkin sudah selesai.';
            }
        } else {
            $response['message'] = 'Gagal Execute Keluar: ' . $stmt->error;
        }
        $stmt->close();
    }

    //================================================================
    // 5. FITUR TAMBAHAN (QRIS SIMULASI)
    //================================================================
    elseif ($_POST['action'] == 'generate_qris') {
        $transaksi_id = $_POST['transaksi_id'];
        $total_biaya  = $_POST['total_biaya'];
        $qris_content = "PARKIR_TX_" . $transaksi_id . "_RP_" . $total_biaya;
        $qr_image_url = "https://bwipjs-api.metafloor.com/?bcid=qrcode&text=" . urlencode($qris_content) . "&scale=3";
        echo json_encode(['status' => 'success', 'qr_url' => $qr_image_url]); exit;
    }
    elseif ($_POST['action'] == 'cek_status_qris') {
        // Simulasi selalu sukses
        echo json_encode(['status' => 'paid']); exit;
    }
}

echo json_encode($response);
$db->close();
exit;
?>