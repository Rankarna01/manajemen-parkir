<?php
$page_title = "Laporan Absensi Karyawan";
require_once '../../core/init.php';

// Cek Keamanan
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// --- QUERY PERBAIKAN ---
// Mengambil kolom 'nama' dari tabel users (u.nama), bukan u.username
$query = "SELECT a.*, u.nama, p.nama_pos 
          FROM absensi a 
          JOIN users u ON a.user_id = u.id 
          JOIN pengaturan_sistem p ON a.pos_id = p.id 
          ORDER BY a.waktu_masuk DESC";

$result = $db->query($query);
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
        <div class="container mx-auto max-w-6xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Laporan Kehadiran & Lokasi</h2>

                <a href="cetak_laporan_absensi.php" target="_blank"
                    class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded-lg flex items-center shadow transition hover:-translate-y-0.5">
                    <i class="fas fa-print mr-2"></i> Cetak Laporan
                </a>
            </div>


            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Petugas</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Pos Jaga</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Check-In</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Check-Out</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <?php echo htmlspecialchars($row['nama']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?php echo htmlspecialchars($row['nama_pos']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">
                                            <?php echo date('d M H:i', strtotime($row['waktu_masuk'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($row['waktu_keluar']): ?>
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">
                                                <?php echo date('d M H:i', strtotime($row['waktu_keluar'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold animate-pulse">
                                                Sedang Bertugas
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if (!empty($row['koordinat_masuk'])): ?>
                                            <a href="http://maps.google.com/maps?q=<?php echo $row['koordinat_masuk']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                                                <i class="fas fa-map-marker-alt mr-1"></i> Peta
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs italic">Lokasi Tidak Terdeteksi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada data absensi hari ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php require_once '../../templates/footer_app.php'; ?>