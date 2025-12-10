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
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Validasi
        if (empty($nama) || empty($email) || empty($password)) {
            $_SESSION['error_message'] = "Semua field wajib diisi.";
        } else {
            // Cek email duplikat
            $stmt_check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $_SESSION['error_message'] = "Email sudah terdaftar. Gunakan email lain.";
            } else {
                // Email aman, hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt_insert = $db->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'pekerja')");
                $stmt_insert->bind_param("sss", $nama, $email, $hashed_password);
                
                if ($stmt_insert->execute()) {
                    $_SESSION['success_message'] = "Pekerja baru berhasil ditambahkan.";
                } else {
                    $_SESSION['error_message'] = "Gagal menambahkan pekerja. Terjadi error DB.";
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
        $email = $_POST['email'];
        $password = $_POST['password']; // Password baru (opsional)

        // Validasi
        if (empty($nama) || empty($email) || empty($id_pekerja)) {
            $_SESSION['error_message'] = "Nama dan Email tidak boleh kosong.";
        } else {
            // Cek email duplikat (pastikan bukan email dia sendiri)
            $stmt_check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->bind_param("si", $email, $id_pekerja);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $_SESSION['error_message'] = "Email sudah terdaftar oleh pengguna lain.";
            } else {
                // Email aman, siapkan query update
                if (!empty($password)) {
                    // Jika password diisi, update password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_update = $db->prepare("UPDATE users SET nama = ?, email = ?, password = ? WHERE id = ?");
                    $stmt_update->bind_param("sssi", $nama, $email, $hashed_password, $id_pekerja);
                } else {
                    // Jika password kosong, jangan update password
                    $stmt_update = $db->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
                    $stmt_update->bind_param("ssi", $nama, $email, $id_pekerja);
                }
                
                if ($stmt_update->execute()) {
                    $_SESSION['success_message'] = "Data pekerja berhasil diperbarui.";
                } else {
                    $_SESSION['error_message'] = "Gagal memperbarui data pekerja.";
                }
                $stmt_update->close();
            }
            $stmt_check->close();
        }
    }
    
    // --- AKSI HAPUS PEKERJA ---
    // Aksi ini akan dipicu oleh JavaScript
    if (isset($_POST['action']) && $_POST['action'] == 'hapus') {
        $id_pekerja = $_POST['id_pekerja_delete'];
        
        // HATI-HATI: Sebaiknya cek dulu apakah pekerja ini terkait dengan transaksi
        // Untuk saat ini, kita langsung delete
        $stmt_delete = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'pekerja'");
        $stmt_delete->bind_param("i", $id_pekerja);
        
        if ($stmt_delete->execute()) {
            $_SESSION['success_message'] = "Data pekerja berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data pekerja (mungkin terkait data lain).";
        }
        $stmt_delete->close();
    }

    // Redirect setelah proses POST selesai
    header('Location: manajemen_pekerja.php');
    exit;
}

