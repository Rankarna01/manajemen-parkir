<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Laporan Keuangan";
require_once '../../core/init.php';

// Menggunakan namespace Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// Keamanan Halaman
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// --- LOGIKA FILTER BARU ---
$filter_type = $_GET['filter_type'] ?? 'harian'; // Default: harian
$laporan_title = '';
$start_datetime_val = '';
$end_datetime_val = '';

switch ($filter_type) {
    case 'mingguan':
        $sql_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $sql_end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $laporan_title = 'Laporan Mingguan';
        break;
        
    case 'bulanan':
        $sql_start = date('Y-m-01 00:00:00');
        $sql_end = date('Y-m-t 23:59:59');
        $laporan_title = 'Laporan Bulanan';
        break;
        
    case 'tahunan':
        $sql_start = date('Y-01-01 00:00:00');
        $sql_end = date('Y-12-31 23:59:59');
        $laporan_title = 'Laporan Tahunan';
        break;
        
    case 'custom':
        // Ambil dari input, jika tidak ada, set default hari ini
        $start_datetime_val = $_GET['start_datetime'] ?? date('Y-m-d') . 'T00:00';
        $end_datetime_val = $_GET['end_datetime'] ?? date('Y-m-d') . 'T23:59';
        
        $sql_start = date('Y-m-d H:i:s', strtotime($start_datetime_val));
        $sql_end = date('Y-m-d H:i:s', strtotime($end_datetime_val));
        $laporan_title = 'Laporan Kustom';
        break;
        
    case 'harian':
    default:
        $sql_start = date('Y-m-d 00:00:00');
        $sql_end = date('Y-m-d 23:59:59');
        $laporan_title = 'Laporan Hari Ini';
        $filter_type = 'harian';
        break;
}

// Simpan nilai input custom filter (bahkan jika tidak aktif)
if(empty($start_datetime_val)) $start_datetime_val = $_GET['start_datetime'] ?? date('Y-m-d') . 'T00:00';
if(empty($end_datetime_val)) $end_datetime_val = $_GET['end_datetime'] ?? date('Y-m-d') . 'T23:59';


// --- AMBIL DATA DARI DATABASE (Query tetap sama) ---
$transaksi_list = [];
$total_pendapatan = 0;

$stmt_list = $db->prepare(
    "SELECT 
        t.*, k.plat_nomor, k.jenis, u.nama AS nama_petugas
     FROM transaksi_parkir t
     JOIN kendaraan k ON t.id_kendaraan = k.id
     LEFT JOIN users u ON t.id_petugas_keluar = u.id
     WHERE t.status = 'keluar' AND t.waktu_keluar BETWEEN ? AND ?
     ORDER BY t.waktu_keluar DESC"
);
$stmt_list->bind_param("ss", $sql_start, $sql_end);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
while ($row = $result_list->fetch_assoc()) {
    $transaksi_list[] = $row;
}
$stmt_list->close();

// Query untuk total pendapatan
$stmt_total = $db->prepare(
    "SELECT SUM(biaya) AS total 
     FROM transaksi_parkir 
     WHERE status = 'keluar' AND waktu_keluar BETWEEN ? AND ?"
);
$stmt_total->bind_param("ss", $sql_start, $sql_end);
$stmt_total->execute();
$total_pendapatan = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total->close();


// --- LOGIKA DOWNLOAD PDF (Menggunakan filter yang sama) ---
if (isset($_GET['download_pdf'])) {
    
    // Mulai buat HTML untuk PDF
    $html_pdf = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Laporan Pendapatan</title>
        <style>
            body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 24px; }
            .header p { margin: 0; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; font-size: 9px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; }
            .total-table { width: 40%; float: right; margin-top: 15px; }
            .total-table td { border: none; padding: 4px; font-size: 11px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Laporan Pendapatan Parkir</h1>
            <p><strong>Periode Laporan: " . $laporan_title . "</strong></p>
            <p>(" . date('d/m/Y H:i', strtotime($sql_start)) . " - " . date('d/m/Y H:i', strtotime($sql_end)) . ")</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Tiket</th>
                    <th>Plat Nomor</th>
                    <th>Jenis</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Durasi</th>
                    <th>Biaya (Rp)</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>";
    
    if (empty($transaksi_list)) {
        $html_pdf .= "<tr><td colspan='9' style='text-align: center;'>Tidak ada data transaksi pada periode ini.</td></tr>";
    } else {
        $no = 1;
        foreach ($transaksi_list as $trx) {
            $waktu_masuk_dt = new DateTime($trx['waktu_masuk']);
            $waktu_keluar_dt = new DateTime($trx['waktu_keluar']);
            $durasi = $waktu_keluar_dt->diff($waktu_masuk_dt);
            $durasi_format = $durasi->d . 'h, ' . $durasi->h . 'j, ' . $durasi->i . 'm';
            
            $html_pdf .= "
                <tr>
                    <td>" . $no++ . "</td>
                    <td>" . htmlspecialchars($trx['kode_barcode']) . "</td>
                    <td>" . htmlspecialchars($trx['plat_nomor']) . "</td>
                    <td>" . ucfirst($trx['jenis']) . "</td>
                    <td>" . date('d/m/y H:i', strtotime($trx['waktu_masuk'])) . "</td>
                    <td>" . date('d/m/y H:i', strtotime($trx['waktu_keluar'])) . "</td>
                    <td>" . $durasi_format . "</td>
                    <td style='text-align: right;'>" . number_format($trx['biaya'], 0, ',', '.') . "</td>
                    <td>" . htmlspecialchars($trx['nama_petugas']) . "</td>
                </tr>";
        }
    }
    
    $html_pdf .= "
            </tbody>
        </table>
        
        <table class='total-table'>
            <tr>
                <td><strong>Total Pendapatan:</strong></td>
                <td style='text-align: right;'><strong>Rp " . number_format($total_pendapatan, 0, ',', '.') . "</strong></td>
            </tr>
        </table>
    </body>
    </html>
    ";
    
    // Konfigurasi Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html_pdf);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    // Download file
    $dompdf->stream("laporan-" . strtolower(str_replace(' ', '-', $laporan_title)) . ".pdf", ["Attachment" => 1]);
    
    exit;
}

