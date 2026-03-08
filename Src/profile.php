<?php 
include "config.php";
session_start();

if (isset($_SESSION['email_admin'])) {
    // PETUGAS LOGIN
    $email = $_SESSION['email_admin'];
    $data = $conn->query("SELECT * FROM super_admin WHERE email_admin='$email'")->fetch_assoc();
    $username = $data['nama_admin'];
    $jabatan = "Super Admin";

} elseif (isset($_SESSION['email_penjual'])) {
    // ADMIN LOGIN
    $email = $_SESSION['email_penjual'];
    $data = $conn->query("SELECT * FROM penjual WHERE email_penjual='$email'")->fetch_assoc();
    $username = $data['nama_penjual'];
    $jabatan = "Seller";

}elseif (isset($_SESSION['email_pembeli'])) {
    // ADMIN LOGIN
    $email = $_SESSION['email_pembeli'];
    $data = $conn->query("SELECT * FROM pembeli WHERE email_pembeli='$email'")->fetch_assoc();
    $username = $data['nama_pembeli'];
    $jabatan = "Buyer";

}  else {
    // TIDAK ADA YANG LOGIN
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../login.php'</script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - BukuBook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-weight: 600;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
            font-weight: 500;
            border-radius: 8px;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
            color: white;
            border-radius: 8px;
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .profile-img-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-section {
            padding: 2rem;
            background-color: white;
            border-radius: 0 0 15px 15px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid var(--light-gray);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .profile-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .profile-info-item:last-child {
            border-bottom: none;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeInUp 0.5s ease both;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-top: 2rem;
        }
        
        .stat-item {
            padding: 1rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .dropdown-menu {
            border-radius: 8px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .text-muted {
            color: var(--gray) !important;
        }
        
        .fw-bold {
            color: var(--dark);
        }
        
        .container-fluid {
            max-width: 1200px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-book me-2"></i>BukuBook
            </a>
            <div class="navbar-nav ms-auto me-4">
                <a class="nav-link" href="<?php 
                    if(isset($_SESSION['email_admin'])) {
                        echo "../Super_admin/beranda.php";
                    } elseif(isset($_SESSION['email_penjual'])) {
                        echo "../Publik/Penjual/beranda.php";
                    } elseif(isset($_SESSION['email_pembeli'])) {
                        echo "../Publik/Pembeli/beranda.php";
                    }
                ?>">
                    <i class="fas fa-arrow-left me-2"></i>KEMBALI
                </a>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <?php 
                    $foto = $data['foto'] ?? null;
                    $src = $foto ? "uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=" . urlencode("4361ee") . "&color=fff&size=120";
                    ?>
                    <img src="<?=$src?>" 
                         alt="Profile" 
                         class="profile-img dropdown-toggle" 
                         id="profileDropdown" 
                         data-bs-toggle="dropdown">
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../src/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <!-- Main Card -->
                <div class="card fade-in">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0"><i class="fas fa-user me-2"></i>PROFIL SAYA</h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <?php
                                $foto = $data['foto'] ?? null;
                                $src = $foto ? "uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=" . urlencode("4361ee") . "&color=fff&size=120";
                                ?>

                                <img src="<?= $src ?>" 
                                    alt="Profile" 
                                    class="profile-img-large mb-3">

                                <h4 class="fw-bold"><?=$username?></h4>
                                <p class="text-muted"><?=$jabatan?></p>
                                <form action="upload_foto.php" method="POST" enctype="multipart/form-data">
                                    <input type="file" name="foto" id="fotoInput" class="d-none" accept="image/*" onchange="this.form.submit()">
                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="document.getElementById('fotoInput').click();">
                                        <i class="fas fa-camera me-1"></i> Ubah Foto
                                    </button>
                                </form>
                                <form action="delete_foto.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus foto profil?');">
                                    <button type="submit" class="btn btn-danger btn-sm mt-2">
                                        <i class="fas fa-trash"></i> Hapus Foto
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-8">
                                <h5 class="mb-3 fw-bold">Informasi Profil</h5>
                                
                                <div class="profile-info-item">
                                    <div>
                                        <span class="fw-bold">Nama Lengkap</span>
                                        <p id="displayName" class="mb-0 text-muted"><?= $username ?></p>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm" onclick="editField('name')">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                </div>
                                
                                <div class="profile-info-item">
                                    <div>
                                        <span class="fw-bold">Email</span>
                                        <p id="displayEmail" class="mb-0 text-muted">
                                            <?php 
                                            if(isset($_SESSION['email_admin'])) {
                                                echo $data['email_admin'];
                                            } elseif(isset($_SESSION['email_penjual'])) {
                                                echo $data['email_penjual'];
                                            } elseif(isset($_SESSION['email_pembeli'])) {
                                                echo $data['email_pembeli'];
                                            }else{
                                                echo '';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <!-- <button class="btn btn-outline-primary btn-sm" onclick="editField('email')">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button> -->
                                </div>
                                
                                <div class="mt-4">
                                    <button class="btn btn-primary" onclick="saveProfile()">
                                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                                    </button>
                                    <button class="btn btn-outline-primary ms-2" onclick="location.href='ubah_password.php'">
                                        <i class="fas fa-key me-2"></i> Ubah Password
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animasi
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.fade-in');
            animatedElements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + 's';
            });
        });
        
        function editField(type) {
            if (type === "name") {
                let current = document.getElementById("displayName").innerText;
                let newValue = prompt("Masukkan nama baru:", current);

                if (newValue) {
                    document.getElementById("displayName").innerText = newValue;
                }
            } 
            else if (type === "email") {
                let current = document.getElementById("displayEmail").innerText;
                let newValue = prompt("Masukkan email baru:", current);

                if (newValue) {
                    document.getElementById("displayEmail").innerText = newValue;
                }
            }
        }

        function saveProfile() {
            let newName = document.getElementById("displayName").innerText;
            let newEmail = document.getElementById("displayEmail").innerText;

            fetch("update_profile.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "name=" + encodeURIComponent(newName) +
                    "&email=" + encodeURIComponent(newEmail)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Berhasil disimpan!");
                    location.reload();
                } else {
                    alert("Gagal: " + data.msg);
                }
            });
        }
    </script>
</body>
</html>