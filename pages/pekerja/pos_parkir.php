<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Pos Parkir";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
}

// Ambil data tarif untuk form manual di cctv_masuk.php
$tarif_options = [];
$result = $db->query("SELECT jenis_kendaraan, tarif_per_jam FROM tarif_parkir");
while ($row = $result->fetch_assoc()) {
    $tarif_options[] = $row;
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

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
        <div class="container mx-auto max-w-7xl">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 bg-white rounded-xl shadow-lg">
                    
                    <div class="p-6 border-b border-gray-200">
                        <form id="formCariTiket">
                            <label for="kode_input" class="block text-sm font-medium text-gray-700 mb-2">Scan Tiket / Masukkan Plat Nomor (Untuk Keluar)</label>
                            <div class="flex">
                                <input type="text" id="kode_input" name="kode_input"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-l-lg text-lg focus:ring-blue-500 focus:border-blue-500 uppercase"
                                       placeholder="Scan tiket atau ketik plat..." required>
                                <button type="submit" id="btnCari" class="px-5 py-3 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700">
                                    <i id="iconCari" class="fas fa-search text-xl"></i>
                                    <i id="spinnerCari" class="fas fa-spinner fa-spin text-xl hidden"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div id="paymentDetails" class="p-6 hidden">
                        <h3 class="text-2xl font-bold text-gray-800 mb-6">Detail Pembayaran</h3>
                        
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4 mb-6">
                            <div>
                                <p class="text-sm text-gray-500">Plat Nomor</p>
                                <p id="detail_plat" class="text-2xl font-bold text-gray-900">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Jenis Kendaraan</p>
                                <p id="detail_jenis" class="text-2xl font-bold text-gray-900">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Waktu Masuk</p>
                                <p id="detail_masuk" class="text-lg font-medium text-gray-700">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Durasi</p>
                                <p id="detail_durasi" class="text-lg font-bold text-blue-600">-</p>
                            </div>
                        </div>

                        <form id="formPembayaran">
                            <input type="hidden" name="action" value="proses_keluar">
                            <input type="hidden" id="hidden_transaksi_id" name="transaksi_id">
                            <input type="hidden" id="hidden_total_biaya" name="total_biaya">

                            <div class="bg-gray-100 p-6 rounded-lg">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-xl font-semibold text-gray-800">Total Biaya:</span>
                                    <span id="detail_biaya" class="text-4xl font-extrabold text-blue-600">Rp 0</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="jumlah_bayar" class="block text-sm font-medium text-gray-700">Jumlah Bayar (Rp)</label>
                                        <input type="number" id="jumlah_bayar" name="jumlah_bayar"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-lg" placeholder="0">
                                    </div>
                                    <div>
                                        <label for="kembalian" class="block text-sm font-medium text-gray-700">Kembalian (Rp)</label>
                                        <input type="text" id="kembalian" name="kembalian"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-lg bg-gray-200" readonly placeholder="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex gap-4 mt-6">
                                <button type="button" id="btnBatal" class="w-1/3 px-6 py-3 bg-gray-300 text-gray-800 rounded-lg font-semibold hover:bg-gray-400">
                                    Batal
                                </button>
                                <button type="submit" id="btnProses" class="w-2/3 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 text-lg flex items-center justify-center">
                                    <i id="iconProses" class="fas fa-check-circle mr-2"></i>
                                    <span id="textProses">Proses Pembayaran & Cetak Struk</span>
                                    <i id="spinnerProses" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="p-6 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                        <h4 class="text-sm font-medium text-gray-600 mb-3">Aksi Darurat</h4>
                        <div class="flex flex-wrap gap-3">
                            <button id="btnInputManual" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600">
                                <i class="fas fa-plus-circle mr-1"></i> Input Manual (Tamu/VIP)
                            </button>
                        </div>
                    </div>

                </div>

                <div class="lg:col-span-1">
                    <div class="space-y-6">
                        
                        <?php require_once 'cctv/cctv_masuk.php'; ?>
                        
                        <?php require_once 'cctv/cctv_keluar.php'; ?>

                        <div class="bg-white p-6 rounded-xl shadow-lg">
                            <h4 class="font-semibold text-gray-800 mb-3">Status Sistem</h4>
                            <div class="flex items-center text-green-600">
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                                <span class="font-medium">Semua Sistem Online</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Palang parkir terhubung dan siap menerima perintah.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<script>
$(document).ready(function() {
    
    // ============================================================
    // ⚙️ KONFIGURASI ALAMAT IP PERANGKAT KERAS (HARDWARE)
    // ============================================================
    // Isi alamat IP sesuai dengan settingan Mikrokontroller Anda
    // ============================================================
    
    // 1. IP PALANG PINTU MASUK (Untuk membuka gate saat ambil tiket)
    const IP_PALANG_MASUK = "http://192.168.1.105/open"; 

    // 2. IP PALANG PINTU KELUAR (Untuk membuka gate saat bayar)
    const IP_PALANG_KELUAR = "http://192.168.1.106/open"; 

    // 3. IP PRINTER TIKET (Opsional, jika printer pakai Network/ESP32)
    // Jika printer USB langsung ke PC, tidak perlu ini (handle via window.print)
    const IP_PRINTER_TIKET = "http://192.168.1.200/print"; 

    // ============================================================


    // Fokus input
    $('#kode_input').focus();

    // --- FITUR BARU: TOMBOL AMBIL TIKET OTOMATIS (DISPENSER) ---
    $('#btnAmbilTiketOtomatis').on('click', function() {
        // Efek loading tombol
        let btn = $(this);
        let originalContent = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        btn.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler_pos.php',
            data: { action: 'ambil_tiket_otomatis' },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    
                    // 1. Tampilkan Notifikasi Sukses
                    Swal.fire({
                        title: 'Tiket Keluar!',
                        html: '<h2 class="text-2xl font-bold">'+response.data.kode_barcode+'</h2><p>Silakan masuk.</p>',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // 2. PERINTAH KE HARDWARE (Simultan)
                    
                    // A. Buka Palang Masuk
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk');

                    // B. Cetak Tiket (Membuka PDF di iframe tersembunyi agar langsung print)
                    let printUrl = '../../cetak_tiket.php?id=' + response.data.transaksi_id;
                    let iframe = $('<iframe>', { src: printUrl, width: 0, height: 0, css: { display: 'none' } }).appendTo('body');
                    
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Koneksi server terputus', 'error');
            },
            complete: function() {
                btn.html(originalContent);
                btn.prop('disabled', false);
            }
        });
    });


    // --- LOGIKA LAMA (FORM MANUAL MASUK) ---
    $('#formKendaraanMasuk').on('submit', function(e) {
        e.preventDefault();
        
        // Tampilkan loading manual
        $('#btnSubmitManual').prop('disabled', true).text('Menyimpan...');
        
        let formData = $(this).serialize();
        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php', data: formData, dataType: 'json',
            success: function(res) {
                if(res.status == 'success') {
                    Swal.fire('Berhasil', 'Data Manual Disimpan', 'success');
                    
                    // BUKA PALANG MASUK (Manual juga buka palang)
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk'); 
                    
                    window.open('../../cetak_tiket.php?id=' + res.data.transaksi_id, '_blank');
                    $('#formKendaraanMasuk')[0].reset();
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            complete: function() {
                $('#btnSubmitManual').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Simpan Manual');
            }
        });
    });


    // --- LOGIKA LAMA (KELUAR & BAYAR) ---
    $('#formCariTiket').on('submit', function(e) {
        e.preventDefault();
        let kode = $('#kode_input').val();
        if (kode === '') return;
        showLoadingCari(true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: { action: 'cari_tiket_atau_plat', kode_input: kode }, dataType: 'json',
            success: function(res) {
                if(res.status=='success') showPaymentState(res.data);
                else Swal.fire('Gagal', res.message, 'error');
            },
            complete: function() { showLoadingCari(false); }
        });
    });

    $('#formPembayaran').on('submit', function(e) {
        e.preventDefault();
        showLoadingProses(true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if(res.status=='success') {
                    Swal.fire({title:'Lunas!', text:'Palang Terbuka', icon:'success', timer:2000, showConfirmButton:false});
                    
                    // 1. BUKA PALANG KELUAR (Panggil IP)
                    panggil_hardware(IP_PALANG_KELUAR, 'Palang Keluar');

                    // 2. Cetak Struk
                    window.open('../../cetak_struk.php?id=' + res.transaksi_id, '_blank');
                    
                    showSearchState();
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            complete: function() { showLoadingProses(false); }
        });
    });

    // --- HELPER FUNCTIONS ---
    
    function showSearchState() {
        $('#paymentDetails').addClass('hidden');
        $('#formCariTiket').removeClass('hidden');
        $('#formCariTiket')[0].reset();
        $('#formPembayaran')[0].reset();
        $('#kembalian').val('');
        $('#kode_input').focus();
    }
    
    function showPaymentState(data) {
        $('#detail_plat').text(data.plat_nomor);
        $('#detail_jenis').text(data.jenis);
        $('#detail_masuk').text(data.waktu_masuk_format);
        $('#detail_durasi').text(data.durasi_format);
        $('#detail_biaya').text('Rp ' + data.total_biaya_format);
        $('#hidden_transaksi_id').val(data.transaksi_id);
        $('#hidden_total_biaya').val(data.total_biaya);
        $('#formCariTiket').addClass('hidden');
        $('#paymentDetails').removeClass('hidden');
        $('#jumlah_bayar').focus();
    }
    
    function showLoadingCari(isLoading) {
        if (isLoading) {
            $('#iconCari').addClass('hidden');
            $('#spinnerCari').removeClass('hidden');
            $('#btnCari').prop('disabled', true);
        } else {
            $('#iconCari').removeClass('hidden');
            $('#spinnerCari').addClass('hidden');
            $('#btnCari').prop('disabled', false);
        }
    }
    
    function showLoadingProses(isLoading) {
        if (isLoading) {
            $('#iconProses').addClass('hidden');
            $('#spinnerProses').removeClass('hidden');
            $('#textProses').text('Memproses...');
            $('#btnProses').prop('disabled', true);
            $('#btnBatal').prop('disabled', true);
        } else {
            $('#iconProses').removeClass('hidden');
            $('#spinnerProses').addClass('hidden');
            $('#textProses').text('Proses Pembayaran & Cetak Struk');
            $('#btnProses').prop('disabled', false);
            $('#btnBatal').prop('disabled', false);
        }
    }
    
    $('#jumlah_bayar').on('input', function() {
        var total = parseInt($('#hidden_total_biaya').val()) || 0;
        var bayar = parseInt($(this).val()) || 0;
        var kembali = bayar - total;
        $('#kembalian').val(kembali < 0 ? 0 : new Intl.NumberFormat('id-ID').format(kembali));
    });
    
    $('#btnBatal').click(function(){ showSearchState(); });
    
    $('#btnInputManual').on('click', function() {
        $('html, body').animate({ scrollTop: $("#formKendaraanMasuk").offset().top - 100 }, 500);
    });


    // ============================================================
    // 🔌 FUNGSI PENGHUBUNG ALAT (HELPER)
    // ============================================================
    function panggil_hardware(url, namaAlat) {
        console.log(`[HARDWARE] Mengirim sinyal ke ${namaAlat}: ${url}`);
        
        // Menggunakan 'no-cors' agar browser tidak memblokir IP lokal yang berbeda origin
        fetch(url, { mode: 'no-cors' })
            .then(() => {
                console.log(`[HARDWARE] Sinyal ke ${namaAlat} TERKIRIM.`);
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
                });
                Toast.fire({ icon: 'success', title: `${namaAlat} Terbuka` });
            })
            .catch(err => {
                console.error(`[HARDWARE] Gagal menghubungi ${namaAlat}:`, err);
                Swal.fire('Koneksi Alat Gagal', `Tidak dapat menghubungi IP ${namaAlat}. Cek kabel LAN/Wifi!`, 'warning');
            });
    }

});
</script>