<?php
//=========================================
// LOGIKA PHP (TIDAK BERUBAH)
//=========================================
$page_title = "Dashboard Owner";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 1. Ambil Jumlah Pekerja
$result_pekerja = $db->query("SELECT COUNT(id) AS total_pekerja FROM users WHERE role = 'pekerja'");
$data_pekerja = $result_pekerja->fetch_assoc();
$total_pekerja = $data_pekerja['total_pekerja'];

// 2. Ambil Pendapatan Hari Ini
$today = date('Y-m-d');
$result_pendapatan = $db->query("SELECT SUM(biaya) AS total_pendapatan FROM transaksi_parkir WHERE status = 'keluar' AND DATE(waktu_keluar) = '$today'");
$data_pendapatan = $result_pendapatan->fetch_assoc();
$total_pendapatan_hari_ini = $data_pendapatan['total_pendapatan'] ?? 0;

// 3. Ambil Kendaraan Masuk Hari Ini
$result_masuk = $db->query("SELECT COUNT(id) AS total_masuk FROM transaksi_parkir WHERE DATE(waktu_masuk) = '$today'");
$data_masuk = $result_masuk->fetch_assoc();
$total_kendaraan_masuk_hari_ini = $data_masuk['total_masuk'];

$result_keluar = $db->query("SELECT COUNT(id) AS total_keluar FROM transaksi_parkir WHERE status = 'keluar' AND DATE(waktu_keluar) = '$today'");
$data_keluar = $result_keluar->fetch_assoc();
$total_kendaraan_keluar_hari_ini = $data_keluar['total_keluar'];

// 4. Ambil Total Kendaraan YANG MASIH DI DALAM
$result_didalam = $db->query("SELECT COUNT(id) AS total_didalam FROM transaksi_parkir WHERE status = 'masuk'");
$data_didalam = $result_didalam->fetch_assoc();
$total_kendaraan_didalam = $data_didalam['total_didalam'];

