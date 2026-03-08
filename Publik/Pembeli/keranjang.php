<?php
session_start();
include "../../Src/config.php";
include "hitung_notif_keranjang.php";
include "hitung_notif_chat.php"; // tambahkan ini untuk chat
$total_keranjang_notif = getCartCount($conn, $email_pembeli);
$total_chat_notif = getChatCount($conn, $email_pembeli); // tambahkan ini
$chat_notifications = getChatNotifications($conn, $email_pembeli); // detail notifikasi

// Cek apakah pembeli sudah login
if (!isset($_SESSION['email_pembeli'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

// Ambil data pembeli dari session
$email_pembeli = $_SESSION['email_pembeli'];
$data_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email_pembeli'")->fetch_assoc();

// PROSES HAPUS ITEM DARI KERANJANG
if (isset($_GET['hapus']) && !empty($_GET['hapus'])) {
    $id_keranjang = $_GET['hapus'];
    $conn->query("DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND email_pembeli = '$email_pembeli'");
    echo "<script>alert('Item berhasil dihapus dari keranjang');window.location.href='keranjang.php';</script>";
    exit();
}

// PROSES UPDATE QUANTITY
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $id_keranjang = $_POST['id_keranjang'];
    $quantity = intval($_POST['quantity']);
    
    // Validasi quantity
    $quantity = max(1, $quantity);
    
    // Ambil data keranjang dan cek stok
    $check_query = $conn->query("
        SELECT k.*, pb.stok 
        FROM keranjang k
        JOIN produk_buku pb ON k.id_buku = pb.id_buku
        WHERE k.id_keranjang = '$id_keranjang' 
        AND k.email_pembeli = '$email_pembeli'
    ");
    
    if ($check_query->num_rows > 0) {
        $data = $check_query->fetch_assoc();
        
        // Cek jika quantity melebihi stok
        if ($quantity > $data['stok']) {
            echo "<script>alert('Jumlah melebihi stok tersedia. Stok: {$data['stok']}');</script>";
        } else {
            // Hitung total harga baru
            $new_total = $data['harga'] * $quantity;
            
            // Update quantity dan total harga
            $update_query = "UPDATE keranjang 
                            SET qty = '$quantity', 
                                total_harga = '$new_total' 
                            WHERE id_keranjang = '$id_keranjang' 
                            AND email_pembeli = '$email_pembeli'";
            
            if ($conn->query($update_query)) {
                echo "<script>alert('Jumlah berhasil diperbarui');</script>";
            } else {
                echo "<script>alert('Gagal memperbarui jumlah');</script>";
            }
        }
    }
    
    // Redirect untuk menghindari resubmit
    echo "<script>window.location.href='keranjang.php';</script>";
    exit();
}

// Ambil data keranjang
$keranjang_query = $conn->query("
    SELECT k.*, pb.judul_buku, pb.harga_buku, pb.stok, pb.email_penjual, pb.nama_penjual, 
           pb.foto as foto_produk, pb.kategori_buku
    FROM keranjang k
    JOIN produk_buku pb ON k.id_buku = pb.id_buku
    WHERE k.email_pembeli = '$email_pembeli' AND pb.status = 'Aktif'
");

// Hitung total keranjang
$total_items = $keranjang_query->num_rows;
$total_harga = 0;
$cart_items = [];

while ($item = $keranjang_query->fetch_assoc()) {
    $subtotal = $item['harga'] * $item['qty'];
    $total_harga += $subtotal;
    $item['subtotal'] = $subtotal;
    $cart_items[] = $item;
}
// // Fungsi untuk menghitung total item di keranjang (untuk semua halaman)
// function hitungTotalItemKeranjang($conn, $email_pembeli) {
//     $query = $conn->query("
//         SELECT COUNT(k.id_buku) as total_qty 
//         FROM keranjang k
//         JOIN produk_buku pb ON k.id_buku = pb.id_buku
//         WHERE k.email_pembeli = '$email_pembeli' AND pb.status = 'Aktif'
//     ");
    
//     if ($query->num_rows > 0) {
//         $data = $query->fetch_assoc();
//         return $data['total_qty'] ? $data['total_qty'] : 0;
//     }
//     return 0;
// }

// // Hitung total item untuk notifikasi
// $total_keranjang_notif = hitungTotalItemKeranjang($conn, $email_pembeli);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Keranjang Belanja</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --sidebar-width: 260px;
            --topbar-height: 70px;
            --bottombar-height: 50px;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
        }

        .logo-container {
            padding: 0 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: #ffd166;
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.7);
            padding: 0 20px 10px;
        }

        .nav-links {
            list-style: none;
        }

        .nav-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            position: relative;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #ffd166;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        .nav-text {
            font-size: 15px;
            font-weight: 500;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Topbar Styles */
        .topbar {
            height: var(--topbar-height);
            background-color: white;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .search-container {
            flex: 1;
            max-width: 500px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .user-avatar:hover {
            transform: scale(1.05);
        }

        .user-details {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-role {
            font-size: 13px;
            color: var(--gray);
            margin-top: 2px;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .welcome-message {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 30px;
        }

        /* Cart Container */
        .cart-container {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 40px;
            color: var(--gray);
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .empty-cart i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-cart h4 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .empty-cart p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* Cart Table */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .cart-table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--light-gray);
        }

        .cart-table td {
            padding: 20px 15px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .cart-table tr:hover {
            background-color: #f8f9fa;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 24px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }

        .product-details h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .product-details .category {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
        }

        .product-details .seller {
            font-size: 14px;
            color: var(--gray);
            margin-top: 3px;
        }

        .price {
            font-size: 16px;
            font-weight: 600;
            color: var(--success);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            border: 1px solid var(--light-gray);
            background-color: white;
            color: var(--dark);
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .qty-btn:hover:not(:disabled) {
            background-color: var(--light-gray);
            border-color: var(--primary);
            color: var(--primary);
        }

        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .qty-input {
            width: 60px;
            height: 35px;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .qty-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .subtotal {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }

        .delete-btn {
            background: none;
            border: none;
            color: var(--danger);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            transform: scale(1.1);
        }
       

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Cart Summary */
        .cart-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-top: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 20px;
            color: var(--success);
        }

        .summary-label {
            font-weight: 600;
            color: var(--dark);
        }

        .summary-value {
            font-weight: 600;
            color: var(--gray);
        }

        .total-value {
            font-size: 20px;
            color: var(--success);
        }

        /* Checkout Button */
        .checkout-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            text-decoration: none;
        }

        .btn-outline {
            background-color: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
            border: 2px solid var(--success);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        /* Bottombar Styles */
        .bottombar {
            height: var(--bottombar-height);
            background-color: white;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--light-gray);
            color: var(--gray);
            font-size: 14px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .breadcrumb-separator {
            color: #adb5bd;
        }

        .breadcrumb-item {
            color: var(--gray);
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb-item:hover {
            color: var(--primary);
        }

        .breadcrumb-item.active {
            color: var(--primary);
            font-weight: 600;
        }

        .copyright {
            font-size: 13px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
                background: none;
                border: none;
                font-size: 22px;
                color: var(--primary);
                cursor: pointer;
                margin-right: 15px;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 0 15px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .cart-table {
                display: block;
                overflow-x: auto;
            }
            
            .cart-table th:nth-child(3),
            .cart-table td:nth-child(3) {
                min-width: 150px;
            }
            
            .cart-table th:nth-child(4),
            .cart-table td:nth-child(4),
            .cart-table th:nth-child(5),
            .cart-table td:nth-child(5),
            .cart-table th:nth-child(6),
            .cart-table td:nth-child(6) {
                min-width: 120px;
            }
            
            .checkout-section {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .checkout-section .btn {
                width: 100%;
                justify-content: center;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
        }

        @media (max-width: 480px) {
            .cart-table th,
            .cart-table td {
                padding: 12px 8px;
            }
            
            .product-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .product-image {
                width: 50px;
                height: 70px;
            }
            
            .quantity-control {
                flex-direction: column;
                align-items: center;
                gap: 5px;
            }
            
            .qty-input {
                width: 50px;
            }
        }

        .menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-book"></i>
                <span>BukuBook</span>
            </div>
        </div>

        <!-- Menu MAIN -->
        <div class="nav-section">
            <div class="section-title">MAIN</div>
            <ul class="nav-links">
                <li>
                    <a href="beranda.php" class="nav-item">
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Menu SHOPPING -->
        <div class="nav-section">
            <div class="section-title">SHOPPING</div>
            <ul class="nav-links">
                <li>
                    <a href="produk.php" class="nav-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="nav-text">Belanja</span>
                    </a>
                </li>
                <li>
                    <a href="keranjang.php" class="nav-item active">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="nav-text">Keranjang</span>
                        <?php if ($total_keranjang_notif > 0): ?>
                        <span class="cart-badge" id="cartBadge">
                            <?php echo ($total_keranjang_notif > 99) ? '99+' : $total_keranjang_notif; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="pesanan.php" class="nav-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="nav-text">Pesanan Saya</span>
                    </a>
                </li>
                <li>
                    <a href="riwayat.php" class="nav-item">
                        <i class="fas fa-history"></i>
                        <span class="nav-text">Riwayat Belanja</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Menu SOCIAL -->
        <div class="nav-section">
            <div class="section-title">SOCIAL</div>
            <ul class="nav-links">
                <li>
                <a href="room_chat.php" class="nav-item">
                <i class="fas fa-comment"></i>
                <span class="nav-text">Chat</span>
                <!-- Notifikasi Chat -->
                <?php if ($total_chat_notif > 0): ?>
                    <span class="chat-badge" id="chatBadge">
                        <?php echo ($total_chat_notif > 99) ? '99+' : $total_chat_notif; ?>
                    </span>
                <?php endif; ?>
            </a>
                </li>
            </ul>
        </div>

        <!-- Menu SUPPORT -->
        <div class="nav-section">
            <div class="section-title">SUPPORT</div>
            <ul class="nav-links">
                <li>
                    <a href="help_center.php" class="nav-item">
                        <i class="fas fa-question-circle"></i>
                        <span class="nav-text">Help Center</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Menu SETTING -->
        <div class="nav-section">
            <div class="section-title">SETTING</div>
            <ul class="nav-links">
                <li>
                    <a href="../../Src/profile.php" class="nav-item">
                        <i class="fas fa-user"></i>
                        <span class="nav-text">Profil Saya</span>
                    </a>
                </li>
                <li>
                    <a href="../../Src/logout.php" class="nav-item logout-link" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Log Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation Bar -->
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?></div>
                    <div class="user-role">Pembeli</div>
                </div>
                <?php 
                $foto = isset($data_pembeli['foto']) ? $data_pembeli['foto'] : null;
                $src = $foto ? "../../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data_pembeli['nama_pembeli']) . "&background=4361ee&color=fff&size=120";
                ?>
                <img src="<?php echo $src; ?>" 
                     alt="Profile" 
                     class="user-avatar"
                     onclick="window.location.href='../../Src/profile.php'">
            </div>
        </header>

        <!-- Content Area -->
        <main class="content">
            <h1 class="page-title">Keranjang Belanja</h1>
            <p class="welcome-message">
                Kelola produk dalam keranjang belanja Anda sebelum melakukan checkout.
            </p>

            <?php if ($total_items > 0): ?>
                <div class="cart-container">
                    <!-- Cart Table -->
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">PRODUK</th>
                                <th style="width: 15%;">HARGA</th>
                                <th style="width: 20%;">JUMLAH</th>
                                <th style="width: 15%;">SUBTOTAL</th>
                                <th style="width: 10%;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): 
                                $foto_produk = !empty($item['foto_produk']) ? "../../Src/uploads/produk/" . $item['foto_produk'] : "https://via.placeholder.com/60x80/cccccc/666666?text=Buku";
                            ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-image">
                                                <img src="<?php echo $foto_produk; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['judul_buku']); ?>"
                                                     onerror="this.src='https://via.placeholder.com/60x80/cccccc/666666?text=Buku'">
                                            </div>
                                            <div class="product-details">
                                                <h4><?php echo htmlspecialchars($item['judul_buku']); ?></h4>
                                                <div class="category"><?php echo htmlspecialchars($item['kategori_buku']); ?></div>
                                                <div class="seller">Penjual: <?php echo htmlspecialchars($item['nama_penjual']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="price">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></div>
                                    </td>
                                    <td>
                                        <form method="POST" action="" class="quantity-form" id="form-<?php echo $item['id_keranjang']; ?>">
                                            <input type="hidden" name="id_keranjang" value="<?php echo $item['id_keranjang']; ?>">
                                            <input type="hidden" name="update_quantity" value="1">
                                            <div class="quantity-control">
                                                <button type="button" 
                                                        class="qty-btn minus" 
                                                        onclick="updateQuantity(<?php echo $item['id_keranjang']; ?>, -1)"
                                                        <?php echo ($item['qty'] <= 1) ? 'disabled' : ''; ?>>
                                                    -
                                                </button>
                                                <input type="number" 
                                                       class="qty-input"
                                                       name="quantity"
                                                       id="qty-<?php echo $item['id_keranjang']; ?>"
                                                       value="<?php echo $item['qty']; ?>" 
                                                       min="1" 
                                                       max="<?php echo $item['stok']; ?>"
                                                       onchange="submitQuantityForm(<?php echo $item['id_keranjang']; ?>)">
                                                <button type="button" 
                                                        class="qty-btn plus" 
                                                        onclick="updateQuantity(<?php echo $item['id_keranjang']; ?>, 1)"
                                                        <?php echo ($item['qty'] >= $item['stok']) ? 'disabled' : ''; ?>>
                                                    +
                                                </button>
                                            </div>
                                        </form>
                                        <div style="font-size: 12px; color: var(--gray); margin-top: 5px;">
                                            Stok: <?php echo $item['stok']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="subtotal">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                                    </td>
                                    <td>
                                        <a href="keranjang.php?hapus=<?php echo $item['id_keranjang']; ?>" 
                                           class="delete-btn" 
                                           title="Hapus dari keranjang"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus item ini dari keranjang?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span class="summary-label">Total Items:</span>
                            <span class="summary-value"><?php echo $total_items; ?> item</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total Harga:</span>
                            <span class="summary-value">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <!-- Checkout Actions -->
                    <div class="checkout-section">
                        <a href="produk.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Lanjut Belanja
                        </a>
                        <a href="checkout.php" class="btn btn-success">
                            <i class="fas fa-arrow-right"></i> Lanjut ke Pembayaran
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Keranjang Belanja Kosong</h4>
                    <p>Tambahkan produk ke keranjang untuk mulai berbelanja.</p>
                    <a href="produk.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Mulai Belanja
                    </a>
                </div>
            <?php endif; ?>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Keranjang</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = 'produk.php?search=' + encodeURIComponent(searchTerm);
                }
            }
        });

        // Show loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Update quantity dengan button
        function updateQuantity(idKeranjang, change) {
            const qtyInput = document.getElementById('qty-' + idKeranjang);
            let currentQty = parseInt(qtyInput.value) || 1;
            let newQty = currentQty + change;
            
            if (newQty < 1) {
                alert('Jumlah minimal 1');
                return;
            }
            
            // Cek stok maksimum
            const maxStock = parseInt(qtyInput.max);
            if (newQty > maxStock) {
                alert('Jumlah melebihi stok tersedia. Stok: ' + maxStock);
                return;
            }
            
            // Update nilai input
            qtyInput.value = newQty;
            
            // Submit form
            submitQuantityForm(idKeranjang);
        }

        // Submit quantity form
        function submitQuantityForm(idKeranjang) {
            const form = document.getElementById('form-' + idKeranjang);
            const qtyInput = document.getElementById('qty-' + idKeranjang);
            const newQty = parseInt(qtyInput.value);
            
            // Validasi input
            if (isNaN(newQty) || newQty < 1) {
                alert('Jumlah minimal 1');
                qtyInput.value = 1;
                return;
            }
            
            // Cek stok
            const maxStock = parseInt(qtyInput.max);
            if (newQty > maxStock) {
                alert('Jumlah melebihi stok tersedia. Stok: ' + maxStock);
                qtyInput.value = maxStock;
                return;
            }
            
            // Tampilkan loading
            showLoading();
            
            // Submit form
            form.submit();
        }

        // Validasi input saat user mengetik langsung
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('input', function(e) {
                const value = this.value;
                // Hanya angka yang diperbolehkan
                if (!/^\d*$/.test(value)) {
                    this.value = this.value.replace(/[^\d]/g, '');
                }
            });
        });

        // Update tombol plus/minus status
        function updateButtonStatus(idKeranjang) {
            const qtyInput = document.getElementById('qty-' + idKeranjang);
            const currentQty = parseInt(qtyInput.value) || 1;
            const maxStock = parseInt(qtyInput.max);
            
            // Cari tombol di form yang sama
            const form = document.getElementById('form-' + idKeranjang);
            const minusBtn = form.querySelector('.minus');
            const plusBtn = form.querySelector('.plus');
            
            // Update status tombol
            if (minusBtn) minusBtn.disabled = currentQty <= 1;
            if (plusBtn) plusBtn.disabled = currentQty >= maxStock;
        }

        // Inisialisasi status tombol saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($cart_items as $item): ?>
                updateButtonStatus(<?php echo $item['id_keranjang']; ?>);
            <?php endforeach; ?>
        });
    </script>
</body>
</html>