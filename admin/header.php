<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Proteksi halaman admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengajuan Dana - Super Admin</title>
    
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
        :root {
            --lkpi-green: #008a3d;
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff; /* Ubah menjadi terang agar seragam */
            --text-main: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            letter-spacing: -0.01em;
            overflow-x: hidden;
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
            transition: all 0.3s ease;
            left: 0;
        }
        
        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
        }
        
        .sidebar-brand img {
            max-width: 140px;
            height: auto;
            mix-blend-mode: multiply; /* Gunakan mix-blend-mode seperti role lain, hapus filter invert */
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
            color: var(--text-muted); /* Ubah warna text default menyesuaikan background terang */
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
            transition: all 0.3s ease;
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

        .navbar-title { margin: 0; font-weight: 700; color: var(--text-main); font-size: 1.25rem; display: flex; align-items: center; gap: 15px; }

        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info { text-align: right; }
        .user-name { font-weight: 600; font-size: 0.875rem; color: var(--text-main); line-height: 1.2; }
        .user-role { font-size: 0.75rem; color: var(--text-muted); }

        .avatar {
            width: 42px;
            height: 42px;
            background: #f1f5f9;
            color: #3b82f6;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
        }

        .mobile-toggle { display: none; background: none; border: none; font-size: 1.5rem; color: var(--text-main); cursor: pointer; padding: 0; }

        @media (max-width: 768px) {
            .sidebar { left: -260px; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; width: 100%; padding: 15px; }
            .mobile-toggle { display: block; }
            .navbar-title { font-size: 1rem; gap: 10px; }
            .navbar-title-text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; opacity: 0; transition: opacity 0.3s; }
            .sidebar-overlay.show { display: block; opacity: 1; }
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
            <div class="menu-label">Control Panel</div>
            
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            
            <a href="dashboard_admin.php" class="<?php echo ($current_page == 'dashboard_admin.php') ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Dashboard Admin
            </a>
            <a href="kelola_user.php" class="<?php echo ($current_page == 'kelola_user.php') ? 'active' : ''; ?>">
                <i class="fas fa-users-gear"></i> Kelola Pengguna
            </a>
            <a href="kelola_divisi.php" class="<?php echo ($current_page == 'kelola_divisi.php') ? 'active' : ''; ?>">
                <i class="fas fa-network-wired"></i> Master Divisi
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
                    if($current_page == 'dashboard_admin.php') echo "Dashboard Super Admin";
                    elseif($current_page == 'kelola_user.php') echo "Manajemen Akun Pengguna";
                    elseif($current_page == 'kelola_divisi.php') echo "Manajemen Struktur Divisi";
                    else echo "Control Panel";
                    ?>
                </span>
            </h4>
            <div class="user-profile">
                <div class="user-info d-none d-md-block">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></div>
                    <div class="user-role">IT / System Admin</div>
                </div>
                <div class="avatar">
                    <i class="fas fa-user-astronaut"></i>
                </div>
            </div>
        </div>