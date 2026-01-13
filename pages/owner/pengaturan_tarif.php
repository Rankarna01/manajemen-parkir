<?php
//=========================================
// PENGATURAN TARIF (PARKIR & MEMBER)
//=========================================
$page_title = "Pengaturan Tarif";
require_once '../../core/init.php';

if ($_SESSION['role'] != 'owner') { header('Location: ../../login.php'); exit; }

// === 1. UPDATE TARIF PARKIR PER JAM ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tarif_parkir'])) {
    $id = $_POST['id_tarif'];
    $tarif = $_POST['tarif_per_jam'];
    
    $stmt = $db->prepare("UPDATE tarif_parkir SET tarif_per_jam = ? WHERE id = ?");
    $stmt->bind_param("di", $tarif, $id);
    if ($stmt->execute()) $_SESSION['success'] = "Tarif parkir diperbarui!";
    else $_SESSION['error'] = "Gagal update tarif parkir.";
    header('Location: pengaturan_tarif.php'); exit;
}

// === 2. UPDATE TARIF MEMBERSHIP ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tarif_member'])) {
    $id = $_POST['id_member_tarif'];
    $harga = $_POST['harga_paket'];
    $ket = $_POST['keterangan'];

    $stmt = $db->prepare("UPDATE tarif_membership SET harga = ?, keterangan = ? WHERE id = ?");
    $stmt->bind_param("dsi", $harga, $ket, $id);
    if ($stmt->execute()) $_SESSION['success'] = "Harga paket membership diperbarui!";
    else $_SESSION['error'] = "Gagal update membership.";
    header('Location: pengaturan_tarif.php'); exit;
}

// === AMBIL DATA ===
$tarif_parkir = [];
$res_p = $db->query("SELECT * FROM tarif_parkir ORDER BY jenis_kendaraan");
while ($row = $res_p->fetch_assoc()) $tarif_parkir[] = $row;

$tarif_member = [];
$res_m = $db->query("SELECT * FROM tarif_membership ORDER BY durasi_bulan ASC");
while ($row = $res_m->fetch_assoc()) $tarif_member[] = $row;
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto max-w-6xl">
            
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-primary tracking-tight">Pengaturan Harga</h2>
                <p class="text-gray-500">Kelola tarif parkir per jam dan harga paket membership.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-700"><i class="fas fa-clock mr-2"></i> Tarif Parkir Per Jam</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Jenis Kendaraan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Tarif / Jam</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tarif_parkir as $t): ?>
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-6 py-4 flex items-center">
                                <span class="h-8 w-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3 font-bold">
                                    <?php echo strtoupper(substr($t['jenis_kendaraan'], 0, 1)); ?>
                                </span>
                                <span class="font-bold text-gray-700 capitalize"><?php echo $t['jenis_kendaraan']; ?></span>
                            </td>
                            <td class="px-6 py-4 font-bold text-accent">Rp <?php echo number_format($t['tarif_per_jam'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-right">
                                <button class="btn-edit-parkir bg-gray-100 hover:bg-blue-100 text-blue-600 px-3 py-1.5 rounded-lg text-sm font-bold transition"
                                        data-id="<?php echo $t['id']; ?>" 
                                        data-jenis="<?php echo $t['jenis_kendaraan']; ?>" 
                                        data-tarif="<?php echo $t['tarif_per_jam']; ?>">
                                    <i class="fas fa-edit"></i> Ubah
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-700"><i class="fas fa-id-card mr-2"></i> Harga Paket Membership</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Durasi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Keterangan Label</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Harga Paket</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($tarif_member as $m): ?>
                        <tr class="hover:bg-accenttransition">
                            <td class="px-6 py-4 font-bold text-gray-800"><?php echo $m['durasi_bulan']; ?> Bulan</td>
                            <td class="px-6 py-4 text-gray-500 text-sm"><?php echo $m['keterangan']; ?></td>
                            <td class="px-6 py-4 font-bold text-accent">Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-right">
                                <button class="btn-edit-member bg-gray-100 hover:bg-yellow-200 text-primary px-3 py-1.5 rounded-lg text-sm font-bold transition"
                                        data-id="<?php echo $m['id']; ?>" 
                                        data-bulan="<?php echo $m['durasi_bulan']; ?>" 
                                        data-harga="<?php echo $m['harga']; ?>"
                                        data-ket="<?php echo $m['keterangan']; ?>">
                                    <i class="fas fa-edit"></i> Ubah
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<div id="modalParkir" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white p-6 rounded-xl shadow-xl w-96">
        <h3 class="text-lg font-bold mb-4">Ubah Tarif Parkir</h3>
        <form method="POST">
            <input type="hidden" name="update_tarif_parkir" value="1">
            <input type="hidden" name="id_tarif" id="p_id">
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-500 mb-1">Jenis Kendaraan</label>
                <input type="text" id="p_jenis" class="w-full border rounded p-2 bg-gray-100" readonly>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 mb-1">Tarif Per Jam (Rp)</label>
                <input type="number" name="tarif_per_jam" id="p_tarif" class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="close-modal px-4 py-2 bg-gray-200 rounded text-gray-700">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="modalMember" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white p-6 rounded-xl shadow-xl w-96">
        <h3 class="text-lg font-bold mb-4 text-primary ">Ubah Harga Membership</h3>
        <form method="POST">
            <input type="hidden" name="update_tarif_member" value="1">
            <input type="hidden" name="id_member_tarif" id="m_id">
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-500 mb-1">Durasi (Bulan)</label>
                <input type="text" id="m_bulan" class="w-full border rounded p-2 bg-gray-100" readonly>
            </div>
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-500 mb-1">Keterangan Label</label>
                <input type="text" name="keterangan" id="m_ket" class="w-full border rounded p-2 focus:ring-2 focus:ring-accent">
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 mb-1">Harga Paket (Rp)</label>
                <input type="number" name="harga_paket" id="m_harga" class="w-full border rounded p-2 focus:ring-2 focus:ring-accent font-bold" required>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="close-modal px-4 py-2 bg-gray-200 rounded text-gray-700">Batal</button>
                <button type="submit" class="px-4 py-2 bg-accent text-white rounded hover:bg-accent">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer_app.php'; ?>

<script>
$(document).ready(function() {
    <?php if(isset($_SESSION['success'])): ?>Swal.fire('Sukses', '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>', 'success');<?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>Swal.fire('Gagal', '<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>', 'error');<?php endif; ?>

    // Edit Parkir
    $('.btn-edit-parkir').click(function() {
        $('#p_id').val($(this).data('id'));
        $('#p_jenis').val($(this).data('jenis'));
        $('#p_tarif').val($(this).data('tarif'));
        $('#modalParkir').removeClass('hidden').addClass('flex');
    });

    // Edit Member
    $('.btn-edit-member').click(function() {
        $('#m_id').val($(this).data('id'));
        $('#m_bulan').val($(this).data('bulan') + ' Bulan');
        $('#m_harga').val($(this).data('harga'));
        $('#m_ket').val($(this).data('ket'));
        $('#modalMember').removeClass('hidden').addClass('flex');
    });

    $('.close-modal').click(function() {
        $('#modalParkir, #modalMember').addClass('hidden').removeClass('flex');
    });
});
</script>