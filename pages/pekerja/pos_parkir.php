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

// A. Cek Penugasan User
$cek_assign = $db->query("SELECT assigned_pos_id FROM users WHERE id = $user_id")->fetch_assoc();
if (!empty($cek_assign['assigned_pos_id'])) {
    $_SESSION['current_pos_id'] = $cek_assign['assigned_pos_id'];
}

// B. Proses Input Modal Pemilihan Pos
if (isset($_POST['pilih_pos_id'])) {
    $_SESSION['current_pos_id'] = $_POST['pilih_pos_id'];
    header("Refresh:0"); exit;
}

// C. Cek Status Session Pos
$show_modal_pilih_pos = false;
$show_modal_absensi   = false;
$config               = null;

if (!isset($_SESSION['current_pos_id'])) {
    $show_modal_pilih_pos = true;
    $daftar_pos = $db->query("SELECT * FROM pengaturan_sistem");
    $page_title = "Pilih Lokasi Pos";
    $tipe_pos   = 'umum'; 
} else {
    $pos_id = $_SESSION['current_pos_id'];
    $stmt_cfg = $db->prepare("SELECT * FROM pengaturan_sistem WHERE id = ?");
    $stmt_cfg->bind_param("i", $pos_id);
    $stmt_cfg->execute();
    $config = $stmt_cfg->get_result()->fetch_assoc();

    if (!$config) { unset($_SESSION['current_pos_id']); header("Refresh:0"); exit; }

    $page_title = "Pos: " . $config['nama_pos'];
    $tipe_pos   = $config['tipe_pos'];

    // D. Cek Absensi
    $stmt_absen = $db->prepare("SELECT id FROM absensi WHERE user_id = ? AND pos_id = ? AND status = 'hadir' AND DATE(waktu_masuk) = CURDATE()");
    $stmt_absen->bind_param("ii", $user_id, $pos_id);
    $stmt_absen->execute();
    if ($stmt_absen->get_result()->num_rows == 0) $show_modal_absensi = true;
}

