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
$data_penjual = $conn->query("SELECT * FROM penjual WHERE email_penjual = '$email_penjual'")->fetch_assoc();
if(!$data_penjual){
    header("Location: ../../login.php");
}
// Ambil semua data penjual (kecuali penjual yang sedang login)
$penjual_list = $conn->query("SELECT * FROM penjual WHERE email_penjual != '$email_penjual' ORDER BY nama_penjual");

// Hitung total penjual lain
$total_penjual_lain = $penjual_list->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Daftar Penjual</title>
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
            margin-left: 54rem;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-message {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 30px;
        }

        /* Stats Card */
        .stats-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stats-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stats-content p {
            font-size: 15px;
            color: var(--gray);
        }

        /* Search Bar */
        .search-container {
            margin-bottom: 25px;
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
            background-color: white;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
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

        /* Penjual Grid */
        .penjual-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .penjual-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .penjual-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .penjual-header {
            padding: 25px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            position: relative;
        }

        .penjual-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .penjual-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .penjual-email {
            font-size: 14px;
            opacity: 0.9;
            word-break: break-all;
        }

        .penjual-body {
            padding: 25px;
        }

        .info-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: var(--dark);
            word-break: break-word;
        }

        .info-value.nik {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid var(--light-gray);
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .no-data i {
            font-size: 60px;
            color: var(--light-gray);
            margin-bottom: 20px;
        }

        .no-data h4 {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .no-data p {
            color: var(--gray);
            max-width: 400px;
            margin: 0 auto;
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
            
            .penjual-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
            .penjual-grid {
                grid-template-columns: 1fr;
            }
            
            .penjual-card {
                margin-bottom: 20px;
            }
        }

        .menu-toggle {
            display: none;
        }

        /* Modal Detail Penjual */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin: 0 auto 20px;
            display: block;
        }

        .modal-details {
            margin-top: 20px;
        }

        .modal-detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .modal-detail-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .modal-detail-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .modal-detail-value {
            font-size: 15px;
            font-weight: 500;
            color: var(--dark);
            word-break: break-word;
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
                    <a href="beranda.php" class="nav-item">
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

        <!-- Menu NETWORK -->
        <div class="nav-section">
            <div class="section-title">NETWORK</div>
            <ul class="nav-links">
                <li>
                    <a href="penjual_lain.php" class="nav-item active">
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
                    <div class="user-name"><?php echo htmlspecialchars($data_penjual['nama_penjual']); ?></div>
                    <div class="user-role">Penjual</div>
                </div>
                <?php 
                $foto = isset($data_penjual['foto']) ? $data_penjual['foto'] : null;
                $src = $foto ? "../../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data_penjual['nama_penjual']) . "&background=4361ee&color=fff&size=120";
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
            <h1 class="page-title">
                <i class="fas fa-users"></i>Daftar Penjual
            </h1>
            <p class="welcome-message">
                Lihat dan jelajahi penjual lain di platform BukuBook. Temukan kolega dan perluas jaringan Anda.
            </p>

            <!-- Stats Card -->
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_penjual_lain; ?></h3>
                    <p>Total Penjual Lain</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari penjual berdasarkan nama atau email...">
                </div>
            </div>

            <!-- Penjual Grid -->
            <div class="penjual-grid" id="penjualGrid">
                <?php if ($penjual_list->num_rows > 0): ?>
                    <?php while ($penjual = $penjual_list->fetch_assoc()): ?>
                        <?php 
                        // Ambil foto penjual
                        $foto_penjual = isset($penjual['foto']) ? $penjual['foto'] : null;
                        $src_foto = $foto_penjual ? "../../Src/uploads/$foto_penjual" : "https://ui-avatars.com/api/?name=" . urlencode($penjual['nama_penjual']) . "&background=4361ee&color=fff&size=120";
                        
                        // Ambil jumlah produk penjual ini
                        $produk_count = $conn->query("SELECT COUNT(*) as total FROM produk_buku WHERE email_penjual = '{$penjual['email_penjual']}'")->fetch_assoc()['total'];
                        ?>
                        <div class="penjual-card" data-nama="<?php echo htmlspecialchars(strtolower($penjual['nama_penjual'])); ?>" data-email="<?php echo htmlspecialchars(strtolower($penjual['email_penjual'])); ?>">
                            <div class="penjual-header">
                                <img src="<?php echo $src_foto; ?>" alt="<?php echo htmlspecialchars($penjual['nama_penjual']); ?>" class="penjual-avatar">
                                <h3 class="penjual-name"><?php echo htmlspecialchars($penjual['nama_penjual']); ?></h3>
                                <p class="penjual-email"><?php echo htmlspecialchars($penjual['email_penjual']); ?></p>
                            </div>
                            <div class="penjual-body">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">NIK Penjual</div>
                                        <div class="info-value nik"><?php echo htmlspecialchars($penjual['nik_penjual']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Alamat</div>
                                        <div class="info-value"><?php echo htmlspecialchars($penjual['alamat_penjual']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Total Produk</div>
                                        <div class="info-value"><?php echo $produk_count; ?> produk</div>
                                    </div>
                                </div>
                                
                                <button class="btn-view-detail" style="
                                    width: 100%;
                                    padding: 10px;
                                    background-color: var(--primary);
                                    color: white;
                                    border: none;
                                    border-radius: 8px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    transition: background-color 0.3s;
                                    margin-top: 15px;
                                " onclick="showPenjualDetail(
                                    '<?php echo addslashes($penjual['nama_penjual']); ?>',
                                    '<?php echo addslashes($penjual['email_penjual']); ?>',
                                    '<?php echo addslashes($penjual['nik_penjual']); ?>',
                                    '<?php echo addslashes($penjual['alamat_penjual']); ?>',
                                    '<?php echo addslashes($src_foto); ?>',
                                    <?php echo $produk_count; ?>
                                )">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users-slash"></i>
                        <h4>Tidak ada penjual lain</h4>
                        <p>Saat ini hanya Anda yang terdaftar sebagai penjual di platform ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Penjual Lain</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Modal Detail Penjual -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Detail Penjual</h3>
                <button class="modal-close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <img id="modalPenjualFoto" src="" alt="Foto Penjual" class="modal-avatar">
                <div class="modal-details">
                    <div class="modal-detail-item">
                        <div class="modal-detail-label">Nama Penjual</div>
                        <div class="modal-detail-value" id="modalNama"></div>
                    </div>
                    <div class="modal-detail-item">
                        <div class="modal-detail-label">Email</div>
                        <div class="modal-detail-value" id="modalEmail"></div>
                    </div>
                    <div class="modal-detail-item">
                        <div class="modal-detail-label">NIK Penjual</div>
                        <div class="modal-detail-value" id="modalNik"></div>
                    </div>
                    <div class="modal-detail-item">
                        <div class="modal-detail-label">Alamat</div>
                        <div class="modal-detail-value" id="modalAlamat"></div>
                    </div>
                    <div class="modal-detail-item">
                        <div class="modal-detail-label">Total Produk</div>
                        <div class="modal-detail-value" id="modalProduk"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.penjual-card');
            
            cards.forEach(card => {
                const nama = card.getAttribute('data-nama');
                const email = card.getAttribute('data-email');
                
                if (nama.includes(searchTerm) || email.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Show penjual detail
        function showPenjualDetail(nama, email, nik, alamat, foto, produkCount) {
            document.getElementById('modalPenjualFoto').src = foto;
            document.getElementById('modalNama').textContent = nama;
            document.getElementById('modalEmail').textContent = email;
            document.getElementById('modalNik').textContent = nik;
            document.getElementById('modalAlamat').textContent = alamat;
            document.getElementById('modalProduk').textContent = produkCount + ' produk';
            
            openModal('detailModal');
        }

        // Close modal when clicking outside
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal('detailModal');
            }
        });
    </script>
</body>
</html>