<?php
// Fungsi untuk menghitung pesan belum dibaca
function getChatCount($conn, $email_pembeli) {
    $query = $conn->query("
        SELECT COUNT(*) as total 
        FROM chat_messages 
        WHERE receiver_id = '$email_pembeli' 
        AND is_read = 0
    ");
    
    if ($query && $query->num_rows > 0) {
        $data = $query->fetch_assoc();
        return $data['total'] ? (int)$data['total'] : 0;
    }
    return 0;
}

// Fungsi sederhana tanpa detail (untuk menghindari error group by)
function getChatNotifications($conn, $email_pembeli) {
    // Return array kosong untuk sementara
    // Atau hapus fungsi ini jika tidak diperlukan
    return [];
}
?>
<style>
    /* ===== NOTIFIKASI KERANJANG ===== */
.chat-badge {
    position: absolute;
    top: 5px;
    right: 20px;
    background-color: #dc3545;
    color: white;
    font-size: 11px;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    animation: cartPulse 2s infinite;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

@keyframes cartPulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    70% {
        transform: scale(1.05);
        box-shadow: 0 0 0 5px rgba(220, 53, 69, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
    }
}

.chat-badge.small {
    font-size: 9px;
    min-width: 15px;
    height: 15px;
    top: 2px;
    right: 15px;
}

/* Badge untuk topbar/mobile */
.topbar-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #dc3545;
    color: white;
    font-size: 10px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>