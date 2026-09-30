<?php
session_start();
require '../config/koneksi.php';

// Pastikan hanya Dirut yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dirut') {
    header("Location: ../index.php");
    exit();
}

$nama_user = $_SESSION['nama'];

// 1. DATA KARTU STATISTIK (Fokus pada jumlah dokumen, bukan nominal)
// Menunggu Otorisasi Dirut
$q_pending = mysqli_query($koneksi, "SELECT COUNT(id) as total_doc FROM pengajuan WHERE status_finance = 'Disetujui' AND status_dirut = 'Pending' AND status_keseluruhan = 'Pending'");
$count_pending = mysqli_fetch_assoc($q_pending)['total_doc'] ?? 0;

// Disetujui Dirut
$q_setuju = mysqli_query($koneksi, "SELECT COUNT(id) as total_doc FROM pengajuan WHERE status_dirut = 'Disetujui'");
$count_setuju = mysqli_fetch_assoc($q_setuju)['total_doc'] ?? 0;

// Ditolak Dirut
$q_tolak = mysqli_query($koneksi, "SELECT COUNT(id) as total_doc FROM pengajuan WHERE status_dirut = 'Ditolak'");
$count_tolak = mysqli_fetch_assoc($q_tolak)['total_doc'] ?? 0;

// ==========================================
// RINGKASAN SERAPAN ANGGARAN BULAN BERJALAN
// ==========================================
// Serapan Anggaran = jumlah REALISASI DANA dari pengajuan yang laporannya
// sudah SELESAI/VALID (bukan dana yang diajukan, dan bukan sekadar disetujui Dirut).
// Detail lengkap (grafik per divisi, filter, tabel rincian) ada di halaman serapan_anggaran.php.
// Di dashboard ini cukup ditampilkan ringkasan bulan berjalan saja.
$bulan_ini = date('Y-m');
$query_ringkasan_bulan_ini = "SELECT COUNT(p.id) AS jumlah_transaksi, SUM(p.realisasi_dana) AS total_realisasi
                              FROM pengajuan p
                              WHERE p.status_laporan = 'Valid'
                              AND DATE_FORMAT(p.tanggal_dibuat, '%Y-%m') = '$bulan_ini'";
$result_ringkasan_bulan_ini = mysqli_query($koneksi, $query_ringkasan_bulan_ini);
$ringkasan_bulan_ini = mysqli_fetch_assoc($result_ringkasan_bulan_ini);

$total_realisasi_bulan_ini   = $ringkasan_bulan_ini['total_realisasi'] ?? 0;
$jumlah_transaksi_bulan_ini  = $ringkasan_bulan_ini['jumlah_transaksi'] ?? 0;
// ==========================================

// 2. TABEL PRIORITAS (Menunggu Otorisasi)
// Mengambil 5 pengajuan teratas yang perlu di-ACC Dirut, diurutkan berdasarkan jatuh tempo paling dekat
$query_prioritas = "SELECT p.*, u.nama AS nama_spv, d.nama_divisi 
                  FROM pengajuan p 
                  JOIN users u ON p.user_id = u.id 
                  LEFT JOIN divisi d ON u.divisi_id = d.id 
                  WHERE p.status_finance = 'Disetujui' AND p.status_dirut = 'Pending' AND p.status_keseluruhan = 'Pending'
                  ORDER BY p.tanggal_jatuh_tempo ASC LIMIT 5";
$result_prioritas = mysqli_query($koneksi, $query_prioritas);

// Panggil Header
require 'header.php';
?>

<!-- Include Chart.js -->
<!-- (Chart.js tidak lagi diperlukan di dashboard ini — grafik lengkap ada di halaman serapan_anggaran.php) -->

