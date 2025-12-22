<?php
$page_title = "Manajemen Alat Per Pos";
require_once '../../core/init.php';

if ($_SESSION['role'] != 'owner') { header('Location: ../../login.php'); exit; }

// --- HAPUS POS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $db->query("DELETE FROM pengaturan_sistem WHERE id=$id");
    header("Location: konfigurasi.php"); exit;
}

// --- AMBIL SEMUA DATA POS ---
$result = $db->query("SELECT * FROM pengaturan_sistem");
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto max-w-6xl">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary tracking-tight">Konfigurasi Alat per Pos</h2>
                    <p class="text-gray-500">Kelola pengaturan perangkat keras untuk setiap pos parkir.</p>
                </div>
                <a href="tambah_pos.php" class="bg-accent hover:bg-yellow-500 text-primary font-bold py-2.5 px-5 rounded-xl shadow-md transition transform hover:-translate-y-0.5 flex items-center">
                    <i class="fas fa-plus mr-2"></i> Tambah Pos Baru
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Nama Pos</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">IP Kamera</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">IP Palang</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-blue-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-primary"><?php echo $row['nama_pos']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($row['tipe_pos'] == 'mobil'): ?>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                            <i class="fas fa-car mr-1.5 mt-0.5"></i> MOBIL
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                            <i class="fas fa-motorcycle mr-1.5 mt-0.5"></i> MOTOR
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="w-4 flex justify-center text-green-500"><i class="fas fa-arrow-right text-[10px]"></i></span>
                                        <?php echo $row['ip_kamera_masuk']; ?>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-4 flex justify-center text-red-500"><i class="fas fa-arrow-left text-[10px]"></i></span>
                                        <?php echo $row['ip_kamera_keluar']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="w-4 flex justify-center text-green-500"><i class="fas fa-arrow-right text-[10px]"></i></span>
                                        <?php echo $row['ip_palang_masuk']; ?>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-4 flex justify-center text-red-500"><i class="fas fa-arrow-left text-[10px]"></i></span>
                                        <?php echo $row['ip_palang_keluar']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center space-x-3">
                                        <a href="edit_pos.php?id=<?php echo $row['id']; ?>" class="text-primary hover:text-accent transition duration-200" title="Edit">
                                            <i class="fas fa-edit fa-lg"></i>
                                        </a>
                                        <a href="?hapus=<?php echo $row['id']; ?>" onclick="return confirm('Hapus pos ini?')" class="text-gray-400 hover:text-red-600 transition duration-200" title="Hapus">
                                            <i class="fas fa-trash-alt fa-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
<?php require_once '../../templates/footer_app.php'; ?>