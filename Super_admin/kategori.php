<?php
session_start();
include "../Src/config.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['email_admin'])) {
    header("Location: ../login.php");
    exit();
}

$email_admin = $_SESSION['email_admin'];
$data = $conn->query("SELECT * FROM super_admin WHERE email_admin = '$email_admin'")->fetch_assoc();

// Handle tambah kategori dengan cek duplikat
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    // Validasi input
    if (empty($nama_kategori)) {
        echo "<script>alert('Nama kategori tidak boleh kosong!');</script>";
    } else {
        // Cek apakah kategori sudah ada (case-insensitive)
        $cek = $conn->query("SELECT * FROM kategori_produk WHERE LOWER(nama_kategori) = LOWER('$nama_kategori')");
        
        if ($cek->num_rows > 0) {
            echo "<script>
                    alert('Kategori \"$nama_kategori\" sudah ada!');
                    window.history.back();
                  </script>";
        } else {
            $query = "INSERT INTO kategori_produk (nama_kategori, deskripsi) VALUES ('$nama_kategori', '$deskripsi')";
            
            if ($conn->query($query)) {
                echo "<script>
                        alert('Kategori berhasil ditambahkan!');
                        window.location.href = 'kategori.php';
                      </script>";
            } else {
                echo "<script>alert('Gagal menambahkan kategori!');</script>";
            }
        }
    }
}

// Handle edit kategori dengan cek duplikat
if (isset($_POST['edit_kategori'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    // Validasi input
    if (empty($nama_kategori)) {
        echo "<script>alert('Nama kategori tidak boleh kosong!');</script>";
    } else {
        // Cek apakah kategori digunakan oleh produk
        $cek_produk = $conn->query("SELECT * FROM produk_buku WHERE kategori_buku IN (SELECT nama_kategori FROM kategori_produk WHERE id = '$id')");
        
        if ($cek_produk->num_rows > 0) {
            echo "<script>
                    alert('Tidak dapat mengedit kategori karena sedang digunakan oleh produk!');
                    window.location.href = 'kategori.php';
                  </script>";
        } else {
            // Cek apakah nama kategori sudah ada (kecuali untuk kategori yang sedang diedit)
            $cek_duplikat = $conn->query("SELECT * FROM kategori_produk WHERE LOWER(nama_kategori) = LOWER('$nama_kategori') AND id != '$id'");
            
            if ($cek_duplikat->num_rows > 0) {
                echo "<script>
                        alert('Kategori \"$nama_kategori\" sudah ada!');
                        window.history.back();
                      </script>";
            } else {
                $query = "UPDATE kategori_produk SET 
                nama_kategori = '$nama_kategori',
                deskripsi = '$deskripsi'
                WHERE id = '$id'";
                
                if ($conn->query($query)) {
                    echo "<script>
                            alert('Kategori berhasil diupdate!');
                            window.location.href = 'kategori.php';
                          </script>";
                } else {
                    echo "<script>alert('Gagal mengupdate kategori!');</script>";
                }
            }
        }
    }
}

// Handle hapus kategori
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Cek apakah kategori digunakan di produk
    $cek_produk = $conn->query("SELECT * FROM produk_buku WHERE kategori_buku IN (SELECT nama_kategori FROM kategori_produk WHERE id = '$id')");
    
    if ($cek_produk->num_rows > 0) {
        echo "<script>
                alert('Tidak dapat menghapus kategori karena sedang digunakan oleh produk!');
                window.location.href = 'kategori.php';
              </script>";
    } else {
        $query = "DELETE FROM kategori_produk WHERE id = '$id'";
        
        if ($conn->query($query)) {
            echo "<script>
                    alert('Kategori berhasil dihapus!');
                    window.location.href = 'kategori.php';
                  </script>";
        } else {
            echo "<script>alert('Gagal menghapus kategori!');</script>";
        }
    }
}

// Ambil data kategori untuk edit (jika ada parameter edit)
$kategori_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = mysqli_real_escape_string($conn, $_GET['edit']);
    $kategori_edit = $conn->query("SELECT * FROM kategori_produk WHERE id = '$id_edit'")->fetch_assoc();
}

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = $search ? "WHERE nama_kategori LIKE '%$search%' OR deskripsi LIKE '%$search%'" : '';

