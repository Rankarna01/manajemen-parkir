<?php
// File: pages/pekerja/cctv/cctv_keluar.php
?>
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gray-800 text-white px-4 py-2 font-semibold flex justify-between items-center">
        <span><i class="fas fa-video mr-2"></i> CCTV Keluar</span>
        <span class="text-xs bg-red-500 px-2 py-1 rounded animate-pulse">LIVE</span>
    </div>

    <div class="bg-black h-48 flex items-center justify-center relative overflow-hidden">
        
        <?php if (!empty($config['ip_kamera_keluar'])): ?>
            <img src="http://<?php echo $config['ip_kamera_keluar']; ?>/stream.mjpg" 
                 class="w-full h-full object-cover"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            
            <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 hidden bg-black">
                <i class="fas fa-video-slash text-4xl mb-2"></i>
                <p class="text-xs">Kamera Offline</p>
                <p class="text-[10px] mt-1"><?php echo $config['ip_kamera_keluar']; ?></p>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center text-gray-500">
                <i class="fas fa-cog text-4xl mb-2 animate-spin"></i>
                <p class="text-xs">IP Kamera Belum Disetting</p>
            </div>
        <?php endif; ?>

    </div>

    <div class="p-4 border-t border-gray-200">
         <h4 class="text-lg font-semibold text-gray-800">Status Gerbang Keluar</h4>
         <p class="text-sm text-gray-600">Menunggu transaksi pembayaran...</p>
    </div>
</div>