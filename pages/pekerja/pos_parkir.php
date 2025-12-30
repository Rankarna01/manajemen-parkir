<?php
//=========================================
// 1. SETUP & KEAMANAN
//=========================================
require_once '../../core/init.php';

// Cek Login
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

//=========================================
// 2. LOGIKA POS, CONFIG & ABSENSI
//=========================================

// A. Cek Penugasan User (Assignment dari Owner)
$cek_assign = $db->query("SELECT assigned_pos_id FROM users WHERE id = $user_id")->fetch_assoc();
if (!empty($cek_assign['assigned_pos_id'])) {
    $_SESSION['current_pos_id'] = $cek_assign['assigned_pos_id'];
}

// B. Proses Input Modal Pemilihan Pos
if (isset($_POST['pilih_pos_id'])) {
    $_SESSION['current_pos_id'] = $_POST['pilih_pos_id'];
    header("Refresh:0"); exit;
}

// C. Cek Status Session Pos & Load Config
$show_modal_pilih_pos = false;
$show_modal_absensi   = false;
$config               = null;

if (!isset($_SESSION['current_pos_id'])) {
    // Jika belum pilih pos -> Tampilkan Modal
    $show_modal_pilih_pos = true;
    $daftar_pos = $db->query("SELECT * FROM pengaturan_sistem");
    $page_title = "Pilih Lokasi Pos";
    $tipe_pos   = 'umum'; 
} else {
    // Jika sudah pilih -> Ambil Config dari Database
    $pos_id = $_SESSION['current_pos_id'];
    $stmt_cfg = $db->prepare("SELECT * FROM pengaturan_sistem WHERE id = ?");
    $stmt_cfg->bind_param("i", $pos_id);
    $stmt_cfg->execute();
    $config = $stmt_cfg->get_result()->fetch_assoc();

    // Validasi jika pos dihapus owner saat sesi aktif
    if (!$config) {
        unset($_SESSION['current_pos_id']);
        header("Refresh:0"); exit;
    }

    $page_title = "Pos: " . $config['nama_pos'];
    $tipe_pos   = $config['tipe_pos'];

    // D. Cek Absensi Hari Ini (Apakah sudah Check-In?)
    $stmt_absen = $db->prepare("SELECT id FROM absensi WHERE user_id = ? AND pos_id = ? AND status = 'hadir' AND DATE(waktu_masuk) = CURDATE()");
    $stmt_absen->bind_param("ii", $user_id, $pos_id);
    $stmt_absen->execute();
    
    if ($stmt_absen->get_result()->num_rows == 0) {
        $show_modal_absensi = true; // Munculkan Modal Absen
    }
}

// E. Tema Warna (Biru = Mobil, Kuning = Motor)
$theme_color  = ($tipe_pos == 'mobil') ? 'blue' : 'yellow';
$theme_bg     = ($tipe_pos == 'mobil') ? 'bg-blue-600' : 'bg-yellow-500';
$theme_border = ($tipe_pos == 'mobil') ? 'border-blue-600' : 'border-yellow-500';

