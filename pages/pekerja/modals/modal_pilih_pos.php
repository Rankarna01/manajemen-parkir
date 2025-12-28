<div class="fixed inset-0 bg-gray-900 bg-opacity-90 z-[9999] flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md text-center relative animate-fade-in-down">
        
        <div class="mb-6">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-map-marked-alt text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Pilih Lokasi Jaga</h2>
            <p class="text-gray-500 text-sm mt-2">Halo! Di pos mana Anda bertugas saat ini?</p>
        </div>
        
        <form method="POST">
            <div class="space-y-3 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                <?php 
                // Pastikan pointer data kembali ke awal jika perlu, atau query ulang
                if ($daftar_pos->num_rows > 0): 
                    while($p = $daftar_pos->fetch_assoc()): 
                        // Tentukan warna badge tipe pos
                        $badge_color = ($p['tipe_pos'] == 'mobil') ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800';
                ?>
                <button type="submit" name="pilih_pos_id" value="<?php echo $p['id']; ?>" 
                        class="w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition group bg-white">
                    <div class="text-left">
                        <div class="font-bold text-gray-800 group-hover:text-blue-700"><?php echo $p['nama_pos']; ?></div>
                        <div class="mt-1">
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded <?php echo $badge_color; ?>">
                                POS <?php echo strtoupper($p['tipe_pos']); ?>
                            </span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-500 transition-transform group-hover:translate-x-1"></i>
                </button>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <div class="p-4 text-gray-500 text-sm border-2 border-dashed border-gray-300 rounded-xl">
                        Belum ada data Pos. Hubungi Owner.
                    </div>
                <?php endif; ?>
            </div>
        </form>

    </div>
</div>