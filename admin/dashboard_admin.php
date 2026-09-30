<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$nama_user = $_SESSION['nama'];

$q_total_users = mysqli_query($koneksi, "SELECT COUNT(id) AS total FROM users");
$total_users = mysqli_fetch_assoc($q_total_users)['total'];

$q_total_divisi = mysqli_query($koneksi, "SELECT COUNT(id) AS total FROM divisi");
$total_divisi = mysqli_fetch_assoc($q_total_divisi)['total'];

// Rincian Akun Aktif
$q_rincian = mysqli_query($koneksi, "SELECT r.nama_role, COUNT(u.id) as jumlah FROM roles r LEFT JOIN users u ON r.id = u.role_id GROUP BY r.id");

require 'header.php';
?>

<style>
    @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

    .welcome-box {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        padding: 30px;
        color: white;
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.3);
        position: relative;
        overflow: hidden;
        margin-bottom: 30px;
        animation: slideUp 0.4s ease forwards;
    }

    .welcome-box::after {
        content: ''; position: absolute; right: -5%; top: -20%; width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%); border-radius: 50%; pointer-events: none;
    }

    .dashboard-card {
        background: #ffffff; border-radius: 16px; padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        transition: all 0.3s ease; animation: slideUp 0.4s ease forwards; opacity: 0;
    }
    .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }

    .card-icon { width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
    .card-info h3 { font-size: 1.8rem; font-weight: 700; color: var(--text-main); margin: 0; line-height: 1.2; }
    .card-info p { margin: 0; font-size: 0.8rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }

    .card-biru { border-bottom: 4px solid #3b82f6; }
    .icon-biru { background: #eff6ff; color: #3b82f6; }
    
    .card-hijau { border-bottom: 4px solid #10b981; }
    .icon-hijau { background: #ecfdf5; color: #10b981; }

    .stat-list {
        background: #ffffff; border-radius: 16px; border: 1px solid #f1f5f9;
        padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        animation: slideUp 0.4s ease forwards; animation-delay: 0.3s; opacity: 0;
    }
    
    .stat-item {
        display: flex; justify-content: space-between; padding: 12px 15px; border-bottom: 1px solid #f1f5f9;
        font-weight: 500; color: var(--text-main);
    }
    .stat-item:last-child { border-bottom: none; }
</style>

<div class="welcome-box">
    <h3 class="fw-bold mb-2">Sistem Kendali, <?php echo htmlspecialchars($nama_user); ?></h3>
    <p class="mb-0" style="opacity: 0.9;">Otoritas penuh manajemen data pengguna dan hak akses aplikasi (Role-Based Access Control)</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6">
        <div class="dashboard-card card-biru" style="animation-delay: 0.1s;">
            <div class="card-info">
                <p>Total Pengguna (Users)</p>
                <h3><?php echo $total_users; ?></h3>
            </div>
            <div class="card-icon icon-biru"><i class="fas fa-users"></i></div>
        </div>
    </div>
    
    <div class="col-12 col-md-6">
        <div class="dashboard-card card-hijau" style="animation-delay: 0.2s;">
            <div class="card-info">
                <p>Total Divisi / Departemen</p>
                <h3><?php echo $total_divisi; ?></h3>
            </div>
            <div class="card-icon icon-hijau"><i class="fas fa-network-wired"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-6">
        <div class="stat-list">
            <h5 class="fw-bold mb-4" style="color: var(--text-main);"><i class="fas fa-id-badge text-primary me-2"></i> Distribusi Akun Berdasarkan Jabatan</h5>
            <?php 
            while($row_rincian = mysqli_fetch_assoc($q_rincian)) { 
                $nama_role = strtoupper($row_rincian['nama_role']);
            ?>
            <div class="stat-item">
                <span class="text-muted"><i class="fas fa-circle text-secondary me-2" style="font-size: 8px;"></i> <?php echo $nama_role; ?></span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3"><?php echo $row_rincian['jumlah']; ?> Akun</span>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>