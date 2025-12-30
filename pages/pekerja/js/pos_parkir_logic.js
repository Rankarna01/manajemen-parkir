$(document).ready(function() {
    
    // --- 1. SET FOKUS KE SCANNER (PENTING) ---
    $('#kode_input').focus();
    // Kalau user klik sembarang, kembalikan fokus ke input setelah delay
    $(document).on('click', function(e) {
        if (!$(e.target).closest('input, button, a').length) {
            setTimeout(() => $('#kode_input').focus(), 500);
        }
    });

    // --- 2. LOGIKA DISPENSER TIKET (MASUK) ---
    $('#btnAmbilTiketOtomatis').on('click', function() {
        let btn = $(this);
        let oriHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Proses...');

        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler_pos.php',
            data: { 
                action: 'ambil_tiket_otomatis',
                jenis_kendaraan: TIPE_POS_INI 
            },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    // Notifikasi Sukses
                    Swal.fire({
                        title: 'Silakan Masuk',
                        text: 'Tiket sedang dicetak...',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Buka Palang Masuk (Hardware)
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk');
                    
                    // CETAK TIKET (Buka PDF di Tab Baru)
                    // Scanner nanti akan membaca barcode dari kertas ini
                    let printUrl = '../../cetak_tiket.php?id=' + res.data.transaksi_id;
                    window.open(printUrl, '_blank'); 
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Gagal koneksi server', 'error'); },
            complete: function() { 
                btn.prop('disabled', false).html(oriHtml); 
                $('#kode_input').focus(); // Kembali siap scan
            }
        });
    });

    // --- 3. LOGIKA SCANNER BARCODE (KELUAR) ---
    // Scanner otomatis menekan ENTER -> Memicu event submit form ini
    $('#formCariTiket').on('submit', function(e) {
        e.preventDefault();
        let kode = $('#kode_input').val();
        if (kode === '') return;
        
        let btn = $('#btnCari');
        let icon = $('#iconCari');
        let spin = $('#spinnerCari');
        
        icon.addClass('hidden'); spin.removeClass('hidden'); btn.prop('disabled', true);

        $.ajax({
            type: 'POST', 
            url: '../../api/ajax_handler_pos.php',
            data: { action: 'cari_tiket_atau_plat', kode_input: kode }, 
            dataType: 'json',
            success: function(res) {
                if(res.status=='success') { 
                    // Jika ketemu, tampilkan detail bayar
                    showPaymentState(res.data); 
                } else { 
                    Swal.fire({
                        title: 'Tidak Ditemukan', 
                        text: 'Coba scan ulang atau input manual.', 
                        icon: 'error',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#kode_input').val('').focus(); 
                }
            },
            complete: function() { 
                icon.removeClass('hidden'); spin.addClass('hidden'); btn.prop('disabled', false); 
            }
        });
    });

    // --- 4. PROSES PEMBAYARAN ---
    $('#formPembayaran').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#btnProses');
        let icon = $('#iconProses');
        let spin = $('#spinnerProses');
        let text = $('#textProses');
        
        icon.addClass('hidden'); spin.removeClass('hidden'); text.text('Proses...'); 
        btn.prop('disabled', true); $('#btnBatal').prop('disabled', true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if(res.status=='success') {
                    Swal.fire({title:'Lunas!', text:'Hati-hati di jalan.', icon:'success', timer:2000, showConfirmButton:false});
                    
                    // Buka Palang Keluar
                    panggil_hardware(IP_PALANG_KELUAR, 'Palang Keluar');
                    
                    // Cetak Struk (Opsional)
                    window.open('../../cetak_struk.php?id=' + res.transaksi_id, '_blank');
                    
                    // Reset Tampilan
                    showSearchState(); 
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            complete: function() { 
                icon.removeClass('hidden'); spin.addClass('hidden'); text.text('Bayar & Buka Palang');
                btn.prop('disabled', false); $('#btnBatal').prop('disabled', false);
            }
        });
    });

    // --- 5. LOGIKA ABSENSI & PULANG ---
    if ($('#btnAbsenMasuk').length) {
        let gpsOptions = { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 };
        
        function successGPS(pos) {
            $('#user_lat').val(pos.coords.latitude); $('#user_long').val(pos.coords.longitude);
            $('#status_lokasi').html("<span class='text-green-600'>Lokasi Terkunci!</span>");
            enableBtn("Konfirmasi Kehadiran", "success");
        }
        function errorGPS() {
            $('#user_lat').val('0'); $('#user_long').val('0'); // Fallback
            $('#status_lokasi').html("<span class='text-orange-500'>GPS Gagal. Masuk Manual.</span>");
            enableBtn("Masuk Tanpa Lokasi", "warning");
        }
        function enableBtn(txt, type) {
            let color = (type=='success') ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-500 hover:bg-orange-600';
            $('#btnAbsenMasuk').removeAttr('disabled').removeClass('bg-gray-300 cursor-not-allowed text-gray-500').addClass(color + ' text-white').html(txt);
        }

        if (navigator.geolocation) navigator.geolocation.getCurrentPosition(successGPS, errorGPS, gpsOptions);
        else errorGPS();

        $('#btnAbsenMasuk').click(function() {
            $.post('../../api/ajax_handler_absen.php', {
                action: 'absen_masuk', lat: $('#user_lat').val() || '0', long: $('#user_long').val() || '0', pos_id: ID_POS_INI
            }, function(res) {
                if(res.status=='success') location.reload();
                else Swal.fire('Gagal', res.message, 'error');
            }, 'json').fail(() => location.reload()); // Fail-safe
        });
    }

    $('#btnPulang').click(function() {
        Swal.fire({
            title: 'Selesai Shift?', icon: 'question', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Logout'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../../api/ajax_handler_absen.php', { action: 'absen_keluar', lat: '0', long: '0' }, function() {
                    window.location.href = '../../logout.php';
                }, 'json').fail(() => window.location.href = '../../logout.php');
            }
        });
    });

    // --- HELPER FUNCTIONS ---
    function showSearchState() {
        $('#paymentDetails').slideUp(); $('#formCariTiket').slideDown();
        $('#formCariTiket')[0].reset(); $('#formPembayaran')[0].reset(); 
        $('#kembalian').val(''); $('#detail_biaya').text('Rp 0');
        setTimeout(() => $('#kode_input').focus(), 500);
    }
    
    function showPaymentState(data) {
        $('#detail_plat').text(data.plat_nomor); $('#detail_jenis').text(data.jenis);
        $('#detail_masuk').text(data.waktu_masuk_format); $('#detail_durasi').text(data.durasi_format);
        $('#detail_biaya').text('Rp ' + data.total_biaya_format);
        $('#hidden_transaksi_id').val(data.transaksi_id); $('#hidden_total_biaya').val(data.total_biaya);
        
        $('#formCariTiket').slideUp(); $('#paymentDetails').slideDown(); 
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
        if($('#formKendaraanMasuk').hasClass('hidden')) $('#formKendaraanMasuk').removeClass('hidden');
        else $('#formKendaraanMasuk').addClass('hidden');
    });

    function panggil_hardware(url, namaAlat) {
        if (!url || url.trim() === "") { console.warn(`[IOT] IP ${namaAlat} kosong.`); return; }
        if (!url.startsWith('http')) { url = 'http://' + url; }
        fetch(url, { mode: 'no-cors' }).catch(err => Swal.fire('Warning', `Gagal koneksi alat ${namaAlat}`, 'warning'));
    }
});