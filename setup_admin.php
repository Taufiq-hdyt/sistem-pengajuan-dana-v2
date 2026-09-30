<?php
// Tampilkan pesan error untuk memudahkan debugging jika terjadi kesalahan
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/koneksi.php';

echo "<div style='font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; background: #f9f9f9;'>";
echo "<h2 style='color: #008a3d; text-align: center;'>Alat Setup Super Admin</h2>";
echo "<hr>";

// 1. Pastikan role 'admin' ada di tabel roles
$query_role = mysqli_query($koneksi, "SELECT id FROM roles WHERE nama_role = 'admin'");
if (mysqli_num_rows($query_role) == 0) {
    mysqli_query($koneksi, "INSERT INTO roles (nama_role) VALUES ('admin')");
    $role_id = mysqli_insert_id($koneksi);
    echo "<p style='color: green;'>&#10004; Role 'admin' berhasil ditambahkan ke database.</p>";
} else {
    $role_id = mysqli_fetch_assoc($query_role)['id'];
    echo "<p style='color: blue;'>&#8505; Role 'admin' sudah ada di database (ID: $role_id).</p>";
}

// 2. Konfigurasi Akun Default
$email_admin = 'admin@likuid.co.id';
$password_plain = 'admin12345';
// Enkripsi password dengan standar yang dipakai di proses_login.php
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// 3. Cek apakah user admin sudah ada, lalu Insert/Update
$query_user = mysqli_query($koneksi, "SELECT id FROM users WHERE email = '$email_admin'");
if (mysqli_num_rows($query_user) > 0) {
    // Jika email sudah ada, kita UPDATE saja password dan role-nya agar pasti benar
    $update = mysqli_query($koneksi, "UPDATE users SET password = '$password_hashed', role_id = $role_id WHERE email = '$email_admin'");
    if($update) {
        echo "<p style='color: green;'>&#10004; Akun admin ditemukan. Password berhasil di-reset ulang dan dienkripsi!</p>";
    } else {
        echo "<p style='color: red;'>&#10006; Gagal mengupdate akun admin: " . mysqli_error($koneksi) . "</p>";
    }
} else {
    // Jika belum ada, kita INSERT baru
    $insert = mysqli_query($koneksi, "INSERT INTO users (nama, email, password, role_id) VALUES ('Super Administrator', '$email_admin', '$password_hashed', $role_id)");
    if($insert) {
        echo "<p style='color: green;'>&#10004; Akun admin baru berhasil dibuat!</p>";
    } else {
        echo "<p style='color: red;'>&#10006; Gagal membuat akun admin: " . mysqli_error($koneksi) . "</p>";
    }
}

echo "<div style='background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #008a3d; margin-top: 20px;'>";
echo "<h4 style='margin-top: 0;'>Gunakan Kredensial Berikut Untuk Login:</h4>";
echo "<b>Email:</b> " . $email_admin . "<br>";
echo "<b>Password:</b> " . $password_plain . "<br>";
echo "</div>";

echo "<div style='margin-top: 25px; text-align: center;'>";
echo "<a href='index.php' style='background: #008a3d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Coba Login Sekarang</a>";
echo "<p style='color: red; font-size: 12px; margin-top: 15px;'><b>PENTING:</b> Hapus file <code>setup_admin.php</code> ini setelah Anda berhasil login demi keamanan!</p>";
echo "</div>";
echo "</div>";
?>