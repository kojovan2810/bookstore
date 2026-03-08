<?php
$email_penjual = $_SESSION['email_penjual'];
// Fungsi untuk menghitung pesan belum dibaca untuk penjual
function getChatCountPenjual($conn, $email_penjual) {
    $query = $conn->query("
        SELECT COUNT(*) as total 
        FROM chat_messages 
        WHERE receiver_id = '$email_penjual' 
        AND is_read = 0
    ");
    
    if ($query && $query->num_rows > 0) {
        $data = $query->fetch_assoc();
        return $data['total'] ? (int)$data['total'] : 0;
    }
    return 0;
}

// Fungsi untuk mendapatkan detail notifikasi penjual
function getChatNotificationsPenjual($conn, $email_penjual) {
    // Query yang compatible dengan sql_mode=only_full_group_by
    $query = $conn->query("
        SELECT 
            sender_id,
            MAX(timestamp) as last_message_time,
            COUNT(*) as jumlah_pesan
        FROM chat_messages 
        WHERE receiver_id = '$email_penjual' 
        AND is_read = 0
        GROUP BY sender_id
        ORDER BY MAX(timestamp) DESC
        LIMIT 5
    ");
    
    $result = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            // Ambil nama pembeli secara terpisah
            $sender_id = $row['sender_id'];
            $pembeli_query = $conn->query("SELECT nama_pembeli FROM pembeli WHERE email_pembeli = '$sender_id'");
            
            $row['nama_pengirim'] = 'Pembeli';
            if ($pembeli_query && $pembeli_query->num_rows > 0) {
                $pembeli_data = $pembeli_query->fetch_assoc();
                $row['nama_pengirim'] = $pembeli_data['nama_pembeli'];
            }
            
            $result[] = $row;
        }
    }
    return $result;
}

// Fungsi untuk menghitung notifikasi pesanan (opsional)
function getOrderNotificationsPenjual($conn, $email_penjual) {
    // Sesuaikan dengan struktur tabel pesanan Anda
    $query = $conn->query("
        SELECT COUNT(*) as total 
        FROM pesanan 
        WHERE email_penjual = '$email_penjual' 
        AND status_pesanan IN ('Menunggu Konfirmasi', 'Diproses')
        AND notification_read = 0
    ");
    
    if ($query && $query->num_rows > 0) {
        $data = $query->fetch_assoc();
        return $data['total'] ? (int)$data['total'] : 0;
    }
    return 0;
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