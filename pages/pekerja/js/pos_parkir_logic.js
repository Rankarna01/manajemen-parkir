$(document).ready(function() {
    
    // Debugging Config
    console.log("POS ID:", ID_POS_INI, "| Tipe:", TIPE_POS_INI);

    // ============================================================
    // 1. LOGIKA ABSENSI (GEOLOCATION) - PERBAIKAN STUCK
    // ============================================================
    if ($('#btnAbsenMasuk').length) {
        
        let gpsOptions = {
            enableHighAccuracy: true,
            timeout: 5000, 
            maximumAge: 0
        };

        function successGPS(position) {
            let lat = position.coords.latitude;
            let long = position.coords.longitude;
            $('#user_lat').val(lat);
            $('#user_long').val(long);
            $('#status_lokasi').html("<span class='text-green-600 flex items-center justify-center gap-2'><i class='fas fa-check-circle'></i> Lokasi Terkunci!</span>");
            enableButton("Konfirmasi Kehadiran", "success");
        }

        function errorGPS(err) {
            console.warn("GPS Error/Timeout: " + err.message);
            // PAKSA ISI 0 AGAR TIDAK STUCK
            $('#user_lat').val('0');
            $('#user_long').val('0');
            $('#status_lokasi').html("<span class='text-orange-500 font-bold'><i class='fas fa-exclamation-triangle'></i> GPS Gagal. Mode Manual Aktif.</span>");
            enableButton("Masuk Tanpa Lokasi", "warning");
        }

        function enableButton(text, type) {
            let btn = $('#btnAbsenMasuk');
            btn.removeAttr('disabled').removeClass('bg-gray-300 text-gray-500 cursor-not-allowed');
            if(type === "success") {
                btn.addClass('bg-green-600 text-white hover:bg-green-700 transform hover:scale-105 cursor-pointer');
            } else {
                btn.addClass('bg-orange-500 text-white hover:bg-orange-600 transform hover:scale-105 cursor-pointer');
            }
            btn.html(text);
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(successGPS, errorGPS, gpsOptions);
        } else {
            errorGPS({message: "Browser not supported"});
        }

        // KLIK TOMBOL ABSEN
        $('#btnAbsenMasuk').click(function() {
            let btn = $(this);
            // Fallback value jika kosong
            let lat = $('#user_lat').val() || '0';
            let long = $('#user_long').val() || '0';

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                type: 'POST',
                url: '../../api/ajax_handler_absen.php',
                data: { action: 'absen_masuk', lat: lat, long: long, pos_id: ID_POS_INI },
                dataType: 'json',
                success: function(res) {
                    if(res.status == 'success') {
                        Swal.fire({ title: 'Berhasil Masuk!', text: 'Selamat bertugas.', icon: 'success', timer: 1500, showConfirmButton: false }).then(() => { location.reload(); });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                        btn.prop('disabled', false).html('Coba Lagi');
                    }
                },
                error: function() {
                    // Jika error server, reload saja sebagai fail-safe
                    location.reload();
                }
            });
        });
    }

    // ============================================================
    // 2. LOGIKA PULANG
    // ============================================================
    $('#btnPulang').click(function() {
        Swal.fire({
            title: 'Selesai Bertugas?', text: "Sistem akan mencatat waktu pulang & logout.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Pulang'
        }).then((result) => {
            if (result.isConfirmed) {
                // Langsung logout agar cepat
                $.ajax({
                    type: 'POST', url: '../../api/ajax_handler_absen.php',
                    data: { action: 'absen_keluar', lat: '0', long: '0' },
                    dataType: 'json',
                    success: function() { window.location.href = '../../logout.php'; },
                    error: function() { window.location.href = '../../logout.php'; }
                });
            }
        });
    });

    // ============================================================
    // 3. LOGIKA POS PARKIR (MANLESS & SCANNER)
    // ============================================================
    
    // FOKUS INPUT SCANNER (PENTING!)
    $('#kode_input').focus();
    // Jika user klik sembarang tempat, kembalikan fokus ke input setelah 5 detik (optional)
    $(document).on('click', function(e) {
        if (!$(e.target).closest('input, button, a').length) {
            $('#kode_input').focus();
        }
    });

    // A. Tombol Manless (Tiket Masuk)
    $('#btnAmbilTiketOtomatis').on('click', function() {
        let btn = $(this);
        let originalContent = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        btn.prop('disabled', true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: { action: 'ambil_tiket_otomatis', jenis_kendaraan: TIPE_POS_INI },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    Swal.fire({
                        title: 'Tiket Keluar!',
                        html: `<h2 class="text-3xl font-bold text-gray-800">${response.data.kode_barcode}</h2>`,
                        icon: 'success', timer: 2000, showConfirmButton: false
                    });
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk');
                    let printUrl = '../../cetak_tiket.php?id=' + response.data.transaksi_id;
                    $('<iframe>', { src: printUrl, width: 0, height: 0, css: { display: 'none' } }).appendTo('body');
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            complete: function() { btn.html(originalContent); btn.prop('disabled', false); $('#kode_input').focus(); }
        });
    });

    // B. SCANNER BARCODE (Submit Form Cari)
    // Scanner otomatis menekan ENTER, jadi event 'submit' akan terpanggil
    $('#formCariTiket').on('submit', function(e) {
        e.preventDefault();
        let kode = $('#kode_input').val();
        if (kode === '') return;
        
        let btn = $('#btnCari'); let icon = $('#iconCari'); let spin = $('#spinnerCari');
        icon.addClass('hidden'); spin.removeClass('hidden'); btn.prop('disabled', true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: { action: 'cari_tiket_atau_plat', kode_input: kode }, dataType: 'json',
            success: function(res) {
                if(res.status=='success') { 
                    showPaymentState(res.data); 
                } else { 
                    Swal.fire('Tidak Ditemukan', res.message, 'error'); 
                    $('#kode_input').val('').focus(); 
                }
            },
            complete: function() { icon.removeClass('hidden'); spin.addClass('hidden'); btn.prop('disabled', false); }
        });
    });

    // C. Pembayaran
    $('#formPembayaran').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btnProses'); let icon = $('#iconProses'); let spin = $('#spinnerProses'); let text = $('#textProses');
        
        icon.addClass('hidden'); spin.removeClass('hidden'); text.text('Memproses...'); 
        btn.prop('disabled', true); $('#btnBatal').prop('disabled', true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if(res.status=='success') {
                    Swal.fire({title:'Lunas!', text:'Palang Terbuka', icon:'success', timer:2000, showConfirmButton:false});
                    panggil_hardware(IP_PALANG_KELUAR, 'Palang Keluar');
                    window.open('../../cetak_struk.php?id=' + res.transaksi_id, '_blank');
                    showSearchState(); // Reset ke mode scan
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            complete: function() { 
                icon.removeClass('hidden'); spin.addClass('hidden'); text.text('Bayar & Cetak');
                btn.prop('disabled', false); $('#btnBatal').prop('disabled', false);
            }
        });
    });

    // Helper UI
    function showSearchState() {
        $('#paymentDetails').slideUp(); 
        $('#formCariTiket').slideDown();
        $('#formCariTiket')[0].reset(); $('#formPembayaran')[0].reset(); 
        $('#kembalian').val(''); $('#detail_biaya').text('Rp 0');
        setTimeout(() => $('#kode_input').focus(), 500); // Fokus balik ke scanner
    }
    
    function showPaymentState(data) {
        $('#detail_plat').text(data.plat_nomor); $('#detail_jenis').text(data.jenis);
        $('#detail_masuk').text(data.waktu_masuk_format); $('#detail_durasi').text(data.durasi_format);
        $('#detail_biaya').text('Rp ' + data.total_biaya_format);
        $('#hidden_transaksi_id').val(data.transaksi_id); $('#hidden_total_biaya').val(data.total_biaya);
        
        $('#formCariTiket').slideUp(); 
        $('#paymentDetails').slideDown(); 
        setTimeout(() => $('#jumlah_bayar').focus(), 500);
    }
    
    $('#jumlah_bayar').on('input', function() {
        var total = parseInt($('#hidden_total_biaya').val()) || 0;
        var bayar = parseInt($(this).val()) || 0;
        var kembali = bayar - total;
        $('#kembalian').val(kembali < 0 ? 0 : new Intl.NumberFormat('id-ID').format(kembali));
    });
    
    $('#btnBatal').click(function(){ showSearchState(); });
    $('#btnInputManual').click(function() { 
        $('html, body').animate({ scrollTop: $("#formKendaraanMasuk").offset().top - 100 }, 500); 
    });

    // Helper Hardware
    function panggil_hardware(url, namaAlat) {
        if (!url || url.trim() === "") { console.warn(`[IOT] IP ${namaAlat} kosong.`); return; }
        if (!url.startsWith('http')) { url = 'http://' + url; }
        fetch(url, { mode: 'no-cors' }).then(() => {
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            Toast.fire({ icon: 'success', title: `${namaAlat} Terbuka` });
        }).catch(err => Swal.fire('Koneksi Alat Gagal', `Gagal menghubungi ${namaAlat}`, 'warning'));
    }
});