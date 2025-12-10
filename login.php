<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Login Sistem Parkir";
require_once 'core/init.php';

// Variabel untuk menyimpan pesan error
$error = '';

// Cek jika sudah login, lempar ke index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Cek jika ada data POST (form disubmit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password tidak boleh kosong!';
    } else {
        // Ambil data user dari database
        $stmt = $db->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Password benar, buat session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect ke halaman index (dashboard)
                header('Location: index.php');
                exit;
            } else {
                // Password salah
                $error = 'Email atau password salah.';
            }
        } else {
            // Email tidak ditemukan
            $error = 'Email atau password salah.';
        }
        $stmt->close();
    }
}
$db->close();

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once 'templates/header.php'; // Memanggil header (Pastikan header memuat konfigurasi Tailwind custom kita) ?>

<div class="min-h-screen flex items-center justify-center bg-secondary">
  <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2 border border-gray-100">

    <div class="relative hidden lg:flex flex-col items-center justify-center text-white p-12 overflow-hidden">
      <div class="absolute inset-0 bg-primary"></div>
      
      <div class="absolute inset-0 opacity-20" 
           style="background: radial-gradient(circle at 10% 20%, rgba(249, 168, 37, 0.4), transparent 40%);"></div>
      
      <div class="relative z-10 flex flex-col items-center text-center">
        <div class="bg-white/10 backdrop-blur-md w-24 h-24 rounded-2xl flex items-center justify-center shadow-lg mb-8 border border-white/10">
          <i class="fas fa-parking text-5xl text-accent"></i>
        </div>
        
        <h2 class="text-3xl font-bold mb-3 tracking-wide">Ventria<span class="text-accent">Park</span></h2>
        <p class="text-gray-300 text-lg max-w-xs font-light">
          Solusi Manajemen Parkir Cerdas & Terintegrasi.
        </p>
      </div>

      <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none">
        <svg class="relative block w-full h-[100px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" 
                  class="fill-white opacity-10"></path>
        </svg>
      </div>
    </div>

    <div class="p-8 md:p-12 flex flex-col justify-center bg-white">
      <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
          <div class="bg-primary/10 text-primary w-12 h-12 rounded-xl flex items-center justify-center shadow-sm">
            <i class="fas fa-user-lock text-xl"></i>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-primary leading-tight">Selamat Datang</h1>
            <p class="text-sm text-gray-500">Silakan masuk ke akun Anda</p>
          </div>
        </div>
      </div>

      <form action="login.php" method="POST" class="space-y-6">
        <?php if (!empty($error)): ?>
          <div class="flex items-center gap-3 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded shadow-sm">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <span class="text-sm font-medium"><?php echo $error; ?></span>
          </div>
        <?php endif; ?>

        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <i class="fas fa-envelope text-gray-400 group-focus-within:text-primary transition-colors"></i>
            </div>
            <input
              type="email" id="email" name="email" required
              class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary transition outline-none text-gray-800 placeholder-gray-400 bg-gray-50 focus:bg-white"
              placeholder="nama@perusahaan.com">
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <i class="fas fa-lock text-gray-400 group-focus-within:text-primary transition-colors"></i>
            </div>
            <input
              type="password" id="password" name="password" required
              class="w-full pl-11 pr-12 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary transition outline-none text-gray-800 placeholder-gray-400 bg-gray-50 focus:bg-white"
              placeholder="••••••••">
            <button type="button" id="togglePw"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary p-2 transition focus:outline-none"
              aria-label="Tampilkan/Sembunyikan password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none group">
            <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer">
            <span class="group-hover:text-primary transition">Ingat saya</span>
          </label>
          <a href="#" class="text-sm font-medium text-primary hover:text-accent transition">Lupa password?</a>
        </div>

        <div class="pt-2">
          <button type="submit"
            class="w-full bg-primary hover:bg-blue-900 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-900/20 transition transform active:scale-[.98] flex items-center justify-center gap-2">
            <span>Masuk Sekarang</span>
            <i class="fas fa-arrow-right text-sm"></i>
          </button>
        </div>
      </form>

      <div class="mt-8 text-center border-t border-gray-100 pt-6">
        <p class="text-xs text-gray-400">
          © <?php echo date('Y'); ?> VentriaPark System. <br>
          <span class="text-gray-300">Secure Access Management</span>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require_once 'templates/footer.php'; // Memanggil footer ?>

<script>
  (function() {
    const input = document.getElementById('password');
    const btn = document.getElementById('togglePw');
    const icon = btn.querySelector('i');

    if (!input || !btn) return;

    btn.addEventListener('click', () => {
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      
      // Ganti icon
      if (isPassword) {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  })();
</script>