<?php
// File: pages/pekerja/cctv/cctv_keluar.php
// File ini hanya untuk menampilkan video/stream kamera keluar.
?>
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gray-800 text-white px-4 py-2 font-semibold">
        <i class="fas fa-video mr-2"></i> CCTV Gerbang Keluar
    </div>

    <!--
    =========================================================
    PETUNJUK:
    1. Pasang <img> stream kamera di baris bawah ini.
    2. Ganti "http://IP_KAMERA_2/stream.mjpg" dengan URL stream kamera Anda.
    =========================================================
    -->
    <div class="p-4 bg-black h-48 flex items-center justify-center">
        <p class="text-gray-500">Waiting for stream...</p>
        <!-- Contoh jika ingin menampilkan kamera:
        <img src="http://IP_KAMERA_2/stream.mjpg" alt="CCTV Keluar" class="h-48 object-cover rounded-lg" />
        -->
    </div>

    <div class="p-4 border-t border-gray-200">
        <h4 class="text-lg font-semibold text-gray-800">Status Gerbang Keluar</h4>
        <p class="text-sm text-gray-600">Menunggu transaksi pembayaran...</p>
    </div>
</div>
