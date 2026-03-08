<?php
session_start();
include "config.php";

// --- Tentukan role (petugas/admin) ---
if (isset($_SESSION['email_admin'])) {
    $id   = $_SESSION['email_admin'];
    $table = "super_admin";
    $key = "email_admin";
    $type = "s"; // INTEGER

} elseif (isset($_SESSION['email_penjual'])) {
    $id   = $_SESSION['email_penjual'];
    $table = "penjual";
    $key = "email_penjual";
    $type = "s"; // STRING

}elseif (isset($_SESSION['email_pembeli'])) {
    $id   = $_SESSION['email_pembeli'];
    $table = "pembeli";
    $key = "email_pembeli";
    $type = "s"; // STRING

} else {
    echo json_encode(["status" => "error", "msg" => "Sesi tidak valid"]);
    exit;
}

// --- Validasi input ---
if (empty($_POST['current']) || empty($_POST['new'])) {
    echo json_encode(["status" => "error", "msg" => "Data tidak lengkap"]);
    exit;
}

$current = $_POST['current'];
$new     = $_POST['new'];

// Password minimal 8 karakter
if (strlen($new) < 8) {
    echo json_encode(["status" => "error", "msg" => "Password baru minimal 8 karakter"]);
    exit;
}

// --- Ambil password lama ---
$stmt = $conn->prepare("SELECT password FROM $table WHERE $key = ?");
$stmt->bind_param($type, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "msg" => "Akun tidak ditemukan"]);
    exit;
}

$data = $result->fetch_assoc();

// --- Cek password lama ---
if (!password_verify($current, $data['password'])) {
    echo json_encode(["status" => "error", "msg" => "Password saat ini salah"]);
    exit;
}

// --- Cek jika password lama == password baru ---
if ($current === $new) {
    echo json_encode(["status" => "error", "msg" => "Password baru tidak boleh sama dengan password lama"]);
    exit;
}

// --- Hash password baru & update ---
$newHash = password_hash($new, PASSWORD_DEFAULT);

$update_stmt = $conn->prepare("UPDATE $table SET password = ? WHERE $key = ?");
$update_stmt->bind_param("s".$type, $newHash, $id);

if ($update_stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "msg" => "Gagal memperbarui password"]);
}

$stmt->close();
$update_stmt->close();
$conn->close();
?>
