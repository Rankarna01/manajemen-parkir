<?php
// Memanggil config DB dan autoload Composer
require_once 'core/init.php';

// Load Dompdf dan Barcode Generator
use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG; // Pastikan library ini sudah diinstall via Composer

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
$stmt->close();
$db->close();

// 3. Generate Gambar Barcode (Base64)
// Ini membuat gambar barcode yang bisa discan oleh alat scanner fisik
$generator = new BarcodeGeneratorPNG();
$barcodeData = $generator->getBarcode($data['kode_barcode'], $generator::TYPE_CODE_128, 2, 50);
$barcodeBase64 = base64_encode($barcodeData);

// 4. Desain HTML untuk Struk (Thermal Printer Friendly)
// Ukuran kertas diset dinamis untuk printer 80mm
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Tiket Parkir - {$data['kode_barcode']}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 10pt; 
            color: #000;
            margin: 0;
            padding: 5px;
            background-color: #fff;
        }
        .container { 
            width: 100%;
            padding: 5px 10px;
            box-sizing: border-box;
        }
        .header { 
            text-align: center; 
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }
        .header h2 { margin: 0; font-size: 14pt; }
        .header p { margin: 2px 0; font-size: 9pt; }
        
        .content-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .content-table td { padding: 2px 0; vertical-align: top; }
        .label { width: 40%; font-size: 9pt; }
        .value { width: 60%; font-weight: bold; font-size: 10pt; text-align: right; }
        
        .big-plat {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 10px 0;
            border: 2px solid #000;
            padding: 5px;
            border-radius: 5px;
        }

        .barcode-area {
            text-align: center;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .barcode-img {
            width: 90%; /* Maksimalkan lebar barcode agar mudah discan */
            height: auto;
        }
        .barcode-text {
            font-size: 10pt;
            letter-spacing: 3px;
            margin-top: 2px;
        }

        .footer { 
            text-align: center; 
            font-size: 8pt; 
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>PARKIR SISTEM</h2>
            <p>Tiket Masuk Kendaraan</p>
        </div>

        <div class='big-plat'>
            " . htmlspecialchars(strtoupper($data['plat_nomor'])) . "
        </div>
        
        <table class='content-table'>
            <tr>
                <td class='label'>ID Transaksi</td>
                <td class='value'>#" . str_pad($data['id_transaksi'], 6, '0', STR_PAD_LEFT) . "</td>
            </tr>
            <tr>
                <td class='label'>Jenis</td>
                <td class='value'>" . ucfirst($data['jenis']) . "</td>
            </tr>
            <tr>
                <td class='label'>Masuk</td>
                <td class='value'>" . date('d/m/y H:i', strtotime($data['waktu_masuk'])) . "</td>
            </tr>
            <tr>
                <td class='label'>Petugas</td>
                <td class='value'>" . htmlspecialchars($data['nama_petugas_masuk']) . "</td>
            </tr>
        </table>
        
        <div class='barcode-area'>
            <img class='barcode-img' src='data:image/png;base64,{$barcodeBase64}' alt='Barcode'>
            <div class='barcode-text'>" . htmlspecialchars($data['kode_barcode']) . "</div>
        </div>
        
        <div class='footer'>
            JANGAN TINGGALKAN TIKET INI<br>
            HILANG TIKET DENDA RP 50.000
            <br><br>
            " . date('d-m-Y H:i:s') . "
        </div>
    </div>
</body>
</html>
";

// 5. Render PDF dengan Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Courier');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Set Ukuran Kertas Thermal (80mm x Auto Height)
// Lebar 80mm ≈ 227 point. Tinggi kita buat panjang (misal 500pt) agar muat konten, printer akan memotong otomatis.
$customPaper = array(0, 0, 227, 500); 
$dompdf->setPaper($customPaper);

// Render
$dompdf->render();

// Output: Langsung preview di browser (tanpa download otomatis) agar petugas bisa print manual jika perlu
$dompdf->stream("tiket-{$data['kode_barcode']}.pdf", ["Attachment" => 0]);
exit;
?>