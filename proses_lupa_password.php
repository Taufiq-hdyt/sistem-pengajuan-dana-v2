<?php
session_start();
// 1. Panggil koneksi database
require 'config/koneksi.php';

// 2. Panggil fungsi pengirim email (Brevo API, bukan SMTP/PHPMailer lagi)
require 'kirim_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // 3. Cek apakah email terdaftar di database
    $query = "SELECT id, nama FROM users WHERE email = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // 4 & 5. Buat kode OTP dan biarkan MySQL yang menghitung kedaluwarsa (15 menit dari sekarang)
        $otp = rand(100000, 999999);

        $update_query = "UPDATE users SET otp_code = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?";
        $update_stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $otp, $email);
        mysqli_stmt_execute($update_stmt);

        // 6. Siapkan konten email
        $subject = 'Kode OTP Reset Password';
        $body    = "
            <h3>Halo {$user['nama']},</h3>
            <p>Kami menerima permintaan untuk mereset password akun Anda.</p>
            <p>Berikut adalah kode OTP Anda:</p>
            <h2 style='color: #008a3d; letter-spacing: 5px;'>{$otp}</h2>
            <p>Kode ini hanya berlaku selama 15 menit. Jangan berikan kode ini kepada siapa pun.</p>
            <br>
            <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
        ";

        // 7. Kirim email lewat Brevo API (HTTPS), bukan SMTP
        $hasil_kirim = kirimEmailViaBrevo($email, $user['nama'], $subject, $body);

        if ($hasil_kirim['success']) {
            // 8. Simpan email ke session agar mudah dipanggil di halaman berikutnya
            $_SESSION['reset_email'] = $email;

            // Arahkan LANGSUNG ke halaman input OTP tanpa pop-up JS
            header("Location: reset_password.php");
            exit();
        } else {
            // Tetap pakai alert JS KHUSUS UNTUK ERROR MAILER agar gampang di-debug/dicek jika gagal
            $pesan_error = htmlspecialchars($hasil_kirim['message'], ENT_QUOTES);
            echo "<script>alert('Gagal mengirim email. Error: {$pesan_error}'); window.location.href='lupa_password.php';</script>";
            exit();
        }

    } else {
        // Jika email tidak ditemukan, lempar parameter 'not_found' ke halaman lupa password
        header("Location: lupa_password.php?status=not_found");
        exit();
    }
} else {
    header("Location: lupa_password.php");
    exit();
}