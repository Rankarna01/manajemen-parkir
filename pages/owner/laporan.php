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

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto max-w-7xl">

            <div class="mb-6">
                <h2 class="text-2xl md:text-3xl font-bold text-primary tracking-tight">
                    Laporan Keuangan
                </h2>
                <p class="text-gray-500">
                    Lihat riwayat transaksi dan pendapatan berdasarkan rentang waktu.
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border-l-4 border-accent p-6 mb-6">
                <div class="mb-5 pb-5 border-b border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-primary">Filter Cepat</h3>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="?filter_type=harian"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition duration-200
                                  <?php echo ($filter_type == 'harian')
                                    ? 'bg-primary text-white shadow-md transform scale-105'
                                    : 'bg-secondary text-gray-600 hover:bg-gray-200 hover:text-primary'; ?>">
                            <i class="fas fa-calendar-day"></i> Hari Ini
                        </a>
                        <a href="?filter_type=mingguan"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition duration-200
                                  <?php echo ($filter_type == 'mingguan')
                                    ? 'bg-primary text-white shadow-md transform scale-105'
                                    : 'bg-secondary text-gray-600 hover:bg-gray-200 hover:text-primary'; ?>">
                            <i class="fas fa-calendar-week"></i> Minggu Ini
                        </a>
                        <a href="?filter_type=bulanan"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition duration-200
                                  <?php echo ($filter_type == 'bulanan')
                                    ? 'bg-primary text-white shadow-md transform scale-105'
                                    : 'bg-secondary text-gray-600 hover:bg-gray-200 hover:text-primary'; ?>">
                            <i class="fas fa-calendar-alt"></i> Bulan Ini
                        </a>
                        <a href="?filter_type=tahunan"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition duration-200
                                  <?php echo ($filter_type == 'tahunan')
                                    ? 'bg-primary text-white shadow-md transform scale-105'
                                    : 'bg-secondary text-gray-600 hover:bg-gray-200 hover:text-primary'; ?>">
                            <i class="fas fa-calendar"></i> Tahun Ini
                        </a>
                    </div>
                </div>

                <h3 class="text-base font-semibold text-primary mb-3">Filter Kustom</h3>
                <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="filter_type" value="custom">

                    <div>
                        <label for="start_datetime" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal & Jam</label>
                        <div class="relative">
                            <i class="fas fa-clock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="datetime-local" id="start_datetime" name="start_datetime"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition"
                                   value="<?php echo $start_datetime_val; ?>">
                        </div>
                    </div>

                    <div>
                        <label for="end_datetime" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal & Jam</label>
                        <div class="relative">
                            <i class="fas fa-hourglass-end absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="datetime-local" id="end_datetime" name="end_datetime"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent focus:border-accent transition"
                                   value="<?php echo $end_datetime_val; ?>">
                        </div>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit"
                                class="w-full bg-accent hover:bg-yellow-600 text-primary font-bold py-2.5 px-4 rounded-xl flex items-center justify-center shadow-md transition duration-200">
                            <i class="fas fa-filter mr-2"></i> Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="bg-accent text-primary w-14 h-14 rounded-full flex items-center justify-center shadow-md">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wider text-gray-500">Total Pendapatan (<?php echo $laporan_title; ?>)</p>
                            <p class="text-4xl font-extrabold text-primary leading-tight mt-1">
                                Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
                            </p>
                            <p class="text-sm text-gray-500 mt-1 flex items-center">
                                <i class="far fa-clock mr-1"></i>
                                Periode: <?php echo date('d/m/Y H:i', strtotime($sql_start)); ?> s/d <?php echo date('d/m/Y H:i', strtotime($sql_end)); ?>
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="laporan.php?download_pdf=1&filter_type=<?php echo $filter_type; ?>&start_datetime=<?php echo urlencode($start_datetime_val); ?>&end_datetime=<?php echo urlencode($end_datetime_val); ?>"
                           class="inline-flex items-center gap-2 bg-success hover:bg-emerald-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transition transform hover:-translate-y-1">
                            <i class="fas fa-file-pdf fa-lg"></i> <span>Download PDF</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-6 pb-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="text-lg font-bold text-primary">Riwayat Transaksi</h3>
                        <p class="text-sm text-gray-500">Daftar kendaraan keluar pada periode ini.</p>
                    </div>
                    <div class="text-accent">
                        <i class="fas fa-list-alt text-2xl"></i>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary text-white sticky top-0 z-10">
                            <tr class="text-left">
                                <th class="px-6 py-4 font-semibold uppercase tracking-wider text-xs">Kode Tiket</th>
                                <th class="px-6 py-4 font-semibold uppercase tracking-wider text-xs">Plat Nomor</th>
                                <th class="px-6 py-4 font-semibold uppercase tracking-wider text-xs">Masuk</th>
                                <th class="px-6 py-4 font-semibold uppercase tracking-wider text-xs">Keluar</th>
                                <th class="px-6 py-4 font-semibold uppercase tracking-wider text-xs">Durasi</th>
                                <th class="px-6 py-4 font-semibold uppercase tracking-wider text-xs">Biaya</th>
                                <th class="px-6 py-4 font-semibold uppercase tracking-wider text-xs">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($transaksi_list)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500 bg-gray-50">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                                            <p>Tidak ada data transaksi pada periode ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_list as $trx): ?>
                                    <?php
                                    $waktu_masuk_dt = new DateTime($trx['waktu_masuk']);
                                    $waktu_keluar_dt = new DateTime($trx['waktu_keluar']);
                                    $durasi = $waktu_keluar_dt->diff($waktu_masuk_dt);
                                    $durasi_format = $durasi->d . 'h ' . $durasi->h . 'j ' . $durasi->i . 'm';
                                    ?>
                                    <tr class="hover:bg-blue-50 transition duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700 font-mono text-xs"><?php echo htmlspecialchars($trx['kode_barcode']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-primary bg-gray-50/50"><?php echo htmlspecialchars($trx['plat_nomor']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo date('d/m/y H:i', strtotime($trx['waktu_masuk'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo date('d/m/y H:i', strtotime($trx['waktu_keluar'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                            <span class="px-2 py-1 rounded bg-secondary text-primary text-xs font-medium">
                                                <?php echo $durasi_format; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-accent">Rp <?php echo number_format($trx['biaya'], 0, ',', '.'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-xs">
                                            <i class="fas fa-user-circle text-gray-400 mr-1"></i>
                                            <?php echo htmlspecialchars($trx['nama_petugas']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 text-xs text-gray-500 flex justify-between items-center">
                    <span>*Data ditampilkan sesuai filter yang dipilih.</span>
                    <span>Total Baris: <?php echo count($transaksi_list); ?></span>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>