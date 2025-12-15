<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Pos Parkir (Motor)";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
}

// Tidak perlu query tarif lagi karena ini khusus motor
$db->close();

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>

<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">
    
    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
        <div class="container mx-auto max-w-7xl">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 flex flex-col gap-6">
                    
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden relative">
                        <div class="h-2 bg-yellow-500"></div>

                        <div class="p-8 border-b border-gray-100">
                            <form id="formCariTiket">
                                <label for="kode_input" class="block text-sm font-bold text-gray-700 mb-3 uppercase tracking-wide">
                                    <i class="fas fa-barcode mr-2"></i> Scan Tiket / Input Plat Nomor
                                </label>
                                <div class="flex shadow-sm rounded-xl overflow-hidden">
                                    <input type="text" id="kode_input" name="kode_input"
                                           class="w-full px-6 py-4 border-2 border-gray-200 border-r-0 rounded-l-xl text-2xl font-bold text-gray-800 focus:ring-0 focus:border-yellow-500 placeholder-gray-300 uppercase transition"
                                           placeholder="SCAN TIKET MOTOR..." required autocomplete="off">
                                    <button type="submit" id="btnCari" class="px-8 py-4 bg-yellow-500 text-white font-bold hover:bg-yellow-600 transition duration-200 border-2 border-yellow-500">
                                        <i id="iconCari" class="fas fa-search text-2xl"></i>
                                        <i id="spinnerCari" class="fas fa-spinner fa-spin text-2xl hidden"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-400 mt-2 ml-1">Pastikan kursor aktif di kolom input sebelum melakukan scan.</p>
                            </form>
                        </div>
                        
                        <div id="paymentDetails" class="p-8 hidden">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-2xl font-bold text-gray-800">Detail Pembayaran</h3>
                                <div class="px-3 py-1 rounded bg-yellow-100 text-yellow-800 text-xs font-bold uppercase tracking-wider">
                                    Transaksi Keluar
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Plat Nomor</p>
                                    <p id="detail_plat" class="text-xl font-extrabold text-gray-800 mt-1">-</p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Jenis</p>
                                    <p id="detail_jenis" class="text-xl font-bold text-gray-800 mt-1 capitalize">-</p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Masuk</p>
                                    <p id="detail_masuk" class="text-base font-medium text-gray-700 mt-1">-</p>
                                </div>
                                <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                                    <p class="text-xs text-blue-600 uppercase font-semibold">Durasi</p>
                                    <p id="detail_durasi" class="text-lg font-bold text-blue-700 mt-1">-</p>
                                </div>
                            </div>

                            <form id="formPembayaran">
                                <input type="hidden" name="action" value="proses_keluar">
                                <input type="hidden" id="hidden_transaksi_id" name="transaksi_id">
                                <input type="hidden" id="hidden_total_biaya" name="total_biaya">

                                <div class="bg-gray-800 rounded-2xl p-6 text-white shadow-lg mb-6 relative overflow-hidden">
                                    <div class="flex justify-between items-end relative z-10">
                                        <div>
                                            <span class="block text-gray-400 text-sm font-medium mb-1">Total Tagihan</span>
                                            <span id="detail_biaya" class="text-5xl font-extrabold text-yellow-400 tracking-tight">Rp 0</span>
                                        </div>
                                        <i class="fas fa-wallet text-4xl text-gray-600 opacity-50"></i>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <label for="jumlah_bayar" class="block text-sm font-bold text-gray-700 mb-2">Uang Diterima (Rp)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">Rp</span>
                                            <input type="number" id="jumlah_bayar" name="jumlah_bayar"
                                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl text-xl font-bold focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition outline-none" 
                                                   placeholder="0" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="kembalian" class="block text-sm font-bold text-gray-700 mb-2">Kembalian (Rp)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 font-bold">Rp</span>
                                            <input type="text" id="kembalian" name="kembalian"
                                                   class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 bg-gray-100 rounded-xl text-xl font-bold text-gray-600 outline-none cursor-not-allowed" 
                                                   readonly placeholder="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex gap-4">
                                    <button type="button" id="btnBatal" class="w-1/3 px-6 py-4 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition duration-200">
                                        Batal
                                    </button>
                                    <button type="submit" id="btnProses" class="w-2/3 px-6 py-4 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 text-lg shadow-lg hover:shadow-xl transition duration-200 flex items-center justify-center transform hover:-translate-y-0.5">
                                        <i id="iconProses" class="fas fa-print mr-2"></i>
                                        <span id="textProses">Bayar & Cetak Struk</span>
                                        <i id="spinnerProses" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                            <div class="text-xs text-gray-400 font-medium">
                                <i class="fas fa-info-circle mr-1"></i> Mode: POS MOTOR (Tekan F5 jika macet)
                            </div>
                            <button id="btnInputManual" class="px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 hover:text-red-700 transition shadow-sm">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Input Manual (Darurat)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="space-y-6">
                        
                        <div class="bg-black rounded-2xl shadow-lg overflow-hidden border-2 border-gray-800 relative group">
                            <div class="absolute top-2 left-2 z-10 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded animate-pulse font-bold">LIVE</div>
                            <?php require_once 'cctv/cctv_masuk.php'; ?>
                            <div class="bg-gray-900 px-4 py-2 text-xs text-gray-400 flex justify-between">
                                <span><i class="fas fa-video mr-1"></i> CAM-01 (Masuk)</span>
                                <span class="text-green-500"><i class="fas fa-wifi"></i></span>
                            </div>
                        </div>
                        
                        <div class="bg-black rounded-2xl shadow-lg overflow-hidden border-2 border-gray-800 relative group">
                            <div class="absolute top-2 left-2 z-10 bg-red-600 text-white text-[10px] px-2 py-0.5 rounded animate-pulse font-bold">LIVE</div>
                            <?php require_once 'cctv/cctv_keluar.php'; ?>
                            <div class="bg-gray-900 px-4 py-2 text-xs text-gray-400 flex justify-between">
                                <span><i class="fas fa-video mr-1"></i> CAM-02 (Keluar)</span>
                                <span class="text-green-500"><i class="fas fa-wifi"></i></span>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-100">
                            <h4 class="font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-server mr-2"></i> Status Perangkat
                            </h4>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl border border-green-100">
                                    <div class="flex items-center">
                                        <span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                                        <span class="text-sm font-bold text-gray-700">Palang Pintu</span>
                                    </div>
                                    <span class="text-xs font-bold text-green-600">ONLINE</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl border border-green-100">
                                    <div class="flex items-center">
                                        <span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                                        <span class="text-sm font-bold text-gray-700">Printer Thermal</span>
                                    </div>
                                    <span class="text-xs font-bold text-green-600">READY</span>
                                </div>
                            </div>
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
    const IP_PALANG_MASUK = "http://192.168.1.105/open"; 
    const IP_PALANG_KELUAR = "http://192.168.1.106/open"; 
    const IP_PRINTER_TIKET = "http://192.168.1.200/print"; 
    // ============================================================

    // Fokus input
    $('#kode_input').focus();

    // --- FITUR BARU: TOMBOL AMBIL TIKET OTOMATIS (DISPENSER) ---
    $('#btnAmbilTiketOtomatis').on('click', function() {
        let btn = $(this);
        let originalContent = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        btn.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler_pos.php',
            data: { 
                action: 'ambil_tiket_otomatis',
                jenis_kendaraan: 'motor' // <--- KHUSUS MOTOR
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    Swal.fire({
                        title: 'Tiket Motor Keluar!',
                        html: '<h2 class="text-2xl font-bold">'+response.data.kode_barcode+'</h2><p>Silakan masuk.</p>',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk');
                    
                    // Cetak Tiket (Hidden Iframe)
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
        $('#btnSubmitManual').prop('disabled', true).text('Menyimpan...');
        let formData = $(this).serialize();
        
        // Tambahkan jenis kendaraan manual jika tidak ada di form
        if (formData.indexOf('jenis_kendaraan') === -1) {
             formData += '&jenis_kendaraan=motor';
        }

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php', data: formData, dataType: 'json',
            success: function(res) {
                if(res.status == 'success') {
                    Swal.fire('Berhasil', 'Motor Masuk Tercatat', 'success');
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
                    panggil_hardware(IP_PALANG_KELUAR, 'Palang Keluar');
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
            $('#textProses').text('Bayar & Cetak Struk');
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
        fetch(url, { mode: 'no-cors' })
            .then(() => {
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
                });
                Toast.fire({ icon: 'success', title: `${namaAlat} Terbuka` });
            })
            .catch(err => {
                Swal.fire('Koneksi Alat Gagal', `Tidak dapat menghubungi IP ${namaAlat}.`, 'warning');
            });
    }
});
</script>