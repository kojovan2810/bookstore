<?php
require '../../Src/config.php'; // sesuaikan

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['kode'])) {
        echo "Kode pesanan tidak ditemukan";
        exit;
    }

    $kode = $_POST['kode'];

    $stmt = $conn->prepare(
        "DELETE FROM pesanan WHERE kode_pesanan = ?"
    );
    $stmt->bind_param("s", $kode);

    if ($stmt->execute()) {
        echo "Refund berhasil diselesaikan";
    } else {
        echo "Gagal menyelesaikan refund";
    }
}
