<?php
session_start();
// Memanggil koneksi database
require '../config/koneksi.php';

// Proteksi halaman: Pastikan yang login adalah Finance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../index.php");
    exit();
}

$nama_user = $_SESSION['nama'];

// QUERY STATISTIK (Disesuaikan dengan Alur Manager -> Finance)
// TUGAS AKTIF
$total_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE status_manager = 'Disetujui' AND status_finance = 'Pending' AND status_keseluruhan = 'Pending'"))['total'];
$total_transfer = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE status_keseluruhan = 'Disetujui' AND bukti_transfer IS NULL"))['total'];
$total_validasi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE status_laporan = 'Menunggu Validasi'"))['total'];

// RIWAYAT & SELESAI
$total_disetujui = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE status_finance = 'Disetujui'"))['total'];
$total_ditolak = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE status_finance = 'Ditolak'"))['total'];
$total_selesai = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as total FROM pengajuan WHERE status_laporan = 'Valid'"))['total'];

// Panggil Header
require 'header.php';
?>

<style>
    /* Animasi masuk untuk cards */
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Welcome Box - Warna Biru Keabuan untuk Finance */
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
        background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    /* Styling Card Statistik */
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
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .card-icon {
        width: 55px;
        height: 55px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
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
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    /* Variasi Warna Card */
    .card-pending { border-left: 4px solid #f59e0b; }
    .icon-pending { background: #fffbeb; color: #d97706; }

    .card-transfer { border-left: 4px solid #0ea5e9; }
    .icon-transfer { background: #e0f2fe; color: #0284c7; }

    .card-validasi { border-left: 4px solid #8b5cf6; }
    .icon-validasi { background: #f3e8ff; color: #7e22ce; }

    .card-setuju { border-left: 4px solid #10b981; }
    .icon-setuju { background: #ecfdf5; color: #059669; }

    .card-tolak { border-left: 4px solid #ef4444; }
    .icon-tolak { background: #fef2f2; color: #dc2626; }

    .card-selesai { border-left: 4px solid var(--lkpi-green); background-color: #f8fafc;}
    .icon-selesai { background: #dcfce7; color: var(--lkpi-green); }
    .card-selesai .card-info h3 { color: var(--lkpi-green); }

    /* Guide Box */
    .guide-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 30px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        animation: slideUp 0.4s ease forwards;
        animation-delay: 0.5s;
        opacity: 0;
        margin-top: 10px;
    }

    .guide-step {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        height: 100%;
        border: 1px dashed #cbd5e1;
    }

    .step-number {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: var(--lkpi-green);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
</style>

<!-- Welcome Box -->
<div class="welcome-box">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-2">Selamat datang, <?php echo htmlspecialchars($nama_user); ?>.</h3>
            <p class="mb-0" style="opacity: 0.85; font-size: 0.95rem;">Panel kontrol utama Departemen Keuangan (Finance). Pantau antrean tugas dan riwayat pengajuan di bawah ini.</p>
        </div>
        <div class="d-none d-md-block text-end">
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7;">Tanggal Hari Ini</span>
            <h5 class="mb-0 fw-bold mt-1"><?php echo date('d F Y'); ?></h5>
        </div>
    </div>
</div>

<h5 class="fw-bold mb-3" style="color: var(--text-main);"><i class="fas fa-inbox text-muted me-2"></i> Tugas Aktif (Perlu Tindakan)</h5>
<div class="row g-4 mb-5">
    <div class="col-12 col-md-4">
        <div class="dashboard-card card-pending" style="animation-delay: 0.1s;">
            <div class="card-info">
                <p>Menunggu Persetujuan</p>
                <h3><?php echo $total_pending; ?></h3>
            </div>
            <div class="card-icon icon-pending">
                <i class="fas fa-file-circle-exclamation"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="dashboard-card card-transfer" style="animation-delay: 0.2s;">
            <div class="card-info">
                <p>Antrean Transfer</p>
                <h3><?php echo $total_transfer; ?></h3>
            </div>
            <div class="card-icon icon-transfer">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="dashboard-card card-validasi" style="animation-delay: 0.3s;">
            <div class="card-info">
                <p>Validasi Laporan</p>
                <h3><?php echo $total_validasi; ?></h3>
            </div>
            <div class="card-icon icon-validasi">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>
    </div>
</div>

<h5 class="fw-bold mb-3" style="color: var(--text-main);"><i class="fas fa-clock-rotate-left text-muted me-2"></i> Riwayat & Status Akhir</h5>
<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="dashboard-card card-setuju" style="animation-delay: 0.4s;">
            <div class="card-info">
                <p>Disetujui Finance</p>
                <h3><?php echo $total_disetujui; ?></h3>
            </div>
            <div class="card-icon icon-setuju">
                <i class="fas fa-check"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="dashboard-card card-tolak" style="animation-delay: 0.5s;">
            <div class="card-info">
                <p>Ditolak Finance</p>
                <h3><?php echo $total_ditolak; ?></h3>
            </div>
            <div class="card-icon icon-tolak">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="dashboard-card card-selesai" style="animation-delay: 0.6s;">
            <div class="card-info">
                <p>Transaksi Selesai</p>
                <h3><?php echo $total_selesai; ?></h3>
            </div>
            <div class="card-icon icon-selesai">
                <i class="fas fa-clipboard-check"></i>
            </div>
        </div>
    </div>
</div>

<!-- Alur Kerja -->
<div class="guide-box">
    <h5 class="fw-bold mb-4" style="color: var(--text-main);">
        <i class="fas fa-sitemap text-primary me-2"></i> Standar Operasional Prosedur (SOP)
    </h5>
    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="guide-step">
                <div class="step-number">1</div>
                <div>
                    <strong class="d-block text-dark mb-1">Persetujuan Awal</strong>
                    <span class="text-muted" style="font-size: 0.85rem; line-height: 1.4;">Periksa rasionalitas harga pada pengajuan dana yang telah di-ACC Manager sebelum diteruskan ke Direksi.</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="guide-step">
                <div class="step-number">2</div>
                <div>
                    <strong class="d-block text-dark mb-1">Pencairan Dana</strong>
                    <span class="text-muted" style="font-size: 0.85rem; line-height: 1.4;">Lakukan transfer ke rekening tujuan untuk pengajuan yang telah disetujui penuh oleh Dirut dan Dirkeu.</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="guide-step">
                <div class="step-number">3</div>
                <div>
                    <strong class="d-block text-dark mb-1">Validasi Laporan</strong>
                    <span class="text-muted" style="font-size: 0.85rem; line-height: 1.4;">Cocokkan nominal pada nota asli dari SPV. Transaksi dianggap <b class="text-success">Selesai</b> jika laporan valid.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panggil Footer -->
<?php require 'footer.php'; ?>