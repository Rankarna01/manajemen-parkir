<?php
require_once '../../core/init.php';

// Cek Keamanan
if ($_SESSION['role'] != 'owner') { 
    header('Location: ../../login.php'); 
    exit; 
}

// 1. Ambil Data Identitas Instansi (Untuk Kop Laporan)
$config = $db->query("SELECT * FROM pengaturan_sistem WHERE id=1")->fetch_assoc();

// 2. Ambil Data Absensi
$query = "SELECT a.*, u.nama, p.nama_pos 
          FROM absensi a 
          JOIN users u ON a.user_id = u.id 
          JOIN pengaturan_sistem p ON a.pos_id = p.id 
          ORDER BY a.waktu_masuk DESC";
$result = $db->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Absensi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        
        .footer { margin-top: 30px; text-align: right; }
        .ttd { margin-top: 50px; margin-right: 30px; }
        
        /* Sembunyikan tombol cetak saat diprint */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.history.back()" style="padding: 10px 20px; cursor: pointer;">&laquo; Kembali</button>
    </div>

    <div class="header">
        <h1><?php echo strtoupper($config['nama_instansi'] ?? 'SISTEM PARKIR'); ?></h1>
        <p><?php echo $config['alamat_instansi'] ?? 'Alamat belum disetting'; ?></p>
        <h3 style="margin-top: 15px;">LAPORAN ABSENSI PEGAWAI</h3>
        <p>Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Nama Petugas</th>
                <th>Pos Jaga</th>
                <th style="width: 15%;">Waktu Masuk</th>
                <th style="width: 15%;">Waktu Keluar</th>
                <th>Keterangan Lokasi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()): 
            ?>
            <tr>
                <td style="text-align: center;"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_pos']); ?></td>
                <td style="text-align: center;"><?php echo date('d/m/Y H:i', strtotime($row['waktu_masuk'])); ?></td>
                <td style="text-align: center;">
                    <?php echo ($row['waktu_keluar']) ? date('d/m/Y H:i', strtotime($row['waktu_keluar'])) : '-'; ?>
                </td>
                <td style="font-size: 10px;">
                    IN: <?php echo $row['koordinat_masuk']; ?><br>
                    OUT: <?php echo $row['koordinat_keluar'] ?? '-'; ?>
                </td>
            </tr>
            <?php 
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">Belum ada data absensi.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Mengetahui,</p>
        <p>Owner / Pimpinan</p>
        <div class="ttd">_______________________</div>
    </div>

</body>
</html>