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

// Hitung statistik
$total_pesanan = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE email_pembeli = '$email_pembeli'")->fetch_assoc()['total'];
$total_belanja = $conn->query("SELECT SUM(total_harga) as total FROM pesanan WHERE email_pembeli = '$email_pembeli' AND approve = 'Disetujui'")->fetch_assoc()['total'] ?? 0;
$pesanan_aktif = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE email_pembeli = '$email_pembeli' AND approve = 'Disetujui' AND status != 'Selesai'")->fetch_assoc()['total'];

// Ambil produk untuk ditampilkan dengan JOIN untuk foto penjual
$produk_query = $conn->query("SELECT pb.*, p.foto as foto_penjual FROM produk_buku pb LEFT JOIN penjual p ON pb.email_penjual = p.email_penjual WHERE pb.status = 'Aktif' LIMIT 4");

// Simpan semua produk dalam array untuk modal
$all_products = [];
while ($produk = $produk_query->fetch_assoc()) {
    $all_products[] = $produk;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Dashboard Pembeli</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-1 .stat-icon {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .stat-2 .stat-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .stat-3 .stat-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .stat-4 .stat-icon {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .stat-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-content p {
            font-size: 15px;
            color: var(--gray);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .action-card:hover .action-icon {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
            transition: all 0.3s;
        }

        .action-card h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .action-card p {
            font-size: 13px;
            color: var(--gray);
            opacity: 0.8;
        }

        .action-card:hover p {
            color: rgba(255, 255, 255, 0.9);
        }

        /* Products Section */
        .products-section {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .section-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }

        .product-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 1px solid var(--light-gray);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 48px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .product-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.4;
            height: 45px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 10px;
        }

        .product-stock {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            justify-content: center;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }

        .btn-block {
            width: 100%;
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            animation: fadeIn 0.3s ease;
        }

        .modal-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 2001;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 25px 30px 15px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--gray);
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-content {
            padding: 30px;
        }

        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .detail-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 64px;
        }

        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .detail-info h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .detail-category {
            font-size: 14px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .detail-price {
            font-size: 28px;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 20px;
        }

        .detail-description {
            margin-bottom: 25px;
        }

        .detail-description h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .detail-description p {
            font-size: 15px;
            color: var(--gray);
            line-height: 1.6;
        }

        .detail-meta {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .meta-item:last-child {
            border-bottom: none;
        }

        .meta-label {
            font-weight: 600;
            color: var(--dark);
        }

        .meta-value {
            color: var(--gray);
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-actions .btn {
            flex: 1;
            padding: 12px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
            
            .section-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .product-detail-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .detail-image {
                height: 250px;
            }
            
            .modal-container {
                width: 95%;
                max-height: 85vh;
            }
        }

        @media (max-width: 480px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-actions {
                flex-direction: column;
            }
        }

        .menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
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
                    <a href="beranda.php" class="nav-item active">
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
                <a href="keranjang.php" class="nav-item">
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
                <!-- <li>
                    <a href="penjual_lain.php" class="nav-item">
                        <i class="fas fa-store"></i>
                        <span class="nav-text">Toko Penjual</span>
                    </a>
                </li> -->
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
            
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari buku, penulis, atau kategori...">
                </div>
            </div>

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
            <h1 class="page-title">Dashboard Pembeli</h1>
            <p class="welcome-message">
                Selamat datang kembali, <strong><?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?></strong>! Temukan buku menarik untuk dibaca hari ini.
            </p>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-1">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_pesanan; ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>
                
                <div class="stat-card stat-2">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rp <?php echo number_format($total_belanja, 0, ',', '.'); ?></h3>
                        <p>Total Belanja</p>
                    </div>
                </div>
                
                <div class="stat-card stat-3">
                    <div class="stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $pesanan_aktif; ?></h3>
                        <p>Pesanan Aktif</p>
                    </div>
                </div>
                
                <div class="stat-card stat-4">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $data_pembeli['rating'] ?? '0'; ?></h3>
                        <p>Rating Pembeli</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="produk.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h4>Belanja Buku</h4>
                    <p>Temukan buku menarik</p>
                </a>
                
                <a href="keranjang.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Keranjang Saya</h4>
                    <p>Lihat dan kelola keranjang</p>
                </a>
                
                <a href="pesanan.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h4>Pesanan Saya</h4>
                    <p>Lacak pesanan Anda</p>
                </a>
                
                <a href="riwayat.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h4>Riwayat Belanja</h4>
                    <p>Lihat riwayat transaksi</p>
                </a>
            </div>

            <!-- Produk Terbaru -->
            <div class="products-section">
                <div class="section-header">
                    <h3><i class="fas fa-book"></i> Produk Terbaru</h3>
                    <a href="produk.php" class="btn btn-primary">Lihat Semua</a>
                </div>
                
                <?php if (count($all_products) > 0): ?>
                    <div class="products-grid">
                        <?php foreach ($all_products as $index => $produk): 
                            $foto_produk = !empty($produk['foto']) ? "../../Src/uploads/produk/" . $produk['foto'] : "https://via.placeholder.com/300x200/cccccc/666666?text=Buku";
                        ?>
                            <div class="product-card" data-product-index="<?php echo $index; ?>">
                                <div class="product-image">
                                    <?php if (!empty($produk['foto'])): ?>
                                        <img src="<?php echo $foto_produk; ?>" 
                                             alt="<?php echo htmlspecialchars($produk['judul_buku']); ?>" 
                                             onerror="this.src='https://via.placeholder.com/300x200/cccccc/666666?text=Buku'">
                                    <?php else: ?>
                                        <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-category"><?php echo htmlspecialchars($produk['kategori_buku']); ?></div>
                                    <h3 class="product-title"><?php echo htmlspecialchars($produk['judul_buku']); ?></h3>
                                    <div class="product-price">Rp <?php echo number_format($produk['harga_buku'], 0, ',', '.'); ?></div>
                                    <div class="product-stock">Stok: <?php echo $produk['stok']; ?> pcs</div>
                                    <div class="product-actions">
                                        <button class="btn btn-primary" onclick="window.location.href='produk.php'">
                                            <i class="fas fa-cart-plus"></i> Belanja
                                        </button>
                                        <button class="btn btn-outline" onclick="showProductDetail(<?php echo $index; ?>)">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-book-open"></i>
                        <h4>Belum ada produk tersedia</h4>
                        <p>Tunggu penjual menambahkan produk baru</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Dashboard</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Product Detail Modal -->
    <div class="modal-overlay" id="productModalOverlay"></div>
    <div class="modal-container" id="productModal" style="display: none;">
        <div class="modal-header">
            <h2 class="modal-title">Detail Produk</h2>
            <button class="modal-close" onclick="closeProductModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-content" id="productModalContent">
            <!-- Content akan diisi oleh JavaScript -->
        </div>
    </div>

    <script>
        // Simpan semua produk dari PHP ke JavaScript
        const allProducts = <?php echo json_encode($all_products); ?>;
        
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Quick action cards animation
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
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

        // Product functions
        function showProductDetail(productIndex) {
            if (!allProducts[productIndex]) {
                alert('Produk tidak ditemukan');
                return;
            }
            
            const product = allProducts[productIndex];
            const modalContent = document.getElementById('productModalContent');
            
            let foto_produk = product.foto ? '../../Src/uploads/produk/' + product.foto : 'https://via.placeholder.com/300x200/cccccc/666666?text=Buku';
            let fotoPenjual = product.foto_penjual ? '../../Src/uploads/' + product.foto_penjual : `https://ui-avatars.com/api/?name=${encodeURIComponent(product.nama_penjual)}&background=4361ee&color=fff&size=120`;
            
            modalContent.innerHTML = `
                <div class="product-detail-grid">
                    <div class="detail-image-container">
                        <div class="detail-image">
                            <img src="${foto_produk}" 
                                 alt="${escapeHtml(product.judul_buku)}" 
                                 onerror="this.src='https://via.placeholder.com/300x200/cccccc/666666?text=Buku'">
                        </div>
                    </div>
                    <div class="detail-info">
                        <div class="detail-category">${escapeHtml(product.kategori_buku)}</div>
                        <h3>${escapeHtml(product.judul_buku)}</h3>
                        <div class="detail-price">Rp ${formatRupiah(product.harga_buku)}</div>
                        
                        <div class="detail-description">
                            <h4>Deskripsi</h4>
                            <p>${escapeHtml(product.deskripsi || 'Tidak ada deskripsi tersedia.')}</p>
                        </div>
                        
                        <div class="detail-meta">
                            <div class="meta-item">
                                <span class="meta-label">Penjual:</span>
                                <span class="meta-value">
                                    <img src="${fotoPenjual}" 
                                         alt="${escapeHtml(product.nama_penjual)}" 
                                         style="width: 24px; height: 24px; border-radius: 50%; vertical-align: middle; margin-right: 5px;">
                                    ${escapeHtml(product.nama_penjual)}
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Email Penjual:</span>
                                <span class="meta-value">${escapeHtml(product.email_penjual)}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Stok Tersedia:</span>
                                <span class="meta-value">${escapeHtml(product.stok)} pcs</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Modal:</span>
                                <span class="meta-value">Rp ${formatRupiah(product.modal)}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Status:</span>
                                <span class="meta-value">
                                    <span style="color: ${product.status === 'Aktif' ? '#28a745' : '#dc3545'}; font-weight: 600;">
                                        ${escapeHtml(product.status)}
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="modal-actions">
                            <button class="btn btn-primary" onclick="addToCartFromModal(${product.id_buku})">
                                <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                            </button>
                            <button class="btn btn-outline" onclick="closeProductModal()">
                                <i class="fas fa-times"></i> Tutup
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Show modal
            document.getElementById('productModalOverlay').style.display = 'block';
            document.getElementById('productModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeProductModal() {
            document.getElementById('productModalOverlay').style.display = 'none';
            document.getElementById('productModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        }

        // Close modal when clicking overlay
        document.getElementById('productModalOverlay').addEventListener('click', closeProductModal);

        // Prevent modal close when clicking inside modal
        document.getElementById('productModal').addEventListener('click', function(event) {
            event.stopPropagation();
        });

        function addToCart(productId, event) {
            if (event) event.stopPropagation();
            
            // AJAX request to add to cart
            fetch('../../Src/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_buku=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Produk berhasil ditambahkan ke keranjang!');
                } else {
                    alert('Gagal menambahkan ke keranjang: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan ke keranjang');
            });
        }

        function addToCartFromModal(productId) {
            addToCart(productId);
        }

        // Format number to Rupiah
        function formatRupiah(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Escape HTML untuk keamanan
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Product card hover effects
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>