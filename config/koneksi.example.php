<?php
// SALIN file ini jadi "koneksi.php" di folder yang sama, lalu isi sesuai lingkungan
// (local / hosting). File "koneksi.php" TIDAK di-commit ke Git (lihat .gitignore).

// Konfigurasi Database
$host     = "localhost";
$username = "root";
$password = "";          // Isi sesuai password database di lingkungan ini
$database = "db_pengajuan_dana_v2";
$port     = 3306;        // Cek port yang dipakai MySQL di lingkungan ini (local: cek phpMyAdmin/XAMPP, hosting: tanya penyedia hosting)

// Mengaktifkan mode exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = mysqli_connect($host, $username, $password, $database, $port);
    mysqli_set_charset($koneksi, "utf8");
} catch (mysqli_sql_exception $e) {
    die("Gagal terhubung ke database: " . $e->getMessage());
}