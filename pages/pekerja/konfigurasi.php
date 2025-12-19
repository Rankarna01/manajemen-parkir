<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Konfigurasi Alat";
require_once '../../core/init.php';

// Keamanan Halaman (HANYA PEKERJA)
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
}

// --- PROSES SIMPAN DATA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama       = $_POST['nama_instansi'];
    $alamat     = $_POST['alamat_instansi'];
    $footer     = $_POST['footer_struk'];
    $ip_cam_in  = $_POST['ip_kamera_masuk'];
    $ip_cam_out = $_POST['ip_kamera_keluar'];
    $ip_gate_in = $_POST['ip_palang_masuk'];
    $ip_gate_out= $_POST['ip_palang_keluar'];
    $ip_printer = $_POST['ip_printer']; // Tambahan printer
    $tipe_pos   = $_POST['tipe_pos'];

    // Update ke database
    $stmt = $db->prepare("UPDATE pengaturan_sistem SET nama_instansi=?, alamat_instansi=?, footer_struk=?, ip_kamera_masuk=?, ip_kamera_keluar=?, ip_palang_masuk=?, ip_palang_keluar=?, ip_printer=?, tipe_pos=? WHERE id=1");
    $stmt->bind_param("sssssssss", $nama, $alamat, $footer, $ip_cam_in, $ip_cam_out, $ip_gate_in, $ip_gate_out, $ip_printer, $tipe_pos);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Pengaturan alat berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan pengaturan.";
    }
    
    // Refresh halaman agar data terbaru muncul
    header("Location: konfigurasi.php");
    exit;
}

// --- AMBIL DATA SAAT INI ---
$data = $db->query("SELECT * FROM pengaturan_sistem WHERE id=1")->fetch_assoc();

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>
<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto max-w-5xl">
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <script>Swal.fire({
                    title: 'Berhasil', 
                    text: '<?php echo $_SESSION['success_message']; ?>', 
                    icon: 'success',
                    confirmButtonColor: '#0B1F4F'
                });</script>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary tracking-tight">Konfigurasi Teknis</h2>
                    <p class="text-gray-500">Atur alamat IP perangkat keras dan identitas struk.</p>
                </div>
            </div>

            <form action="" method="POST" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                
                <div class="p-6 bg-primary border-b border-blue-900 text-white flex justify-between items-center">
                    <span class="font-bold flex items-center tracking-wide text-lg">
                        <i class="fas fa-cogs mr-3 text-accent"></i> PENGATURAN ALAT
                    </span>
                    <span class="bg-blue-900/50 text-accent text-xs font-bold py-1.5 px-3 rounded-lg border border-blue-800 uppercase tracking-wider">
                        Mode Teknisi
                    </span>
                </div>

                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-10">
                    
                    <div class="space-y-6">
                        <h3 class="text-gray-800 font-bold border-b pb-3 mb-4 flex items-center text-lg">
                            <div class="w-8 h-8 rounded-lg bg-secondary text-primary flex items-center justify-center mr-3">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            Koneksi Perangkat (IP)
                        </h3>
                        
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">IP CCTV Masuk</label>
                                <input type="text" name="ip_kamera_masuk" value="<?php echo htmlspecialchars($data['ip_kamera_masuk']); ?>" 
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">IP CCTV Keluar</label>
                                <input type="text" name="ip_kamera_keluar" value="<?php echo htmlspecialchars($data['ip_kamera_keluar']); ?>" 
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                            
                            <div class="border-t border-dashed border-gray-200 my-1"></div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Palang Masuk</label>
                                <input type="text" name="ip_palang_masuk" value="<?php echo htmlspecialchars($data['ip_palang_masuk']); ?>" 
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                                <p class="text-xs text-gray-400 mt-1 italic">Contoh: http://192.168.1.105/open</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Palang Keluar</label>
                                <input type="text" name="ip_palang_keluar" value="<?php echo htmlspecialchars($data['ip_palang_keluar']); ?>" 
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                            
                            <div class="border-t border-dashed border-gray-200 my-1"></div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Printer Tiket</label>
                                <input type="text" name="ip_printer" value="<?php echo htmlspecialchars($data['ip_printer']); ?>" 
                                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none font-mono text-sm transition">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h3 class="text-gray-800 font-bold border-b pb-3 mb-4 flex items-center text-lg">
                            <div class="w-8 h-8 rounded-lg bg-secondary text-primary flex items-center justify-center mr-3">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            Sistem & Struk
                        </h3>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Jenis POS Ini</label>
                            <div class="relative">
                                <select name="tipe_pos" class="w-full border-2 border-primary bg-blue-50/50 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary outline-none font-bold text-primary appearance-none">
                                    <option value="mobil" <?php echo ($data['tipe_pos']=='mobil')?'selected':''; ?>>POS MOBIL</option>
                                    <option value="motor" <?php echo ($data['tipe_pos']=='motor')?'selected':''; ?>>POS MOTOR</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-primary">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <p class="text-xs text-red-500 mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i> Mengubah ini akan merubah tarif otomatis sistem.
                            </p>
                        </div>

                        <div class="border-t border-dashed border-gray-200 my-4"></div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Instansi (Header 1)</label>
                            <input type="text" name="nama_instansi" value="<?php echo htmlspecialchars($data['nama_instansi']); ?>" 
                                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Contoh: VENTRIA MALL">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Alamat (Header 2)</label>
                            <input type="text" name="alamat_instansi" value="<?php echo htmlspecialchars($data['alamat_instansi']); ?>" 
                                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Jl. Sudirman No. 1">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Pesan Footer</label>
                            <textarea name="footer_struk" rows="2"
                                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Terima Kasih atas Kunjungan Anda"><?php echo htmlspecialchars($data['footer_struk']); ?></textarea>
                        </div>
                    </div>

                </div>

                <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="reset" class="px-6 py-2.5 bg-white border border-gray-300 text-gray-600 rounded-xl hover:bg-gray-100 font-bold transition">
                        Reset
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-xl font-bold shadow-lg hover:bg-blue-900 transition transform hover:-translate-y-0.5 flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Konfigurasi
                    </button>
                </div>

            </form>

        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>