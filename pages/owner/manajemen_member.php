<?php
//=========================================
// MANAJEMEN MEMBER (FIXED AJAX HISTORY)
//=========================================
require_once '../../core/init.php';

// Cek Role Owner
if ($_SESSION['role'] != 'owner') { header('Location: ../../login.php'); exit; }

// --- 1. AJAX HANDLER (BAGIAN YANG ERROR TADI) ---
if (isset($_POST['action']) && $_POST['action'] == 'get_history') {
    // Bersihkan buffer agar tidak ada HTML error yang masuk ke JSON
    ob_clean(); 
    header('Content-Type: application/json');
    
    $id_member = (int)$_POST['id'];
    
    // PERBAIKAN DI SINI: Mengganti 'u.username' menjadi 'u.nama'
    // Pastikan tabel 'users' kamu punya kolom 'nama'. Jika error lagi, cek nama kolomnya.
    $query = "SELECT t.*, u.nama as admin_name 
              FROM transaksi_membership t 
              LEFT JOIN users u ON t.id_user = u.id 
              WHERE t.id_member = ? 
              ORDER BY t.tanggal_bayar DESC";
              
    $stmt = $db->prepare($query);
    
    if(!$stmt) {
        // Jika masih error, kirim pesan error database asli ke JSON
        echo json_encode(['error' => 'SQL Error: ' . $db->error]); 
        exit;
    }
    
    $stmt->bind_param("i", $id_member);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while($row = $result->fetch_assoc()) {
        $row['formatted_date'] = date('d/m/Y H:i', strtotime($row['tanggal_bayar']));
        $row['formatted_total'] = "Rp " . number_format($row['total_bayar'], 0, ',', '.');
        // Jika nama admin kosong (misal user dihapus), ganti jadi '-'
        $row['admin_name'] = $row['admin_name'] ?? 'System';
        $history[] = $row;
    }
    
    echo json_encode($history);
    exit; // PENTING: Stop script di sini
}

// --- 2. PROSES POST (CRUD & TOPUP) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // TAMBAH MEMBER
    if ($action == 'tambah') {
        $rfid = trim($_POST['rfid_uid']);
        $nama = trim($_POST['nama']);
        $nip  = trim($_POST['nip']);
        $plat = strtoupper(trim($_POST['plat_nomor']));
        $expired = $_POST['tanggal_expired'];
        
        $cek = $db->query("SELECT id FROM members WHERE rfid_uid = '$rfid'");
        if ($cek->num_rows > 0) {
            $_SESSION['error'] = "RFID sudah terdaftar!";
        } else {
            $stmt = $db->prepare("INSERT INTO members (rfid_uid, nama, nip, plat_nomor, tanggal_expired) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $rfid, $nama, $nip, $plat, $expired);
            if ($stmt->execute()) $_SESSION['success'] = "Member berhasil ditambahkan.";
        }
    }
    // EDIT MEMBER
    elseif ($action == 'edit') {
        $id = $_POST['id_member'];
        $rfid = trim($_POST['rfid_uid']);
        $nama = trim($_POST['nama']);
        $nip = trim($_POST['nip']);
        $plat = strtoupper(trim($_POST['plat_nomor']));
        $expired = $_POST['tanggal_expired'];
        $status = $_POST['status'];
        
        $stmt = $db->prepare("UPDATE members SET rfid_uid=?, nama=?, nip=?, plat_nomor=?, tanggal_expired=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssi", $rfid, $nama, $nip, $plat, $expired, $status, $id);
        if ($stmt->execute()) $_SESSION['success'] = "Data diperbarui.";
    }
    // HAPUS MEMBER
    elseif ($action == 'hapus') {
        $id = $_POST['id_hapus'];
        $db->query("DELETE FROM members WHERE id=$id");
        $_SESSION['success'] = "Member dihapus.";
    }
    // TOP UP
    elseif ($action == 'topup') {
        $id_member = $_POST['id_member_topup'];
        $bulan = (int) $_POST['paket_bulan'];
        $nominal = (int) str_replace('.', '', $_POST['nominal_bayar']);
        $id_user = $_SESSION['user_id'];

        // Ambil expired lama
        $q = $db->query("SELECT tanggal_expired FROM members WHERE id = $id_member");
        $d = $q->fetch_assoc();
        $old_expired = $d['tanggal_expired'];
        $today = date('Y-m-d');

        // Logic tanggal
        $start_date = ($old_expired < $today) ? $today : $old_expired;
        $new_expired = date('Y-m-d', strtotime("+$bulan month", strtotime($start_date)));

        // Update Member
        $db->query("UPDATE members SET tanggal_expired = '$new_expired', status = 'aktif' WHERE id = $id_member");

        // Simpan Transaksi
        $stmt = $db->prepare("INSERT INTO transaksi_membership (id_member, paket_bulan, total_bayar, id_user, keterangan) VALUES (?, ?, ?, ?, ?)");
        $ket = "Perpanjang $bulan Bulan";
        $stmt->bind_param("iiiss", $id_member, $bulan, $nominal, $id_user, $ket);
        
        if ($stmt->execute()) $_SESSION['success'] = "Perpanjangan berhasil! Exp: " . date('d/m/Y', strtotime($new_expired));
    }
    header("Location: manajemen_member.php"); exit;
}

