<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengajuan Dana - SPV</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

    <style>
        /* Palet Warna Modern */
        :root {
            --lkpi-green: #008a3d;
            --lkpi-lime: #9bc92b;
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff;
            --text-main: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            letter-spacing: -0.01em;
            overflow-x: hidden; /* Mencegah scroll menyamping */
        }

        /* --- SIDEBAR STYLING --- */
        .sidebar {
            background-color: var(--bg-sidebar);
            min-height: 100vh;
            border-right: 1px solid var(--border-color);
            position: fixed;
            width: 260px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease; /* Animasi buka tutup */
            left: 0;
        }
        
        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
        }
        
        .sidebar-brand img {
            max-width: 140px;
            height: auto;
            mix-blend-mode: multiply; 
        }

        .sidebar-menu { padding-top: 10px; }

        .menu-label {
            padding: 10px 25px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            padding: 12px 25px;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-menu a:hover {
            color: var(--lkpi-green);
            background-color: #f8fafc;
        }

        .sidebar-menu a.active {
            color: var(--lkpi-green);
            background-color: #f0fdf4;
            border-left-color: var(--lkpi-green);
            font-weight: 600;
        }

        .logout-btn {
            color: #ef4444 !important;
            margin-top: auto;
            margin-bottom: 20px;
        }
        .logout-btn:hover { background-color: #fef2f2 !important; }

        /* --- CONTENT & NAVBAR STYLING --- */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: all 0.3s ease; /* Animasi saat sidebar dilipat */
            width: calc(100% - 260px);
        }

        .top-navbar {
            background: #ffffff;
            padding: 15px 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.5);
        }

        .navbar-title {
            margin: 0;
            font-weight: 700;
            color: var(--text-main);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info { text-align: right; }
        .user-name { font-weight: 600; font-size: 0.875rem; color: var(--text-main); line-height: 1.2; }
        .user-role { font-size: 0.75rem; color: var(--text-muted); }

        .avatar {
            width: 42px;
            height: 42px;
            background: #f0fdf4;
            color: var(--lkpi-green);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
        }

        /* Tombol Hamburger Khusus Mobile */
        .mobile-toggle {
            display: none; /* Sembunyikan di PC */
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-main);
            cursor: pointer;
            padding: 0;
        }

        /* Tampilan Khusus HP (Lebar layar kurang dari 768px) */
        @media (max-width: 768px) {
            .sidebar {
                left: -260px; /* Sembunyikan sidebar ke kiri */
                box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            }
            .sidebar.show {
                left: 0; /* Tampilkan sidebar saat diklik */
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px; /* Kurangi padding di HP */
            }
            .mobile-toggle {
                display: block; /* Munculkan tombol garis 3 */
            }
            .navbar-title {
                font-size: 1rem; /* Kecilkan sedikit font judul di HP agar tidak turun */
                gap: 10px; /* Kurangi jarak antara tombol garis 3 dan judul */
            }
            .navbar-title-text {
                /* Memotong teks jika terlalu panjang dan tambahkan elipsis (...) di ujung */
                white-space: nowrap; 
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 200px; /* Batasi lebar maksimal teks judul di HP */
            }
            /* Overlay Hitam saat menu HP terbuka */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                opacity: 0;
                transition: opacity 0.3s;
            }
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
        }
    </style>
</head>
<body>

    <!-- Overlay Hitam untuk HP -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <img src="../logoCV.png" alt="Logo LKPI" onerror="this.style.display='none';">
        </div>
        
        <div class="sidebar-menu flex-grow-1 d-flex flex-column">
            <div class="menu-label">Menu Utama</div>
            
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            
            <!-- Mengganti icon fa-grid-2 menjadi fa-chart-pie yang lebih universal -->
            <a href="dashboard_spv.php" class="<?php echo ($current_page == 'dashboard_spv.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="tambah_pengajuan.php" class="<?php echo ($current_page == 'tambah_pengajuan.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> Buat Pengajuan
            </a>
            <a href="riwayat_pengajuan.php" class="<?php echo ($current_page == 'riwayat_pengajuan.php') ? 'active' : ''; ?>">
                <i class="fas fa-clock-rotate-left"></i> Riwayat Pengajuan
            </a>
            <a href="laporan.php" class="<?php echo (in_array($current_page, ['laporan.php', 'laporan_pengajuan.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i> Laporan Pertanggungjawaban
            </a>
            
            <div class="menu-label mt-3">Pengaturan</div>
            <a href="../ubah_password.php">
                <i class="fas fa-shield-halved"></i> Ubah Password
            </a>
            
            <a href="#" onclick="konfirmasiLogout('../logout.php')" class="logout-btn">
                <i class="fas fa-arrow-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT START -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <h4 class="navbar-title">
                <button class="mobile-toggle" id="btnToggleMenu">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-title-text">
                    <?php 
                    if($current_page == 'dashboard_spv.php') echo "Dashboard Analytics";
                    elseif($current_page == 'riwayat_pengajuan.php') echo "Riwayat & Status Pengajuan";
                    elseif($current_page == 'tambah_pengajuan.php') echo "Form Pengajuan Dana";
                    elseif($current_page == 'laporan.php') echo "Laporan Pertanggungjawaban";
                    elseif($current_page == 'laporan_pengajuan.php') echo "Kelola Laporan Pengajuan";
                    else echo "Panel SPV";
                    ?>
                </span>
            </h4>
            <div class="user-profile">
                <?php
                // Mengambil nama divisi untuk ditampilkan di bawah nama user
                $nama_divisi = '';
                if (isset($_SESSION['nama_divisi'])) {
                    $nama_divisi = $_SESSION['nama_divisi'];
                } elseif (isset($koneksi) && isset($_SESSION['user_id'])) {
                    // Jika belum ada di session, ambil dari database berdasarkan user_id
                    $uid = $_SESSION['user_id'];
                    $q_divisi = mysqli_query($koneksi, "SELECT d.nama_divisi FROM users u LEFT JOIN divisi d ON u.divisi_id = d.id WHERE u.id = '$uid'");
                    if ($q_divisi && mysqli_num_rows($q_divisi) > 0) {
                        $row_divisi = mysqli_fetch_assoc($q_divisi);
                        $nama_divisi = $row_divisi['nama_divisi'] ?? '';
                        $_SESSION['nama_divisi'] = $nama_divisi; // Simpan ke session agar tidak query berulang-ulang
                    }
                }
                ?>
                <!-- Sembunyikan nama di HP agar muat, hanya tampilkan icon orang -->
                <div class="user-info d-none d-md-block">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Supervisor'); ?></div>
                    <div class="user-role">Supervisor <?php echo htmlspecialchars($nama_divisi); ?></div>
                </div>
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
        <!-- KONTEN HALAMAN AKAN MUNCUL DI BAWAH SINI -->