// E. Tema Warna
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
                                    <div class="flex justify-between items-center mb-3">
                                        <label class="block text-sm font-bold text-primary uppercase tracking-wide">
                                            <i class="fas fa-barcode mr-2"></i> Scan Tiket / Kartu
                                        </label>
                                        
                                        <button type="button" id="btnSimulasiRFID" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold hover:bg-blue-200 transition border border-blue-200">
                                            <i class="fas fa-wifi mr-1"></i> Simulasi Tap RFID
                                        </button>
                                    </div>

                                    <div class="flex shadow-md rounded-xl overflow-hidden group focus-within:ring-2 focus-within:ring-<?php echo $theme_color; ?>-500 transition-all">
                                        <input type="text" id="kode_input" name="kode_input" 
                                               class="w-full px-6 py-5 border-2 border-gray-200 border-r-0 rounded-l-xl text-3xl font-extrabold text-gray-800 focus:outline-none placeholder-gray-300 uppercase tracking-widest" 
                                               placeholder="SCAN..." required autocomplete="off" autofocus>
                                        <button type="submit" id="btnCari" class="px-8 <?php echo $theme_bg; ?> text-white font-bold hover:opacity-90">
                                            <i id="iconCari" class="fas fa-search text-2xl"></i>
                                            <i id="spinnerCari" class="fas fa-spinner fa-spin text-2xl hidden"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2 ml-1">Tempel Kartu RFID atau Scan Barcode Tiket.</p>
                                </form>
                            </div>
                            
                            <div id="paymentDetails" class="p-8 hidden">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-2xl font-bold text-primary">Transaksi Keluar</h3>
                                    
                                    <div id="badge_member" class="hidden px-4 py-1 bg-blue-600 text-white text-xs font-bold uppercase rounded-full shadow-md animate-pulse">
                                        <i class="fas fa-crown mr-1"></i> MEMBER AKTIF
                                    </div>
                                    <div id="badge_umum" class="px-3 py-1 bg-gray-200 text-gray-600 text-xs font-bold uppercase rounded-lg">Umum</div>
                                </div>
                                
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                                    <div class="p-4 bg-gray-50 rounded-xl border"><p class="text-[10px] text-gray-500 font-bold">PLAT NOMOR</p><p id="detail_plat" class="text-xl font-black text-gray-800">-</p></div>
                                    <div class="p-4 bg-gray-50 rounded-xl border"><p class="text-[10px] text-gray-500 font-bold">JENIS</p><p id="detail_jenis" class="text-xl font-bold text-gray-800 capitalize">-</p></div>
                                    <div class="p-4 bg-gray-50 rounded-xl border"><p class="text-[10px] text-gray-500 font-bold">MASUK</p><p id="detail_masuk" class="text-base font-bold text-gray-800">-</p></div>
                                    <div class="p-4 bg-blue-50 rounded-xl border border-blue-100"><p class="text-[10px] text-blue-600 font-bold">DURASI</p><p id="detail_durasi" class="text-lg font-bold text-blue-800">-</p></div>
                                </div>

                                <form id="formPembayaran">
                                    <input type="hidden" name="action" value="proses_keluar">
                                    <input type="hidden" id="hidden_transaksi_id" name="transaksi_id">
                                    <input type="hidden" id="hidden_total_biaya" name="total_biaya">

                                    <div class="bg-primary rounded-2xl p-6 text-white shadow-lg mb-6 flex justify-between items-center transition-all" id="box_biaya">
                                        <div>
                                            <span class="block text-gray-300 text-sm font-medium mb-1">Total Biaya</span>
                                            <span id="detail_biaya" class="text-5xl font-extrabold text-yellow-400">Rp 0</span>
                                            <p id="info_member" class="text-xs text-blue-300 mt-1 hidden">Gratis untuk Member</p>
                                        </div>
                                        <i class="fas fa-wallet text-5xl text-white/10"></i>
                                    </div>

                                    <div id="area_qris" class="hidden text-center mb-6 bg-white border-2 border-gray-200 p-4 rounded-xl">
                                        <p class="text-sm font-bold text-gray-600 mb-2">Scan QRIS</p>
                                        <div class="flex justify-center"><img id="img_qris" src="" class="w-40 h-40 object-contain"></div>
                                        <p class="text-xs text-gray-400 mt-2 animate-pulse">Menunggu pembayaran...</p>
                                    </div>

                                    <div id="area_tunai" class="grid grid-cols-2 gap-4 mb-6">
                                        <div><label class="text-xs font-bold text-gray-500">DITERIMA (RP)</label><input type="number" id="jumlah_bayar" name="jumlah_bayar" class="w-full p-3 border-2 rounded-xl text-xl font-bold focus:ring-2 focus:ring-blue-500" placeholder="0"></div>
                                        <div><label class="text-xs font-bold text-gray-500">KEMBALI (RP)</label><input type="text" id="kembalian" class="w-full p-3 bg-gray-100 border-2 rounded-xl text-xl font-bold text-gray-500" readonly></div>
                                    </div>
                                    
                                    <div class="flex gap-3">
                                        <button type="button" id="btnBatal" class="w-1/4 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl">Batal</button>
                                        <button type="button" id="btnBayarQris" class="w-1/3 py-3 bg-gray-800 text-white font-bold rounded-xl"><i class="fas fa-qrcode mr-2"></i> QRIS</button>
                                        <button type="submit" id="btnProses" class="flex-1 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-lg flex justify-center items-center gap-2">
                                            <i id="iconProses" class="fas fa-print"></i> <span id="textProses">Proses & Buka</span>
                                            <i id="spinnerProses" class="fas fa-spinner fa-spin hidden"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center rounded-b-2xl">
                                <div class="text-xs text-gray-500 font-medium"><i class="fas fa-info-circle mr-1 text-primary"></i> Mode: <b>POS <?php echo strtoupper($tipe_pos); ?></b></div>
                                <button id="btnInputManual" class="px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition shadow-sm"><i class="fas fa-keyboard mr-1"></i> Input Manual</button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1 space-y-6">
                        <?php if ($config): ?>
                            
                            <div class="bg-black rounded-2xl shadow-lg overflow-hidden border-4 border-gray-800 relative group">
                                <div class="absolute top-3 left-3 z-10 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded animate-pulse font-bold tracking-wider">MASUK</div>
                                <div class="bg-white">
                                    <div class="bg-black h-48 flex items-center justify-center relative overflow-hidden">
                                        <?php if (!empty($config['ip_kamera_masuk'])): ?>
                                            <img src="http://<?php echo $config['ip_kamera_masuk']; ?>/stream.mjpg" class="w-full h-full object-cover" onerror="this.style.display='none';">
                                        <?php else: ?>
                                            <div class="text-gray-500 text-xs text-center"><i class="fas fa-video-slash text-2xl mb-1"></i><br>No IP Masuk</div>
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
                                <div class="absolute top-3 left-3 z-10 bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded animate-pulse font-bold tracking-wider">KELUAR</div>
                                <div class="bg-white">
                                    <div class="bg-black h-48 flex items-center justify-center relative overflow-hidden">
                                        <?php if (!empty($config['ip_kamera_keluar'])): ?>
                                            <img src="http://<?php echo $config['ip_kamera_keluar']; ?>/stream.mjpg" class="w-full h-full object-cover" onerror="this.style.display='none';">
                                        <?php else: ?>
                                            <div class="text-gray-500 text-xs text-center"><i class="fas fa-video-slash text-2xl mb-1"></i><br>No IP Keluar</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-2 border-t border-gray-200 text-center bg-gray-50">
                                        <span class="text-xs font-bold text-gray-500 tracking-wide">MONITORING GATE KELUAR</span>
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