// --- AMBIL DATA MEMBER UTAMA ---
$members = [];
$res = $db->query("SELECT * FROM members ORDER BY tanggal_expired ASC");
while ($row = $res->fetch_assoc()) $members[] = $row;

// --- AMBIL DATA PAKET TARIF DARI DB ---
$paket_tarif = [];
// Cek dulu apakah tabel tarif_membership ada, kalau tidak buat array kosong agar tidak error
$cek_tabel = $db->query("SHOW TABLES LIKE 'tarif_membership'");
if($cek_tabel->num_rows > 0){
    $res_tarif = $db->query("SELECT * FROM tarif_membership ORDER BY durasi_bulan ASC");
    while ($row = $res_tarif->fetch_assoc()) $paket_tarif[] = $row;
} else {
    // Fallback jika tabel belum dibuat (agar halaman tetap jalan)
    $paket_tarif = [
        ['durasi_bulan'=>1, 'harga'=>100000, 'keterangan'=>'Default 1 Bulan'],
        ['durasi_bulan'=>3, 'harga'=>280000, 'keterangan'=>'Default 3 Bulan'],
        ['durasi_bulan'=>6, 'harga'=>550000, 'keterangan'=>'Default 6 Bulan'],
    ];
}

$page_title = "Manajemen Member";
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>
    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-primary tracking-tight">Manajemen Member</h2>
                    <p class="text-gray-500">Kelola member, cetak kartu, dan perpanjangan.</p>
                </div>
                <button id="btnTambah" class="bg-accent hover:bg-yellow-500 text-primary font-bold py-2.5 px-5 rounded-xl shadow-md flex items-center transition">
                    <i class="fas fa-plus-circle mr-2"></i> Member Baru
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Member Info</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Kartu / Plat</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Masa Aktif</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($members as $m): 
                                $today = date('Y-m-d');
                                $exp = $m['tanggal_expired'];
                                $diff = (strtotime($exp) - strtotime($today)) / (60 * 60 * 24); 
                                
                                $badge_cls = ($exp < $today) ? 'bg-red-100 text-red-600' : ($diff <= 7 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700');
                                $badge_txt = ($exp < $today) ? 'EXPIRED' : ($diff <= 7 ? 'WARNING' : 'AKTIF');
                                if($m['status'] == 'non-aktif') { $badge_cls = 'bg-gray-100 text-gray-500'; $badge_txt = 'BLOKIR'; }
                            ?>
                            <tr class="hover:bg-blue-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800"><?php echo htmlspecialchars($m['nama']); ?></div>
                                    <div class="text-xs text-gray-500 font-mono">NIP: <?php echo htmlspecialchars($m['nip'] ?? '-'); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-blue-600 font-bold"><?php echo htmlspecialchars($m['rfid_uid']); ?></div>
                                    <div class="mt-1 px-2 py-0.5 bg-gray-100 rounded text-xs font-bold inline-block border border-gray-300"><?php echo htmlspecialchars($m['plat_nomor']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm"><?php echo date('d M Y', strtotime($exp)); ?></td>
                                <td class="px-6 py-4 text-center"><span class="px-3 py-1 text-[10px] font-bold rounded-full <?php echo $badge_cls; ?>"><?php echo $badge_txt; ?></span></td>
                                <td class="px-6 py-4 text-right flex justify-end gap-1">
                                    <button class="btn-topup bg-green-500 text-white p-2 rounded shadow" title="Top Up" data-json='<?php echo json_encode($m); ?>'><i class="fas fa-wallet"></i></button>
                                    <button class="btn-history bg-primary text-white p-2 rounded shadow" title="Riwayat" data-id="<?php echo $m['id']; ?>" data-nama="<?php echo htmlspecialchars($m['nama']); ?>"><i class="fas fa-history"></i></button>
                                    <button class="btn-cetak bg-blue-500 text-white p-2 rounded shadow" title="Cetak" data-json='<?php echo json_encode($m); ?>'><i class="fas fa-print"></i></button>
                                    <button class="btn-edit bg-gray-200 text-gray-600 p-2 rounded" title="Edit" data-json='<?php echo json_encode($m); ?>'><i class="fas fa-edit"></i></button>
                                    <button class="btn-hapus bg-red-100 text-red-500 p-2 rounded" title="Hapus" data-id="<?php echo $m['id']; ?>"><i class="fas fa-trash"></i></button>
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

<div id="modalHistory" class="fixed inset-0 bg-gray-900 bg-opacity-70 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all scale-100 flex flex-col max-h-[80vh]">
        <div class="bg-primary p-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fas fa-history mr-2"></i> Riwayat <span id="historyNama"></span></h3>
            <button class="close-history hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-0">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr><th class="px-6 py-3 text-left text-xs font-bold text-gray-500">Tanggal</th><th class="px-6 py-3 text-left text-xs font-bold text-gray-500">Paket</th><th class="px-6 py-3 text-left text-xs font-bold text-gray-500">Admin</th><th class="px-6 py-3 text-right text-xs font-bold text-gray-500">Nominal</th></tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="tableHistoryBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalTopUp" class="fixed inset-0 bg-gray-900 bg-opacity-70 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-primary p-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fas fa-wallet mr-2"></i> Perpanjang Member</h3>
            <button class="close-topup hover:text-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <form action="" method="POST" class="p-6">
            <input type="hidden" name="action" value="topup">
            <input type="hidden" name="id_member_topup" id="idMemberTopUp">
            
            <div class="mb-4 bg-green-50 p-3 rounded border border-green-100">
                <p id="topupNama" class="font-bold text-gray-800 text-lg">Nama</p>
                <p class="text-xs text-gray-500">Expired saat ini: <span id="topupOldExp" class="font-bold text-red-600"></span></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Paket</label>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach($paket_tarif as $p): ?>
                    <button type="button" class="paket-btn py-2 px-2 border-2 border-gray-200 rounded-lg text-sm font-bold hover:border-green-500 hover:bg-green-50 transition text-left" 
                            data-bulan="<?php echo $p['durasi_bulan']; ?>" 
                            data-harga="<?php echo $p['harga']; ?>">
                        <div class="text-gray-800"><?php echo $p['durasi_bulan']; ?> Bulan</div>
                        <div class="text-green-600 text-xs">Rp <?php echo number_format($p['harga'],0,',','.'); ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="paket_bulan" id="inputBulan" required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700">Total Bayar</label>
                <input type="text" name="nominal_bayar" id="inputHarga" class="w-full text-2xl font-black text-primary border-b-2 border-blue-200 focus:outline-none py-2 bg-transparent" readonly placeholder="0">
            </div>
            
            <div class="text-center text-sm text-gray-500 mb-4">Baru aktif s/d: <span id="newExpiredPreview" class="font-bold text-black">-</span></div>

            <button type="submit" class="w-full py-3 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-blue-700">Konfirmasi</button>
        </form>
    </div>
</div>

<div id="modalMember" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white p-8 rounded-2xl w-full max-w-lg">
        <h3 class="text-xl font-bold mb-4" id="modalTitle">Member</h3>
        <form action="" method="POST" id="formMember">
            <input type="hidden" name="action" id="formAction" value="tambah">
            <input type="hidden" name="id_member" id="idMember">
            <div class="grid grid-cols-2 gap-4 mb-3">
                <input type="text" name="rfid_uid" id="rfidUid" class="border rounded p-2" placeholder="RFID" required>
                <input type="text" name="plat_nomor" id="platNomor" class="border rounded p-2 uppercase" placeholder="Plat No">
            </div>
            <input type="text" name="nama" id="namaMember" class="border rounded p-2 w-full mb-3" placeholder="Nama Lengkap" required>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <input type="text" name="nip" id="nipMember" class="border rounded p-2" placeholder="NIP">
                <input type="date" name="tanggal_expired" id="tglExpired" class="border rounded p-2" required>
            </div>
            <div id="boxStatus" class="hidden mb-3">
                <select name="status" id="statusMember" class="border rounded p-2 w-full"><option value="aktif">Aktif</option><option value="non-aktif">Non-Aktif</option></select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="close-modal bg-gray-200 px-4 py-2 rounded">Batal</button>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="printArea" class="hidden">
    <div class="flex flex-col gap-10 justify-center items-center h-full">
        <div class="id-card bg-white border border-gray-300 relative rounded-xl overflow-hidden">
            <div class="h-20 bg-primary p-4 flex items-center gap-3 text-white">
                <div class="w-10 h-10 bg-accent rounded flex items-center justify-center font-bold text-black text-sm">VP</div>
                <div><h1 class="font-bold text-lg">VentriaPark</h1><p class="text-[10px]">MEMBER ACCESS</p></div>
            </div>
            <div class="p-5">
                <h2 id="cardNama" class="font-bold text-xl uppercase mb-1">NAMA</h2>
                <p id="cardPlat" class="bg-gray-200 px-2 py-1 rounded inline-block font-bold text-sm">PLAT</p>
                <p class="text-xs text-gray-500 mt-2">Exp: <span id="cardExpired"></span></p>
            </div>
        </div>
        <div class="id-card bg-gray-50 border border-gray-300 relative rounded-xl overflow-hidden flex flex-col">
            <div class="bg-gray-800 text-white text-center py-1 text-[10px] font-bold">TERMS</div>
            <div class="flex-1 flex flex-col justify-center items-center p-4">
                <svg id="barcode"></svg>
                <p class="text-[9px] mt-1" id="cardRfidText">UID</p>
            </div>
        </div>
    </div>
</div>
<style>
    .id-card { width: 340px; height: 215px; }
    @media print { 
        body * { visibility: hidden; } #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<?php require_once '../../templates/footer_app.php'; ?>

<script>
$(document).ready(function() {
    <?php if(isset($_SESSION['success'])): ?>Swal.fire('Sukses', '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>', 'success');<?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>Swal.fire('Gagal', '<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>', 'error');<?php endif; ?>

    // --- LOGIC HISTORY (AJAX FIX) ---
    $('.btn-history').click(function() {
        let id = $(this).data('id');
        let nama = $(this).data('nama');
        $('#historyNama').text(nama);
        $('#modalHistory').removeClass('hidden').addClass('flex');
        $('#tableHistoryBody').html('<tr><td colspan="4" class="px-6 py-4 text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');

        $.ajax({
            type: 'POST',
            url: 'manajemen_member.php',
            data: { action: 'get_history', id: id },
            dataType: 'json',
            success: function(res) {
                let html = '';
                if(res.length > 0) {
                    res.forEach(item => {
                        html += `<tr class="border-b">
                            <td class="px-6 py-3 text-sm">${item.formatted_date}</td>
                            <td class="px-6 py-3 text-sm font-bold">${item.paket_bulan} Bulan</td>
                            <td class="px-6 py-3 text-sm text-gray-500">${item.admin_name}</td>
                            <td class="px-6 py-3 text-sm font-bold text-green-600 text-right">${item.formatted_total}</td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada riwayat.</td></tr>';
                }
                $('#tableHistoryBody').html(html);
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText); // Cek console browser untuk liat error asli
                $('#tableHistoryBody').html('<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Gagal memuat data (SQL Error).</td></tr>');
            }
        });
    });
    $('.close-history').click(function() { $('#modalHistory').addClass('hidden').removeClass('flex'); });

    // --- LOGIC TOP UP (DINAMIS) ---
    let currentExpDate = new Date();
    $('.btn-topup').click(function() {
        let data = $(this).data('json');
        $('#idMemberTopUp').val(data.id);
        $('#topupNama').text(data.nama);
        $('#topupOldExp').text(data.tanggal_expired);
        
        currentExpDate = new Date(data.tanggal_expired);
        let today = new Date();
        if(currentExpDate < today) currentExpDate = today;

        $('.paket-btn').removeClass('border-green-500 bg-green-50');
        $('#inputBulan').val(''); $('#inputHarga').val(''); $('#newExpiredPreview').text('-');
        $('#modalTopUp').removeClass('hidden').addClass('flex');
    });
    $('.close-topup').click(function() { $('#modalTopUp').addClass('hidden').removeClass('flex'); });

    $('.paket-btn').click(function() {
        $('.paket-btn').removeClass('border-green-500 bg-green-50');
        $(this).addClass('border-green-500 bg-green-50');
        
        let bulan = parseInt($(this).data('bulan'));
        let harga = parseInt($(this).data('harga'));
        
        $('#inputBulan').val(bulan);
        $('#inputHarga').val(new Intl.NumberFormat('id-ID').format(harga));
        
        let newDate = new Date(currentExpDate);
        newDate.setMonth(newDate.getMonth() + bulan);
        $('#newExpiredPreview').text(newDate.toLocaleDateString('id-ID'));
    });

    // --- LOGIC LAIN (CRUD & PRINT) SAMA SEPERTI SEBELUMNYA ---
    $('#btnTambah').click(function() { $('#modalTitle').text('Tambah'); $('#formAction').val('tambah'); $('#formMember')[0].reset(); $('#boxStatus').addClass('hidden'); $('#rfidUid').focus(); $('#modalMember').removeClass('hidden').addClass('flex'); });
    $('.close-modal').click(function() { $('#modalMember').addClass('hidden').removeClass('flex'); });
    $('.btn-edit').click(function() { let data = $(this).data('json'); $('#modalTitle').text('Edit'); $('#formAction').val('edit'); $('#idMember').val(data.id); $('#rfidUid').val(data.rfid_uid); $('#namaMember').val(data.nama); $('#nipMember').val(data.nip); $('#platNomor').val(data.plat_nomor); $('#tglExpired').val(data.tanggal_expired); $('#statusMember').val(data.status); $('#boxStatus').removeClass('hidden'); $('#modalMember').removeClass('hidden').addClass('flex'); });
    $('.btn-hapus').click(function() { let id = $(this).data('id'); Swal.fire({title:'Hapus?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33'}).then((r)=>{if(r.isConfirmed){$('<form method="POST"><input type="hidden" name="action" value="hapus"><input type="hidden" name="id_hapus" value="'+id+'"></form>').appendTo('body').submit();}}); });
    
    $('.btn-cetak').click(function() {
        let data = $(this).data('json');
        $('#cardNama').text(data.nama); $('#cardPlat').text(data.plat_nomor);
        $('#cardExpired').text(new Date(data.tanggal_expired).toLocaleDateString('en-GB', {month:'short', year:'numeric'}).toUpperCase());
        $('#cardRfidText').text('UID: '+data.rfid_uid);
        JsBarcode("#barcode", data.rfid_uid, {format:"CODE128", width:1.5, height:30, displayValue:false, background:"transparent"});
        setTimeout(function(){ window.print(); }, 500);
    });
});
</script>