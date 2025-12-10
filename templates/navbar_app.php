<?php
// templates/navbar_app.php
$user_nama = $_SESSION['nama'] ?? 'Pengguna';
?>

<header class="bg-primary shadow-lg border-b border-blue-900 px-6 py-3 flex justify-between items-center text-white relative z-20">

    <div class="flex items-center space-x-3">
    </div>

    <div id="realtimeClock" class="text-center font-medium hidden sm:block text-gray-200 bg-blue-900/50 px-4 py-1.5 rounded-full border border-blue-800/50 shadow-inner text-sm"></div>

    <div class="flex items-center space-x-4">
        <span class="hidden md:inline text-sm text-gray-300">
            Halo, <span class="font-bold text-white"><?php echo htmlspecialchars($user_nama); ?></span>
        </span>

        <div class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-primary font-bold text-sm shadow-lg ring-2 ring-primary/50">
            <?php echo strtoupper(substr($user_nama, 0, 1)); // Inisial nama ?>
        </div>

        <a href="../../logout.php" 
           class="flex items-center space-x-2 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition duration-200 shadow-md hover:shadow-lg border border-red-500">
            <i class="fas fa-sign-out-alt"></i>
            <span class="hidden sm:inline">Logout</span>
        </a>
    </div>
</header>

<script>
function updateRealtimeClock() {
    const clockElement = document.getElementById('realtimeClock');
    if (!clockElement) return;

    const now = new Date();
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    const dayName = days[now.getDay()];
    const date = now.getDate();
    const monthName = months[now.getMonth()];
    const year = now.getFullYear();

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    // Format tampilan
    clockElement.innerHTML = `
        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-2">
            <span class="font-semibold text-accent">${dayName}, ${date} ${monthName} ${year}</span>
            <span class="hidden sm:inline text-gray-400">|</span>
            <span class="text-base font-bold tracking-widest">${hours}:${minutes}:${seconds}</span>
        </div>`;
}

// Jalankan terus
setInterval(updateRealtimeClock, 1000);
updateRealtimeClock();
</script>