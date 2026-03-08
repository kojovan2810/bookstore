<?php
session_start();
include "../../Src/config.php";
include "hitung_notif_chat_penjual.php";
include "hitung_notif_pesanan.php";
// pesanan
$total_order_notif_penjual = getTotalOrderNotificationsPenjual($conn, $email_penjual);
$order_notifications_detail = getOrderNotificationsDetailPenjual($conn, $email_penjual);
// chat
$total_chat_notif_penjual = getChatCountPenjual($conn, $email_penjual);
// Cek apakah penjual sudah login
if (!isset($_SESSION['email_penjual'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

$email_penjual = $_SESSION['email_penjual'];
$data = $conn->query("SELECT * FROM penjual WHERE email_penjual = '$email_penjual'")->fetch_assoc();

if(!$data){
    header("Location: ../../login.php");
}

// Hitung statistik khusus penjual
$total_produk = $conn->query("SELECT COUNT(*) as total FROM produk_buku WHERE email_penjual = '$email_penjual'")->fetch_assoc()['total'];

// Untuk sementara, statistik pesanan dan pendapatan di-set ke 0 karena tabel pesanan belum ada
$total_pesanan = 0;
$total_pendapatan = 0;
$total_pendapatan_formatted = '0';

// Produk terbaru - berdasarkan email penjual saja (tanpa tanggal karena kolom tidak ada)
$produk_terbaru = $conn->query("SELECT * FROM produk_buku WHERE email_penjual = '$email_penjual' LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Dashboard Penjual</title>
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
        /* Penambahan Style Notifikasi Lonceng */
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            /* margin-left: 20rem; */
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            font-size: 20px;
            color: var(--gray);
            transition: color 0.3s;
        }

        .notification-bell:hover {
            color: var(--primary);
        }

        .bell-badge {
            position: absolute;
            top: -11px;
            right: -20px;
            background-color: var(--danger);
            color: white;
            font-size: 11px;
            padding: 1px 5px;
            border-radius: 50%;
            border: 2px solid white;
            font-weight: bold;
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

        /* Penjual Info Card */
        .penjual-info-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .penjual-info-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .penjual-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--light-gray);
        }

        .penjual-details h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .penjual-details p {
            color: var(--gray);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .penjual-details .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .penjual-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
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

        /* Produk Terbaru */
        .recent-products {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }

        .recent-products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .recent-products-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th {
            text-align: left;
            padding: 12px 15px;
            background-color: #f8f9fa;
            color: var(--gray);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            border-bottom: 2px solid var(--light-gray);
        }

        .products-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .products-table tr:last-child td {
            border-bottom: none;
        }

        .products-table tr:hover {
            background-color: #f8f9fa;
        }

        .product-name {
            font-weight: 500;
            color: var(--dark);
        }

        .product-price {
            font-weight: 600;
            color: var(--success);
        }

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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
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
            
            .penjual-info-header {
                flex-direction: column;
                text-align: center;
            }
            
            .penjual-info-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
            
            .recent-products-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .products-table {
                display: block;
                overflow-x: auto;
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
            
            .penjual-info-grid {
                grid-template-columns: 1fr;
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

        <!-- Menu MANAGEMENT -->
        <div class="nav-section">
            <div class="section-title">MANAGEMENT</div>
            <ul class="nav-links">
                <li>
                    <a href="produk.php" class="nav-item">
                        <i class="fas fa-box"></i>
                        <span class="nav-text">Produk</span>
                    </a>
                </li>
                <li>
                <a href="pesanan.php" class="nav-item order-nav">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="nav-text">Pesanan</span>
                        <?php if ($total_order_notif_penjual > 0): ?>
                        <span class="order-badge-penjual combo" id="orderBadgePenjual" title="<?php echo $total_order_notif_penjual; ?> pesanan perlu ditindak">
                            <?php echo ($total_order_notif_penjual > 99) ? '99+' : $total_order_notif_penjual; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="laporan.php" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Laporan</span>
                    </a>
                </li>
                <li>
                <a href="room_chat_penjual.php" class="nav-item">
                    <i class="fas fa-comment"></i>
                    <span class="nav-text">Chat</span>
                    <?php if ($total_chat_notif_penjual > 0): ?>
                        <span class="chat-badge" id="chatBadgePenjual" title="<?php echo $total_chat_notif_penjual; ?> pesan belum dibaca">
                            <?php echo ($total_chat_notif_penjual > 99) ? '99+' : $total_chat_notif_penjual; ?>
                        </span>
                    <?php endif; ?>
                </a>
                </li>
            </ul>
        </div>
        <div class="nav-section">
            <div class="section-title">NETWORK</div>
            <ul class="nav-links">
                <li>
                    <a href="penjual_lain.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Penjual Lain</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Menu SUPPORT -->
        <div class="nav-section">
            <div class="section-title">SUPPORT</div>
            <!-- <ul class="nav-links">
                <li>
                    <a href="admin.php" class="nav-item">
                        <i class="fas fa-user-cog"></i>
                        <span class="nav-text">Admin</span>
                    </a>
                </li>
            </ul> -->
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
            <div class="user-name"><?php echo htmlspecialchars($data['nama_penjual']); ?></div>
            <div class="user-role">Penjual</div>
        </div>
        <?php 
        $foto = isset($data['foto']) ? $data['foto'] : null;
        $src = $foto ? "../../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data['nama_penjual']) . "&background=4361ee&color=fff&size=120";
        ?>
        <img src="<?php echo $src; ?>" 
             alt="Profile" 
             class="user-avatar"
             onclick="window.location.href='../../Src/profile.php'">
    </div>
    <div class="topbar-actions">
        <?php 
        // Menghitung total gabungan chat dan pesanan
        $total_gabungan_notif = ($total_order_notif_penjual ?? 0) + ($total_chat_notif_penjual ?? 0);
        ?>
        <div class="notification-bell" onclick="window.location.href='pesanan.php'" title="Notifikasi Baru">
            <i class="fas fa-bell"></i>
            <?php if ($total_gabungan_notif > 0): ?>
                <span class="bell-badge">
                    <?php echo ($total_gabungan_notif > 99) ? '99+' : $total_gabungan_notif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</header>

        <!-- Content Area -->
        <main class="content">
            <h1 class="page-title">Dashboard Penjual</h1>
            <p class="welcome-message">
                Selamat datang kembali, <strong><?php echo htmlspecialchars($data['nama_penjual']); ?></strong>! Lihat statistik dan aktivitas toko buku Anda.
            </p>

            <!-- Penjual Information -->
            <div class="penjual-info-card">
                <div class="penjual-info-header">
                    <?php 
                    $avatarSrc = $foto ? "../../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data['nama_penjual']) . "&background=4361ee&color=fff&size=300";
                    ?>
                    <img src="<?php echo $avatarSrc; ?>" alt="Penjual Avatar" class="penjual-avatar">
                    <div class="penjual-details">
                        <h3><?php echo htmlspecialchars($data['nama_penjual']); ?></h3>
                        <p><?php echo htmlspecialchars($data['email_penjual']); ?></p>
                        <p><?php echo htmlspecialchars($data['alamat_penjual'] ?? 'Alamat belum diisi'); ?></p>
                        <span class="role-badge">Penjual</span>
                    </div>
                </div>
                <div class="penjual-info-grid">
                    <div class="info-item">
                        <div class="info-label">Status Toko</div>
                        <div class="info-value" style="color: var(--success);">Aktif</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Bergabung Sejak</div>
                        <div class="info-value"><?php echo date('d F Y', strtotime($data['tanggal_daftar'] ?? date('Y-m-d'))); ?></div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-1">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_produk; ?></h3>
                        <p>Total Produk</p>
                    </div>
                </div>
                
                <div class="stat-card stat-2">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_pesanan; ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>
                
                <div class="stat-card stat-3">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rp <?php echo $total_pendapatan_formatted; ?></h3>
                        <p>Total Pendapatan</p>
                    </div>
                </div>
                
                <div class="stat-card stat-4">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $data['rating'] ?? '0'; ?></h3>
                        <p>Rating Toko</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="produk.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h4>Kelola Produk</h4>
                    <p>Tambah, edit atau hapus produk</p>
                </a>
                
                <a href="pesanan.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Lihat Pesanan</h4>
                    <p>Kelola pesanan dari pembeli</p>
                </a>
                
                <a href="laporan.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4>Laporan Penjualan</h4>
                    <p>Analisis penjualan dan statistik</p>
                </a>
                
                <a href="../Src/profile.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h4>Profil Toko</h4>
                    <p>Ubah informasi toko dan profil</p>
                </a>
            </div>

            <!-- Produk Saya -->
            <div class="recent-products">
                <div class="recent-products-header">
                    <h3><i class="fas fa-book me-2"></i>Produk Saya</h3>
                    <a href="produk.php" class="btn btn-primary btn-sm">Lihat Semua</a>
                </div>
                
                <?php if ($produk_terbaru && $produk_terbaru->num_rows > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Judul Buku</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Penjual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($produk = $produk_terbaru->fetch_assoc()): ?>
                                <tr>
                                    <td class="product-name"><?php echo htmlspecialchars($produk['judul_buku']); ?></td>
                                    <td><?php echo htmlspecialchars($produk['kategori_buku']); ?></td>
                                    <td class="product-price">Rp <?php echo number_format($produk['harga_buku'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($produk['nama_penjual']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-box-open"></i>
                        <h4>Belum ada produk</h4>
                        <p>Mulai tambahkan produk pertama Anda</p>
                        <a href="produk.php?action=tambah" class="btn btn-primary mt-3">Tambah Produk</a>
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

    <script>
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
        document.querySelector('.search-box input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    alert('Fitur pencarian untuk: ' + searchTerm);
                    // Implement your search logic here
                    // window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
                }
            }
        });
    </script>
</body>
</html>