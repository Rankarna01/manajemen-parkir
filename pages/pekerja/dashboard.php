<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Dashboard Pekerja";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
}

// Ambil ID pekerja dari session
$pekerja_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// 1. Ambil data: Kendaraan Masuk (Oleh pekerja ini, hari ini)
$stmt_masuk = $db->prepare("SELECT COUNT(id) AS total FROM transaksi_parkir WHERE id_petugas_masuk = ? AND DATE(waktu_masuk) = ?");
$stmt_masuk->bind_param("is", $pekerja_id, $today);
$stmt_masuk->execute();
$total_masuk_hari_ini = $stmt_masuk->get_result()->fetch_assoc()['total'];
$stmt_masuk->close();

// 2. Ambil data: Kendaraan Keluar (Oleh pekerja ini, hari ini)
$stmt_keluar = $db->prepare("SELECT COUNT(id) AS total FROM transaksi_parkir WHERE id_petugas_keluar = ? AND DATE(waktu_keluar) = ?");
$stmt_keluar->bind_param("is", $pekerja_id, $today);
$stmt_keluar->execute();
$total_keluar_hari_ini = $stmt_keluar->get_result()->fetch_assoc()['total'];
$stmt_keluar->close();

// 3. Ambil data: Pendapatan Shift Ini (Diterima oleh pekerja ini, hari ini)
$stmt_pendapatan = $db->prepare("SELECT SUM(biaya) AS total FROM transaksi_parkir WHERE id_petugas_keluar = ? AND DATE(waktu_keluar) = ?");
$stmt_pendapatan->bind_param("is", $pekerja_id, $today);
$stmt_pendapatan->execute();
$total_pendapatan_hari_ini = $stmt_pendapatan->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_pendapatan->close();

// 4. Ambil data: Total Kendaraan MASIH DI DALAM (Semua)
$result_didalam = $db->query("SELECT COUNT(id) AS total FROM transaksi_parkir WHERE status = 'masuk'");
$total_kendaraan_didalam = $result_didalam->fetch_assoc()['total'];


// --- Data untuk Grafik 7 Hari Terakhir ---
$chart_labels = [];
$chart_data_masuk = [];
$chart_data_keluar = [];

for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $hari = date('D', strtotime($tanggal)); // Format 'Sun', 'Mon'
    
    // Tambahkan label hari (Contoh: "Mon", "Tue")
    $chart_labels[] = $hari;

    // Query Masuk (oleh pekerja ini)
    $stmt_masuk_chart = $db->prepare("SELECT COUNT(id) AS total FROM transaksi_parkir WHERE id_petugas_masuk = ? AND DATE(waktu_masuk) = ?");
    $stmt_masuk_chart->bind_param("is", $pekerja_id, $tanggal);
    $stmt_masuk_chart->execute();
    $chart_data_masuk[] = $stmt_masuk_chart->get_result()->fetch_assoc()['total'];
    $stmt_masuk_chart->close();

    // Query Keluar (oleh pekerja ini)
    $stmt_keluar_chart = $db->prepare("SELECT COUNT(id) AS total FROM transaksi_parkir WHERE id_petugas_keluar = ? AND DATE(waktu_keluar) = ?");
    $stmt_keluar_chart->bind_param("is", $pekerja_id, $tanggal);
    $stmt_keluar_chart->execute();
    $chart_data_keluar[] = $stmt_keluar_chart->get_result()->fetch_assoc()['total'];
    $stmt_keluar_chart->close();
}

// Konversi data PHP ke JSON untuk JavaScript
$json_labels = json_encode($chart_labels);
$json_data_masuk = json_encode($chart_data_masuk);
$json_data_keluar = json_encode($chart_data_keluar);

