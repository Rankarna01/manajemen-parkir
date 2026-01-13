//=========================================
// POS PARKIR LOGIC - COMPLETE VERSION
// Menggabungkan semua fitur: Input Manual, Member, QRIS, GPS
//=========================================

$(document).ready(function() {
    
    // Debugging Config
    console.log("POS ID:", ID_POS_INI, "| Tipe:", TIPE_POS_INI);
    console.log("IP Palang Masuk:", IP_PALANG_MASUK);
    console.log("IP Palang Keluar:", IP_PALANG_KELUAR);

    // Variabel Timer Polling QRIS
    let qrisInterval;

    //=====================================
    // 1. SIMULASI TAP RFID
    //=====================================
    $('#btnSimulasiRFID').click(function() {
        Swal.fire({
            title: 'Simulasi Tap RFID',
            input: 'text',
            inputLabel: 'Masukkan Nomor Kartu (UID)',
            inputPlaceholder: 'Contoh: 12345',
            showCancelButton: true,
            confirmButtonText: 'Tap Kartu!',
            confirmButtonColor: '#10071fff',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $('#kode_input').val(result.value);
                $('#formCariTiket').submit();
            }
        });
    });

    //=====================================
    // 2. AUTO FOCUS KE INPUT SCAN
    //=====================================
    $('#kode_input').focus();
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('input, button, a, select, textarea').length) {
            setTimeout(() => $('#kode_input').focus(), 500);
        }
    });

    //=====================================
    // 3. TOMBOL INPUT MANUAL (TOGGLE)
    //=====================================
    $('#btnInputManual').click(function() {
        $('#formKendaraanMasuk').toggleClass('hidden');
        $(this).toggleClass('bg-red-600 text-white border-red-600');
        
        if (!$('#formKendaraanMasuk').hasClass('hidden')) {
            $('input[name="plat_nomor"]').focus();
        } else {
            $('#kode_input').focus();
        }
    });

    //=====================================
    // 4. FORM KENDARAAN MASUK MANUAL
    //=====================================
    $('#formKendaraanMasuk').on('submit', function(e) {
        e.preventDefault();
        
        const platNomor = $('input[name="plat_nomor"]').val().trim().toUpperCase();
        
        if (!platNomor) {
            Swal.fire('Perhatian!', 'Plat nomor wajib diisi.', 'warning');
            return;
        }

        const $btnSubmit = $('#btnSubmitManual');
        const originalText = $btnSubmit.html();
        $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler_pos.php',
            data: {
                action: 'kendaraan_masuk_manual',
                plat_nomor: platNomor,
                jenis_kendaraan: TIPE_POS_INI
            },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dicatat!',
                        html: `<div class="text-left bg-gray-50 p-4 rounded-lg border">
                            <p class="mb-2"><strong class="text-primary">Plat Nomor:</strong> <span class="font-mono">${res.data.plat_nomor}</span></p>
                            <p class="mb-2"><strong class="text-primary">Kode Tiket:</strong> <span class="font-mono text-sm">${res.data.kode_barcode}</span></p>
                            <p class="mb-2"><strong class="text-primary">Jenis:</strong> ${res.data.jenis}</p>
                            <p class="text-xs text-gray-500 mt-3 border-t pt-2">${res.data.waktu_masuk}</p>
                        </div>`,
                        confirmButtonText: 'OK',
                        timer: 4000
                    }).then(() => {
                        // Reset form
                        $('#formKendaraanMasuk')[0].reset();
                        $('#formKendaraanMasuk').addClass('hidden');
                        $('#btnInputManual').removeClass('bg-red-600 text-white border-red-600');
                        
                        // Print tiket
                        let printUrl = '../../cetak_tiket.php?id=' + res.data.transaksi_id;
                        window.open(printUrl, '_blank');
                        
                        // Buka palang
                        panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk');
                        
                        // Focus kembali
                        $('#kode_input').focus();
                    });
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            },
            error: function(xhr) {
                console.error('Ajax Error:', xhr);
                Swal.fire('Error!', 'Terjadi kesalahan koneksi ke server.', 'error');
            },
            complete: function() {
                $btnSubmit.prop('disabled', false).html(originalText);
            }
        });
    });

    //=====================================
    // 5. AMBIL TIKET OTOMATIS (MANLESS)
    //=====================================
    $('#btnAmbilTiketOtomatis').on('click', function() {
        let btn = $(this);
        let oriHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

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
                    Swal.fire({
                        title: 'Tiket Keluar!',
                        html: `<div class="text-center">
                            <div class="text-3xl font-bold text-primary mb-2">${res.data.kode_barcode}</div>
                            <p class="text-sm text-gray-600">Plat Sementara: ${res.data.plat_nomor}</p>
                            <p class="text-xs text-gray-400 mt-2">${res.data.waktu_masuk}</p>
                        </div>`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Buka palang
                    panggil_hardware(IP_PALANG_MASUK, 'Palang Masuk');
                    
                    // Print tiket
                    let printUrl = '../../cetak_tiket.php?id=' + res.data.transaksi_id;
                    window.open(printUrl, '_blank');
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() { 
                Swal.fire('Error', 'Gagal koneksi ke server', 'error'); 
            },
            complete: function() { 
                btn.prop('disabled', false).html(oriHtml); 
                $('#kode_input').focus(); 
            }
        });
    });

    //=====================================
    // 6. SCANNER BARCODE / RFID (KELUAR)
    //=====================================
    $('#formCariTiket').on('submit', function(e) {
        e.preventDefault();
        
        let kode = $('#kode_input').val().trim();
        if (kode === '') {
            Swal.fire('Perhatian!', 'Silakan scan tiket atau masukkan kode.', 'warning');
            return;
        }
        
        let btn = $('#btnCari');
        let icon = $('#iconCari');
        let spin = $('#spinnerCari');
        
        icon.addClass('hidden'); 
        spin.removeClass('hidden'); 
        btn.prop('disabled', true);

        $.ajax({
            type: 'POST', 
            url: '../../api/ajax_handler_pos.php',
            data: { 
                action: 'cari_tiket_atau_plat', 
                kode_input: kode 
            }, 
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') { 
                    showPaymentState(res.data);
                    $('#kode_input').val('');
                } else { 
                    Swal.fire({
                        title: 'Tidak Ditemukan', 
                        text: res.message, 
                        icon: 'error',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#kode_input').val('').focus(); 
                }
            },
            error: function(xhr) {
                console.error('Ajax Error:', xhr);
                Swal.fire('Error!', 'Gagal mencari data.', 'error');
            },
            complete: function() { 
                icon.removeClass('hidden'); 
                spin.addClass('hidden'); 
                btn.prop('disabled', false); 
            }
        });
    });

    //=====================================
    // 7. HITUNG KEMBALIAN REALTIME
    //=====================================
    $('#jumlah_bayar').on('input', function() {
        const total = parseInt($('#hidden_total_biaya').val()) || 0;
        const bayar = parseInt($(this).val()) || 0;
        const kembali = bayar - total;
        
        if (kembali >= 0) {
            $('#kembalian').val('Rp ' + new Intl.NumberFormat('id-ID').format(kembali));
            $('#kembalian').removeClass('text-red-500').addClass('text-green-600');
        } else {
            $('#kembalian').val('Kurang: Rp ' + new Intl.NumberFormat('id-ID').format(Math.abs(kembali)));
            $('#kembalian').removeClass('text-green-600').addClass('text-red-500');
        }
    });

    //=====================================
    // 8. PROSES PEMBAYARAN (TUNAI)
    //=====================================
    $('#formPembayaran').on('submit', function(e) {
        e.preventDefault();
        
        const totalBiaya = parseFloat($('#hidden_total_biaya').val()) || 0;
        const jumlahBayar = parseFloat($('#jumlah_bayar').val()) || 0;
        const isMember = !$('#badge_member').hasClass('hidden');
        
        // Validasi pembayaran (kecuali member)
        if (!isMember && jumlahBayar < totalBiaya) {
            Swal.fire('Perhatian!', 'Jumlah bayar kurang dari total biaya.', 'warning');
            return;
        }
        
        let btn = $('#btnProses');
        let icon = $('#iconProses');
        let spin = $('#spinnerProses');
        let text = $('#textProses');
        
        icon.addClass('hidden'); 
        spin.removeClass('hidden'); 
        text.text('Memproses...'); 
        btn.prop('disabled', true); 
        $('#btnBatal').prop('disabled', true);

        $.ajax({
            type: 'POST', 
            url: '../../api/ajax_handler_pos.php',
            data: $(this).serialize(), 
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    let titleText = isMember ? 'Member Valid!' : 'Pembayaran Lunas!';
                    let messageText = isMember ? 'Selamat jalan!' : 'Palang akan terbuka...';
                    
                    Swal.fire({
                        title: titleText, 
                        text: messageText, 
                        icon: 'success', 
                        timer: 2000, 
                        showConfirmButton: false
                    });
                    
                    // Buka palang keluar
                    panggil_hardware(IP_PALANG_KELUAR, 'Palang Keluar');
                    
                    // Cetak Struk (Hanya untuk Non-Member)
                    if (!isMember) {
                        window.open('../../cetak_struk.php?id=' + res.transaksi_id, '_blank');
                    }
                    
                    // Reset ke state awal
                    setTimeout(() => showSearchState(), 2000);
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function(xhr) {
                console.error('Ajax Error:', xhr);
                Swal.fire('Error!', 'Gagal memproses pembayaran.', 'error');
            },
            complete: function() { 
                icon.removeClass('hidden'); 
                spin.addClass('hidden'); 
                text.text('Proses & Buka');
                btn.prop('disabled', false); 
                $('#btnBatal').prop('disabled', false);
            }
        });
    });

    //=====================================
    // 9. PEMBAYARAN VIA QRIS
    //=====================================
    $('#btnBayarQris').click(function() {
        let transaksi_id = $('#hidden_transaksi_id').val();
        let total_biaya = $('#hidden_total_biaya').val();

        if (!transaksi_id || !total_biaya) {
            Swal.fire('Error', 'Data transaksi tidak lengkap.', 'error');
            return;
        }

        $('#btnBayarQris').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        
        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler_pos.php',
            data: { 
                action: 'generate_qris', 
                transaksi_id: transaksi_id, 
                total_biaya: total_biaya 
            },
            dataType: 'json',
            success: function(res) {
                if (res.status == 'success') {
                    // Sembunyikan area tunai, tampilkan QRIS
                    $('#area_tunai').slideUp();
                    $('#area_qris').removeClass('hidden').slideDown();
                    $('#img_qris').attr('src', res.qr_url);
                    $('#btnProses').prop('disabled', true).addClass('opacity-50');

                    Swal.fire({
                        title: 'Scan QRIS',
                        text: 'Menunggu pembayaran...',
                        icon: 'info',
                        showConfirmButton: false,
                        timer: 3000
                    });

                    // Mulai Polling Status Pembayaran
                    qrisInterval = setInterval(function() {
                        $.ajax({
                            type: 'POST', 
                            url: '../../api/ajax_handler_pos.php',
                            data: { 
                                action: 'cek_status_qris', 
                                transaksi_id: transaksi_id 
                            },
                            dataType: 'json',
                            success: function(r) {
                                if (r.status == 'paid') {
                                    clearInterval(qrisInterval);
                                    
                                    Swal.fire({
                                        title: 'Pembayaran Diterima!',
                                        text: 'QRIS berhasil dibayar.',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                    
                                    // Trigger submit form pembayaran
                                    $('#formPembayaran').submit();
                                }
                            }
                        });
                    }, 3000); // Polling setiap 3 detik
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal generate QRIS', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Gagal menghubungi server QRIS.', 'error');
            },
            complete: function() {
                $('#btnBayarQris').prop('disabled', false).html('<i class="fas fa-qrcode mr-2"></i> QRIS');
            }
        });
    });

    //=====================================
    // 10. TOMBOL BATAL
    //=====================================
    $('#btnBatal').click(function() {
        Swal.fire({
            title: 'Batalkan Transaksi?',
            text: 'Data yang sudah diinput akan hilang.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Tidak',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                showSearchState();
            }
        });
    });

    //=====================================
    // 11. LOGIKA ABSENSI (GPS)
    //=====================================
    if ($('#btnAbsenMasuk').length) {
        let gpsOptions = { 
            enableHighAccuracy: true, 
            timeout: 5000, 
            maximumAge: 0 
        };
        
        function successGPS(pos) {
            $('#user_lat').val(pos.coords.latitude);
            $('#user_long').val(pos.coords.longitude);
            $('#status_lokasi').html("<span class='text-green-600'><i class='fas fa-check-circle'></i> Lokasi Terkunci!</span>");
            enableBtn("Konfirmasi Kehadiran", "success");
        }
        
        function errorGPS() {
            $('#user_lat').val('0');
            $('#user_long').val('0');
            $('#status_lokasi').html("<span class='text-orange-500'><i class='fas fa-exclamation-triangle'></i> GPS Gagal. Masuk Manual.</span>");
            enableBtn("Masuk Tanpa Lokasi", "warning");
        }
        
        function enableBtn(txt, type) {
            let color = (type == 'success') ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-500 hover:bg-orange-600';
            $('#btnAbsenMasuk')
                .removeAttr('disabled')
                .removeClass('bg-gray-300 cursor-not-allowed text-gray-500')
                .addClass(color + ' text-white')
                .html(txt);
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(successGPS, errorGPS, gpsOptions);
        } else {
            errorGPS();
        }

        $('#btnAbsenMasuk').click(function() {
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            
            $.post('../../api/ajax_handler_absen.php', {
                action: 'absen_masuk',
                lat: $('#user_lat').val() || '0',
                long: $('#user_long').val() || '0',
                pos_id: ID_POS_INI
            }, function(res) {
                if (res.status == 'success') {
                    Swal.fire({
                        title: 'Absen Berhasil!',
                        text: 'Selamat bekerja.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            }, 'json').fail(() => {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                setTimeout(() => location.reload(), 1500);
            });
        });
    }

    //=====================================
    // 12. TOMBOL PULANG (ABSEN KELUAR)
    //=====================================
    $('#btnPulang').click(function() {
        Swal.fire({
            title: 'Selesai Shift?',
            text: 'Anda akan absen keluar dan logout dari sistem.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Pulang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../../api/ajax_handler_absen.php', {
                    action: 'absen_keluar',
                    lat: '0',
                    long: '0'
                }, function() {
                    window.location.href = '../../logout.php';
                }, 'json').fail(() => {
                    window.location.href = '../../logout.php';
                });
            }
        });
    });

    //=====================================
    // HELPER FUNCTIONS
    //=====================================
    
    /**
     * Reset tampilan ke state awal (pencarian)
     */
    function showSearchState() {
        // Stop QRIS polling jika ada
        if (qrisInterval) {
            clearInterval(qrisInterval);
            qrisInterval = null;
        }

        // Hide payment details, show search form
        $('#paymentDetails').slideUp();
        $('#formCariTiket').slideDown();
        
        // Reset forms
        $('#formCariTiket')[0].reset();
        $('#formPembayaran')[0].reset();
        $('#kembalian').val('');
        $('#detail_biaya').text('Rp 0');

        // Reset QRIS UI
        $('#area_qris').addClass('hidden');
        $('#area_tunai').show();
        $('#btnBayarQris').prop('disabled', false).html('<i class="fas fa-qrcode mr-2"></i> QRIS').show();
        $('#btnProses').prop('disabled', false).removeClass('opacity-50').html('<i class="fas fa-print"></i> Proses & Buka');

        // Reset Member UI
        $('#badge_member').addClass('hidden');
        $('#badge_umum').removeClass('hidden');
        $('#box_biaya').removeClass('bg-blue-600').addClass('bg-primary');
        $('#info_member').addClass('hidden');
        $('#jumlah_bayar').prop('readonly', false).removeClass('bg-gray-100').val('');

        // Focus ke input scan
        setTimeout(() => $('#kode_input').focus(), 500);
    }
    
    /**
     * Tampilkan detail pembayaran dengan logika member
     */
    function showPaymentState(data) {
        // Isi data transaksi ke UI
        $('#detail_plat').text(data.plat_nomor);
        $('#detail_jenis').text(data.jenis);
        $('#detail_masuk').text(data.waktu_masuk_format);
        $('#detail_durasi').text(data.durasi_format);
        $('#detail_biaya').text('Rp ' + data.total_biaya_format);
        
        $('#hidden_transaksi_id').val(data.transaksi_id);
        $('#hidden_total_biaya').val(data.total_biaya);
        
        // --- LOGIKA MEMBER vs UMUM ---
        if (data.is_member) {
            // MODE MEMBER (GRATIS)
            $('#badge_member').removeClass('hidden');
            $('#badge_umum').addClass('hidden');
            
            $('#box_biaya').removeClass('bg-primary').addClass('bg-primary');
            
            // Tampilkan info member jika ada
            let memberInfo = data.member_nama ? 'Halo, ' + data.member_nama : 'Member Aktif';
            $('#info_member').removeClass('hidden').text(memberInfo);
            
            // Kunci input bayar (member gratis)
            $('#jumlah_bayar').val('0').prop('readonly', true).addClass('bg-gray-100');
            $('#kembalian').val('Rp 0');
            
            // Sembunyikan tombol QRIS
            $('#btnBayarQris').hide();
            
            // Ubah label tombol proses
            $('#btnProses').html('<i class="fas fa-check-circle"></i> Buka Palang (Gratis)');
            
            // Focus ke tombol proses
            setTimeout(() => $('#btnProses').focus(), 500);
        } else {
            // MODE UMUM (BAYAR)
            $('#badge_member').addClass('hidden');
            $('#badge_umum').removeClass('hidden');
            
            $('#box_biaya').removeClass('bg-primary').addClass('bg-primary');
            $('#info_member').addClass('hidden');
            
            // Enable input bayar
            $('#jumlah_bayar').val('').prop('readonly', false).removeClass('bg-gray-100');
            $('#kembalian').val('');
            
            // Tampilkan tombol QRIS
            $('#btnBayarQris').show();
            
            // Label tombol normal
            $('#btnProses').html('<i class="fas fa-print"></i> Proses & Buka');
            
            // Focus ke input bayar
            setTimeout(() => $('#jumlah_bayar').focus(), 500);
        }
        
        // Tampilkan container pembayaran
        $('#formCariTiket').slideUp();
        $('#paymentDetails').slideDown();
        
        // Scroll ke section pembayaran
        $('html, body').animate({
            scrollTop: $('#paymentDetails').offset().top - 100
        }, 500);
    }
    
    /**
     * Panggil hardware (Palang, Printer, dll)
     */
    function panggil_hardware(url, namaAlat) {
        if (!url || url.trim() === "") {
            console.warn(`[IOT] IP ${namaAlat} tidak dikonfigurasi.`);
            return;
        }
        
        // Tambahkan http:// jika belum ada
        if (!url.startsWith('http')) {
            url = 'http://' + url;
        }
        
        console.log(`[IOT] Menghubungi ${namaAlat}: ${url}`);
        
        fetch(url, { 
            mode: 'no-cors',
            method: 'GET'
        })
        .then(() => {
            console.log(`[IOT] ${namaAlat} berhasil dipanggil.`);
        })
        .catch(err => {
            console.error(`[IOT] Gagal koneksi ${namaAlat}:`, err);
            // Swal.fire('Warning', `Gagal koneksi ke ${namaAlat}`, 'warning');
        });
    }

    //=====================================
    // AUTO-FOCUS INTERVAL (SAFETY)
    //=====================================
    setInterval(function() {
        // Hanya auto-focus jika tidak ada modal/input lain yang aktif
        if ($('#paymentDetails').hasClass('hidden') && 
            $('#formKendaraanMasuk').hasClass('hidden') &&
            !$('.swal2-container').length) {
            $('#kode_input').focus();
        }
    }, 3000);

    // Log ready state
    console.log("✅ POS Parkir System Ready!");
});