$db->close();
//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>

<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">
    
    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <!-- background putih sesuai permintaan -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-white p-6">
        <div class="container mx-auto max-w-7xl">

            <!-- Header Section -->
            <div class="mb-6">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">
                    Laporan Keuangan
                </h2>
                <p class="text-gray-500">
                    Lihat riwayat transaksi dan pendapatan berdasarkan rentang waktu.
                </p>
            </div>

            <!-- Filter Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <!-- Quick Filter -->
                <div class="mb-5 pb-5 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-gray-800">Filter Cepat</h3>
                        
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="?filter_type=harian"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                                  <?php echo ($filter_type == 'harian')
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-calendar-day"></i> Hari Ini
                        </a>
                        <a href="?filter_type=mingguan"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                                  <?php echo ($filter_type == 'mingguan')
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-calendar-week"></i> Minggu Ini
                        </a>
                        <a href="?filter_type=bulanan"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                                  <?php echo ($filter_type == 'bulanan')
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-calendar-alt"></i> Bulan Ini
                        </a>
                        <a href="?filter_type=tahunan"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                                  <?php echo ($filter_type == 'tahunan')
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-calendar"></i> Tahun Ini
                        </a>
                    </div>
                </div>

                <!-- Custom Filter -->
                <h3 class="text-base font-semibold text-gray-800 mb-3">Filter Kustom</h3>
                <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="filter_type" value="custom">

                    <div>
                        <label for="start_datetime" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal & Jam</label>
                        <div class="relative">
                            <i class="fas fa-clock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="datetime-local" id="start_datetime" name="start_datetime"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   value="<?php echo $start_datetime_val; ?>">
                        </div>
                    </div>

                    <div>
                        <label for="end_datetime" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal & Jam</label>
                        <div class="relative">
                            <i class="fas fa-hourglass-end absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="datetime-local" id="end_datetime" name="end_datetime"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   value="<?php echo $end_datetime_val; ?>">
                        </div>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl flex items-center justify-center shadow-sm transition">
                            <i class="fas fa-filter mr-2"></i> Filter Kustom
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Card -->
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="bg-blue-600 text-white w-12 h-12 rounded-xl flex items-center justify-center shadow">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-700">Total Pendapatan (<?php echo $laporan_title; ?>)</p>
                            <p class="text-4xl font-extrabold text-gray-900 leading-tight">
                                Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                Periode <?php echo date('d/m/Y H:i', strtotime($sql_start)); ?> s/d <?php echo date('d/m/Y H:i', strtotime($sql_end)); ?>
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="laporan.php?download_pdf=1&filter_type=<?php echo $filter_type; ?>&start_datetime=<?php echo urlencode($start_datetime_val); ?>&end_datetime=<?php echo urlencode($end_datetime_val); ?>"
                           class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-5 rounded-xl shadow-sm transition">
                            <i class="fas fa-file-pdf"></i> Download Laporan Ini
                        </a>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-6 pb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Riwayat Transaksi (Kendaraan Keluar)</h3>
                    <p class="text-sm text-gray-500">Semua transaksi pada periode yang dipilih.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr class="text-left">
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Kode Tiket</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Plat Nomor</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Waktu Masuk</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Waktu Keluar</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Durasi</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Biaya</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($transaksi_list)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-6 text-center text-gray-500">
                                        Tidak ada data transaksi pada periode ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_list as $trx): ?>
                                    <?php
                                    $waktu_masuk_dt = new DateTime($trx['waktu_masuk']);
                                    $waktu_keluar_dt = new DateTime($trx['waktu_keluar']);
                                    $durasi = $waktu_keluar_dt->diff($waktu_masuk_dt);
                                    $durasi_format = $durasi->d . 'h, ' . $durasi->h . 'j, ' . $durasi->i . 'm';
                                    ?>
                                    <tr class="hover:bg-gray-50/60 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($trx['kode_barcode']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo htmlspecialchars($trx['plat_nomor']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo date('d/m/y H:i', strtotime($trx['waktu_masuk'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo date('d/m/y H:i', strtotime($trx['waktu_keluar'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo $durasi_format; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">Rp <?php echo number_format($trx['biaya'], 0, ',', '.'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($trx['nama_petugas']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer table (optional info) -->
                <div class="px-6 py-4 border-t border-gray-100 text-xs text-gray-500">
                    *Data ditampilkan sesuai filter yang dipilih.
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>
