<?php
// Fungsi untuk menghitung pesanan belum diproses untuk penjual
$email_penjual = $_SESSION['email_penjual'];
function getOrderCountPenjual($conn, $email_penjual) {
    $query = $conn->query("
        SELECT COUNT(*) as total 
        FROM pesanan 
        WHERE email_penjual = '$email_penjual' 
        AND approve IS NULL
    ");
    
    if ($query && $query->num_rows > 0) {
        $data = $query->fetch_assoc();
        return $data['total'] ? (int)$data['total'] : 0;
    }
    return 0;
}

// Fungsi untuk menghitung pesanan yang sudah disetujui tapi belum dikirim
function getOrderToShipCountPenjual($conn, $email_penjual) {
    $query = $conn->query("
        SELECT COUNT(*) as total 
        FROM pesanan 
        WHERE email_penjual = '$email_penjual' 
        AND approve = 'Disetujui' 
        AND (status IS NULL OR status = '')
    ");
    
    if ($query && $query->num_rows > 0) {
        $data = $query->fetch_assoc();
        return $data['total'] ? (int)$data['total'] : 0;
    }
    return 0;
}

// Fungsi untuk total semua notifikasi pesanan (belum diproses + belum dikirim)
function getTotalOrderNotificationsPenjual($conn, $email_penjual) {
    $pending = getOrderCountPenjual($conn, $email_penjual);
    $to_ship = getOrderToShipCountPenjual($conn, $email_penjual);
    return $pending + $to_ship;
}

// Fungsi untuk mendapatkan detail pesanan pending
function getOrderNotificationsDetailPenjual($conn, $email_penjual) {
    $query = $conn->query("
        SELECT 
            kode_pesanan,
            judul_buku,
            qty,
            nama_pembeli,
            tanggal_pesanan
        FROM pesanan 
        WHERE email_penjual = '$email_penjual' 
        AND approve IS NULL
        ORDER BY tanggal_pesanan DESC
        LIMIT 5
    ");
    
    $result = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $result[] = $row;
        }
    }
    return $result;
}

// Fungsi untuk menandai pesanan sebagai dibaca (opsional)
function markOrderAsReadPenjual($conn, $email_penjual, $kode_pesanan = null) {
    if ($kode_pesanan) {
        $sql = "UPDATE pesanan SET notification_read = 1 
                WHERE email_penjual = '$email_penjual' 
                AND kode_pesanan = '$kode_pesanan'";
    } else {
        $sql = "UPDATE pesanan SET notification_read = 1 
                WHERE email_penjual = '$email_penjual'";
    }
    
    return $conn->query($sql);
}
?>
<style>
    /* ===== BADGE NOTIFIKASI PESANAN PENJUAL ===== */

/* Badge untuk pesanan */
.order-badge-penjual {
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

.order-badge-penjual.small {
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