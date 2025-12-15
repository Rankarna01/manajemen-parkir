<?php
//=========================================
// AJAX HANDLER UNTUK POS PARKING (POS MOTOR)
//=========================================

header('Content-Type: application/json');
require_once '../core/init.php'; 

$response = [
    'status' => 'error',
    'message' => 'Aksi tidak dikenal.'
];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Sesi Anda telah habis. Silakan login kembali.';
    echo json_encode($response); exit;
}

if (isset($_POST['action'])) {
    
    //=====================================
    // AKSI: KENDARAAN MASUK MANUAL
    //=====================================
    if ($_POST['action'] == 'kendaraan_masuk_manual') {
        $plat_nomor = strtoupper(trim($_POST['plat_nomor']));
        // Ambil jenis kendaraan dari POST, default 'motor'
        $jenis_kendaraan = $_POST['jenis_kendaraan'] ?? 'motor';
        $id_petugas_masuk = $_SESSION['user_id'];
        
        if (empty($plat_nomor)) {
            $response['message'] = 'Plat nomor wajib diisi.'; echo json_encode($response); exit;
        }

        // Cek duplikasi
        $stmt_cek = $db->prepare("SELECT t.id FROM transaksi_parkir t JOIN kendaraan k ON t.id_kendaraan = k.id WHERE k.plat_nomor = ? AND t.status = 'masuk'");
        $stmt_cek->bind_param("s", $plat_nomor); $stmt_cek->execute();
        if ($stmt_cek->get_result()->num_rows > 0) {
            $response['message'] = "Gagal! Kendaraan '$plat_nomor' sudah tercatat masuk."; echo json_encode($response); exit;
        }
        $stmt_cek->close();

        // Cari atau Buat data kendaraan
        $id_kendaraan = null;
        $stmt_find = $db->prepare("SELECT id FROM kendaraan WHERE plat_nomor = ?");
        $stmt_find->bind_param("s", $plat_nomor); $stmt_find->execute();
        $res_kend = $stmt_find->get_result();
        
        if ($res_kend->num_rows > 0) {
            $id_kendaraan = $res_kend->fetch_assoc()['id'];
        } else {
            $stmt_ins = $db->prepare("INSERT INTO kendaraan (plat_nomor, jenis) VALUES (?, ?)");
            $stmt_ins->bind_param("ss", $plat_nomor, $jenis_kendaraan);
            $stmt_ins->execute();
            $id_kendaraan = $stmt_ins->insert_id;
            $stmt_ins->close();
        }
        $stmt_find->close();

        // Simpan Transaksi
        $waktu_masuk = date('Y-m-d H:i:s');
        $kode_prefix = 'PK-MTR-' . date('Ymd') . '-'; // Prefix MTR untuk Motor

        $stmt_trx = $db->prepare("INSERT INTO transaksi_parkir (id_kendaraan, kode_barcode, waktu_masuk, status, id_petugas_masuk) VALUES (?, '', ?, 'masuk', ?)");
        $stmt_trx->bind_param("isi", $id_kendaraan, $waktu_masuk, $id_petugas_masuk);
        
        if ($stmt_trx->execute()) {
            $trx_id = $stmt_trx->insert_id;
            $barcode_final = $kode_prefix . str_pad($trx_id, 5, '0', STR_PAD_LEFT);
            $db->query("UPDATE transaksi_parkir SET kode_barcode = '$barcode_final' WHERE id = $trx_id");

            $response['status'] = 'success';
            $response['message'] = 'Motor berhasil dicatat.';
            $response['data'] = [
                'transaksi_id' => $trx_id,
                'kode_barcode' => $barcode_final,
                'plat_nomor' => $plat_nomor,
            ];
        } else {
            $response['message'] = 'Gagal menyimpan data transaksi.';
        }
        $stmt_trx->close();
    }
    
    //=====================================
    // AKSI: AMBIL TIKET OTOMATIS (MANLESS)
    //=====================================
    elseif ($_POST['action'] == 'ambil_tiket_otomatis') {
        $id_petugas = $_SESSION['user_id'];
        
        // Ambil jenis kendaraan dari POST, default 'motor'
        $jenis_kendaraan = $_POST['jenis_kendaraan'] ?? 'motor';

        $plat_sementara = "MTR-" . date('Hi') . "-" . rand(10,99);

        // Simpan Kendaraan Sementara
        $stmt_kend = $db->prepare("INSERT INTO kendaraan (plat_nomor, jenis) VALUES (?, ?)");
        $stmt_kend->bind_param("ss", $plat_sementara, $jenis_kendaraan);
        $stmt_kend->execute();
        $id_kendaraan = $stmt_kend->insert_id;
        $stmt_kend->close();

        // Simpan Transaksi
        $waktu_masuk = date('Y-m-d H:i:s');
        $kode_prefix = 'PK-MTR-' . date('Ymd') . '-';
        
        $stmt_trx = $db->prepare("INSERT INTO transaksi_parkir (id_kendaraan, kode_barcode, waktu_masuk, status, id_petugas_masuk) VALUES (?, '', ?, 'masuk', ?)");
        $stmt_trx->bind_param("isi", $id_kendaraan, $waktu_masuk, $id_petugas);
        
        if ($stmt_trx->execute()) {
            $trx_id = $stmt_trx->insert_id;
            $barcode_final = $kode_prefix . str_pad($trx_id, 5, '0', STR_PAD_LEFT);
            $db->query("UPDATE transaksi_parkir SET kode_barcode = '$barcode_final' WHERE id = $trx_id");

            $response['status'] = 'success';
            $response['message'] = 'Tiket Motor Keluar.';
            $response['data'] = [
                'transaksi_id' => $trx_id,
                'kode_barcode' => $barcode_final,
                'plat_nomor'   => $plat_sementara,
                'waktu_masuk'  => $waktu_masuk
            ];
        } else {
            $response['message'] = 'Gagal generate tiket.';
        }
        $stmt_trx->close();
    }

    //=====================================
    // AKSI: CARI KENDARAAN (KELUAR)
    //=====================================
    elseif ($_POST['action'] == 'cari_tiket_atau_plat') {
        $kode_input = trim($_POST['kode_input']);
        if (empty($kode_input)) {
            $response['message'] = 'Input kosong.'; echo json_encode($response); exit;
        }

        $stmt = $db->prepare("SELECT t.id AS transaksi_id, t.waktu_masuk, k.plat_nomor, k.jenis, tar.tarif_per_jam 
                              FROM transaksi_parkir t 
                              JOIN kendaraan k ON t.id_kendaraan = k.id 
                              LEFT JOIN tarif_parkir tar ON k.jenis = tar.jenis_kendaraan 
                              WHERE (t.kode_barcode = ? OR k.plat_nomor = ?) AND t.status = 'masuk'");
        $stmt->bind_param("ss", $kode_input, $kode_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $response['message'] = "Data tidak ditemukan."; echo json_encode($response); exit;
        }

        $data = $result->fetch_assoc();
        
        // Cek apakah jenis kendaraan sesuai pos (MOTOR)
        if (strtolower($data['jenis']) != 'motor') {
            $response['message'] = "Salah Jalur! Ini Pos Motor, kendaraan terdeteksi: " . ucfirst($data['jenis']);
            echo json_encode($response); exit;
        }

        // Hitung Biaya
        $waktu_masuk = new DateTime($data['waktu_masuk']);
        $waktu_sekarang = new DateTime();
        $durasi_detik = $waktu_sekarang->getTimestamp() - $waktu_masuk->getTimestamp();
        $total_jam = ceil($durasi_detik / 3600);
        if ($total_jam <= 0) $total_jam = 1;

        $tarif_per_jam = (float) $data['tarif_per_jam'];
        $total_biaya = $total_jam * $tarif_per_jam;
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
            'total_biaya_format' => number_format($total_biaya, 0, ',', '.')
        ];
        $stmt->close();
    }
    
    //=====================================
    // AKSI: PROSES KELUAR
    //=====================================
    elseif ($_POST['action'] == 'proses_keluar') {
        $transaksi_id = $_POST['transaksi_id'];
        $total_biaya = $_POST['total_biaya'];
        $id_petugas_keluar = $_SESSION['user_id'];
        $waktu_keluar = date('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE transaksi_parkir SET status = 'keluar', waktu_keluar = ?, biaya = ?, id_petugas_keluar = ? WHERE id = ? AND status = 'masuk'");
        $stmt->bind_param("sdii", $waktu_keluar, $total_biaya, $id_petugas_keluar, $transaksi_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $response['status'] = 'success';
            $response['transaksi_id'] = $transaksi_id;
        } else {
            $response['message'] = 'Gagal proses keluar.';
        }
        $stmt->close();
    }
}

echo json_encode($response);
$db->close();
exit;
?>