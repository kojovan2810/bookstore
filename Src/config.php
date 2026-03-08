<?php
$host = "localhost"; // Server database
$user = "root"; // Username database (default: root)
$pass = ""; // Password database (kosong jika di XAMPP)
$db   = "bookstore"; // Nama database

// Membuat koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