//=========================================
// TAMPILAN HTML
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>
<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">

    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto">

            <div class="relative overflow-hidden rounded-2xl bg-primary p-6 mb-6 shadow-xl">
                <div class="absolute inset-0 opacity-10"
                     style="background: radial-gradient(800px 300px at 10% -10%, rgba(255,255,255,.35), transparent),
                                     radial-gradient(600px 200px at 90% 120%, rgba(249, 168, 37, .35), transparent);"></div>
                <div class="relative flex items-center">
                    <div class="bg-accent/20 backdrop-blur rounded-xl p-3 shadow-md">
                        <i class="fas fa-parking text-3xl text-accent"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl md:text-3xl font-bold text-white">Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?>!</h2>
                        <p class="text-gray-300">Ringkasan aktivitas parkir hari ini.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 transition duration-300 hover:shadow-2xl border-l-4 border-accent">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-accent/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex items-center mb-4">
                        <div class="bg-accent text-white p-4 rounded-full shadow-md">
                            <i class="fas fa-wallet fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Pendapatan Hari Ini</p>
                            <p class="text-2xl font-extrabold text-gray-900 leading-tight">
                                Rp <span id="moneyCount"><?php echo number_format($total_pendapatan_hari_ini ?? 0, 0, ',', '.'); ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="relative h-32 w-full">
                        <canvas id="chartRevenue"></canvas>
                    </div>
                </div>

                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 transition duration-300 hover:shadow-2xl border-l-4 border-primary">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-primary/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex items-center">
                        <div class="bg-primary text-white p-4 rounded-full shadow-md">
                            <i class="fas fa-exchange-alt fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Kendaraan Masuk</p>
                            <p class="text-2xl font-extrabold text-gray-900 leading-tight">
                                <span class="text-primary"><?php echo (int)($total_kendaraan_masuk_hari_ini ?? 0); ?></span> unit
                            </p>
                        </div>
                    </div>

                    <div class="relative flex items-center mt-5 border-t border-secondary pt-4">
                        <div class="bg-success text-white p-4 rounded-full shadow-md"> 
                            <i class="fas fa-door-open fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Kendaraan Keluar</p>
                            <p class="text-2xl font-extrabold text-gray-900">
                                <span class="text-success"><?php echo (int)($total_kendaraan_keluar_hari_ini ?? 0); ?></span> unit
                            </p>
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 transition duration-300 hover:shadow-2xl border-l-4 border-primary">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-primary/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex items-center mb-4">
                        <div class="bg-primary text-white p-4 rounded-full shadow-md">
                            <i class="fas fa-car-side fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Masih Parkir (Live)</p>
                            <p class="text-2xl font-extrabold text-gray-900">
                                <span id="didalamCount" class="text-primary"><?php echo (int)($total_kendaraan_didalam ?? 0); ?></span>
                                <span class="text-base font-medium text-gray-500">unit</span>
                            </p>
                        </div>
                    </div>
                    <div class="relative h-32 w-full">
                        <canvas id="chartGaugeInside"></canvas>
                    </div>
                    <p class="text-xs text-center text-gray-500 mt-1">Kapasitas Asumsi</p>
                </div>

                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 transition duration-300 hover:shadow-2xl border-l-4 border-accent">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-accent/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex items-center">
                        <div class="bg-accent text-primary p-4 rounded-full shadow-md">
                            <i class="fas fa-user-friends fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Pekerja Aktif</p>
                            <p class="text-2xl font-extrabold text-gray-900">
                                <span id="pekerjaCount" class="text-accent"><?php echo (int)($total_pekerja ?? 0); ?></span>
                                <span class="text-base font-medium text-gray-500">orang</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-12">
                        <div class="h-3 w-full bg-secondary/80 rounded-full overflow-hidden shadow-inner">
                            <div id="barPekerja" class="h-full bg-accent rounded-full shadow-sm transition-all duration-1000 ease-out" style="width:0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Komposisi shift aktif.</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Grafik Komparatif Harian</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-accent/10 text-primary font-semibold border border-accent/30">Data Instan</span>
                    </div>
                    <div class="relative h-80 w-full">
                        <canvas id="chartCompare"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Tren Indeks Aktivitas</h3>
                    <div class="relative h-80 w-full">
                        <canvas id="chartPulse"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const PRIMARY_COLOR = '#0B1F4F';
const ACCENT_COLOR = '#F9A825';
const SECONDARY_COLOR = '#EBEFF3';
const SUCCESS_COLOR = '#10B981';
const MUTED_BLUE = '#4A90E2';

/* Data dari PHP */
const pendapatan = <?php echo (float)($total_pendapatan_hari_ini ?? 0); ?>;
const masuk = <?php echo (int)($total_kendaraan_masuk_hari_ini ?? 0); ?>;
const didalam = <?php echo (int)($total_kendaraan_didalam ?? 0); ?>;
const pekerja = <?php echo (int)($total_pekerja ?? 0); ?>;
const keluar = <?php echo (int)($total_kendaraan_keluar_hari_ini ?? 0); ?>;

/* Konstanta Target */
const TARGET_PENDAPATAN = 5000000; 
const TARGET_MASUK = 120; 
const KAPASITAS_PARKIR = 200; 
const KEBUTUHAN_SHIFT = 10; 

