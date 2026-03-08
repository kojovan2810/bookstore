<?php
include '../Src/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];

    // Cek apakah email terdaftar di tabel super_admin
    $cek_admin = $conn->query("SELECT * FROM super_admin WHERE email_admin = '$email'");
    if ($cek_admin->num_rows == 1) {
        $_SESSION['reset_role'] = 'super_admin';
        $_SESSION['reset_email'] = $email;
        header('Location: fp_proses_otp.php?type=forgot');
        exit();
    }

    // Cek apakah email terdaftar di tabel penjual
    $cek_penjual = $conn->query("SELECT * FROM penjual WHERE email_penjual = '$email'");
    if ($cek_penjual->num_rows == 1) {
        $_SESSION['reset_role'] = 'penjual';
        $_SESSION['reset_email'] = $email;
        header('Location: fp_proses_otp.php?type=forgot');
        exit();
    }

    // Cek apakah email terdaftar di tabel pembeli
    $cek_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email'");
    if ($cek_pembeli->num_rows == 1) {
        $_SESSION['reset_role'] = 'pembeli';
        $_SESSION['reset_email'] = $email;
        header('Location: fp_proses_otp.php?type=forgot');
        exit();
    }

    // Jika email tidak ditemukan di semua tabel
    echo "<script>alert('Email tidak terdaftar!');</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - BukuBook</title>
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
            --warning: #ffc107;
            --warning-dark: #e0a800;
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

        .forgot-container {
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

        /* Right Panel - Forgot Password Form */
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
            line-height: 1.5;
        }

        .forgot-form {
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

        .info-box {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            font-size: 14px;
            color: #856404;
        }

        .info-box i {
            color: var(--warning);
            margin-right: 10px;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, var(--warning), var(--warning-dark));
            color: #212529;
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
            box-shadow: 0 7px 15px rgba(255, 193, 7, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: var(--gray);
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-left: 5px;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .forgot-container {
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
    <div class="forgot-container">
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="logo fade-in">
                <div class="logo-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="logo-text">BukuBook</div>
            </div>
            
            <div class="brand-message fade-in delay-1">
                <h1>Lupa Password?</h1>
                <p>Jangan khawatir! Kami akan membantu Anda mendapatkan akses kembali ke akun Anda.</p>
            </div>
            
            <div class="features">
                <div class="feature fade-in delay-2">
                    <i class="fas fa-shield-alt"></i>
                    <p>Keamanan akun Anda adalah prioritas kami</p>
                </div>
                <div class="feature fade-in delay-2">
                    <i class="fas fa-envelope"></i>
                    <p>Kami akan mengirimkan kode OTP ke email Anda</p>
                </div>
                <div class="feature fade-in delay-2">
                    <i class="fas fa-clock"></i>
                    <p>Proses reset password cepat dan mudah</p>
                </div>
            </div>
        </div>

        <!-- Right Panel - Forgot Password Form -->
        <div class="form-panel">
            <div class="form-header fade-in">
                <h2>Reset Password</h2>
                <p>Masukkan alamat email yang terdaftar pada akun Anda. Kami akan mengirimkan kode OTP untuk verifikasi.</p>
            </div>
            
            <div class="info-box fade-in delay-1">
                <i class="fas fa-info-circle"></i>
                Pastikan email yang Anda masukkan adalah email yang terdaftar di sistem kami.
            </div>
            
            <form class="forgot-form" action="" method="POST">
                <div class="input-group fade-in delay-2">
                    <label for="email">Alamat Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Masukkan email terdaftar Anda" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit fade-in delay-3">
                    <i class="fas fa-paper-plane"></i>
                    Kirim Kode OTP
                </button>
                
                <div class="login-link fade-in delay-3">
                    Ingat password Anda? <a href="login.php">Kembali ke halaman login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tambahkan efek interaktif pada form
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[type="email"]');
            
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