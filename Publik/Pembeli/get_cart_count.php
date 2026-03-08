<?php
session_start();
include "../../Src/config.php";

if (!isset($_SESSION['email_pembeli'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$email_pembeli = $_SESSION['email_pembeli'];

$query = $conn->query("
    SELECT SUM(qty) as total_qty 
    FROM keranjang k
    JOIN produk_buku pb ON k.id_buku = pb.id_buku
    WHERE k.email_pembeli = '$email_pembeli' AND pb.status = 'Aktif'
");

if ($query->num_rows > 0) {
    $data = $query->fetch_assoc();
    $count = $data['total_qty'] ? $data['total_qty'] : 0;
} else {
    $count = 0;
}

echo json_encode(['count' => $count]);
?>