<?php
session_start();
include "../src/config.php";

// --- Tentukan role (petugas / admin) ---
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
// --- Validasi File ---
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== 0) {
    echo "<script>alert('Gagal upload foto!'); window.history.back();</script>";
    exit;
}

$foto = $_FILES['foto'];

// --- Buat folder jika belum ada ---
$folder = "uploads/";
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

// --- Generate nama file unik ---
$ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
$namaFileBaru = "foto_" . time() . "_" . rand(100,999) . "." . $ext;

// --- Ambil foto lama untuk dihapus ---
$q = $conn->query("SELECT foto FROM $table WHERE $key='$id'");
$data = $q->fetch_assoc();
$foto_lama = $data['foto'];

if ($foto_lama && file_exists($folder . $foto_lama)) {
    unlink($folder . $foto_lama); // hapus foto lama
}

// --- Upload file baru ---
move_uploaded_file($foto['tmp_name'], $folder . $namaFileBaru);

// --- Update database ---
$update = $conn->query("UPDATE $table SET foto='$namaFileBaru' WHERE $key='$id'");

// --- Response ---
if ($update) {
    echo "<script>alert('Foto profil berhasil diperbarui!'); window.location.href='profile.php';</script>";
} else {
    echo "<script>alert('Gagal menyimpan foto!'); window.history.back();</script>";
}
?>
