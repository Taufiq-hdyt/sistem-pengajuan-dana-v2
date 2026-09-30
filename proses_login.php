<?php
// Mulai session
session_start();

// 1. Panggil file koneksi yang SUDAH BENAR
require 'config/koneksi.php';

// 2. Pastikan data dikirim melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Cek jika kosong
    if (empty($email) || empty($password)) {
        header("Location: index.php?error=empty");
        exit();
    }

    // 3. Query JOIN: Mengambil data user sekaligus mencocokkan ID Role dengan Nama Role
    $query = "SELECT users.id, users.nama, users.email, users.password, roles.nama_role AS role 
              FROM users 
              JOIN roles ON users.role_id = roles.id 
              WHERE users.email = ?";
              
    // Gunakan variabel $koneksi yang berasal dari file config/koneksi.php
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // 4. Cek apakah email ditemukan
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // 5. Verifikasi Password (menggunakan password_verify)
        if (password_verify($password, $user['password'])) {
            
            // Simpan data ke Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role']; // Berisi: admin, spv, finance, dll

            // 6. Arahkan pengguna berdasarkan Role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard_admin.php");
                    break;
                case 'spv':
                header("Location: spv/dashboard_spv.php");
                break;
            case 'manager':
                header("Location: manager/dashboard_manager.php");
                break;
            case 'finance':
                header("Location: finance/dashboard_finance.php");
                break;
                case 'finance':
                    header("Location: finance/dashboard_finance.php");
                    break;
                case 'dirut':
                    header("Location: dirut/dashboard_dirut.php");
                    break;
                case 'dirkeu':
                    header("Location: dirkeu/dashboard_dirkeu.php");
                    break;
                default:
                    // Role tidak dikenali
                    header("Location: index.php?error=role");
                    break;
            }
            exit();

        } else {
            // Jika password salah (Kirim parameter error ke index.php)
            header("Location: index.php?error=1");
            exit();
        }
    } else {
        // Jika email tidak ada di database (Kirim parameter error ke index.php)
        header("Location: index.php?error=1");
        exit();
    }

    // Tutup statement
    mysqli_stmt_close($stmt);
} else {
    // Akses tanpa POST dikembalikan ke halaman login
    header("Location: index.php");
    exit();
}
?>