<?php
include 'Src/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Logika login akan ditambahkan di sini
    $email = $_POST['email'];
    $pw = $_POST['password'];

    $cek_admin = $conn->query("SELECT * FROM super_admin WHERE email_admin= '$email'");
    $cek_penjual = $conn->query("SELECT * FROM penjual WHERE email_penjual= '$email'");
    $cek_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli= '$email'");


    if($cek_admin -> num_rows > 0){
        $data = $cek_admin -> fetch_assoc();
        $nama_admin = $data['nama_admin'];
        $email_admin = $data['email_admin'];
        $_SESSION['nama_admin'] = $nama_admin;
        $_SESSION['email_admin'] = $email_admin;
        echo "<script>alert('Selamat Datang ".$_SESSION['nama_admin']."'); window.location.href='Super_admin/beranda.php'</script>";
    }elseif($cek_penjual -> num_rows > 0){
        $data = $cek_penjual -> fetch_assoc();
        $nama_penjual = $data['nama_penjual'];
        $email_penjual = $data['email_penjual'];
        $_SESSION['nama_penjual'] = $nama_penjual;
        $_SESSION['email_penjual'] = $email_penjual;
        $update = $conn->query("UPDATE penjual SET status='Online' WHERE email_penjual='$email_penjual'");
        echo "<script>alert('Selamat Datang ".$_SESSION['nama_penjual']."'); window.location.href='Publik/Penjual/beranda.php'</script>";
    }elseif($cek_pembeli -> num_rows > 0){
        $data = $cek_pembeli -> fetch_assoc();
        $nama_pembeli = $data['nama_pembeli'];
        $email_pembeli = $data['email_pembeli'];
        $_SESSION['nama_pembeli'] = $nama_pembeli;
        $_SESSION['email_pembeli'] = $email_pembeli;
        $update = $conn->query("UPDATE pembeli SET status='Online' WHERE email_pembeli='$email_pembeli'");
        echo "<script>alert('Selamat Datang ".$_SESSION['nama_pembeli']."'); window.location.href='Publik/Pembeli/beranda.php'</script>";
    }else{
        echo "<script>alert('The account is not registered yet!'); window.location.href='login.php'</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login -  BukuBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --background: #f5f7fb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fb 0%, #e3e7f8 100%);
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        /* Left Panel - Branding */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .logo-icon {
            font-size: 42px;
            color: #ffd166;
        }

        .logo-text {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .brand-message {
            position: relative;
            z-index: 2;
            margin-bottom: 40px;
        }

        .brand-message h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .brand-message p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .features {
            position: relative;
            z-index: 2;
            margin-top: 30px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .feature i {
            font-size: 20px;
            color: #ffd166;
            background: rgba(255, 255, 255, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature p {
            font-size: 15px;
        }

        /* Right Panel - Login Form */
        .form-panel {
            flex: 1;
            background-color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--gray);
            font-size: 16px;
        }

        .login-form {
            width: 100%;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 15px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
        }

        .input-with-icon input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            font-size: 16px;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }

        .input-with-icon input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .remember-me label {
            color: var(--gray);
            cursor: pointer;
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            position: relative;
            margin: 25px 0;
            color: var(--gray);
            font-size: 14px;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: var(--light-gray);
        }

        .divider::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: var(--light-gray);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: var(--gray);
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-left: 5px;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .brand-panel {
                padding: 40px 30px;
            }
            
            .form-panel {
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .brand-panel {
                padding: 30px 20px;
            }
            
            .logo {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .logo-text {
                font-size: 28px;
            }
            
            .brand-message h1 {
                font-size: 24px;
            }
            
            .form-panel {
                padding: 30px 20px;
            }
            
            .form-header h2 {
                font-size: 28px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        .delay-1 {
            animation-delay: 0.2s;
            opacity: 0;
        }

        .delay-2 {
            animation-delay: 0.4s;
            opacity: 0;
        }

        .delay-3 {
            animation-delay: 0.6s;
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="logo fade-in">
                <div class="logo-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="logo-text">BukuBook</div>
            </div>
            
            <div class="brand-message fade-in delay-1">
                <h1>Selamat Datang Kembali</h1>
                <p>Masuk ke dashboard admin untuk mengelola koleksi buku dan melayani pelanggan dengan lebih baik.</p>
            </div>
            
            <div class="features">
                <div class="feature fade-in delay-2">
                    <i class="fas fa-book-open"></i>
                    <p>Kelola ribuan koleksi buku dengan mudah</p>
                </div>
                <div class="feature fade-in delay-2">
                    <i class="fas fa-chart-line"></i>
                    <p>Pantau statistik penjualan secara real-time</p>
                </div>
                <div class="feature fade-in delay-2">
                    <i class="fas fa-users"></i>
                    <p>Kelola data pelanggan dan admin</p>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="form-panel">
            <div class="form-header fade-in">
                <h2>Hi, welcome back!</h2>
                <p>Masukkan kredensial Anda untuk mengakses dashboard</p>
            </div>
            
            <form class="login-form" action="" method="POST">
                <div class="input-group fade-in delay-1">
                    <label for="email">Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Masukkan email Anda" required>
                    </div>
                </div>
                
                <div class="input-group fade-in delay-1">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                    </div>
                </div>
                
                <div class="form-options fade-in delay-2">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Ingat saya</label>
                    </div>
                    <a href="Security/fp_validasi_email.php" class="forgot-password">Lupa Password?</a>
                </div>
                
                <button type="submit" class="btn-submit fade-in delay-2">
            <i class="fas fa-sign-in-alt"></i>
            Masuk
        </button>
        
        <div class="divider fade-in delay-3">---- atau ----</div>
        
        <div class="register-link fade-in delay-3">
            Belum punya akun? <a href="Src/register.php">Daftar di sini</a>
        </div>
        </form>
        </div>
    </div>

    <script>
        // Tambahkan efek interaktif pada form
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            
            inputs.forEach(input => {
                // Efek saat input fokus
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                // Efek saat input kehilangan fokus
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
                
                // Validasi sederhana saat typing
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.style.backgroundColor = '#fff';
                    } else {
                        this.style.backgroundColor = '#f8f9fa';
                    }
                });
            });
            
            // Efek pada tombol submit
            const submitBtn = document.querySelector('.btn-submit');
            
            submitBtn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            submitBtn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>