// === PROSES GET (BACA DATA) ===
$pekerja_list = [];
$result = $db->query("SELECT id, nama, email, created_at FROM users WHERE role = 'pekerja' ORDER BY nama");
while ($row = $result->fetch_assoc()) {
    $pekerja_list[] = $row;
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
        
        <div class="container mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary tracking-tight">Manajemen Pekerja</h2>
                    <p class="text-gray-500">Tambah, edit, atau hapus data petugas parkir.</p>
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
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Bergabung Sejak</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (empty($pekerja_list)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 bg-gray-50">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-users-slash text-4xl text-gray-300 mb-2"></i>
                                            <p>Belum ada data pekerja.</p>
                                        </div>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo htmlspecialchars($pekerja['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 py-1 rounded bg-secondary text-gray-600 text-xs font-medium">
                                            <?php echo date('d M Y', strtotime($pekerja['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button class="btn-edit text-primary hover:text-accent transition duration-200"
                                                data-id="<?php echo $pekerja['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars($pekerja['nama']); ?>"
                                                data-email="<?php echo htmlspecialchars($pekerja['email']); ?>"
                                                title="Edit">
                                            <i class="fas fa-edit fa-lg"></i>
                                        </button>
                                        <button class="btn-hapus text-gray-400 hover:text-red-600 transition duration-200"
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
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md transform transition-all scale-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-primary">Tambah Pekerja Baru</h3>
            <button id="btnBatalTambah" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="manajemen_pekerja.php" method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="mb-4">
                <label for="nama" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition outline-none" required placeholder="Contoh: Budi Santoso">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition outline-none" required placeholder="email@contoh.com">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition outline-none" required placeholder="********">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="btnBatalTambahModal" class="px-5 py-2.5 bg-secondary text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-blue-900 shadow-md transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50 backdrop-blur-sm">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md transform transition-all scale-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-primary">Edit Data Pekerja</h3>
            <button id="btnBatalEdit" class="text-gray-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="manajemen_pekerja.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="modal_edit_id" name="id_pekerja">
            
            <div class="mb-4">
                <label for="modal_edit_nama" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                <input type="text" id="modal_edit_nama" name="nama" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition outline-none" required>
            </div>
            <div class="mb-4">
                <label for="modal_edit_email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" id="modal_edit_email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition outline-none" required>
            </div>
            <div class="mb-6">
                <label for="modal_edit_password" class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                <input type="password" id="modal_edit_password" name="password" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition outline-none" placeholder="Kosongkan jika tidak diubah">
                <p class="text-xs text-gray-400 mt-1">Biarkan kosong jika tidak ingin mengganti password.</p>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" id="btnBatalEditModal" class="px-5 py-2.5 bg-secondary text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-blue-900 shadow-md transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<script>
$(document).ready(function() {
    
    // --- NOTIFIKASI SWEETALERT ---
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: '<?php echo $_SESSION['success_message']; ?>',
            icon: 'success',
            confirmButtonColor: '#0B1F4F', // Primary Color
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Gagal!',
            text: '<?php echo $_SESSION['error_message']; ?>',
            icon: 'error',
            confirmButtonColor: '#EF4444', // Red Color
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    // --- MODAL TAMBAH ---
    // Tampilkan modal tambah
    $('#btnTambahModal').on('click', function() {
        $('#tambahModal').removeClass('hidden').addClass('flex');
    });
    // Sembunyikan modal tambah
    $('#btnBatalTambah, #btnBatalTambahModal').on('click', function() {
        $('#tambahModal').addClass('hidden').removeClass('flex');
    });

    // --- MODAL EDIT ---
    // Tampilkan modal edit
    $('.btn-edit').on('click', function() {
        // Ambil data dari tombol
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var email = $(this).data('email');
        
        // Isi form di modal
        $('#modal_edit_id').val(id);
        $('#modal_edit_nama').val(nama);
        $('#modal_edit_email').val(email);
        $('#modal_edit_password').val(''); // Kosongkan field password
        
        // Tampilkan modal
        $('#editModal').removeClass('hidden').addClass('flex');
    });
    // Sembunyikan modal edit
    $('#btnBatalEdit, #btnBatalEditModal').on('click', function() {
        $('#editModal').addClass('hidden').removeClass('flex');
    });

    // --- AKSI HAPUS (DENGAN SWEETALERT) ---
    $('.btn-hapus').on('click', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        
        Swal.fire({
            title: 'Anda yakin?',
            text: "Anda akan menghapus data pekerja '" + nama + "'. Aksi ini tidak bisa dibatalkan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444', // Red Color
            cancelButtonColor: '#0B1F4F', // Primary Color
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Buat form dinamis untuk submit POST
                var form = $('<form action="manajemen_pekerja.php" method="POST"></form>');
                form.append('<input type="hidden" name="action" value="hapus">');
                form.append('<input type="hidden" name="id_pekerja_delete" value="' + id + '">');
                $('body').append(form);
                form.submit();
            }
        });
    });

});
</script>