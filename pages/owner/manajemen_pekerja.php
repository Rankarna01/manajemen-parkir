<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Manajemen Pekerja";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// === PROSES POST (TAMBAH, EDIT, HAPUS) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- AKSI TAMBAH PEKERJA ---
    if (isset($_POST['action']) && $_POST['action'] == 'tambah') {
        $nama = $_POST['nama'];
        $np   = $_POST['np']; // GANTI email JADI np
        $password = $_POST['password'];
        $assigned_pos = !empty($_POST['assigned_pos_id']) ? $_POST['assigned_pos_id'] : NULL;
        
        // Validasi
        if (empty($nama) || empty($np) || empty($password)) {
            $_SESSION['error_message'] = "Semua field wajib diisi.";
        } else {
            // Cek NP duplikat
            $stmt_check = $db->prepare("SELECT id FROM users WHERE np = ?");
            $stmt_check->bind_param("s", $np);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows > 0) {
                $_SESSION['error_message'] = "Nomor Pekerja (NP) sudah terdaftar.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Query Insert NP
                $stmt_insert = $db->prepare("INSERT INTO users (nama, np, password, role, assigned_pos_id) VALUES (?, ?, ?, 'pekerja', ?)");
                $stmt_insert->bind_param("sssi", $nama, $np, $hashed_password, $assigned_pos);
                
                if ($stmt_insert->execute()) {
                    $_SESSION['success_message'] = "Pekerja baru berhasil ditambahkan.";
                } else {
                    $_SESSION['error_message'] = "Gagal menambahkan pekerja. Error DB.";
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
    
    // --- AKSI EDIT PEKERJA ---
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id_pekerja = $_POST['id_pekerja'];
        $nama = $_POST['nama'];
        $np   = $_POST['np']; // GANTI email JADI np
        $password = $_POST['password']; 
        $assigned_pos = !empty($_POST['assigned_pos_id']) ? $_POST['assigned_pos_id'] : NULL;

        if (empty($nama) || empty($np) || empty($id_pekerja)) {
            $_SESSION['error_message'] = "Nama dan NP tidak boleh kosong.";
        } else {
            // Cek NP duplikat
            $stmt_check = $db->prepare("SELECT id FROM users WHERE np = ? AND id != ?");
            $stmt_check->bind_param("si", $np, $id_pekerja);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows > 0) {
                $_SESSION['error_message'] = "Nomor Pekerja sudah dipakai orang lain.";
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_update = $db->prepare("UPDATE users SET nama=?, np=?, password=?, assigned_pos_id=? WHERE id=?");
                    $stmt_update->bind_param("sssii", $nama, $np, $hashed_password, $assigned_pos, $id_pekerja);
                } else {
                    $stmt_update = $db->prepare("UPDATE users SET nama=?, np=?, assigned_pos_id=? WHERE id=?");
                    $stmt_update->bind_param("ssii", $nama, $np, $assigned_pos, $id_pekerja);
                }
                
                if ($stmt_update->execute()) {
                    $_SESSION['success_message'] = "Data pekerja diperbarui.";
                } else {
                    $_SESSION['error_message'] = "Gagal update data.";
                }
                $stmt_update->close();
            }
            $stmt_check->close();
        }
    }
    
    // --- AKSI HAPUS PEKERJA ---
    if (isset($_POST['action']) && $_POST['action'] == 'hapus') {
        $id_pekerja = $_POST['id_pekerja_delete'];
        $stmt_delete = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'pekerja'");
        $stmt_delete->bind_param("i", $id_pekerja);
        
        if ($stmt_delete->execute()) {
            $_SESSION['success_message'] = "Data pekerja dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal hapus data.";
        }
        $stmt_delete->close();
    }

    header('Location: manajemen_pekerja.php');
    exit;
}

// === PROSES GET (BACA DATA) ===
$pekerja_list = [];
// GANTI QUERY: u.email -> u.np
$query = "SELECT u.id, u.nama, u.np, u.created_at, u.assigned_pos_id, p.nama_pos 
          FROM users u 
          LEFT JOIN pengaturan_sistem p ON u.assigned_pos_id = p.id 
          WHERE u.role = 'pekerja' 
          ORDER BY u.nama";
$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    $pekerja_list[] = $row;
}

// Ambil list Pos untuk Dropdown
$pos_options = [];
$res_pos = $db->query("SELECT id, nama_pos, tipe_pos FROM pengaturan_sistem");
while($p = $res_pos->fetch_assoc()){
    $pos_options[] = $p;
}

