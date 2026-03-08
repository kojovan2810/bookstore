<?php
session_start();
include "../../Src/config.php";

// Cek apakah penjual sudah login
if (!isset($_SESSION['email_penjual'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$email_penjual = $_SESSION['email_penjual'];
$chat_with = isset($_GET['chat_with']) ? $_GET['chat_with'] : '';

if ($chat_with) {
    // Hitung jumlah pesan baru
    $count_query = $conn->query("
        SELECT COUNT(*) as count FROM chat_messages 
        WHERE (sender_id = '$email_penjual' AND receiver_id = '$chat_with')
        OR (sender_id = '$chat_with' AND receiver_id = '$email_penjual')
    ");
    
    $result = $count_query->fetch_assoc();
    echo json_encode(['count' => $result['count']]);
} else {
    echo json_encode(['count' => 0]);
}
?>