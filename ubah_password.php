<?php
session_start();
require 'config/koneksi.php'; // Pastikan path koneksi benar

// Proteksi: Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_user = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];

// Tentukan URL dashboard untuk tombol "Kembali" sesuai role
$url_dashboard = "";
switch ($role) {
    case 'admin': $url_dashboard = "admin/dashboard_admin.php"; break;
    case 'spv': $url_dashboard = "spv/dashboard_spv.php"; break;
    case 'manager': $url_dashboard = "manager/dashboard_manager.php"; break;
    case 'finance': $url_dashboard = "finance/dashboard_finance.php"; break;
    case 'dirut': $url_dashboard = "dirut/dashboard_dirut.php"; break;
    case 'dirkeu': $url_dashboard = "dirkeu/dashboard_dirkeu.php"; break;
    default: $url_dashboard = "index.php"; break;
}

// PROSES UBAH PASSWORD
if (isset($_POST['ubah_password'])) {
    $pass_lama = $_POST['pass_lama'];
    $pass_baru = $_POST['pass_baru'];
    $pass_konfirm = $_POST['pass_konfirm'];

    // 1. Ambil password hash saat ini dari database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // 2. Verifikasi kecocokan password lama
    if (password_verify($pass_lama, $row['password'])) {
        
        // 3. Cek apakah password baru & konfirmasi sama
        if ($pass_baru === $pass_konfirm) {
            
            // 4. Enkripsi password baru
            $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
            
            // 5. Update ke database
            $query_update = "UPDATE users SET password = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($koneksi, $query_update);
            mysqli_stmt_bind_param($stmt_update, "si", $hash_baru, $id_user);
            
            if (mysqli_stmt_execute($stmt_update)) {
                // Logout user untuk memaksa login dengan password baru
                $berhasil = true;
            } else {
                $error_msg = "Terjadi kesalahan pada sistem database.";
            }
        } else {
            $error_msg = "Password baru dan konfirmasi tidak cocok!";
        }
    } else {
        $error_msg = "Password lama yang Anda masukkan salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Kata Sandi - Portal Pengajuan Dana LKPI</title>
    
    <!-- Google Fonts & Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

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
            max-width: 450px;
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
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: var(--lkpi-green);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        
        <div class="text-center">
            <div class="user-badge">
                <i class="fas fa-user-circle me-2"></i> <?php echo htmlspecialchars($nama); ?>
            </div>
        </div>

        <h2 class="login-title">Perbarui Kata Sandi</h2>
        <p class="login-subtitle">Demi keamanan, silakan perbarui kata sandi Anda secara berkala.</p>

        <form action="" method="POST">
            
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Kata Sandi Saat Ini</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="fas fa-unlock-keyhole"></i></span>
                    <input type="password" id="pass_lama" name="pass_lama" class="form-control" required placeholder="Masukkan kata sandi lama">
                    <button class="btn-toggle-pass" type="button" onclick="togglePassword('pass_lama', 'eye1')" title="Tampilkan Sandi">
                        <i class="far fa-eye" id="eye1"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Kata Sandi Baru</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="fas fa-lock"></i></span>
                    <input type="password" id="pass_baru" name="pass_baru" class="form-control" required minlength="8" placeholder="Minimal 8 karakter">
                    <button class="btn-toggle-pass" type="button" onclick="togglePassword('pass_baru', 'eye2')" title="Tampilkan Sandi">
                        <i class="far fa-eye" id="eye2"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted" style="font-size: 0.85rem; font-weight: 600;">Konfirmasi Kata Sandi Baru</label>
                <div class="input-group">
                    <span class="input-group-text rounded-start-2"><i class="fas fa-check-circle"></i></span>
                    <input type="password" id="pass_konfirm" name="pass_konfirm" class="form-control" required minlength="8" placeholder="Ulangi kata sandi baru">
                    <button class="btn-toggle-pass" type="button" onclick="togglePassword('pass_konfirm', 'eye3')" title="Tampilkan Sandi">
                        <i class="far fa-eye" id="eye3"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="ubah_password" class="btn-submit">
                Simpan Perubahan <i class="fas fa-save ms-2"></i>
            </button>
            
        </form>

        <a href="<?php echo $url_dashboard; ?>" class="back-link">
            <i class="fas fa-arrow-left me-1"></i> Batal & Kembali ke Dashboard
        </a>
    </div>
    
    <div class="text-center mt-4 text-muted" style="font-size: 0.75rem;">
        &copy; <?php echo date('Y'); ?> PT. Likuid Pharmalab Indonesia.
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

<?php 
// Notifikasi Error
if(isset($error_msg)) { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Gagal!',
                text: '<?php echo $error_msg; ?>',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        });
    </script>
<?php } ?>

<?php 
// Notifikasi Sukses
if(isset($berhasil) && $berhasil) { ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Kata sandi Anda telah diperbarui. Silakan login kembali.',
                icon: 'success',
                confirmButtonColor: '#008a3d',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        });
    </script>
<?php } ?>

</body>
</html>