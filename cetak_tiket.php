<?php
// FILE: cetak_tiket.php
require_once 'core/init.php';
require_once 'vendor/autoload.php'; // Pastikan folder vendor Dompdf ada

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Validasi Input
if (!isset($_GET['id'])) {
    die("Error: ID Transaksi tidak ditemukan.");
}

$transaksi_id = (int) $_GET['id'];

// 2. Ambil Data Transaksi
$stmt = $db->prepare(
    "SELECT 
        t.id AS id_transaksi,
        t.kode_barcode, 
        t.waktu_masuk,
        k.plat_nomor, 
        k.jenis,
        petugas_masuk.nama AS nama_petugas_masuk
     FROM transaksi_parkir t
     JOIN kendaraan k ON t.id_kendaraan = k.id
     JOIN users petugas_masuk ON t.id_petugas_masuk = petugas_masuk.id
     WHERE t.id = ?"
);
$stmt->bind_param("i", $transaksi_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: Data transaksi tidak ditemukan.");
}

$data = $result->fetch_assoc();

// 3. GENERATE BARCODE (Metode Paling Stabil: API Online)
// Kita gunakan bwip-js API. Ini menghasilkan gambar barcode Code 128 yang presisi.
// Tidak perlu install library tambahan di server.
$barcode_text = $data['kode_barcode'];
$barcode_url  = "https://bwipjs-api.metafloor.com/?bcid=code128&text={$barcode_text}&scale=3&height=12&includetext";

// Convert gambar ke Base64 agar bisa masuk ke PDF (Bypass masalah SSL/Image loading)
try {
    $barcode_image = base64_encode(file_get_contents($barcode_url));
    $src_barcode   = 'data:image/png;base64,' . $barcode_image;
} catch (Exception $e) {
    $src_barcode   = ''; // Fallback jika internet mati (barcode tidak muncul)
}

// 4. HTML Layout (Thermal Printer 80mm)
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Tiket Parkir</title>
    <style>
        @page { margin: 0; padding: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 10pt; 
            color: #000;
            margin: 0;
            padding: 5px 10px;
        }
        .header { 
            text-align: center; 
            border-bottom: 2px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header h2 { margin: 0; font-size: 14pt; font-weight: bold; }
        .header p { margin: 2px 0; font-size: 8pt; }
        
        .big-plat {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            border: 2px solid #000;
            padding: 5px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        td { vertical-align: top; padding: 2px 0; }
        .label { font-size: 9pt; width: 35%; }
        .val { font-weight: bold; text-align: right; width: 65%; }
        
        .barcode-box {
            text-align: center;
            margin-top: 10px;
            padding: 5px 0;
        }
        .barcode-img {
            width: 95%; /* Scanner lebih mudah baca jika lebar */
            height: auto;
        }
        
        .footer { 
            text-align: center; 
            font-size: 8pt; 
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class='header'>
        <h2>PARKIR POS</h2>
        <p>Tiket Masuk Kendaraan</p>
    </div>

    <div class='big-plat'>
        " . htmlspecialchars(strtoupper($data['plat_nomor'])) . "
    </div>
    
    <table>
        <tr>
            <td class='label'>ID Tiket</td>
            <td class='val'>#" . $data['id_transaksi'] . "</td>
        </tr>
        <tr>
            <td class='label'>Jenis</td>
            <td class='val'>" . ucfirst($data['jenis']) . "</td>
        </tr>
        <tr>
            <td class='label'>Masuk</td>
            <td class='val'>" . date('d/m/y H:i', strtotime($data['waktu_masuk'])) . "</td>
        </tr>
        <tr>
            <td class='label'>Petugas</td>
            <td class='val'>" . htmlspecialchars($data['nama_petugas_masuk']) . "</td>
        </tr>
    </table>
    
    <div class='barcode-box'>
        <img class='barcode-img' src='{$src_barcode}' alt='Barcode'>
    </div>
    
    <div class='footer'>
        JANGAN TINGGALKAN TIKET INI<br>
        DENDA TIKET HILANG RP 20.000
    </div>
</body>
</html>
";

// 5. Render PDF
$options = new Options();
$options->set('isRemoteEnabled', true); // Wajib true agar gambar barcode muncul
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Set ukuran kertas: Lebar 80mm (~227pt), Tinggi Otomatis (panjang ke bawah)
$dompdf->setPaper(array(0, 0, 227, 600));

$dompdf->render();

// Tampilkan PDF (Attachment: 0 artinya preview di browser, bukan download)
$dompdf->stream("tiket-{$data['kode_barcode']}.pdf", ["Attachment" => 0]);
?>