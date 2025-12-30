[CCTV Gerbang Masuk]
        |
        |  (LAN IP: 192.168.1.20)
        v
      [Router/Switch]
        |
        |  (LAN IP: 192.168.1.10)
        v
   [Server Web / Backend PHP]
        |
        v
   Ditampilkan di halaman web



   CCTV Masuk  ─────▶│ Server Web   │───▶ Mengirim perintah palang
(192.168.1.20)    │ PHP + AJAX   │     Gpark
                  └──────┬──────┘
                         │
                         ▼
                  ┌──────────────┐
                  │ Mikrokontrol │───▶ Relay ──▶ Motor Palang
                  │ 192.168.1.103│
                  └──────────────┘




Tahap 1: Konfigurasi Jaringan (Pondasi Utama)
Kamu harus memastikan IP Address tidak berubah-ubah (Static IP).

Siapkan Router: Pastikan ada router yang menghubungkan semua alat (bisa pakai modem ISP biasa atau Router Mikrotik).

Setting Static IP: Masuk ke settingan Router atau setting di masing-masing alat, dan patenkan IP-nya seperti skema di atas:

Server Linux: 192.168.1.10

CCTV Masuk: 192.168.1.101

Palang Masuk (ESP32): 192.168.1.105

...dan seterusnya.

Kenapa? Agar kode const IP_PALANG_MASUK = "..." di kodingan kita tidak perlu diganti-ganti terus.

Tahap 2: Setup Server Linux (Di Ruangan Server)
Komputer ini akan hidup 24 jam sebagai "Otak".

Install OS: Ubuntu Server atau Debian (Ringan & Stabil).

Install Web Server (LAMP Stack):

sudo apt install apache2 mysql-server php libapache2-mod-php

Deploy Website:

Masukkan folder proyek sistem-parkir kita ke /var/www/html/.

Import Database:

Buka phpMyAdmin (atau terminal), import file SQL db_parkir_otomatis yang sudah kita buat.

Test Lokal:

Buka browser di PC Server, ketik http://localhost/sistem-parkir. Pastikan jalan.

Tahap 3: Setup PC Pos Parkir (Di Lapangan)
Ini komputer yang dipakai Mas-mas parkir.

Koneksi: Sambungkan ke Router (Kabel LAN lebih baik daripada WiFi).

Akses Web: Buka browser (Chrome), ketik alamat IP Server:

http://192.168.1.10/sistem-parkir

Cek CCTV & Alat:

Pastikan saat buka menu "Pos Parkir", video CCTV muncul.

Coba tekan tombol "Ambil Tiket", pastikan Palang terbuka.

Tahap 4: Membuatnya "Online" untuk Owner (KUNCI RAHASIA)
Karena server ada di lokal (belakang router), Owner tidak bisa langsung akses dari internet. Kita butuh VPN Tunnel.

Solusi Paling Mudah & Gratis: Gunakan "Tailscale" atau "ZeroTier".

Di Server Linux (Ruangan Server):

Install Tailscale: curl -fsSL https://tailscale.com/install.sh | sh

Login dan jalankan: sudo tailscale up

Kamu akan dapat IP Khusus VPN (misal: 100.10.10.1).

Di HP/Laptop Owner:

Install aplikasi Tailscale.

Login dengan akun yang sama.

Aktifkan.

Cara Owner Monitoring:

Owner cukup buka browser di HP-nya.

Ketik IP VPN Server tadi: http://100.10.10.1/sistem-parkir.
BOOM! Owner bisa melihat dashboard dan laporan secara real-time seolah-olah dia ada di ruangan server, tapi database tetap aman di lokal.


desain- nota
barcode tiket p. qris
login id bukan email
absensi
lokasi kordinat kurang akurat
cctv aktif terus 
absensi tersendiri untuk produk sistem
