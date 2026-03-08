<?php
session_start();
include "config.php";

if (isset($_SESSION['email_admin'])) {

    // PETUGAS
    $id = $_SESSION['email_admin'];
    $table = "super_admin";
    $key = "email_admin";

} elseif (isset($_SESSION['email_penjual'])) {

    // ADMIN
    $id = $_SESSION['email_penjual'];
    $table = "penjual";
    $key = "email_penjual";

}elseif (isset($_SESSION['email_pembeli'])) {

    // ADMIN
    $id = $_SESSION['email_pembeli'];
    $table = "pembeli";
    $key = "email_pembeli";

} else {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../login.php'</script>";
    exit();
}

// Ambil data foto lama
$q = $conn->query("SELECT foto FROM $table WHERE $key='$id'");
$data = $q->fetch_assoc();
$foto_lama = $data['foto'];

// Jika tidak ada foto
if (!$foto_lama) {
    echo "<script>alert('Tidak ada foto yang bisa dihapus!'); window.history.back();</script>";
    exit();
}

// Hapus file
$file_path = "uploads/" . $foto_lama;
if (file_exists($file_path)) {
    unlink($file_path);
}

// Update database
$conn->query("UPDATE $table SET foto=NULL WHERE $key='$id'");

// Selesai
echo "<script>alert('Foto berhasil dihapus!'); window.location.href='profile.php';</script>";
?>
