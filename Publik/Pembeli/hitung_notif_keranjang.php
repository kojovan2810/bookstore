<?php
include "../../Src/config.php";
$email_pembeli = $_SESSION['email_pembeli'];
function getCartCount($conn, $email_pembeli) {
    $query = $conn->query("
        SELECT COUNT(k.id_buku) as total_qty 
        FROM keranjang k
        JOIN produk_buku pb ON k.id_buku = pb.id_buku
        WHERE k.email_pembeli = '$email_pembeli' AND pb.status = 'Aktif'
    ");
    
    if ($query->num_rows > 0) {
        $data = $query->fetch_assoc();
        return $data['total_qty'] ? $data['total_qty'] : 0;
    }
    return 0;
}
?>
<style>
    /* Cart Badge Notification */
.cart-badge {
    position: absolute;
    top: 5px;
    right: 20px;
    background-color: var(--danger);
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
    animation: pulse 2s infinite;
}

.nav-item {
    position: relative;
}

@keyframes pulse {
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

/* Topbar notification badge (alternatif) */
.topbar-cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: var(--danger);
    color: white;
    font-size: 10px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>