$db->close();

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>
<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary p-6">
        <div class="container mx-auto max-w-7xl">
            
            <div class="relative overflow-hidden rounded-2xl bg-primary p-6 mb-6 shadow-xl">
                <div class="absolute inset-0 opacity-10"
                    style="background: radial-gradient(700px 240px at 15% -10%, rgba(255,255,255,.35), transparent),
                                      radial-gradient(600px 200px at 85% 120%, rgba(249, 168, 37, .35), transparent);"></div>
                <div class="relative flex items-center">
                    <div class="bg-accent/20 backdrop-blur rounded-xl p-3 shadow-md">
                        <i class="fas fa-id-badge text-3xl text-accent"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl md:text-3xl font-bold text-white">Hai, <?php echo htmlspecialchars($_SESSION['nama']); ?> 👋</h2>
                        <p class="text-gray-300">Ringkasan aktivitas shift Anda hari ini.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                
                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-5 transition duration-300 hover:shadow-2xl border-l-4 border-accent">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-accent/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex flex-col items-center text-center">
                        <div class="bg-accent text-primary p-4 rounded-full shadow-md mb-3">
                            <i class="fas fa-arrow-down text-xl"></i>
                        </div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Masuk (Shift Anda)</p>
                        <h3 class="text-3xl font-extrabold text-primary mt-1">
                            <?php echo (int)$total_masuk_hari_ini; ?>
                        </h3>
                        <p class="text-xs text-gray-500">mobil/motor</p>
                        
                        <div class="w-full mt-4">
                            <div class="h-2 w-full bg-secondary rounded-full overflow-hidden">
                                <div id="barMasuk" class="h-full bg-accent rounded-full shadow-sm" style="width:0%"></div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2">Progress terhadap target shift.</p>
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-5 transition duration-300 hover:shadow-2xl border-l-4 border-success">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-success/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex flex-col items-center text-center">
                        <div class="bg-success text-white p-4 rounded-full shadow-md mb-3">
                            <i class="fas fa-arrow-up text-xl"></i>
                        </div>
                        <p class="text-xs uppercase tracking-wide text-success font-semibold">Keluar (Shift Anda)</p>
                        <h3 class="text-3xl font-extrabold text-primary mt-1">
                            <?php echo (int)$total_keluar_hari_ini; ?>
                        </h3>
                        <p class="text-xs text-gray-500">mobil/motor</p>
                        
                        <div class="w-full mt-4 h-16 relative">
                            <canvas id="sparkKeluar"></canvas>
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-5 transition duration-300 hover:shadow-2xl border-l-4 border-accent">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-accent/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex flex-col items-center text-center">
                        <div class="bg-accent text-primary p-4 rounded-full shadow-md mb-3">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Pendapatan (Shift Anda)</p>
                        <h3 class="text-3xl font-extrabold text-primary mt-1">
                            Rp <?php echo number_format($total_pendapatan_hari_ini, 0, ',', '.'); ?>
                        </h3>
                        
                        <div class="w-full mt-4 h-24 relative">
                            <canvas id="doughnutPendapatan"></canvas>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-2">Pencapaian target.</p>
                    </div>
                </div>

                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-5 transition duration-300 hover:shadow-2xl border-l-4 border-primary">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-primary/10 group-hover:scale-125 transition"></div>
                    <div class="relative flex flex-col items-center text-center">
                        <div class="bg-primary text-white p-4 rounded-full shadow-md mb-3">
                            <i class="fas fa-car text-xl"></i>
                        </div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Masih Parkir (Total)</p>
                        <h3 class="text-3xl font-extrabold text-primary mt-1">
                            <?php echo (int)$total_kendaraan_didalam; ?>
                        </h3>
                        
                        <div class="w-full mt-4 h-24 relative">
                            <canvas id="gaugeParkir"></canvas>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-2">Terisi vs Kapasitas.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 border border-gray-100 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-primary">Aktivitas Anda (7 Hari Terakhir)</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-accent/10 text-primary font-semibold border border-accent/30">Live Data</span>
                    </div>
                    <div class="relative h-80 w-full">
                        <canvas id="workerActivityChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 flex flex-col">
                    <h3 class="text-xl font-bold text-primary mb-4">Indeks Beban Kerja</h3>
                    <div class="relative h-80 w-full">
                        <canvas id="chartPulse"></canvas>
                    </div>
                    <p class="text-xs text-gray-400 mt-3 text-center">Indeks visual beban kerja real-time.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// --- Konstanta Warna (Sesuai Brand VentriaPark) ---
const PRIMARY_COLOR = '#0B1F4F';
const ACCENT_COLOR = '#F9A825';
const SECONDARY_COLOR = '#EBEFF3';
const SUCCESS_COLOR = '#10B981';

/* =========================
   Data dari PHP (as is)
   ========================= */
const totalMasuk = <?php echo (int)$total_masuk_hari_ini; ?>;
const totalKeluar = <?php echo (int)$total_keluar_hari_ini; ?>;
const totalPendapatan = <?php echo (float)$total_pendapatan_hari_ini; ?>;
const totalDidalam = <?php echo (int)$total_kendaraan_didalam; ?>;

const labels7 = <?php echo $json_labels; ?>;       
const dataMasuk7 = <?php echo $json_data_masuk; ?>;    
const dataKeluar7 = <?php echo $json_data_keluar; ?>;  

/* ============================================
   Target/kapasitas (front-end only)
   ============================================ */
const TARGET_SHIFT_MASUK = 60;        
const TARGET_PENDAPATAN_SHIFT = 2500000; 
const KAPASITAS_PARKIR = 200;

/* ============
   Utilities
   ============ */
