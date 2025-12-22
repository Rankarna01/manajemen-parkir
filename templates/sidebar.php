<?php
// templates/sidebar.php

$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'pekerja';
?>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<div x-data="{ open: window.innerWidth >= 768 ? true : false }" class="relative">

    <button @click="open = !open"
        class="fixed top-4 left-4 z-50 md:hidden bg-accent text-primary p-2 rounded-lg shadow-lg focus:outline-none transition hover:bg-yellow-600">
        <i class="fas fa-bars"></i>
    </button>

    <div :class="open ? 'translate-x-0' : '-translate-x-64'"
        class="fixed md:static inset-y-0 left-0 w-64 bg-primary text-white flex flex-col shadow-2xl transform transition-transform duration-300 ease-in-out z-40 h-screen no-scrollbar">

        <div class="flex items-center justify-center h-20 border-b border-primary-900 shadow-md">
            <div class="flex items-center space-x-3">
                <div class="text-accent p-1">
                    <i class="fas fa-parking text-3xl"></i>
                </div>
                <span class="text-2xl font-bold tracking-wider">
                    Ventria<span class="text-accent">Park</span>
                </span>
            </div>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto no-scrollbar">

            <?php if ($user_role == 'owner'): ?>
                <a href="dashboard.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'dashboard.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-gauge w-6 text-center"></i>
                    <span class="ml-4">Dashboard</span>
                </a>

                <a href="laporan.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'laporan.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-file-alt w-6 text-center"></i>
                    <span class="ml-4">Laporan</span>
                </a>

                <a href="manajemen_pekerja.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'manajemen_pekerja.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-users w-6 text-center"></i>
                    <span class="ml-4">Manajemen Pekerja</span>
                </a>

                <a href="pengaturan_tarif.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'pengaturan_tarif.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-dollar-sign w-6 text-center"></i>
                    <span class="ml-4">Pengaturan Tarif</span>
                </a>

                <a href="riwayat_kendaraan.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'riwayat_kendaraan.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-rotate-left w-6 text-center"></i>
                    <span class="ml-4">Riwayat Kendaraan</span>
                </a>

                <a href="konfigurasi.php" 
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'konfigurasi.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-cogs w-6 text-center"></i>
                    <span class="ml-4">Konfigurasi Alat</span>
                </a>

            <?php elseif ($user_role == 'pekerja'): ?>
                <a href="pos_parkir.php" 
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'pos_parkir.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-desktop w-6 text-center"></i>
                    <span class="ml-4">Pos Parkir</span>
                </a>
                
                <a href="riwayat_kendaraan.php" 
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'riwayat_kendaraan.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-history w-6 text-center"></i>
                    <span class="ml-4">Riwayat Kendaraan</span>
                </a>

                
                <a href="dashboard.php" 
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'dashboard.php') 
                        ? 'bg-accent text-primary shadow-md' 
                        : 'hover:bg-primary-700 hover:text-accent'; ?>">
                    <i class="fas fa-tachometer-alt w-6 text-center"></i>
                    <span class="ml-4">Dashboard</span>
                </a>
            
            <?php endif; ?>
        </nav>

        <div class="px-4 py-4 border-t border-primary-700">
            <a href="../../logout.php"
                class="flex items-center px-4 py-2.5 rounded-lg text-red-400 hover:bg-red-600 hover:text-white font-medium transition duration-200">
                <i class="fas fa-sign-out-alt w-6 text-center"></i>
                <span class="ml-4">Logout</span>
            </a>
        </div>
    </div>

    <div x-show="open" @click="open = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-40 z-30 md:hidden"></div>
</div>