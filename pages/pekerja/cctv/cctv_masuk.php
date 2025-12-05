<?php
// File: pages/pekerja/cctv/cctv_masuk.php
?>
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gray-800 text-white px-4 py-2 font-semibold flex justify-between items-center">
        <span><i class="fas fa-video mr-2"></i> CCTV Gerbang Masuk</span>
        <span class="text-xs bg-red-500 px-2 py-1 rounded animate-pulse">LIVE</span>
    </div>

    <div class="p-4 bg-black h-48 flex items-center justify-center relative">
        
        <div class="text-center">
            <i class="fas fa-video-slash text-gray-600 text-4xl mb-2"></i>
            <p class="text-gray-500 text-sm">Video Stream Masuk...</p>
        </div>

    </div>
    
    <div class="p-4 border-t border-gray-200">
        
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200 text-center shadow-sm">
            <h5 class="text-sm font-bold text-blue-800 mb-2">DISPENSER TIKET OTOMATIS</h5>
            
            <button type="button" id="btnAmbilTiketOtomatis" 
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transform transition hover:scale-105 flex items-center justify-center gap-2">
                <i class="fas fa-print fa-lg"></i>
                <span>TEKAN UNTUK AMBIL TIKET</span>
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
                    placeholder="BK 1234 ABC" required>
            </div>

            <div class="mb-4">
                <label for="jenis_kendaraan_manual" class="block text-sm font-medium text-gray-700">Jenis Kendaraan</label>
                <select id="jenis_kendaraan_manual" name="jenis_kendaraan"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Pilih Jenis</option>
                    <?php if (!empty($tarif_options)): ?>
                        <?php foreach ($tarif_options as $tarif): ?>
                            <option value="<?php echo $tarif['jenis_kendaraan']; ?>">
                                <?php echo ucfirst($tarif['jenis_kendaraan']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Error: Tarif tidak dimuat</option>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" id="btnSubmitManual" class="w-full bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center text-sm shadow-md">
                <i id="iconSubmitManual" class="fas fa-save mr-2"></i>
                <span id="textSubmitManual">Simpan Manual</span>
                <i id="spinnerSubmitManual" class="fas fa-spinner fa-spin ml-2 hidden"></i>
            </button>
        </form>
    </div>
</div>