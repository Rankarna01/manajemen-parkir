<?php
// File: pages/pekerja/cctv/cctv_masuk.php
// Variabel $config sudah tersedia karena file ini di-include oleh pos_parkir.php
?>
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gray-800 text-white px-4 py-2 font-semibold flex justify-between items-center">
        <span><i class="fas fa-video mr-2"></i> CCTV Masuk (<?php echo strtoupper($config['tipe_pos']); ?>)</span>
        <span class="text-xs bg-red-500 px-2 py-1 rounded animate-pulse">LIVE</span>
    </div>

    <div class="bg-black h-48 flex items-center justify-center relative overflow-hidden">
        
        <?php if (!empty($config['ip_kamera_masuk'])): ?>
            <img src="http://<?php echo $config['ip_kamera_masuk']; ?>/stream.mjpg" 
                 class="w-full h-full object-cover"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            
            <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 hidden bg-black">
                <i class="fas fa-video-slash text-4xl mb-2"></i>
                <p class="text-xs">Kamera Offline / IP Salah</p>
                <p class="text-[10px] mt-1"><?php echo $config['ip_kamera_masuk']; ?></p>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center text-gray-500">
                <i class="fas fa-cog text-4xl mb-2 animate-spin"></i>
                <p class="text-xs">IP Kamera Belum Disetting</p>
            </div>
        <?php endif; ?>

    </div>
    
    <div class="p-4 border-t border-gray-200">
        
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200 text-center shadow-sm">
            <h5 class="text-sm font-bold text-blue-800 mb-2">DISPENSER TIKET OTOMATIS</h5>
            
            <button type="button" id="btnAmbilTiketOtomatis" 
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transform transition hover:scale-105 flex items-center justify-center gap-2">
                <i class="fas fa-print fa-lg"></i>
                <span>AMBIL TIKET <?php echo strtoupper($config['tipe_pos']); ?></span>
            </button>
            
            <p class="text-xs text-blue-600 mt-2">
                <i class="fas fa-info-circle"></i> Rekam Data > Cetak Tiket > Buka Palang
            </p>
        </div>

        <hr class="border-gray-200 my-4">

        <h4 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wide">Input Manual (Backup)</h4>
        
        <form id="formKendaraanMasuk">
            <input type="hidden" name="action" value="kendaraan_masuk_manual">

            <div class="mb-3">
                <label for="plat_nomor_manual" class="block text-sm font-medium text-gray-700">Plat Nomor</label>
                <input type="text" id="plat_nomor_manual" name="plat_nomor"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase"
                    placeholder="Contoh: BK 1234 ABC" required>
            </div>

            <div class="mb-4">
                <label for="jenis_kendaraan_manual" class="block text-sm font-medium text-gray-700">Jenis Kendaraan</label>
                <input type="text" name="jenis_kendaraan_display" value="<?php echo ucfirst($config['tipe_pos']); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed font-bold" readonly>
                <input type="hidden" name="jenis_kendaraan" value="<?php echo $config['tipe_pos']; ?>">
            </div>

            <button type="submit" id="btnSubmitManual" class="w-full bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center text-sm shadow-md transition transform hover:-translate-y-0.5">
                <i id="iconSubmitManual" class="fas fa-save mr-2"></i>
                <span id="textSubmitManual">Simpan Manual</span>
                <i id="spinnerSubmitManual" class="fas fa-spinner fa-spin ml-2 hidden"></i>
            </button>
        </form>
    </div>
</div>