function animateWidth(el, percent, duration = 900) {
  if (!el) return;
  const start = 0;
  const t0 = performance.now();
  function tick(now) {
    const p = Math.min((now - t0) / duration, 1);
    const eased = 1 - Math.pow(1 - p, 3);
    el.style.width = (start + (percent - start) * eased) + '%';
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

/* ==============
   Progress Masuk
   ============== */
animateWidth(document.getElementById('barMasuk'), Math.min(100, (totalMasuk / TARGET_SHIFT_MASUK) * 100));

/* =========================
   Sparkline Keluar (Line)
   ========================= */
new Chart(document.getElementById('sparkKeluar'), {
  type: 'line',
  data: {
    labels: labels7,
    datasets: [{
      data: dataKeluar7,
      tension: 0.4,
      pointRadius: 0,
      borderWidth: 2,
      borderColor: SUCCESS_COLOR,
      backgroundColor: (context) => {
        const ctx = context.chart.ctx;
        const gradient = ctx.createLinearGradient(0, 0, 0, 100);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)'); // Success Color Transparent
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');
        return gradient;
      },
      fill: true
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    scales: { x: { display: false }, y: { display: false } },
    animation: { duration: 1000 }
  }
});

/* ==============================
   Doughnut Pendapatan vs Target
   ============================== */
new Chart(document.getElementById('doughnutPendapatan'), {
  type: 'doughnut',
  data: {
    labels: ['Tercapai', 'Sisa'],
    datasets: [{
      data: [
        Math.max(0, Math.min(totalPendapatan, TARGET_PENDAPATAN_SHIFT)),
        Math.max(0, TARGET_PENDAPATAN_SHIFT - totalPendapatan)
      ],
      borderWidth: 0,
      hoverOffset: 4,
      backgroundColor: [ACCENT_COLOR, SECONDARY_COLOR]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '75%',
    plugins: { legend: { display: false } },
    animation: { animateScale: true, animateRotate: true }
  }
});

/* =========================
   Gauge Parkir (Semi-donut)
   ========================= */
new Chart(document.getElementById('gaugeParkir'), {
  type: 'doughnut',
  data: {
    labels: ['Terisi', 'Kosong'],
    datasets: [{
      data: [
        Math.min(totalDidalam, KAPASITAS_PARKIR),
        Math.max(0, KAPASITAS_PARKIR - totalDidalam)
      ],
      borderWidth: 0,
      backgroundColor: [PRIMARY_COLOR, SECONDARY_COLOR]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    circumference: 180,
    rotation: -90,
    cutout: '75%',
    plugins: { legend: { display: false } },
    animation: { animateRotate: true, animateScale: true }
  }
});

/* ===================================
   Bar Aktivitas (Masuk vs Keluar 7H)
   =================================== */
new Chart(document.getElementById('workerActivityChart'), {
  type: 'bar',
  data: {
    labels: labels7,
    datasets: [
      { 
        label: 'Masuk', 
        data: dataMasuk7, 
        borderWidth: 0, 
        borderRadius: 4, 
        backgroundColor: ACCENT_COLOR 
      },
      { 
        label: 'Keluar', 
        data: dataKeluar7, 
        borderWidth: 0, 
        borderRadius: 4, 
        backgroundColor: SUCCESS_COLOR 
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom', labels: { usePointStyle: true } },
      tooltip: { 
          mode: 'index', 
          intersect: false,
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
          beginAtZero: true, 
          grid: { color: 'rgba(11, 31, 79, 0.05)', borderDash: [5, 5] }, 
          ticks: { stepSize: 1 },
          border: { display: false }
      }
    },
    animation: { duration: 900, easing: 'easeOutCubic' }
  }
});

/* ============================
   Line Pulse Indeks Aktivitas
   ============================ */
const indeksNow = Math.round(
  (totalMasuk/Math.max(TARGET_SHIFT_MASUK,1))*60 +
  (totalKeluar/Math.max(TARGET_SHIFT_MASUK,1))*25 +
  (totalPendapatan/Math.max(TARGET_PENDAPATAN_SHIFT,1))*15
);
const pulsePoints = [12, 20, 28, 36, 44, 57, 63, Math.min(100, indeksNow)];

new Chart(document.getElementById('chartPulse'), {
  type: 'line',
  data: {
    labels: ['06:00','08:00','10:00','12:00','14:00','16:00','18:00','Sekarang'],
    datasets: [{ 
        label: 'Indeks Aktivitas', 
        data: pulsePoints, 
        tension: .4, 
        pointRadius: 0, 
        pointHoverRadius: 6,
        borderWidth: 3,
        borderColor: PRIMARY_COLOR,
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
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { 
          beginAtZero: true, 
          max: 100, 
          grid: { display: false },
          border: { display: false }
      }
    },
    animation: { duration: 1200, easing: 'easeOutQuart' }
  }
});
</script>