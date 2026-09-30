<?php
// Mulai session agar kita bisa mengaksesnya
session_start();

// Kosongkan semua variabel session
$_SESSION = array();

// Hancurkan session secara keseluruhan
session_destroy();

// Redirect ke halaman login dengan membawa parameter 'status=logout'
// Parameter ini bisa ditangkap di index.php untuk menampilkan notifikasi "Anda berhasil keluar"
header("Location: index.php?status=logout");
exit();
?>