$db->close();
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary tracking-tight">Manajemen Pekerja</h2>
                    <p class="text-gray-500">Atur akun petugas dan lokasi tugas mereka.</p>
                </div>
                <button id="btnTambahModal" class="bg-accent hover:bg-yellow-500 text-primary font-bold py-2.5 px-5 rounded-xl shadow-md transition transform hover:-translate-y-0.5 flex items-center">
                    <i class="fas fa-plus mr-2"></i> Tambah Pekerja
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Nama</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Nomor Pekerja (NP)</th> <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Penugasan Pos</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Bergabung</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (empty($pekerja_list)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 bg-gray-50">
                                        <i class="fas fa-users-slash text-4xl text-gray-300 mb-2"></i>
                                        <p>Belum ada data pekerja.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pekerja_list as $pekerja): ?>
                                <tr class="hover:bg-blue-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full bg-secondary text-primary flex items-center justify-center text-sm font-bold mr-3">
                                                <?php echo strtoupper(substr($pekerja['nama'], 0, 1)); ?>
                                            </div>
                                            <div class="text-sm font-bold text-primary">
                                                <?php echo htmlspecialchars($pekerja['nama']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 font-mono font-bold">
                                        <?php echo htmlspecialchars($pekerja['np']); ?> </td>
                                    <td class="px-6 py-4">
                                        <?php if($pekerja['assigned_pos_id']): ?>
                                            <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">
                                                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo $pekerja['nama_pos']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded">Bebas / Belum Ditugaskan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo date('d M Y', strtotime($pekerja['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                        <button class="btn-edit text-primary hover:text-accent transition"
                                                data-id="<?php echo $pekerja['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars($pekerja['nama']); ?>"
                                                data-np="<?php echo htmlspecialchars($pekerja['np']); ?>" 
                                                data-pos="<?php echo $pekerja['assigned_pos_id']; ?>"
                                                title="Edit">
                                            <i class="fas fa-edit fa-lg"></i>
                                        </button>
                                        <button class="btn-hapus text-gray-400 hover:text-red-600 transition"
                                                data-id="<?php echo $pekerja['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars($pekerja['nama']); ?>"
                                                title="Hapus">
                                            <i class="fas fa-trash-alt fa-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="tambahModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50 backdrop-blur-sm">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-primary">Tambah Pekerja Baru</h3>
            <button id="btnBatalTambah" class="text-gray-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Pekerja (NP)</label>
                <input type="text" name="np" class="w-full px-4 py-2 border rounded-lg font-mono" required placeholder="Contoh: 2023001">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tugaskan Di Pos (Opsional)</label>
                <select name="assigned_pos_id" class="w-full px-4 py-2 border rounded-lg bg-white">
                    <option value="">-- Bebas (Bisa Pilih Sendiri) --</option>
                    <?php foreach($pos_options as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo $p['nama_pos']; ?> (<?php echo strtoupper($p['tipe_pos']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="btnBatalTambahModal" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50 backdrop-blur-sm">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-primary">Edit Data Pekerja</h3>
            <button id="btnBatalEdit" class="text-gray-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="modal_edit_id" name="id_pekerja">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" id="modal_edit_nama" name="nama" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Pekerja (NP)</label>
                <input type="text" id="modal_edit_np" name="np" class="w-full px-4 py-2 border rounded-lg font-mono" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tugaskan Di Pos</label>
                <select id="modal_edit_pos" name="assigned_pos_id" class="w-full px-4 py-2 border rounded-lg bg-white">
                    <option value="">-- Bebas --</option>
                    <?php foreach($pos_options as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo $p['nama_pos']; ?> (<?php echo strtoupper($p['tipe_pos']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru</label>
                <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg" placeholder="(Kosongkan jika tetap)">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="btnBatalEditModal" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer_app.php'; ?>

<script>
$(document).ready(function() {
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire('Berhasil!', '<?php echo $_SESSION['success_message']; ?>', 'success');
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire('Gagal!', '<?php echo $_SESSION['error_message']; ?>', 'error');
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    $('#btnTambahModal').click(function() { $('#tambahModal').removeClass('hidden').addClass('flex'); });
    $('#btnBatalTambah, #btnBatalTambahModal').click(function() { $('#tambahModal').addClass('hidden').removeClass('flex'); });

    // JS untuk Edit (Mapping data ke form)
    $('.btn-edit').click(function() {
        $('#modal_edit_id').val($(this).data('id'));
        $('#modal_edit_nama').val($(this).data('nama'));
        $('#modal_edit_np').val($(this).data('np')); // Ambil data NP
        $('#modal_edit_pos').val($(this).data('pos')); 
        
        $('#editModal').removeClass('hidden').addClass('flex');
    });
    $('#btnBatalEdit, #btnBatalEditModal').click(function() { $('#editModal').addClass('hidden').removeClass('flex'); });

    $('.btn-hapus').click(function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        Swal.fire({
            title: 'Hapus ' + nama + '?',
            text: "Data tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = $('<form action="" method="POST"><input type="hidden" name="action" value="hapus"><input type="hidden" name="id_pekerja_delete" value="' + id + '"></form>');
                $('body').append(form);
                form.submit();
            }
        });
    });
});
</script>