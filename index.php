<?php
session_start();

// Redirect otomatis berdasarkan role jika sudah login
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
if ($role === 'spv') {
    header("Location: spv/dashboard_spv.php");
    exit();
} elseif ($role === 'manager') {
    header("Location: manager/dashboard_manager.php");
    exit();
} elseif ($role === 'finance') {
    header("Location: finance/dashboard_finance.php");
    exit();
} elseif ($role === 'dirut') {
        header("Location: dirut/dashboard_dirut.php");
        exit();
    } elseif ($role === 'dirkeu') {
        header("Location: dirkeu/dashboard_dirkeu.php");
        exit();
    } else {
        // Jika role tidak dikenali (jaga-jaga), logout paksa
        header("Location: logout.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal Pengajuan Dana LKPI</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --lkpi-green: #008a3d;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            z-index: 10;
        }

        .login-card {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 40px 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid rgba(255,255,255,0.5);
            backdrop-filter: blur(10px);
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo img {
            max-width: 160px;
            height: auto;
            mix-blend-mode: multiply; /* Menghilangkan background putih kusam jika ada */
        }

        .login-title {
            text-align: center;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 5px;
        }

        .login-subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 30px;
        }

        /* Styling Input Field Modern */
        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-right: none;
            color: #94a3b8;
        }

        .form-control {
            border-left: none;
            border-color: #cbd5e1;
            padding-left: 0;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #cbd5e1; /* Hindari glow biru bawaan bootstrap */
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--lkpi-green);
        }
        
        .input-group:focus-within .input-group-text {
            color: var(--lkpi-green);
        }

        .btn-toggle-pass {
            background: transparent;
            border: 1px solid #cbd5e1;
            border-left: none;
            color: #94a3b8;
            cursor: pointer;
            border-radius: 0 8px 8px 0;
            padding: 0 15px;
        }
        .btn-toggle-pass:hover { color: var(--text-main); }
        .input-group:focus-within .btn-toggle-pass { border-color: var(--lkpi-green); }

        .btn-login {
            background-color: var(--lkpi-green);
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: #007031;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 138, 61, 0.2);
        }

        .forgot-link {
            color: var(--lkpi-green);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .forgot-link:hover { color: #005a27; text-decoration: underline; }

        .notification-message {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
        }
        
        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: #16a34a;
            border-color: #bbf7d0;
        }

        .alert-info {
            background-color: #f8fafc;
            color: #334155;
            border-color: #cbd5e1;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        
        <div class="brand-logo">
            <!-- Logo menggunakan logoo.png sesuai permintaan -->
            <img src="logoCV.png" alt="Logo LKPI" onerror="this.style.display='none';">
        </div>

        <h2 class="login-title">Portal Pengajuan Dana</h2>
        <p class="login-subtitle">Silakan masuk dengan kredensial Anda</p>

        <?php
        // Menampilkan pesan error jika ada parameter error di URL (misal: index.php?error=1)
        if (isset($_GET['error'])) {
            $pesan = "Email atau Password tidak valid.";
            if ($_GET['error'] == 'empty') $pesan = "Harap isi semua kolom.";
            
            echo '<div class="notification-message alert-error"><i class="fas fa-circle-exclamation"></i> ' . $pesan . '</div>';
        }
        // Pesan jika berhasil ganti password (dari fitur reset atau ubah password)
        if (isset($_GET['reset'])) {
            echo '<div class="notification-message alert-success"><i class="fas fa-check-circle"></i> Password berhasil diubah. Silakan login.</div>';
        }
        // Pesan jika berhasil logout
        if (isset($_GET['status']) && $_GET['status'] == 'logout') {
            echo '<div class="notification-message alert-info"><i class="fas fa-info-circle text-primary"></i> Anda telah berhasil keluar dari sistem.</div>';
        }
        ?>

        <form action="proses_login.php" method="POST">
            
            <div class="mb-3">
                <label for="email" class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Alamat Email</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="far fa-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control rounded-end-2" required placeholder="nama@gmail.com" autofocus
                           oninvalid="this.setCustomValidity('Format email salah. Pastikan menggunakan tanda @')" 
                           oninput="this.setCustomValidity('')">
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label text-muted mb-0" style="font-size: 0.85rem; font-weight: 600;">Kata Sandi</label>
                    <a href="lupa_password.php" class="forgot-link">Lupa sandi?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
                    <button class="btn-toggle-pass" type="button" id="btnToggle" onclick="togglePassword()" title="Tampilkan/Sembunyikan Sandi">
                        <i class="far fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                Masuk ke Sistem <i class="fas fa-arrow-right-to-bracket ms-2"></i>
            </button>
            
        </form>
    </div>
    
    <div class="text-center mt-4 text-muted" style="font-size: 0.75rem;">
        &copy; <?php echo date('Y'); ?> CV. Tiga Likuid Plastindo.<br>All rights reserved.
    </div>
</div>

<script>
    // Fungsi untuk menampilkan/menyembunyikan teks password
    function togglePassword() {
        var inputPass = document.getElementById("password");
        var iconEye = document.getElementById("eyeIcon");
        
        if (inputPass.type === "password") {
            inputPass.type = "text";
            iconEye.classList.remove("fa-eye");
            iconEye.classList.add("fa-eye-slash");
        } else {
            inputPass.type = "password";
            iconEye.classList.remove("fa-eye-slash");
            iconEye.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>