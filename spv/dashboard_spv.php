<?php
session_start();
// Memanggil koneksi dari luar folder spv/
require '../config/koneksi.php';

// Proteksi halaman: Pastikan yang login adalah SPV
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'spv') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'];

// Mengambil statistik pengajuan berdasarkan status_keseluruhan untuk user yang sedang login
// 1. Total Semua Pengajuan
$query_total = mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE user_id = '$user_id'");
$total_pengajuan = mysqli_fetch_assoc($query_total)['total'];

// 2. Pengajuan Pending
$query_pending = mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE user_id = '$user_id' AND status_keseluruhan = 'Pending'");
$total_pending = mysqli_fetch_assoc($query_pending)['total'];

// 3. Pengajuan Disetujui
$query_setuju = mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE user_id = '$user_id' AND status_keseluruhan = 'Disetujui'");
$total_setuju = mysqli_fetch_assoc($query_setuju)['total'];

// 4. Pengajuan Ditolak
$query_tolak = mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE user_id = '$user_id' AND status_keseluruhan = 'Ditolak'");
$total_tolak = mysqli_fetch_assoc($query_tolak)['total'];

// MEMANGGIL HEADER & SIDEBAR
require 'header.php';
?>

<style>
    /* Animasi masuk untuk cards */
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dashboard-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s ease;
        animation: slideUp 0.4s ease forwards;
        opacity: 0; /* Awalnya sembunyi, lalu dianimasikan masuk */
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
    }

    .card-info h3 {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
        line-height: 1.2;
    }

    .card-info p {
        margin: 0;
        font-size: 0.8rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    /* Warna Khusus Card */
    .card-total { border-bottom: 4px solid var(--lkpi-green); }
    .icon-total { background: #f0fdf4; color: var(--lkpi-green); }

    .card-pending { border-bottom: 4px solid #f59e0b; }
    .icon-pending { background: #fffbeb; color: #f59e0b; }

    .card-setuju { border-bottom: 4px solid #10b981; }
    .icon-setuju { background: #ecfdf5; color: #10b981; }

    .card-tolak { border-bottom: 4px solid #ef4444; }
    .icon-tolak { background: #fef2f2; color: #ef4444; }

    /* Welcome Box */
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

    .guide-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 25px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        animation: slideUp 0.4s ease forwards;
        animation-delay: 0.5s;
        opacity: 0;
    }
</style>

<div class="welcome-box">
    <h3 class="fw-bold mb-2">Selamat Datang, <?php echo htmlspecialchars($nama_user); ?>!</h3>
    <p class="mb-0" style="opacity: 0.9;">Ini adalah panel Supervisor. Anda dapat memantau ringkasan pengajuan dana Anda di sini.</p>
</div>

<!-- Gunakan Bootstrap Grid untuk Responsif -->
<div class="row g-4 mb-4">
    <!-- Col-12 di HP (Penuh), Col-6 di Tablet (Setengah), Col-3 di PC (Seperempat) -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="dashboard-card card-total" style="animation-delay: 0.1s;">
            <div class="card-info">
                <p>Total Pengajuan</p>
                <h3><?php echo $total_pengajuan; ?></h3>
            </div>
            <div class="card-icon icon-total">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="dashboard-card card-pending" style="animation-delay: 0.2s;">
            <div class="card-info">
                <p>Pending / Berjalan</p>
                <h3><?php echo $total_pending; ?></h3>
            </div>
            <div class="card-icon icon-pending">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="dashboard-card card-setuju" style="animation-delay: 0.3s;">
            <div class="card-info">
                <p>Telah Disetujui</p>
                <h3><?php echo $total_setuju; ?></h3>
            </div>
            <div class="card-icon icon-setuju">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-3">
        <div class="dashboard-card card-tolak" style="animation-delay: 0.4s;">
            <div class="card-info">
                <p>Dibatalkan</p>
                <h3><?php echo $total_tolak; ?></h3>
            </div>
            <div class="card-icon icon-tolak">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
    </div>
</div>

<div class="guide-box">
    <h5 class="fw-bold" style="color: var(--text-main); margin-bottom: 20px;">
        <i class="fas fa-info-circle text-primary me-2"></i> Instruksi Operasional
    </h5>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="d-flex align-items-start">
                <div class="card-icon" style="width: 45px; height: 45px; background: #e0f2fe; color: #0284c7; font-size: 1.1rem; margin-right: 15px;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <strong class="d-block text-dark mb-1">Pengajuan Baru</strong>
                    <span class="text-muted" style="font-size: 0.85rem; line-height: 1.4; display: block;">Navigasi ke menu 'Buat Pengajuan' untuk memproses permohonan dana baru. Pastikan dokumen penawaran (Quotation) terlampir dengan valid.</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-start">
                <div class="card-icon" style="width: 45px; height: 45px; background: #dcfce7; color: #16a34a; font-size: 1.1rem; margin-right: 15px;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div>
                    <strong class="d-block text-dark mb-1">Monitoring Status</strong>
                    <span class="text-muted" style="font-size: 0.85rem; line-height: 1.4; display: block;">Lacak progres validasi dari Departemen Keuangan (Finance) dan Direksi melalui menu 'Riwayat Pengajuan'.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MEMANGGIL FOOTER -->
<?php require 'footer.php'; ?>