// Hitung total data
$total_query = $conn->query("SELECT COUNT(*) as total FROM kategori_produk $where_clause");
$total_row = $total_query->fetch_assoc();
$total_data = $total_row['total'];

// Konfigurasi pagination
$limit = 5; // Maksimal 5 data per halaman
$total_pages = ceil($total_data / $limit);

// Tentukan halaman saat ini
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Hitung offset
$offset = ($page - 1) * $limit;

// Query data dengan pagination
$query_kategori = "SELECT * FROM kategori_produk $where_clause ORDER BY nama_kategori LIMIT $limit OFFSET $offset";
$cek = $conn->query($query_kategori);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Categories Management</title>
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
            --warning: #ffc107;
            --danger: #dc3545;
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

        .page-subtitle {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 30px;
        }

        /* Categories Header */
        .categories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .add-category-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .add-category-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid var(--light-gray);
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }

        td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--light-gray);
            font-size: 15px;
        }

        tbody tr {
            transition: background-color 0.3s;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .category-name {
            font-weight: 600;
            color: var(--dark);
        }

        .category-description {
            color: var(--gray);
            max-width: 300px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .btn-edit:hover {
            background-color: rgba(40, 167, 69, 0.2);
        }

        .btn-delete {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .btn-delete:hover {
            background-color: rgba(220, 53, 69, 0.2);
        }

        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
        }

        .pagination {
            display: flex;
            list-style: none;
            gap: 8px;
        }

        .page-item {
            margin: 0 2px;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: white;
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            border: 1px solid var(--light-gray);
            transition: all 0.3s;
        }

        .page-link:hover {
            background-color: #f8f9fa;
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-item.disabled .page-link {
            color: var(--gray);
            cursor: not-allowed;
            background-color: #f8f9fa;
        }

        .page-item.disabled .page-link:hover {
            border-color: var(--light-gray);
            color: var(--gray);
            background-color: #f8f9fa;
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
            
            .user-details {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 0 15px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .categories-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 700px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-overlay.active {
            display: flex;
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
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--dark);
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .menu-toggle {
            display: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state-icon {
            font-size: 60px;
            color: var(--light-gray);
            margin-bottom: 20px;
        }

        .empty-state-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .empty-state-description {
            color: var(--gray);
            max-width: 400px;
            margin: 0 auto 20px;
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
                    <a href="kategori.php" class="nav-item active">
                        <i class="fas fa-tags"></i>
                        <span class="nav-text">Categories</span>
                        <!-- <span class="notification-badge"><?php echo $total_data; ?></span> -->
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
        <div class="section-title">SETTING</div>
        <div class="nav-logout">
            <ul class="nav-links">
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
            
            <!-- Search Form -->
            <div class="search-container">
                <form action="" method="GET" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Type here to search categories..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
            </div>

            <!-- User info -->
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?= $_SESSION['nama_admin'] ?? '' ?></div>
                    <div class="user-role">Super admin</div>
                </div>
                <?php 
                $username = $data['nama_admin'] ?? 'Admin';
                $foto = $data['foto'] ?? null;
                $src = $foto ? "../Src/Uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=4361ee&color=fff&size=120";
                ?>
                <img src="<?php echo $src; ?>" 
                     alt="Profile" 
                     class="user-avatar"
                     id="profileDropdown"
                     onclick="window.location.href='../Src/profile.php'">
            </div>
        </header>

        <!-- Content Area -->
        <main class="content">
            <h1 class="page-title">Categories Management</h1>
            <p class="page-subtitle">Book Categories<br>Manage and organize your book collection by categories</p>

            <!-- Categories Header -->
            <div class="categories-header">
                <button class="add-category-btn" onclick="openModal('tambahModal')">
                    <i class="fas fa-plus"></i>
                    Add Category
                </button>
            </div>

            <!-- Categories Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($cek->num_rows > 0) {
                            $no = $offset + 1;
                            while($row = $cek->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $no . "</td>";
                                echo "<td class='category-name'>" . htmlspecialchars($row['nama_kategori']) . "</td>";
                                echo "<td class='category-description'>" . htmlspecialchars($row['deskripsi']) . "</td>";
                                echo "<td>";
                                echo "<div class='action-buttons'>";
                                echo "<button class='btn-edit' onclick=\"openModal('editModal', " . $row['id'] . ", '" . addslashes($row['nama_kategori']) . "', '" . addslashes($row['deskripsi']) . "')\">";
                                echo "<i class='fas fa-edit'></i> Edit";
                                echo "</button>";
                                echo "<button class='btn-delete' onclick=\"confirmDelete(" . $row['id'] . ", '" . addslashes($row['nama_kategori']) . "')\">";
                                echo "<i class='fas fa-trash'></i> Delete";
                                echo "</button>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align: center; padding: 40px;'>No categories found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" aria-label="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // Tampilkan maksimal 5 nomor halaman
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    $start_page = max(1, $end_page - 4);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" aria-label="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Info Pagination -->
            <div style="text-align: center; color: var(--gray); font-size: 14px; margin-top: 10px;">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_data); ?> of <?php echo $total_data; ?> categories
            </div>
            <?php endif; ?>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Categories</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. All rights reserved.
            </div>
        </footer>
    </div>

    <!-- Modal Tambah Kategori -->
    <div class="modal-overlay" id="tambahModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add New Category</h3>
                <button class="modal-close" onclick="closeModal('tambahModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_kategori" class="form-label">Category Name</label>
                        <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" placeholder="Enter category name" required>
                    </div>
                    <div class="form-group">
                        <label for="deskripsi" class="form-label">Description <span>(Max 100)</span></label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control form-textarea" placeholder="Enter category description" maxlength='100'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('tambahModal')">Cancel</button>
                    <button type="submit" class="btn-primary" name="tambah_kategori">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kategori -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Category</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_id_kategori" name="id_kategori">
                    <div class="form-group">
                        <label for="edit_nama_kategori" class="form-label">Category Name</label>
                        <input type="text" id="edit_nama_kategori" name="nama_kategori" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_deskripsi" class="form-label">Description <span>(Max 100)</span></label>
                        <textarea id="edit_deskripsi" name="deskripsi" class="form-control form-textarea"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-primary" name="edit_kategori">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                <p id="deleteCategoryName" style="font-weight: 600; margin-top: 10px;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <a href="" id="deleteLink" class="btn-danger" style="text-decoration: none; display: inline-block; padding: 12px 24px; border-radius: 8px;">Delete</a>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Modal functions
        function openModal(modalId, id = null, nama = '', deskripsi = '') {
            if (modalId === 'editModal' && id !== null) {
                document.getElementById('edit_id_kategori').value = id;
                document.getElementById('edit_nama_kategori').value = nama;
                document.getElementById('edit_deskripsi').value = deskripsi;
            }
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Delete confirmation
        function confirmDelete(id, nama) {
            document.getElementById('deleteCategoryName').textContent = 'Category: ' + nama;
            document.getElementById('deleteLink').href = '?hapus=' + id;
            openModal('deleteModal');
        }

        // Setting maximal deskripsi kategori
        const textarea = document.getElementById("deskripsi" && "edit_deskripsi");
        const counter = document.getElementById("charCount");
        const limit = 100;

        textarea.addEventListener("input", () => {
        const length = textarea.value.length;
        if (length > limit) {
            textarea.value = textarea.value.substring(0, limit); // potong otomatis
        }
        counter.textContent = `${textarea.value.length}/${limit}`;
        });

        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Jika ada data edit dari URL parameter
        <?php if ($kategori_edit): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('edit_id_kategori').value = '<?php echo $kategori_edit['id']; ?>';
            document.getElementById('edit_nama_kategori').value = '<?php echo htmlspecialchars($kategori_edit['nama_kategori']); ?>';
            document.getElementById('edit_deskripsi').value = '<?php echo htmlspecialchars($kategori_edit['deskripsi']); ?>';
            openModal('editModal');
        });
        <?php endif; ?>
    </script>
</body>
</html>