<?php
session_start();
include "../Src/config.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['email_admin'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../login.php'</script>";
    exit();
}

$email_admin = $_SESSION['email_admin'];
$data = $conn->query("SELECT * FROM super_admin WHERE email_admin= '$email_admin'")->fetch_assoc();
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
                    <a href="kategori.php" class="nav-item">
                        <i class="fas fa-tags"></i>
                        <span class="nav-text">Categories</span>
                    </a>
                </li>
                <li>
                    <a href="akun_pembeli.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Pembeli</span>
                    </a>
                </li>
                <li>
                    <a href="akun_penjual.php" class="nav-item">
                        <i class="fas fa-user-cog"></i>
                        <span class="nav-text">Penjual</span>
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
                    <a href="../Src/profile.php" class="nav-item">
                        <i class="fas fa-user"></i>
                        <span class="nav-text">Profil Saya</span>
                    </a>
                </li>
                <li>
                    <a href="../Src/logout.php" class="nav-item logout-link" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
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
                    <div class="user-name"><?= htmlspecialchars($_SESSION['nama_admin'] ?? 'Admin'); ?></div>
                    <div class="user-role">Super Admin</div>
                </div>
                <?php 
                $foto = $data['foto'] ?? null;
                $username = $_SESSION['nama_admin'] ?? 'Admin';
                $src = $foto ? "../Src/Uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=4361ee&color=fff&size=120";
                ?>
                <img src="<?= $src; ?>" 
                     alt="Profile" 
                     class="user-avatar"
                     onclick="window.location.href='../Src/profile.php'">
            </div>
        </header>

        <!-- Content Area -->
        <main class="content">
            <h1 class="page-title">
                <i class="fas fa-question-circle"></i>Help Center
            </h1>
            <p class="welcome-message">
                Temukan panduan penggunaan sistem dan solusi untuk masalah Anda.
            </p>

            <!-- Help Center Header -->
            <div class="help-header">
                <h1><i class="fas fa-life-ring"></i> Pusat Bantuan Super Admin</h1>
                <p>Temukan panduan lengkap untuk mengelola sistem BukuBook. Kami berkomitmen memberikan dukungan terbaik untuk administrator.</p>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari panduan atau solusi...">
            </div>

            <!-- Quick Links -->
            <div class="quick-links">
                <div class="quick-link-card" data-category="kategori">
                    <div class="quick-link-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Manajemen Kategori</h3>
                    <p>Cara mengelola kategori buku dan produk</p>
                </div>
                
                <div class="quick-link-card" data-category="pembeli">
                    <div class="quick-link-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Manajemen Pembeli</h3>
                    <p>Kelola data pembeli dan aktivitas transaksi</p>
                </div>
                
                <div class="quick-link-card" data-category="penjual">
                    <div class="quick-link-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3>Manajemen Penjual</h3>
                    <p>Verifikasi dan kelola akun penjual</p>
                </div>
                
                <div class="quick-link-card" data-category="sistem">
                    <div class="quick-link-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3>Pengaturan Sistem</h3>
                    <p>Konfigurasi sistem dan pengaturan umum</p>
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
                                <span>Bagaimana cara menambahkan kategori buku baru?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Untuk menambahkan kategori baru:</p>
                                <ol>
                                    <li>Buka halaman <strong>Categories</strong> dari menu Management</li>
                                    <li>Klik tombol <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(67, 97, 238, 0.1); color: var(--primary);">Add Category</span></li>
                                    <li>Isi nama kategori dan deskripsi</li>
                                    <li>Klik <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(40, 167, 69, 0.1); color: var(--success);">Save Category</span> untuk menyimpan</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Bagaimana cara mengelola data pembeli?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Manajemen data pembeli tersedia di halaman <strong>Pembeli</strong>:</p>
                                <ul>
                                    <li>Lihat semua pembeli dalam tabel</li>
                                    <li>Gunakan fitur pencarian untuk mencari pembeli spesifik</li>
                                    <li>Edit informasi pembeli dengan klik tombol edit</li>
                                    <li>Nonaktifkan akun jika diperlukan</li>
                                    <li>Lihat riwayat transaksi pembeli</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Bagaimana cara memverifikasi akun penjual baru?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Proses verifikasi penjual:</p>
                                <ol>
                                    <li>Buka halaman <strong>Penjual</strong></li>
                                    <li>Lihat daftar penjual yang menunggu verifikasi</li>
                                    <li>Periksa dokumen yang diunggah (KTP, SIUP, dll)</li>
                                    <li>Klik tombol <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(40, 167, 69, 0.1); color: var(--success);">Verify</span> untuk menyetujui</li>
                                    <li>Atau klik <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; background-color: rgba(220, 53, 69, 0.1); color: var(--danger);">Reject</span> untuk menolak dengan alasan</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Bagaimana cara melihat laporan transaksi?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Laporan transaksi tersedia di Dashboard:</p>
                                <ul>
                                    <li>Lihat statistik penjualan harian, mingguan, bulanan</li>
                                    <li>Ekspor laporan ke format PDF atau Excel</li>
                                    <li>Filter laporan berdasarkan tanggal atau kategori</li>
                                    <li>Akses riwayat transaksi lengkap</li>
                                    <li>Monitor performa penjual terbaik</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">
                                <span>Bagaimana cara reset password pengguna?</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Untuk reset password pengguna:</p>
                                <ol>
                                    <li>Temukan pengguna di halaman Pembeli atau Penjual</li>
                                    <li>Klik tombol edit pada baris pengguna tersebut</li>
                                    <li>Pilih opsi "Reset Password"</li>
                                    <li>Sistem akan mengirim email reset password ke pengguna</li>
                                    <li>Password sementara akan di-generate otomatis</li>
                                </ol>
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
                                <p>+62 812-3456-7890 (Admin Support)</p>
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
                                <p>admin@bukubook.com</p>
                                <p>support@bukubook.com</p>
                                <p>Biasanya dibalas dalam 1-2 jam kerja</p>
                            </div>
                        </div>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Live Chat</h4>
                                <p>Chat langsung dengan admin support</p>
                                <p>Tersedia 08:00 - 22:00 WIB setiap hari</p>
                                <p>Waktu respons rata-rata: 3 menit</p>
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
            const searchTerm = this.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
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
                    'kategori': [
                        'Bagaimana cara menambahkan kategori buku baru?'
                    ],
                    'pembeli': [
                        'Bagaimana cara mengelola data pembeli?'
                    ],
                    'penjual': [
                        'Bagaimana cara memverifikasi akun penjual baru?'
                    ],
                    'sistem': [
                        'Bagaimana cara melihat laporan transaksi?',
                        'Bagaimana cara reset password pengguna?'
                    ]
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