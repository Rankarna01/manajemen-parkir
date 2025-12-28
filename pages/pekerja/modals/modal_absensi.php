<!-- Modal Absensi Masuk - FIXED VERSION -->
<div id="modalAbsensi" class="fixed inset-0 bg-gray-900 bg-opacity-95 z-50 flex items-center justify-center backdrop-blur-md">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md text-center relative overflow-hidden animate-fade-in-up">
        
        <!-- Loading Bar -->
        <div class="h-1.5 bg-gray-200 absolute top-0 left-0 w-full overflow-hidden">
            <div class="h-full bg-green-500 w-1/2 animate-loading-bar"></div>
        </div>
        
        <!-- Header -->
        <div class="mb-6 mt-2">
            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                <i class="fas fa-fingerprint text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Absensi Kehadiran</h2>
            <p class="text-gray-500 mt-2 text-sm">
                Anda akan memulai tugas di: <br>
                <b class="text-gray-800 text-lg"><?php echo $config['nama_pos']; ?></b>
            </p>
        </div>

        <!-- Status GPS -->
        <div class="bg-gray-50 rounded-lg p-3 mb-6 border border-gray-200">
            <div id="status_lokasi" class="text-sm font-semibold text-orange-500 flex items-center justify-center gap-2">
                <i class="fas fa-satellite-dish fa-spin"></i> 
                <span>Mendeteksi Lokasi GPS...</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Mohon izinkan akses lokasi browser Anda.</p>
        </div>
        
        <!-- Hidden Input Koordinat -->
        <input type="hidden" id="user_lat" value="0">
        <input type="hidden" id="user_long" value="0">
        
        <!-- Tombol Absen - PENTING: Tanpa pointer-events-none -->
        <button type="button" id="btnAbsenMasuk" disabled 
                class="w-full py-3.5 bg-gray-300 text-gray-500 rounded-xl font-bold shadow-lg transition-all duration-300 cursor-not-allowed flex items-center justify-center gap-2"
                style="position: relative; z-index: 10;">
            <i class="fas fa-lock"></i> 
            <span>Menunggu GPS...</span>
        </button>
        
        <!-- Debug Info (Opsional - Hapus di Production) -->
        <div id="debugInfo" class="mt-3 text-xs text-gray-400 hidden"></div>
    </div>
</div>

<style>
/* Prevent body scroll */
body.modal-open {
    overflow: hidden;
}

/* Loading bar animation */
@keyframes loading-bar {
    0% { left: -50%; }
    100% { left: 100%; }
}
.animate-loading-bar {
    position: absolute;
    animation: loading-bar 1.5s infinite linear;
}

/* Fade in animation */
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 0.4s ease-out;
}

/* Ensure button is clickable */
#btnAbsenMasuk {
    pointer-events: auto !important;
}
#btnAbsenMasuk:not([disabled]) {
    cursor: pointer !important;
}
</style>

<script>
// Lock body scroll
document.body.classList.add('modal-open');

// Debug: Log modal loaded
console.log('✓ Modal Absensi loaded');
console.log('✓ Button exists:', document.getElementById('btnAbsenMasuk') !== null);
</script>