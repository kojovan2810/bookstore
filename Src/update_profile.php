<?php
session_start();
include "config.php";

// --- Tentukan role (petugas / admin) ---
if (isset($_SESSION['email_admin'])) {
    $id = $_SESSION['email_admin'];
    $table = "super_admin";
    $key = "email_admin";
    $username = "nama_admin";

} elseif (isset($_SESSION['email_penjual'])) {
    $id = $_SESSION['email_penjual'];
    $table = "penjual";
    $key = "email_penjual";
    $username = "nama_penjual";

}elseif (isset($_SESSION['email_pembeli'])) {
    $id = $_SESSION['email_pembeli'];
    $table = "pembeli";
    $key = "email_pembeli";
    $username = "nama_pembeli";

} else {
    echo json_encode(["status" => "error", "msg" => "Not logged in"]);
    exit();
}

// --- Ambil data dari POST ---
$name  = $_POST['name'];
$email = $_POST['email'];

// --- Update data ---
$update = $conn->query("UPDATE $table SET $username ='$name', $key='$email' WHERE $key='$id'");

// --- Response ---
if ($update) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "msg" => $conn->error]);
}
?>
