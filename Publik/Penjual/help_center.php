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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Help Center</title>
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

        /* Help Center Header */
        .help-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .help-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .help-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Search Box */
        .search-box {
            max-width: 600px;
            margin: 0 auto 40px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 18px 25px;
            padding-left: 60px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 5px 25px rgba(67, 97, 238, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.3rem;
        }

        /* Quick Links */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .quick-link-card {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .quick-link-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.15);
            border-color: var(--primary);
            color: var(--primary);
        }

        .quick-link-icon {
            width: 70px;
            height: 70px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: var(--primary);
            transition: all 0.3s;
        }

        .quick-link-card:hover .quick-link-icon {
            background-color: var(--primary);
            color: white;
        }

        .quick-link-card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--dark);
            transition: color 0.3s;
        }

        .quick-link-card:hover h3 {
            color: var(--primary);
        }

        .quick-link-card p {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* FAQ Section */
        .help-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 992px) {
            .help-content {
                grid-template-columns: 1fr;
            }
        }

        .help-section {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--light-gray);
        }

        .section-title-main {
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title-main i {
            color: var(--primary);
        }

        /* FAQ Items */
        .faq-item {
            margin-bottom: 15px;
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
        }

        .faq-question {
            padding: 20px;
            background: var(--light);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: var(--dark);
            transition: background 0.3s;
        }

        .faq-question:hover {
            background: #eef2ff;
        }

        .faq-question i {
            transition: transform 0.3s;
            color: var(--primary);
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            line-height: 1.6;
        }

        .faq-item.active .faq-answer {
            padding: 20px;
            max-height: 500px;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        /* Contact Methods */
        .contact-methods {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-method {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: var(--light);
            border-radius: 10px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .contact-method:hover {
            background: #eef2ff;
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .contact-details h4 {
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .contact-details p {
            color: var(--gray);
            margin-bottom: 4px;
            font-size: 0.95rem;
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
            
            .help-header h1 {
                font-size: 2rem;
            }
            
            .help-header p {
                font-size: 1rem;
            }
            
            .help-section {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 0 15px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .quick-links {
                grid-template-columns: 1fr;
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
            .help-header {
                padding: 30px 20px;
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
            <ul class="nav-links">
                <li>
                    <a href="help_center.php" class="nav-item active">
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
                     onclick="window.location.href='../Src/profile.php'">
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
                <i class="fas fa-question-circle"></i>Help Center
            </h1>
            <p class="welcome-message">
                Temukan solusi untuk masalah Anda atau hubungi tim dukungan kami. Kami siap membantu.
            </p>

            <!-- Help Center Header -->
            <div class="help-header">
                <h1><i class="fas fa-life-ring"></i> Pusat Bantuan BukuBook</h1>
                <p>Temukan solusi cepat untuk masalah Anda atau hubungi tim dukungan kami. Kami berkomitmen memberikan pelayanan terbaik untuk penjual.</p>
            </div>

            <!-- Search Box -->
            <!-- <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari solusi untuk masalah Anda...">
            </div> -->

            <!-- Quick Links -->
            <div class="quick-links">
                <div class="quick-link-card" data-category="pesanan">
                    <div class="quick-link-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Pesanan & Pengiriman</h3>
                    <p>Cara mengelola pesanan, input resi, dan masalah pengiriman</p>
                </div>
                
                <div class="quick-link-card" data-category="pembayaran">
                    <div class="quick-link-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Pembayaran & Refund</h3>
                    <p>Proses pembayaran, verifikasi, dan pengembalian dana</p>
                </div>
                
                <div class="quick-link-card" data-category="akun">
                    <div class="quick-link-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3>Akun & Keamanan</h3>
                    <p>Kelola akun, ganti password, dan pengaturan keamanan</p>
                </div>
                
                <div class="quick-link-card" data-category="produk">
                    <div class="quick-link-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Produk & Buku</h3>
                    <p>Tambah produk, kelola stok, dan informasi produk</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="help-content">
                <!-- FAQ Section -->
                <section class="help-section">
                    <h2 class="section-title-main"><i class="fas fa-question-circle"></i> Pertanyaan Umum (FAQ)</h2>
                    
                    <div class="faq-list">
                        <div class="faq-item active">
                            <div class="faq-question">
                                <span>Bagaimana cara menyetujui pesanan dari pembeli?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Untuk menyetujui pesanan, buka halaman <strong>Pesanan</strong>, temukan pesanan dengan status "Menunggu", lalu klik tombol <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(40, 167, 69, 0.1); color: #28a745;">Setujui</span>. Pastikan stok produk mencukupi sebelum menyetujui pesanan.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Bagaimana cara input nomor resi pengiriman?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Setelah menyetujui pesanan, klik tombol <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(23, 162, 184, 0.1); color: #17a2b8;">Input Resi</span> pada pesanan tersebut. Masukkan nomor resi yang diberikan oleh kurir, lalu simpan. Status pesanan akan otomatis berubah menjadi "Dikirim".</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Apa yang harus dilakukan jika stok tidak mencukupi?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Jika stok tidak mencukupi, Anda dapat menolak pesanan dengan menekan tombol <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">Tolak</span>. Sistem akan otomatis mengembalikan dana kepada pembeli. Pastikan untuk selalu memperbarui stok produk Anda secara berkala.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Bagaimana cara melihat bukti pembayaran dari pembeli?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Bukti pembayaran dapat dilihat dengan menekan tombol <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(23, 162, 184, 0.1); color: #17a2b8;">Bukti</span> pada tabel pesanan. Pastikan untuk memverifikasi bukti pembayaran sebelum menyetujui pesanan.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Berapa lama waktu untuk dana cair ke rekening penjual?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Dana akan dicairkan ke rekening Anda dalam waktu 1-3 hari kerja setelah pesanan dikonfirmasi sebagai "Selesai" oleh pembeli. Pastikan data rekening Anda sudah benar di halaman profil.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact Section -->
                <section class="help-section">
                    <h2 class="section-title-main"><i class="fas fa-headset"></i> Hubungi Kami</h2>
                    
                    <div class="contact-methods">
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Telepon & WhatsApp</h4>
                                <p>+62 812-3456-7890 (Customer Service)</p>
                                <p>+62 813-9876-5432 (Technical Support)</p>
                                <p>Senin - Jumat: 08:00 - 22:00 WIB</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Email</h4>
                                <p>support@bukubook.com</p>
                                <p>penjual@bukubook.com</p>
                                <p>Biasanya dibalas dalam 1-2 hari kerja</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Live Chat</h4>
                                <p>Chat langsung dengan customer service</p>
                                <p>Tersedia 08:00 - 22:00 WIB setiap hari</p>
                                <p>Waktu respons rata-rata: 5 menit</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Kantor Pusat</h4>
                                <p>Jl. Buku Ilmu No. 123, Jakarta Pusat</p>
                                <p>10110, Indonesia</p>
                                <p>Senin - Jumat: 09:00 - 17:00 WIB</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Help Center</span>
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

        // FAQ Toggle Functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                faqItem.classList.toggle('active');
            });
        });

        // Search Functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer p').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    // Open if contains search term
                    if (searchTerm && !item.classList.contains('active')) {
                        item.classList.add('active');
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Quick Links Filter
        document.querySelectorAll('.quick-link-card').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const category = this.getAttribute('data-category');
                const faqItems = document.querySelectorAll('.faq-item');
                
                // Close all FAQ items first
                faqItems.forEach(item => {
                    item.classList.remove('active');
                });
                
                // Filter by category
                const categoryTitles = {
                    'pesanan': [
                        'Bagaimana cara menyetujui pesanan dari pembeli?',
                        'Bagaimana cara input nomor resi pengiriman?',
                        'Apa yang harus dilakukan jika stok tidak mencukupi?'
                    ],
                    'pembayaran': [
                        'Bagaimana cara melihat bukti pembayaran dari pembeli?',
                        'Berapa lama waktu untuk dana cair ke rekening penjual?'
                    ],
                    'akun': [],
                    'produk': []
                };
                
                if (categoryTitles[category] && categoryTitles[category].length > 0) {
                    faqItems.forEach(item => {
                        const question = item.querySelector('.faq-question span').textContent;
                        if (categoryTitles[category].includes(question)) {
                            item.style.display = 'block';
                            item.classList.add('active');
                            // Scroll to FAQ section
                            document.querySelector('.help-section').scrollIntoView({ behavior: 'smooth' });
                        } else {
                            item.style.display = 'none';
                        }
                    });
                } else {
                    // Show all if no specific FAQ for category
                    faqItems.forEach(item => {
                        item.style.display = 'block';
                    });
                }
            });
        });

        // Auto-focus search input on page load
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                }, 500);
            }
            
            // Open first FAQ item by default
            const firstFaqItem = document.querySelector('.faq-item');
            if (firstFaqItem) {
                firstFaqItem.classList.add('active');
            }
        });
    </script>
</body>
</html>