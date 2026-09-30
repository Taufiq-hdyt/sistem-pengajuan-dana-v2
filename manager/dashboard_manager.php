<?php
session_start();
require '../config/koneksi.php';

// Pastikan yang login adalah Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_user = $_SESSION['nama'];

// CATATAN VERSI "1 MANAGER UNTUK SEMUA DIVISI":
// Query di bawah TIDAK lagi difilter berdasarkan divisi_id manager,
// karena manager ini bertanggung jawab atas pengajuan dari SELURUH divisi.

// 1. Total Menunggu Persetujuan Manager (Semua Divisi)
$query_pending = "SELECT COUNT(p.id) as total FROM pengajuan p 
                  WHERE p.status_manager = 'Pending' AND p.status_keseluruhan = 'Pending'";
$total_pending = mysqli_fetch_assoc(mysqli_query($koneksi, $query_pending))['total'];

// 2. Total Disetujui Manager (Semua Divisi)
$query_setuju = "SELECT COUNT(p.id) as total FROM pengajuan p 
                 WHERE p.status_manager = 'Disetujui'";
$total_setuju = mysqli_fetch_assoc(mysqli_query($koneksi, $query_setuju))['total'];

// 3. Total Ditolak Manager (Semua Divisi)
$query_tolak = "SELECT COUNT(p.id) as total FROM pengajuan p 
                WHERE p.status_manager = 'Ditolak'";
$total_tolak = mysqli_fetch_assoc(mysqli_query($koneksi, $query_tolak))['total'];

// Memanggil Header
require 'header.php';
?>

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
        opacity: 0;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

    .card-pending { border-bottom: 4px solid #f59e0b; }
    .icon-pending { background: #fffbeb; color: #f59e0b; }

    .card-setuju { border-bottom: 4px solid #10b981; }
    .icon-setuju { background: #ecfdf5; color: #10b981; }

    .card-tolak { border-bottom: 4px solid #ef4444; }
    .icon-tolak { background: #fef2f2; color: #ef4444; }

    .guide-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 25px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        animation: slideUp 0.4s ease forwards;
        animation-delay: 0.4s;
        opacity: 0;
    }
</style>

<div class="welcome-box">
    <h3 class="fw-bold mb-2">Selamat Datang, <?php echo htmlspecialchars($nama_user); ?>!</h3>
    <p class="mb-0" style="opacity: 0.9;">Ini adalah panel Manager. Anda bertanggung jawab melakukan validasi tahap pertama terhadap pengajuan dana dari SPV <strong>seluruh divisi</strong> sebelum dilanjutkan ke Departemen Finance.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="dashboard-card card-pending" style="animation-delay: 0.1s;">
            <div class="card-info">
                <p>Menunggu Otorisasi Anda</p>
                <h3><?php echo $total_pending; ?></h3>
            </div>
            <div class="card-icon icon-pending">
                <i class="fas fa-file-circle-exclamation"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="dashboard-card card-setuju" style="animation-delay: 0.2s;">
            <div class="card-info">
                <p>Disetujui Manager</p>
                <h3><?php echo $total_setuju; ?></h3>
            </div>
            <div class="card-icon icon-setuju">
                <i class="fas fa-check"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="dashboard-card card-tolak" style="animation-delay: 0.3s;">
            <div class="card-info">
                <p>Ditolak / Batal</p>
                <h3><?php echo $total_tolak; ?></h3>
            </div>
            <div class="card-icon icon-tolak">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>
</div>

<div class="guide-box">
    <h5 class="fw-bold" style="color: var(--text-main); margin-bottom: 20px;">
        <i class="fas fa-info-circle text-primary me-2"></i> Peran Anda sebagai Manager
    </h5>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="d-flex align-items-start">
                <div class="card-icon" style="width: 45px; height: 45px; background: #e0f2fe; color: #0284c7; font-size: 1.1rem; margin-right: 15px;">
                    <i class="fas fa-search-dollar"></i>
                </div>
                <div>
                    <strong class="d-block text-dark mb-1">Verifikasi Anggaran</strong>
                    <span class="text-muted" style="font-size: 0.85rem; line-height: 1.4; display: block;">Pastikan nominal pengajuan dari SPV logis dan sesuai dengan rencana kerja (budget plan) divisi terkait bulan ini.</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-start">
                <div class="card-icon" style="width: 45px; height: 45px; background: #fffbeb; color: #d97706; font-size: 1.1rem; margin-right: 15px;">
                    <i class="fas fa-rotate-left"></i>
                </div>
                <div>
                    <strong class="d-block text-dark mb-1">Fitur Minta Revisi</strong>
                    <span class="text-muted" style="font-size: 0.85rem; line-height: 1.4; display: block;">Jika file penawaran tidak jelas atau ada yang keliru, Anda dapat meminta SPV untuk merevisi dokumen sebelum menyetujuinya.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>