$db->close();
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto max-w-7xl">
            
            <div class="<?php echo ($show_modal_pilih_pos || $show_modal_absensi) ? 'filter blur-sm pointer-events-none' : ''; ?>">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden relative">
                            <div class="h-2 bg-gradient-to-r from-<?php echo $theme_color; ?>-400 via-<?php echo $theme_color; ?>-500 to-<?php echo $theme_color; ?>-600"></div>

                            <div class="p-8 border-b border-gray-100">
                                <form id="formCariTiket">
                                    <label for="kode_input" class="block text-sm font-bold text-primary mb-3 uppercase tracking-wide">
                                        <i class="fas fa-barcode mr-2"></i> Scan Tiket / Input Plat
                                    </label>
                                    <div class="flex shadow-md rounded-xl overflow-hidden group focus-within:ring-2 focus-within:ring-<?php echo $theme_color; ?>-500 transition-all">
                                        <input type="text" id="kode_input" name="kode_input" 
                                               class="w-full px-6 py-5 border-2 border-gray-200 border-r-0 rounded-l-xl text-3xl font-extrabold text-gray-800 focus:outline-none placeholder-gray-300 uppercase tracking-widest" 
                                               placeholder="SCAN TIKET..." required autocomplete="off" autofocus>
                                        
                                        <button type="submit" id="btnCari" class="px-8 <?php echo $theme_bg; ?> text-white font-bold hover:opacity-90 transition duration-200 border-2 <?php echo $theme_border; ?> flex items-center justify-center">
                                            <i id="iconCari" class="fas fa-search text-2xl"></i>
                                            <i id="spinnerCari" class="fas fa-spinner fa-spin text-2xl hidden"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2 ml-1"><i class="fas fa-info-circle mr-1"></i> Scan tiket atau ketik plat lalu tekan Enter.</p>
                                </form>
                            </div>
                            
                            <div id="paymentDetails" class="p-8 hidden">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-2xl font-bold text-primary">Detail Pembayaran</h3>
                                    <div class="px-3 py-1 rounded-lg bg-red-50 text-red-700 text-xs font-bold uppercase tracking-wider border border-red-100">Transaksi Keluar</div>
                                </div>
                                
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Plat Nomor</p>
                                        <p id="detail_plat" class="text-xl font-black text-primary mt-1">-</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Jenis</p>
                                        <p id="detail_jenis" class="text-xl font-bold text-gray-800 mt-1 capitalize">-</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Masuk</p>
                                        <p id="detail_masuk" class="text-base font-medium text-gray-700 mt-1">-</p>
                                    </div>
                                    <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                                        <p class="text-[10px] text-blue-600 uppercase font-bold tracking-wider">Durasi</p>
                                        <p id="detail_durasi" class="text-lg font-bold text-primary mt-1">-</p>
                                    </div>
                                </div>

                                <form id="formPembayaran">
                                    <input type="hidden" name="action" value="proses_keluar">
                                    <input type="hidden" id="hidden_transaksi_id" name="transaksi_id">
                                    <input type="hidden" id="hidden_total_biaya" name="total_biaya">

                                    <div class="bg-primary rounded-2xl p-6 text-white shadow-lg mb-8 relative overflow-hidden group">
                                        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/5 rounded-full blur-2xl group-hover:bg-white/10 transition"></div>
                                        <div class="flex justify-between items-end relative z-10">
                                            <div>
                                                <span class="block text-gray-300 text-sm font-medium mb-1 uppercase tracking-wide">Total Tagihan</span>
                                                <span id="detail_biaya" class="text-5xl font-extrabold text-<?php echo $theme_color; ?>-400 tracking-tight">Rp 0</span>
                                            </div>
                                            <i class="fas fa-wallet text-5xl text-white/10"></i>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">Uang Diterima (Rp)</label>
                                            <input type="number" id="jumlah_bayar" name="jumlah_bayar" class="w-full pl-4 py-4 border-2 border-gray-300 rounded-xl text-2xl font-bold focus:ring-2 focus:ring-<?php echo $theme_color; ?>-500 focus:border-<?php echo $theme_color; ?>-500 transition outline-none" placeholder="0" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-2">Kembalian (Rp)</label>
                                            <input type="text" id="kembalian" name="kembalian" class="w-full pl-4 py-4 border-2 border-gray-200 bg-gray-100 rounded-xl text-2xl font-bold text-gray-500 outline-none cursor-not-allowed" readonly placeholder="0">
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-4">
                                        <button type="button" id="btnBatal" class="w-1/3 px-6 py-4 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                                        <button type="submit" id="btnProses" class="w-2/3 px-6 py-4 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 text-xl shadow-lg transition flex items-center justify-center">
                                            <i id="iconProses" class="fas fa-print mr-3"></i> <span id="textProses">Bayar & Cetak</span>
                                            <i id="spinnerProses" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center rounded-b-2xl">
                                <div class="text-xs text-gray-500 font-medium">
                                    <i class="fas fa-info-circle mr-1 text-primary"></i> Mode: <b>POS <?php echo strtoupper($tipe_pos); ?></b>
                                </div>
                                <button id="btnInputManual" class="px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition shadow-sm">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Input Manual
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="space-y-6">
                            <?php if ($config): ?>
                                <div class="bg-black rounded-2xl shadow-lg overflow-hidden border-4 border-gray-800 relative group">
                                    <div class="absolute top-3 left-3 z-10 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded animate-pulse font-bold tracking-wider">LIVE</div>
                                    <div class="bg-white">
                                        <div class="bg-black h-48 flex items-center justify-center relative overflow-hidden">
                                            <?php if (!empty($config['ip_kamera_masuk'])): ?>
                                                <img src="http://<?php echo $config['ip_kamera_masuk']; ?>/stream.mjpg" class="w-full h-full object-cover" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <div class="text-gray-500 text-xs text-center"><i class="fas fa-video-slash text-2xl mb-1"></i><br>No IP Config</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-4 border-t border-gray-200">
                                            <button type="button" id="btnAmbilTiketOtomatis" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg flex items-center justify-center gap-2">
                                                <i class="fas fa-print"></i> AMBIL TIKET <?php echo strtoupper($tipe_pos); ?>
                                            </button>
                                            
                                            <form id="formKendaraanMasuk" class="mt-4 pt-4 border-t hidden">
                                                <input type="hidden" name="action" value="kendaraan_masuk_manual">
                                                <input type="text" name="plat_nomor" class="w-full border p-2 rounded mb-2 uppercase" placeholder="Plat Nomor Manual" required>
                                                <button type="submit" id="btnSubmitManual" class="w-full bg-gray-700 text-white py-1 rounded">Simpan Manual</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-black rounded-2xl shadow-lg overflow-hidden border-4 border-gray-800 relative group">
                                    <div class="absolute top-3 left-3 z-10 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded animate-pulse font-bold tracking-wider">LIVE</div>
                                    <div class="bg-white">
                                        <div class="bg-black h-48 flex items-center justify-center">
                                            <?php if (!empty($config['ip_kamera_keluar'])): ?>
                                                <img src="http://<?php echo $config['ip_kamera_keluar']; ?>/stream.mjpg" class="w-full h-full object-cover" onerror="this.style.display='none';">
                                            <?php else: ?>
                                                <div class="text-gray-500 text-xs text-center"><i class="fas fa-video-slash text-2xl mb-1"></i><br>No IP Config</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-gray-200 rounded-2xl p-6 text-center text-gray-500">Menunggu Pemilihan Pos...</div>
                            <?php endif; ?>

                            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-100">
                                <h4 class="font-bold text-primary mb-4 flex items-center"><i class="fas fa-server mr-2"></i> Status Perangkat</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl border border-green-100">
                                        <div class="flex items-center"><span class="relative flex h-3 w-3 mr-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span></span><span class="text-sm font-bold text-gray-700">Palang Pintu</span></div>
                                        <span class="text-xs font-bold text-green-600 bg-white px-2 py-1 rounded shadow-sm">TERHUBUNG</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl border border-green-100">
                                        <div class="flex items-center"><span class="relative flex h-3 w-3 mr-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span></span><span class="text-sm font-bold text-gray-700">Printer Thermal</span></div>
                                        <span class="text-xs font-bold text-green-600 bg-white px-2 py-1 rounded shadow-sm">READY</span>
                                    </div>
                                </div>
                            </div>
                            
                            <button id="btnPulang" class="w-full py-3 bg-red-100 text-red-600 rounded-xl font-bold hover:bg-red-200 transition">
                                <i class="fas fa-sign-out-alt mr-2"></i> Selesai Tugas / Pulang
                            </button>
                        </div>
                    </div>

                </div>
            </div>
    </main>
</div>

<?php 
if ($show_modal_pilih_pos) { include 'modals/modal_pilih_pos.php'; }
if ($show_modal_absensi && !$show_modal_pilih_pos) { include 'modals/modal_absensi.php'; }
?>

<?php require_once '../../templates/footer_app.php'; ?>

<script>
    const IP_PALANG_MASUK  = "<?php echo $config['ip_palang_masuk'] ?? ''; ?>"; 
    const IP_PALANG_KELUAR = "<?php echo $config['ip_palang_keluar'] ?? ''; ?>"; 
    const TIPE_POS_INI     = "<?php echo $config['tipe_pos'] ?? 'motor'; ?>";
    const ID_POS_INI       = "<?php echo $config['id'] ?? 0; ?>";
</script>
<script src="js/pos_parkir_logic.js?v=<?php echo time(); ?>"></script>