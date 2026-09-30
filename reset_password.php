<?php
session_start();

// Aktifkan error reporting untuk debugging jika diperlukan
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/koneksi.php';

// Proteksi: Jika user belum mengisi form email sebelumnya, kembalikan ke halaman lupa password
if (!isset($_SESSION['reset_email'])) {
    header("Location: lupa_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// PROSES RESET PASSWORD
if (isset($_POST['submit_reset'])) {
    $otp_input = trim($_POST['otp']);
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    // 1. Cek apakah konfirmasi password cocok
    if ($password_baru !== $konfirmasi_password) {
        echo "<script>alert('Password baru dan konfirmasi tidak cocok!');</script>";
    } else {
        // 2. Ambil data OTP dan cek apakah belum kedaluwarsa berdasarkan database (NOW())
        $query = "SELECT otp_code FROM users WHERE email = ? AND otp_expiry >= NOW()";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        // 3. Validasi
        if ($user) {
            // Jika data ditemukan (artinya OTP belum kedaluwarsa), cek apakah angka OTP cocok
            if ($user['otp_code'] === $otp_input) {
                
                // OTP Valid! Enkripsi password baru
                $password_hashed = password_hash($password_baru, PASSWORD_DEFAULT);

                // 4. Update password di database dan bersihkan OTP
                $update_query = "UPDATE users SET password = ?, otp_code = NULL, otp_expiry = NULL WHERE email = ?";
                $update_stmt = mysqli_prepare($koneksi, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ss", $password_hashed, $email);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Hapus session email karena proses sudah selesai
                    unset($_SESSION['reset_email']); 
                    // Redirect dengan parameter sukses
                    echo "<script>alert('Password berhasil diubah! Silakan login dengan password baru.'); window.location.href='index.php?reset=success';</script>";
                    exit();
                } else {
                    echo "<script>alert('Terjadi kesalahan saat menyimpan password baru.');</script>";
                }

            } else {
                // Jika angka OTP yang diketik salah
                echo "<script>alert('Kode OTP yang Anda masukkan salah!');</script>";
            }
        } else {
            // Jika data tidak ditemukan, artinya waktu 15 menit sudah lewat atau tidak valid
            echo "<script>alert('Kode OTP sudah kedaluwarsa atau tidak valid! Silakan minta ulang kode.'); window.location.href='lupa_password.php';</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Sandi - Portal Pengajuan Dana LKPI</title>
    
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

        .btn-submit {
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
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 20px;
            transition: color 0.2s ease;
        }

        .back-link:hover { 
            color: var(--lkpi-green); 
            text-decoration: underline;
        }

        .otp-input {
            text-align: center;
            font-size: 1.2rem;
            letter-spacing: 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        
        <div class="brand-logo">
            <img src="pt_likuid_pharmalab_indonesia_logo.jpeg" alt="Logo LKPI" onerror="this.style.display='none';">
        </div>

        <h2 class="login-title">Buat Kata Sandi Baru</h2>
        <p class="login-subtitle">Masukkan kode OTP 6 digit yang dikirim ke email Anda beserta kata sandi baru.</p>

        <form action="" method="POST">
            
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Kode OTP</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="fas fa-key"></i></span>
                    <input type="text" name="otp" class="form-control rounded-end-2 otp-input" required placeholder="••••••" maxlength="6" autofocus
                           oninvalid="this.setCustomValidity('Harap masukkan 6 digit kode OTP')" 
                           oninput="this.setCustomValidity('')">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Kata Sandi Baru</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password_baru" name="password_baru" class="form-control" required placeholder="Minimal 8 karakter" minlength="8"
                           oninvalid="this.setCustomValidity('Harap masukkan kata sandi baru (minimal 8 karakter)')" 
                           oninput="this.setCustomValidity('')">
                    <button class="btn-toggle-pass" type="button" onclick="togglePassword('password_baru', 'eyeIcon1')" title="Tampilkan/Sembunyikan">
                        <i class="far fa-eye" id="eyeIcon1"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Konfirmasi Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="fas fa-check-circle"></i></span>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" required placeholder="Ulangi kata sandi" minlength="8"
                           oninvalid="this.setCustomValidity('Harap ulangi kata sandi baru Anda')" 
                           oninput="this.setCustomValidity('')">
                    <button class="btn-toggle-pass" type="button" onclick="togglePassword('konfirmasi_password', 'eyeIcon2')" title="Tampilkan/Sembunyikan">
                        <i class="far fa-eye" id="eyeIcon2"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="submit_reset" class="btn-submit">
                Simpan Kata Sandi <i class="fas fa-save ms-2"></i>
            </button>
            
        </form>

        <a href="lupa_password.php" class="back-link">
            &laquo; Kirim Ulang OTP
        </a>
    </div>
    
    <div class="text-center mt-4 text-muted" style="font-size: 0.75rem;">
        &copy; <?php echo date('Y'); ?> CV. Tiga Likuid Plastindo.
    </div>
</div>

<script>
    // Fungsi untuk menampilkan/menyembunyikan teks password
    function togglePassword(inputId, iconId) {
        var inputPass = document.getElementById(inputId);
        var iconEye = document.getElementById(iconId);
        
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