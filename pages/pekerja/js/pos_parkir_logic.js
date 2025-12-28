$(document).ready(function() {
    
    // Debugging Config (Opsional)
    console.log("POS ID:", ID_POS_INI, "| Tipe:", TIPE_POS_INI);

    // ============================================================
    // 1. LOGIKA ABSENSI (GEOLOCATION)
    // ============================================================
    
    // Hanya jalankan jika modal absen ada di layar
    if ($('#btnAbsenMasuk').length) {
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                // Sukses Deteksi
                function(position) {
                    let lat = position.coords.latitude;
                    let long = position.coords.longitude;

                    // Isi input hidden
                    $('#user_lat').val(lat);
                    $('#user_long').val(long);
                    
                    // Update UI Status
                    $('#status_lokasi').html(`
                        <span class='text-green-600 flex items-center justify-center gap-2'>
                            <i class='fas fa-map-marker-alt'></i> Lokasi Terkunci: ${lat.toFixed(4)}, ${long.toFixed(4)}
                        </span>
                    `);
                    
                    // Aktifkan Tombol
                    $('#btnAbsenMasuk')
                        .removeAttr('disabled')
                        .removeClass('bg-gray-300 text-gray-500 cursor-not-allowed')
                        .addClass('bg-green-600 text-white hover:bg-green-700 transform hover:scale-105 cursor-pointer');
                }, 
                // Gagal Deteksi
                function(error) {
                    let msg = "Gagal deteksi lokasi.";
                    if(error.code == 1) msg = "Izin GPS ditolak. Mohon aktifkan.";
                    $('#status_lokasi').html(`<span class='text-red-500 font-bold'><i class='fas fa-exclamation-triangle'></i> ${msg}</span>`);
                }
            );
        } else {
            $('#status_lokasi').text("Browser Anda tidak mendukung Geolocation.");
        }

        // Klik Tombol Absen
        $('#btnAbsenMasuk').click(function() {
            let btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                type: 'POST',
                url: '../../api/ajax_handler_absen.php',
                data: { 
                    action: 'absen_masuk', 
                    lat: $('#user_lat').val(), 
                    long: $('#user_long').val(), 
                    pos_id: ID_POS_INI 
                },
                dataType: 'json',
                success: function(res) {
                    if(res.status == 'success') {
                        Swal.fire({
                            title: 'Berhasil Masuk!',
                            text: 'Selamat bertugas.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Reload untuk hilangkan modal
                        });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                        btn.prop('disabled', false).text('Coba Lagi');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    btn.prop('disabled', false).text('Coba Lagi');
                }
            });
        });
    }

    // ============================================================
    // 2. LOGIKA PULANG / CHECK-OUT
    // ============================================================
    $('#btnPulang').click(function() {
        Swal.fire({
            title: 'Selesai Bertugas?',
            text: "Sistem akan mencatat waktu pulang & lokasi Anda.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Pulang & Logout'
        }).then((result) => {
            if (result.isConfirmed) {
                // Ambil Lokasi Pulang
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        prosesPulang(position.coords.latitude, position.coords.longitude);
                    }, function() {
                        // Jika gagal GPS pulang, tetap izinkan pulang (opsional, set 0,0)
                        prosesPulang(0, 0); 
                    });
                } else {
                    prosesPulang(0, 0);
                }
            }
        });
    });

    function prosesPulang(lat, long) {
        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler_absen.php',
            data: { action: 'absen_keluar', lat: lat, long: long },
            dataType: 'json',
            success: function(res) {
                window.location.href = '../../logout.php';
            }
        });
    }

    // ============================================================
    // 3. LOGIKA POS PARKIR (SISTEM UTAMA)
    // ============================================================
    
    // Fokus otomatis ke input scan
    $('#kode_input').focus();

    // A. Tombol Ambil Tiket Otomatis (Manless)
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
                jenis_kendaraan: TIPE_POS_INI // Dinamis dari Database
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    Swal.fire({
                        title: 'Tiket Keluar!',
                        html: `<div class="text-center">
                                <h2 class="text-3xl font-bold text-gray-800">${response.data.kode_barcode}</h2>
                                <p class="text-gray-500 mt-2">Plat Sementara: ${response.data.plat_nomor}</p>
                               </div>`,
                        icon: 'success',
                        timer: 2500,
                        showConfirmButton: false
                    });
                    
                    // Trigger Hardware
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk');
                    
                    // Cetak Tiket (Hidden)
                    let printUrl = '../../cetak_tiket.php?id=' + response.data.transaksi_id;
                    let iframe = $('<iframe>', { src: printUrl, width: 0, height: 0, css: { display: 'none' } }).appendTo('body');
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Koneksi server terputus', 'error'); },
            complete: function() {
                btn.html(originalContent);
                btn.prop('disabled', false);
            }
        });
    });

    // B. Form Manual Input
    $('#formKendaraanMasuk').on('submit', function(e) {
        e.preventDefault();
        $('#btnSubmitManual').prop('disabled', true).text('Menyimpan...');
        
        let formData = $(this).serialize();
        if (formData.indexOf('jenis_kendaraan') === -1) { formData += '&jenis_kendaraan=' + TIPE_POS_INI; }

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php', data: formData, dataType: 'json',
            success: function(res) {
                if(res.status == 'success') {
                    Swal.fire('Berhasil', 'Kendaraan Masuk Tercatat', 'success');
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk'); 
                    window.open('../../cetak_tiket.php?id=' + res.data.transaksi_id, '_blank');
                    $('#formKendaraanMasuk')[0].reset();
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            complete: function() {
                $('#btnSubmitManual').prop('disabled', false).html('Simpan Manual');
            }
        });
    });

    // C. Cari Tiket (Keluar)
    $('#formCariTiket').on('submit', function(e) {
        e.preventDefault();
        let kode = $('#kode_input').val();
        if (kode === '') return;
        
        // Animasi Loading Tombol Cari
        let btn = $('#btnCari');
        let icon = $('#iconCari');
        let spin = $('#spinnerCari');
        
        icon.addClass('hidden'); spin.removeClass('hidden'); btn.prop('disabled', true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: { action: 'cari_tiket_atau_plat', kode_input: kode }, dataType: 'json',
            success: function(res) {
                if(res.status=='success') {
                    showPaymentState(res.data);
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    $('#kode_input').val('').focus();
                }
            },
            complete: function() { 
                icon.removeClass('hidden'); spin.addClass('hidden'); btn.prop('disabled', false); 
            }
        });
    });

    // D. Proses Pembayaran
    $('#formPembayaran').on('submit', function(e) {
        e.preventDefault();
        
        // Animasi Loading Bayar
        let btn = $('#btnProses');
        let icon = $('#iconProses');
        let spin = $('#spinnerProses');
        let text = $('#textProses');
        
        icon.addClass('hidden'); spin.removeClass('hidden'); text.text('Memproses...'); 
        btn.prop('disabled', true); $('#btnBatal').prop('disabled', true);

        $.ajax({
            type: 'POST', url: '../../api/ajax_handler_pos.php',
            data: $(this).serialize(), dataType: 'json',
            success: function(res) {
                if(res.status=='success') {
                    Swal.fire({
                        title: 'Pembayaran Sukses!',
                        text: 'Palang pintu terbuka.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    panggil_hardware(IP_PALANG_KELUAR, 'Palang Keluar');
                    window.open('../../cetak_struk.php?id=' + res.transaksi_id, '_blank');
                    showSearchState();
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

    // --- HELPER FUNCTIONS UI ---
    function showSearchState() {
        $('#paymentDetails').slideUp(); 
        $('#formCariTiket').slideDown();
        $('#formCariTiket')[0].reset(); $('#formPembayaran')[0].reset(); 
        $('#kembalian').val(''); $('#detail_biaya').text('Rp 0');
        setTimeout(() => $('#kode_input').focus(), 500);
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
    
    // Hitung Kembalian
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

    // --- HELPER HARDWARE (IOT) ---
    function panggil_hardware(url, namaAlat) {
        if (!url || url.trim() === "") { 
            console.warn(`[IOT] IP ${namaAlat} kosong. Cek Konfigurasi.`); return; 
        }
        if (!url.startsWith('http')) { url = 'http://' + url; }

        console.log(`[IOT] Sending Signal to ${namaAlat}: ${url}`);
        
        fetch(url, { mode: 'no-cors' })
            .then(() => {
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                Toast.fire({ icon: 'success', title: `${namaAlat} Terbuka` });
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Koneksi Alat Gagal', `Gagal menghubungi ${namaAlat}. Pastikan kabel LAN terpasang.`, 'warning');
            });
    }

});