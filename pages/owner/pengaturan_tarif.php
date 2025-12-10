<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Pengaturan Tarif";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// === PROSES UPDATE DATA (Method POST) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tarif'])) {
    $id = $_POST['id_tarif'];
    $tarif_per_jam = $_POST['tarif_per_jam'];

    // Validasi sederhana
    if (!empty($id) && is_numeric($tarif_per_jam)) {
        $stmt = $db->prepare("UPDATE tarif_parkir SET tarif_per_jam = ? WHERE id = ?");
        $stmt->bind_param("di", $tarif_per_jam, $id);
        
        if ($stmt->execute()) {
            // Set session flash message untuk notifikasi SweetAlert
            $_SESSION['success_message'] = "Tarif berhasil diperbarui!";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui tarif.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Data tidak valid.";
    }

    // Redirect ke halaman ini lagi untuk refresh data & menghindari resubmit form
    header('Location: pengaturan_tarif.php');
    exit;
}

// === PROSES AMBIL DATA (Method GET) ===
$tarifs = [];
$result = $db->query("SELECT * FROM tarif_parkir ORDER BY jenis_kendaraan");
while ($row = $result->fetch_assoc()) {
    $tarifs[] = $row;
}

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>

<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">
    
    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        
        <div class="container mx-auto">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-primary tracking-tight">Manajemen Tarif Parkir</h2>
                <p class="text-gray-500">Atur biaya parkir per jam untuk setiap jenis kendaraan.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">
                                    Jenis Kendaraan
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">
                                    Tarif per Jam
                                </th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($tarifs as $tarif): ?>
                            <tr class="hover:bg-blue-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-lg bg-secondary text-primary flex items-center justify-center mr-3">
                                            <?php if(strtolower($tarif['jenis_kendaraan']) == 'mobil'): ?>
                                                <i class="fas fa-car"></i>
                                            <?php elseif(strtolower($tarif['jenis_kendaraan']) == 'motor'): ?>
                                                <i class="fas fa-motorcycle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-truck"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm font-bold text-primary capitalize">
                                            <?php echo htmlspecialchars($tarif['jenis_kendaraan']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-accent">
                                        Rp <?php echo number_format($tarif['tarif_per_jam'], 0, ',', '.'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="btn-edit text-primary hover:text-accent transition duration-200"
                                            data-id="<?php echo $tarif['id']; ?>"
                                            data-jenis="<?php echo htmlspecialchars($tarif['jenis_kendaraan']); ?>"
                                            data-tarif="<?php echo $tarif['tarif_per_jam']; ?>">
                                        <i class="fas fa-edit mr-1"></i> Ubah
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50 backdrop-blur-sm">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md transform transition-all scale-100">
        
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-primary">Ubah Tarif</h3>
            <button id="btnBatal" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form action="pengaturan_tarif.php" method="POST">
            <input type="hidden" id="modal_id_tarif" name="id_tarif">
            <input type="hidden" name="update_tarif" value="1">

            <div class="mb-4">
                <label for="modal_jenis_kendaraan" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kendaraan</label>
                <input type="text" id="modal_jenis_kendaraan" name="jenis_kendaraan"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-gray-100 text-gray-500 focus:outline-none cursor-not-allowed"
                       readonly>
            </div>
            
            <div class="mb-6">
                <label for="modal_tarif_per_jam" class="block text-sm font-semibold text-gray-700 mb-2">Tarif per Jam (Rp)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rp</span>
                    <input type="number" id="modal_tarif_per_jam" name="tarif_per_jam"
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition outline-none font-bold text-gray-800"
                           placeholder="Contoh: 5000" required>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="btnBatalModal" class="px-5 py-2.5 bg-secondary text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-blue-900 shadow-md transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<script>
$(document).ready(function() {
    
    // 1. Tampilkan Modal saat tombol "Ubah" diklik
    $('.btn-edit').on('click', function() {
        // Ambil data dari tombol
        var id = $(this).data('id');
        var jenis = $(this).data('jenis');
        var tarif = $(this).data('tarif');

        // Isi form di dalam modal
        $('#modal_id_tarif').val(id);
        $('#modal_jenis_kendaraan').val(jenis);
        $('#modal_tarif_per_jam').val(tarif);

        // Tampilkan modal
        $('#editModal').removeClass('hidden').addClass('flex');
    });

    // 2. Sembunyikan Modal saat tombol "Batal" (di dalam modal) diklik
    $('#btnBatal, #btnBatalModal').on('click', function() {
        $('#editModal').addClass('hidden').removeClass('flex');
    });

    // 3. Tampilkan Notifikasi SweetAlert (jika ada session flash message)
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: '<?php echo $_SESSION['success_message']; ?>',
            icon: 'success',
            confirmButtonColor: '#0B1F4F', // Primary Color
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['success_message']); // Hapus session setelah ditampilkan ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Gagal!',
            text: '<?php echo $_SESSION['error_message']; ?>',
            icon: 'error',
            confirmButtonColor: '#EF4444', // Red Color
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['error_message']); // Hapus session setelah ditampilkan ?>
    <?php endif; ?>

});
</script>