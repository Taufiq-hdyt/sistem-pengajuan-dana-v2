<?php
session_start();

// Jika sudah login, arahkan kembali sesuai role
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'spv') header("Location: spv/dashboard_spv.php");
    elseif ($role === 'manager') header("Location: manager/dashboard_manager.php");
    elseif ($role === 'finance') header("Location: finance/dashboard_finance.php");
    elseif ($role === 'dirut') header("Location: dirut/dashboard_dirut.php");
    elseif ($role === 'dirkeu') header("Location: dirkeu/dashboard_dirkeu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - Portal Pengajuan Dana LKPI</title>
    
    <!-- Google Fonts & Bootstrap -->
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
            margin-bottom: 25px;
        }

        .brand-logo img {
            max-width: 140px;
            height: auto;
            mix-blend-mode: multiply;
        }

        .login-title {
            text-align: center;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 8px;
        }

        .login-subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 30px;
            line-height: 1.5;
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
            border-color: #cbd5e1;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--lkpi-green);
        }
        
        .input-group:focus-within .input-group-text {
            color: var(--lkpi-green);
        }

        .btn-submit {
            background-color: var(--lkpi-green);
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #007031;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 138, 61, 0.2);
        }

        .back-link {
            display: inline-block;
            width: 100%;
            text-align: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 20px;
            transition: color 0.2s ease;
        }

        .back-link:hover { 
            color: var(--lkpi-green); 
        }

        .alert-box {
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        
        <div class="brand-logo">
            <img src="logoCV.png" alt="Logo LKPI" onerror="this.style.display='none';">
        </div>

        <h2 class="login-title">Lupa Kata Sandi?</h2>
        <p class="login-subtitle">Masukkan alamat email yang terdaftar. Kami akan mengirimkan instruksi untuk mengatur ulang kata sandi Anda.</p>

        <?php
        // Menampilkan pesan notifikasi berdasarkan parameter URL
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'not_found') {
                echo '<div class="alert-box alert-error"><i class="fas fa-circle-exclamation mt-1"></i> Email tidak ditemukan dalam sistem kami.</div>';
            } elseif ($_GET['status'] == 'success') {
                echo '<div class="alert-box alert-success"><i class="fas fa-check-circle mt-1"></i> Instruksi pemulihan kata sandi telah dikirim ke email Anda. Silakan cek kotak masuk/spam.</div>';
            }
        }
        ?>

        <!-- Form akan diarahkan ke proses_lupa_password.php (file terpisah untuk penanganan logika) -->
        <form action="proses_lupa_password.php" method="POST">
            
            <div class="mb-4">
                <label for="email" class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Alamat Email Terdaftar</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="far fa-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control rounded-end-2" required placeholder="nama@gmail.com" autofocus 
                           oninvalid="this.setCustomValidity('Format email salah. Pastikan menggunakan tanda @')" 
                           oninput="this.setCustomValidity('')">
                </div>
            </div>

            <button type="submit" name="submit_reset" class="btn-submit">
                Kirim Tautan Reset <i class="fas fa-paper-plane ms-2"></i>
            </button>
            
        </form>

        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke halaman masuk
        </a>
    </div>
    
    <div class="text-center mt-4 text-muted" style="font-size: 0.75rem;">
        &copy; <?php echo date('Y'); ?> CV. Tiga Likuid Plastindo.
    </div>
</div>

</body>
</html>