/* Utility Animasi */
function animateWidth(el, percent, duration = 800) {
    const start = 0;
    const startTime = performance.now();
    function tick(now) {
        const p = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.style.width = (start + (percent - start) * eased) + '%';
        if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

const barPekerja = document.getElementById('barPekerja');
animateWidth(barPekerja, Math.min(100, (pekerja / KEBUTUHAN_SHIFT) * 100));

/* =======================================================
   1. CHART PENDAPATAN (Doughnut)
   ======================================================= */
new Chart(document.getElementById('chartRevenue'), {
    type: 'doughnut',
    data: {
        labels: ['Tercapai', 'Sisa'],
        datasets: [{
            data: [Math.max(0, Math.min(pendapatan, TARGET_PENDAPATAN)), Math.max(0, TARGET_PENDAPATAN - pendapatan)],
            borderWidth: 0,
            hoverOffset: 4,
            backgroundColor: [ACCENT_COLOR, SECONDARY_COLOR]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Wajib false agar ikut container
        cutout: '75%', // Lebih tipis agar terlihat modern
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) => 'Rp ' + ctx.parsed.toLocaleString('id-ID')
                }
            }
        },
        animation: { animateRotate: true, animateScale: true }
    }
});

/* =======================================================
   2. CHART GAUGE PARKIR (Semi-Doughnut)
   ======================================================= */
new Chart(document.getElementById('chartGaugeInside'), {
    type: 'doughnut',
    data: {
        labels: ['Terisi', 'Kosong'],
        datasets: [{
            data: [Math.min(didalam, KAPASITAS_PARKIR), Math.max(0, KAPASITAS_PARKIR - didalam)],
            borderWidth: 0,
            backgroundColor: [PRIMARY_COLOR, SECONDARY_COLOR]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Wajib false
        circumference: 180,
        rotation: -90,
        cutout: '75%',
        plugins: { legend: { display: false } },
        animation: { animateRotate: true, animateScale: true }
    }
});

/* =======================================================
   3. CHART BAR KOMPARATIF (Big Chart)
   ======================================================= */
new Chart(document.getElementById('chartCompare'), {
    type: 'bar',
    data: {
        labels: ['Masuk', 'Keluar', 'Di Dalam', 'Pekerja'],
        datasets: [{
            label: 'Jumlah',
            data: [masuk, keluar, didalam, pekerja],
            borderWidth: 0,
            borderRadius: 8, // Bar membulat modern
            borderSkipped: false,
            backgroundColor: [ACCENT_COLOR, SUCCESS_COLOR, PRIMARY_COLOR, MUTED_BLUE]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Wajib false agar mengisi tinggi h-80
        plugins: {
            legend: { display: false },
            tooltip: { 
                backgroundColor: PRIMARY_COLOR,
                padding: 10,
                cornerRadius: 8
            }
        },
        scales: {
            x: { 
                grid: { display: false },
                ticks: { font: { family: 'Poppins' } }
            },
            y: { 
                grid: { color: '#f3f4f6', borderDash: [5, 5] },
                border: { display: false }, // Hapus garis border sumbu Y
                beginAtZero: true 
            }
        },
        animation: { duration: 1000, easing: 'easeOutQuart' }
    }
});

/* =======================================================
   4. CHART PULSE (Line)
   ======================================================= */
const idx = Math.round((masuk/120)*60 + (didalam/200)*30 + (pekerja/10)*10);
const pulsePoints = [10, 15, 25, 30, 45, 50, 40, Math.min(100, idx)];

new Chart(document.getElementById('chartPulse'), {
    type: 'line',
    data: {
        labels: ['06:00','08:00','10:00','12:00','14:00','16:00','18:00','Now'],
        datasets: [{
            label: 'Aktivitas',
            data: pulsePoints,
            tension: 0.4, // Kurva halus
            pointRadius: 0, // Sembunyikan titik default
            pointHoverRadius: 6,
            borderColor: PRIMARY_COLOR,
            borderWidth: 3,
            backgroundColor: (context) => {
                const ctx = context.chart.ctx;
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(11, 31, 79, 0.2)');
                gradient.addColorStop(1, 'rgba(11, 31, 79, 0.0)');
                return gradient;
            },
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Wajib false
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { display: false, min: 0, max: 100 } // Sembunyikan sumbu Y agar bersih
        },
        interaction: {
            mode: 'index',
            intersect: false,
        },
        animation: { duration: 1500, easing: 'easeOutQuart' }
    }
});
</script>