<style>
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .welcome-box {
        background: linear-gradient(135deg, var(--lkpi-green) 0%, #005a27 100%);
        border-radius: 16px;
        padding: 30px;
        color: white;
        box-shadow: 0 10px 15px -3px rgba(0, 138, 61, 0.2);
        position: relative;
        overflow: hidden;
        margin-bottom: 30px;
        animation: slideUp 0.4s ease forwards;
    }

    .welcome-box::after {
        content: '';
        position: absolute;
        right: -5%;
        top: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    /* Styling Card Eksekutif (Besar & Bersih) */
    .exec-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        animation: slideUp 0.4s ease forwards;
        opacity: 0;
        height: 100%;
    }

    .exec-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .exec-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .exec-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }

    .exec-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .exec-body h2 {
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--text-main);
        margin: 0 0 5px 0;
        line-height: 1.2;
    }

    .exec-body h2 span {
        font-size: 1.1rem;
        font-weight: 600;
        color: #94a3b8;
    }

    .exec-body p {
        margin: 0;
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 500;
    }

    /* Warna Khas Tiap Card */
    .card-warn .exec-icon { background: #fffbeb; color: #d97706; }
    .card-warn .exec-body h2 { color: #d97706; }
    
    .card-succ .exec-icon { background: #ecfdf5; color: #059669; }
    .card-succ .exec-body h2 { color: #059669; }
    
    .card-dang .exec-icon { background: #fef2f2; color: #dc2626; }

    /* Box Modern untuk Tabel & Grafik */
    .panel-box {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 25px;
        margin-bottom: 25px;
        animation: slideUp 0.4s ease forwards;
        animation-delay: 0.2s;
        opacity: 0;
        height: 100%; /* Agar tinggi sama dalam row */
    }

    .panel-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Styling Tabel Modern */
    .table-modern { margin-bottom: 0; }
    .table-modern thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
    }
    .table-modern tbody td {
        padding: 16px 20px;
        vertical-align: middle;
        font-size: 0.875rem;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }
    .table-modern tbody tr:hover { background-color: #f8fafc; }

    .vendor-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; display: block; margin-bottom: 3px; }
    .nominal-text { color: #008a3d; font-weight: 700; font-size: 0.95rem; }
    
    .badge-soft {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        background-color: #f1f5f9; 
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .btn-action {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s;
        display: inline-block;
    }
    .btn-action:hover { background: #dcfce7; color: #15803d; border-color: #86efac; }
</style>

<div class="welcome-box">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-2">Panel Eksekutif, <?php echo htmlspecialchars($nama_user); ?>.</h3>
            <p class="mb-0" style="opacity: 0.85; font-size: 0.95rem;">Selamat datang. Berikut adalah ringkasan antrean pengajuan dokumen yang membutuhkan keputusan Anda hari ini.</p>
        </div>
        <div class="d-none d-md-block text-end">
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7;">Tanggal Hari Ini</span>
            <h5 class="mb-0 fw-bold mt-1"><?php echo date('d F Y'); ?></h5>
        </div>
    </div>
</div>

<!-- 3 Kartu Fokus Utama (Fokus pada Dokumen) -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
        <div class="exec-card card-warn" style="animation-delay: 0.1s;">
            <div class="exec-header">
                <h4 class="exec-title">Menunggu Keputusan</h4>
                <div class="exec-icon"><i class="fas fa-file-signature"></i></div>
            </div>
            <div class="exec-body">
                <h2><?php echo $count_pending; ?> <span>Dokumen</span></h2>
                <p>Membutuhkan otorisasi Anda</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="exec-card card-succ" style="animation-delay: 0.2s;">
            <div class="exec-header">
                <h4 class="exec-title">Pengajuan Disetujui</h4>
                <div class="exec-icon"><i class="fas fa-check-double"></i></div>
            </div>
            <div class="exec-body">
                <h2><?php echo $count_setuju; ?> <span>Dokumen</span></h2>
                <p>Telah Anda otorisasi</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="exec-card card-dang" style="animation-delay: 0.3s;">
            <div class="exec-header">
                <h4 class="exec-title">Pengajuan Ditolak</h4>
                <div class="exec-icon"><i class="fas fa-ban"></i></div>
            </div>
            <div class="exec-body">
                <h2><?php echo $count_tolak; ?> <span>Dokumen</span></h2>
                <p>Dibatalkan oleh Anda</p>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan Serapan Anggaran (full width, di atas) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="panel-box" style="animation-delay: 0.1s;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 55px; height: 55px; border-radius: 14px; background: #ecfdf5; color: var(--lkpi-green); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                        <i class="fas fa-sack-dollar"></i>
                    </div>
                    <div>
                        <span class="d-block text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Total Realisasi Bulan Ini</span>
                        <h3 class="mb-0" style="color: var(--lkpi-green); font-weight: 800; letter-spacing: -0.5px;">Rp <?php echo number_format($total_realisasi_bulan_ini, 0, ',', '.'); ?></h3>
                        <span class="text-muted" style="font-size: 0.8rem;"><?php echo $jumlah_transaksi_bulan_ini; ?> transaksi selesai</span>
                    </div>
                </div>
                <a href="serapan_anggaran.php" class="btn-action">
                    <i class="fas fa-chart-pie me-1"></i> Lihat Detail Serapan Anggaran
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Prioritas Kerja (full width, di bawah) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="panel-box" style="animation-delay: 0.2s;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="panel-title m-0"><i class="fas fa-clipboard-list text-warning"></i> Menunggu Keputusan Anda</h5>
                <?php if($count_pending > 5) { ?>
                    <a href="persetujuan.php" class="btn btn-sm btn-light border fw-semibold text-muted">Lihat Semua (<?php echo $count_pending; ?>)</a>
                <?php } ?>
            </div>
            
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th class="ps-4">Deadline</th>
                            <th>Divisi & SPV</th>
                            <th>Vendor & Nominal</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result_prioritas) > 0) {
                            while ($row = mysqli_fetch_assoc($result_prioritas)) { 
                                $nominal = "Rp " . number_format($row['biaya_diajukan'], 0, ',', '.');
                                $tgl_tempo = date('d F Y', strtotime($row['tanggal_jatuh_tempo']));
                        ?>
                        <tr>
                            <td class="ps-4 text-danger fw-bold"><i class="far fa-calendar-alt me-1"></i> <?php echo $tgl_tempo; ?></td>
                            <td>
                                <strong class="d-block text-dark"><?php echo htmlspecialchars($row['nama_spv']); ?></strong>
                                <span class="badge-soft mt-1"><?php echo htmlspecialchars($row['nama_divisi'] ?? '-'); ?></span>
                            </td>
                            <td>
                                <span class="vendor-name"><?php echo htmlspecialchars($row['nama_vendor']); ?></span>
                                <div class="nominal-text"><?php echo $nominal; ?></div>
                            </td>
                            <td class="text-center pe-4">
                                <a href="persetujuan.php" class="btn-action"><i class="fas fa-pen-to-square me-1"></i> Proses</a>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } else { 
                            echo "<tr><td colspan='4' class='text-center py-5 text-muted'><i class='fas fa-mug-hot fa-3x mb-3 d-block opacity-25'></i> Tidak ada tugas mendesak hari ini.</td></tr>"; 
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tambahkan sedikit style untuk scrollbar agar tetap elegan -->
<?php require 'footer.php'; ?>