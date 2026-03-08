<?php
session_start();
include "../Src/config.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['email_admin'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../login.php'</script>";
    exit();
}

$email_admin = $_SESSION['email_admin'];
$data = $conn->query("SELECT * FROM super_admin WHERE email_admin = '$email_admin'")->fetch_assoc();

// Hitung statistik
$total_categories = $conn->query("SELECT COUNT(*) as total FROM kategori_produk")->fetch_assoc()['total'];
$total_buyers = $conn->query("SELECT COUNT(*) as total FROM pembeli")->fetch_assoc()['total'];
$total_sellers = $conn->query("SELECT COUNT(*) as total FROM penjual")->fetch_assoc()['total'];
// $total_books = $conn->query("SELECT COUNT(*) as total FROM produk")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Dashboard Super Admin</title>
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

        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: 8px;
            right: 20px;
            background-color: #ff4757;
            color: white;
            font-size: 12px;
            font-weight: 600;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Admin Info Card */
        .admin-info-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .admin-info-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .admin-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--light-gray);
        }

        .admin-details h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .admin-details p {
            color: var(--gray);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .admin-details .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .admin-info-grid {
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
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
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
            
            .admin-info-header {
                flex-direction: column;
                text-align: center;
            }
            
            .admin-info-grid {
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
            
            .admin-info-grid {
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
                <!-- <li>
                    <a href="../Src/profile.php" class="nav-item">
                        <i class="fas fa-user"></i>
                        <span class="nav-text">My Profile</span>
                    </a>
                </li> -->
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
            
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Type here to search books, authors, categories...">
                </div>
            </div>

            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($data['nama_admin']); ?></div>
                    <div class="user-role">Super Admin</div>
                </div>
                <?php 
                $foto = isset($data['foto']) ? $data['foto'] : null;
                $src = $foto ? "../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data['nama_admin']) . "&background=4361ee&color=fff&size=120";
                ?>
                <img src="<?php echo $src; ?>" 
                     alt="Profile" 
                     class="user-avatar"
                     onclick="window.location.href='../Src/profile.php'">
            </div>
        </header>

        <!-- Content Area -->
        <main class="content">
            <h1 class="page-title">Dashboard Super Admin</h1>
            <p class="welcome-message">
                Welcome back, <strong><?php echo htmlspecialchars($data['nama_admin']); ?></strong>! Here's what's happening with your bookstore today.
            </p>

            <!-- Admin Information -->
            <div class="admin-info-card">
                <div class="admin-info-header">
                    <?php 
                    $avatarSrc = $foto ? "../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data['nama_admin']) . "&background=4361ee&color=fff&size=300";
                    ?>
                    <img src="<?php echo $avatarSrc; ?>" alt="Admin Avatar" class="admin-avatar">
                    <div class="admin-details">
                        <h3><?php echo htmlspecialchars($data['nama_admin']); ?></h3>
                        <p><?php echo htmlspecialchars($data['email_admin']); ?></p>
                        <span class="role-badge">Super Administrator</span>
                    </div>
                </div>
                <div class="admin-info-grid">
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value" style="color: var(--success);">Active</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Permissions</div>
                        <div class="info-value">Full Access</div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-1">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_categories; ?></h3>
                        <p>Categories</p>
                    </div>
                </div>
                
                <div class="stat-card stat-2">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_buyers; ?></h3>
                        <p>Total Buyers</p>
                    </div>
                </div>
                
                <div class="stat-card stat-3">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_sellers; ?></h3>
                        <p>Active Sellers</p>
                    </div>
                </div>
                
                
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="kategori.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h4>Manage Categories</h4>
                    <p>Add, edit or remove book categories</p>
                </a>
                
                <a href="akun_pembeli.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>View Buyers</h4>
                    <p>Manage buyer accounts and orders</p>
                </a>
                
                <a href="akun_penjual_lain.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h4>Manage Sellers</h4>
                    <p>Approve and manage seller accounts</p>
                </a>
                
                <a href="help_center.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h4>Help Center</h4>
                    <p>Get support and documentation</p>
                </a>
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
                &copy; <?php echo date('Y'); ?> BukuBook. All rights reserved.
            </div>
        </footer>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Update current time
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const dateString = now.toLocaleDateString('id-ID', options);
            const timeString = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            // You can add this to the page if you want to display it
            // document.getElementById('currentDate').textContent = dateString;
            // document.getElementById('currentTime').textContent = timeString;
        }

        // Update time every minute
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Quick action cards animation
        document.querySelectorAll('.action-card').forEach(card => {
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