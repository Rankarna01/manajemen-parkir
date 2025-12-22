<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Edit Pos Parkir";
require_once '../../core/init.php';

// 1. Cek Keamanan (Hanya Owner)
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 2. Cek apakah ada ID di URL?
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: konfigurasi.php");
    exit;
}

$id_pos = $_GET['id'];

// 3. Ambil Data Lama dari Database
$stmt_get = $db->prepare("SELECT * FROM pengaturan_sistem WHERE id = ?");
$stmt_get->bind_param("i", $id_pos);
$stmt_get->execute();
$result = $stmt_get->get_result();

if ($result->num_rows == 0) {
    die("Data pos tidak ditemukan.");
}

$data = $result->fetch_assoc();


// 4. Proses Update Data (Saat Tombol Simpan ditekan)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_pos   = $_POST['nama_pos'];
    $tipe_pos   = $_POST['tipe_pos'];
    
    $ip_cam_in  = $_POST['ip_kamera_masuk'];
    $ip_cam_out = $_POST['ip_kamera_keluar'];
    $ip_gate_in = $_POST['ip_palang_masuk'];
    $ip_gate_out= $_POST['ip_palang_keluar'];
    $ip_printer = $_POST['ip_printer'];

    // Query Update
    $stmt_update = $db->prepare("UPDATE pengaturan_sistem SET nama_pos=?, tipe_pos=?, ip_kamera_masuk=?, ip_kamera_keluar=?, ip_palang_masuk=?, ip_palang_keluar=?, ip_printer=? WHERE id=?");
    
    $stmt_update->bind_param("sssssssi", $nama_pos, $tipe_pos, $ip_cam_in, $ip_cam_out, $ip_gate_in, $ip_gate_out, $ip_printer, $id_pos);

    if ($stmt_update->execute()) {
        $_SESSION['success_message'] = "Data Pos berhasil diperbarui!";
        header("Location: konfigurasi.php"); // Kembali ke daftar
        exit;
    } else {
        $error_msg = "Gagal memperbarui: " . $db->error;
    }
}
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto max-w-4xl">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary tracking-tight">Edit Pos Parkir</h2>
                    <p class="text-gray-500">Ubah konfigurasi untuk pos: <b class="text-primary"><?php echo htmlspecialchars($data['nama_pos']); ?></b></p>
                </div>
                <a href="konfigurasi.php" class="bg-white border border-gray-300 text-gray-600 px-4 py-2 rounded-xl hover:bg-gray-50 transition shadow-sm font-medium flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>

            <?php if (isset($error_msg)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-xl shadow-sm flex items-center" role="alert">
                    <i class="fas fa-exclamation-circle mr-3 text-lg"></i>
                    <p><?php echo $error_msg; ?></p>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-10">
                    
                    <div class="space-y-6">
                        <h3 class="text-gray-800 font-bold border-b pb-3 mb-4 flex items-center text-lg">
                            <div class="w-8 h-8 rounded-lg bg-secondary text-primary flex items-center justify-center mr-3">
                                <i class="fas fa-edit"></i>
                            </div>
                            Identitas Pos
                        </h3>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Pos <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_pos" required value="<?php echo htmlspecialchars($data['nama_pos']); ?>"
                                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition placeholder-gray-400">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tipe Kendaraan <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select name="tipe_pos" required class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white appearance-none transition cursor-pointer">
                                    <option value="mobil" <?php echo ($data['tipe_pos'] == 'mobil') ? 'selected' : ''; ?>>Mobil (Roda 4)</option>
                                    <option value="motor" <?php echo ($data['tipe_pos'] == 'motor') ? 'selected' : ''; ?>>Motor (Roda 2)</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i> Menentukan tarif otomatis dan kode tiket.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h3 class="text-gray-800 font-bold border-b pb-3 mb-4 flex items-center text-lg">
                            <div class="w-8 h-8 rounded-lg bg-secondary text-primary flex items-center justify-center mr-3">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            Alamat IP Perangkat
                        </h3>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">IP Palang Masuk</label>
                                <input type="text" name="ip_palang_masuk" value="<?php echo htmlspecialchars($data['ip_palang_masuk']); ?>"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">IP Palang Keluar</label>
                                <input type="text" name="ip_palang_keluar" value="<?php echo htmlspecialchars($data['ip_palang_keluar']); ?>"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                            
                            <div class="border-t border-dashed border-gray-200 my-1"></div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">IP CCTV Masuk</label>
                                <input type="text" name="ip_kamera_masuk" value="<?php echo htmlspecialchars($data['ip_kamera_masuk']); ?>"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">IP CCTV Keluar</label>
                                <input type="text" name="ip_kamera_keluar" value="<?php echo htmlspecialchars($data['ip_kamera_keluar']); ?>"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                            
                            <div class="border-t border-dashed border-gray-200 my-1"></div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">IP Printer Tiket</label>
                                <input type="text" name="ip_printer" value="<?php echo htmlspecialchars($data['ip_printer']); ?>"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="konfigurasi.php" class="px-6 py-2.5 bg-white border border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-100 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-blue-900 transition transform hover:-translate-y-0.5 flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; ?>