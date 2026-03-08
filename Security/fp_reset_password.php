<?php
session_start();
include "../Src/config.php";

// Cek apakah OTP sudah terverifikasi
if (!isset($_SESSION['reset_email'])) {
    echo "<script>
            alert('Akses tidak valid! Silakan verifikasi OTP terlebih dahulu.');
            window.location.href = 'fp_validasi_email.php';
          </script>";
    exit();
}

// Pastikan ada email untuk reset
if (!isset($_SESSION['reset_email'])) {
    echo "<script>
            alert('Data tidak ditemukan! Silakan mulai ulang.');
            window.location.href = 'fp_validasi_email.php';
          </script>";
    exit();
}

$email = $_SESSION['reset_email'];

// Tentukan role berdasarkan email
$role = '';
$username = "";
$table = "";
$username_field = "";

// Coba cari di tabel super_admin
$query = $conn->query("SELECT * FROM super_admin WHERE email_admin = '$email'");
if ($query && $query->num_rows > 0) {
    $role = 'super_admin';
    $table = "super_admin";
    $username_field = "nama_admin";
    $data = $query->fetch_assoc();
    $username = $data[$username_field];
} 
// Jika tidak ditemukan, coba di tabel penjual
else {
    $query = $conn->query("SELECT * FROM penjual WHERE email_penjual = '$email'");
    if ($query && $query->num_rows > 0) {
        $role = 'penjual';
        $table = "penjual";
        $username_field = "nama_penjual";
        $data = $query->fetch_assoc();
        $username = $data[$username_field];
    } 
    // Jika tidak ditemukan, coba di tabel pembeli
    else {
        $query = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email'");
        if ($query && $query->num_rows > 0) {
            $role = 'pembeli';
            $table = "pembeli";
            $username_field = "nama_pembeli";
            $data = $query->fetch_assoc();
            $username = $data[$username_field];
        } else {
            echo "<script>
                    alert('Data pengguna tidak ditemukan!');
                    window.location.href = 'fp_validasi_email.php';
                  </script>";
            exit();
        }
    }
}

// Simpan role ke session untuk digunakan di update_password.php
$_SESSION['reset_role'] = $role;
$_SESSION['reset_table'] = $table;
$_SESSION['reset_username_field'] = $username_field;

// Proses reset password jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validasi password
    if (empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('Password tidak boleh kosong!');</script>";
    } elseif ($new_password !== $confirm_password) {
        echo "<script>alert('Password tidak cocok!');</script>";
    } elseif (strlen($new_password) < 8) {
        echo "<script>alert('Password minimal 8 karakter!');</script>";
    } else {
        // Hash password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password di database berdasarkan role
        $update_success = false;
        
        if ($role == 'super_admin') {
            $update_query = "UPDATE super_admin SET password = '$hashed_password' WHERE email_admin = '$email'";
        } elseif ($role == 'penjual') {
            $update_query = "UPDATE penjual SET password = '$hashed_password' WHERE email_penjual = '$email'";
        } elseif ($role == 'pembeli') {
            $update_query = "UPDATE pembeli SET password = '$hashed_password' WHERE email_pembeli = '$email'";
        }
        
        if (isset($update_query) && $conn->query($update_query)) {
            $update_success = true;
        }
        
        if ($update_success) {
            // Hapus session reset
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_role']);
            unset($_SESSION['reset_table']);
            unset($_SESSION['reset_username_field']);
            
            // Tampilkan modal sukses
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        
                        // Auto-redirect setelah 3 detik
                        setTimeout(function() {
                            window.location.href = '../login.php';
                        }, 3000);
                    });
                  </script>";
        } else {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('errorMessage').textContent = 'Gagal mereset password!';
                        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        errorModal.show();
                    });
                  </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BukuBook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Styles */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand i {
            color: #ffd166;
            font-size: 1.5rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 8px 15px !important;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white !important;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .profile-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            border: 2px solid white;
        }

        /* Main Container */
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f5f7fb 0%, #e3e7f8 100%);
            min-height: calc(100vh - 74px);
        }

        /* Card Styles */
        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            background: white;
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px 30px;
            border-bottom: none;
        }

        .card-header-custom h3 {
            font-weight: 700;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .card-header-custom h3 i {
            margin-right: 12px;
            font-size: 1.5rem;
        }

        .card-header-custom p {
            opacity: 0.9;
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        /* Content Section */
        .content-section {
            padding: 35px;
        }

        /* User Info Card */
        .user-info-card {
            background: linear-gradient(135deg, #f8f9fe 0%, #f0f2ff 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .info-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .user-info-label {
            font-size: 0.85rem;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .user-info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .badge-custom {
            background: linear-gradient(135deg, var(--success), #20c997);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .form-control-custom {
            border: 2px solid #e1e5f1;
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f8f9fe;
        }

        .form-control-custom:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background-color: white;
        }

        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.1rem;
            cursor: pointer;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Password Strength */
        .password-strength {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s;
        }

        /* Password Requirements */
        .password-requirements {
            background-color: #f8f9fe;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .password-requirements h6 {
            color: var(--dark);
            font-size: 0.95rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .requirement i {
            margin-right: 10px;
            font-size: 0.8rem;
        }

        .requirement.valid {
            color: var(--success);
        }

        .requirement.valid i {
            color: var(--success);
        }

        .requirement.invalid {
            color: var(--danger);
        }

        .requirement.invalid i {
            color: var(--danger);
        }

        /* Button Styles */
        .btn-custom {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            border: none;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #20c997);
            border: none;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        /* Modal Styles */
        .modal-content-custom {
            border-radius: 16px;
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .modal-header-custom {
            border-bottom: none;
            padding: 25px 30px 0;
        }

        .modal-body-custom {
            padding: 30px;
            text-align: center;
        }

        .modal-footer-custom {
            border-top: none;
            padding: 0 30px 25px;
            justify-content: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
            }
            
            .content-section {
                padding: 25px 20px;
            }
            
            .user-info-card .row > div {
                margin-bottom: 15px;
            }
            
            .btn-custom {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
            }
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book me-2"></i>BukuBook
            </a>
            <div class="navbar-nav ms-auto me-4">
                <a class="nav-link" href="../login.php">
                    <i class="fas fa-sign-in-alt me-2"></i>LOGIN
                </a>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <?php 
                    $initial = strtoupper(substr($username, 0, 1));
                    ?>
                    <div class="profile-img dropdown-toggle" 
                         data-bs-toggle="dropdown"
                         aria-expanded="false">
                        <?php echo $initial; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../login.php"><i class="fas fa-sign-in-alt me-2"></i>Login</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="register.php"><i class="fas fa-user-plus me-2"></i>Daftar Akun Baru</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-container">
        <div class="card-custom fade-in">
            <!-- Card Header -->
            <div class="card-header-custom">
                <h3><i class="fas fa-key me-2"></i>RESET PASSWORD</h3>
                <p>Mengatur ulang password untuk akun: <?php echo htmlspecialchars($email); ?></p>
            </div>
            
            <!-- Card Body -->
            <div class="content-section">
                <!-- User Info Card -->
                <div class="user-info-card fade-in">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="col">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="user-info-label">NAMA</div>
                                    <div class="user-info-value"><?php echo htmlspecialchars($username); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="user-info-label">EMAIL</div>
                                    <div class="user-info-value"><?php echo htmlspecialchars($email); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="user-info-label">PERAN</div>
                                    <div class="user-info-value">
                                        <?php 
                                        if ($role == 'super_admin') {
                                            echo 'Super Admin';
                                        } elseif ($role == 'penjual') {
                                            echo 'Penjual';
                                        } elseif ($role == 'pembeli') {
                                            echo 'Pembeli';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="user-info-label">STATUS</div>
                                    <div class="user-info-value">
                                        <span class="badge-custom">OTP Terverifikasi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Password Reset Form -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <form method="POST" action="" id="resetPasswordForm">
                            <div class="mb-4">
                                <label for="newPassword" class="form-label">Password Baru</label>
                                <div class="password-input-group">
                                    <input type="password" 
                                           class="form-control form-control-custom" 
                                           id="newPassword" 
                                           name="new_password"
                                           placeholder="Masukkan password baru" 
                                           required
                                           oninput="checkPasswordStrength(this.value)">
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('newPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                
                                <!-- Password Requirements -->
                                <div class="password-requirements">
                                    <h6 class="fw-bold mb-3">Password harus memenuhi:</h6>
                                    <div class="requirement invalid" id="reqLength">
                                        <i class="fas fa-circle"></i>
                                        <span>Minimal 8 karakter</span>
                                    </div>
                                    <div class="requirement invalid" id="reqUppercase">
                                        <i class="fas fa-circle"></i>
                                        <span>Minimal 1 huruf besar</span>
                                    </div>
                                    <div class="requirement invalid" id="reqLowercase">
                                        <i class="fas fa-circle"></i>
                                        <span>Minimal 1 huruf kecil</span>
                                    </div>
                                    <div class="requirement invalid" id="reqNumber">
                                        <i class="fas fa-circle"></i>
                                        <span>Minimal 1 angka</span>
                                    </div>
                                    <div class="requirement invalid" id="reqSpecial">
                                        <i class="fas fa-circle"></i>
                                        <span>Minimal 1 karakter khusus</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirmPassword" class="form-label">Konfirmasi Password Baru</label>
                                <div class="password-input-group">
                                    <input type="password" 
                                           class="form-control form-control-custom" 
                                           id="confirmPassword" 
                                           name="confirm_password"
                                           placeholder="Masukkan ulang password baru" 
                                           required
                                           oninput="checkPasswordMatch()">
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback d-none" id="passwordMismatch">
                                    Password tidak cocok
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-5">
                                <a href="../login.php" class="btn btn-outline-primary btn-custom">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Login
                                </a>
                                <button type="submit" class="btn btn-success btn-custom" id="submitButton" disabled>
                                    <i class="fas fa-save me-2"></i>Reset Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-custom">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title text-success" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Berhasil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-custom">
                    <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                    <h5>Password berhasil direset!</h5>
                    <p class="text-muted">Password Anda telah berhasil diperbarui. Silakan login dengan password baru.</p>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <a href="../login.php" class="btn btn-success btn-custom">Login Sekarang</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-custom">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title text-danger" id="errorModalLabel">
                        <i class="fas fa-exclamation-circle me-2"></i>Error
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-custom">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                    <h5 id="errorMessage">Terjadi kesalahan</h5>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-primary btn-custom" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cache DOM elements
        const elements = {
            form: document.getElementById('resetPasswordForm'),
            newPassword: document.getElementById('newPassword'),
            confirmPassword: document.getElementById('confirmPassword'),
            submitButton: document.getElementById('submitButton'),
            passwordStrengthBar: document.getElementById('passwordStrengthBar'),
            passwordMismatch: document.getElementById('passwordMismatch')
        };

        // Password requirements pattern
        const requirements = {
            length: /.{8,}/,
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            number: /[0-9]/,
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/
        };

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto focus on password field
            elements.newPassword.focus();
        });

        // Toggle password visibility
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        // Check password strength
        function checkPasswordStrength(password) {
            let strength = 0;
            
            // Check each requirement and update UI
            Object.keys(requirements).forEach((key, index) => {
                const isValid = requirements[key].test(password);
                updateRequirement(`req${key.charAt(0).toUpperCase() + key.slice(1)}`, isValid);
                if (isValid) strength += 20;
            });
            
            // Update strength bar
            updateStrengthBar(strength);
            validateForm();
        }
        
        // Update requirement indicator
        function updateRequirement(elementId, isValid) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('i');
            
            if (isValid) {
                element.classList.replace('invalid', 'valid');
                icon.classList.replace('fa-circle', 'fa-check-circle');
            } else {
                element.classList.replace('valid', 'invalid');
                icon.classList.replace('fa-check-circle', 'fa-circle');
            }
        }
        
        // Update strength bar
        function updateStrengthBar(strength) {
            elements.passwordStrengthBar.style.width = `${strength}%`;
            
            if (strength < 40) {
                elements.passwordStrengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 80) {
                elements.passwordStrengthBar.style.backgroundColor = '#ffc107';
            } else {
                elements.passwordStrengthBar.style.backgroundColor = '#28a745';
            }
        }
        
        // Check if passwords match
        function checkPasswordMatch() {
            const newPassword = elements.newPassword.value;
            const confirmPassword = elements.confirmPassword.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                elements.passwordMismatch.classList.remove('d-none');
                elements.confirmPassword.classList.add('is-invalid');
            } else {
                elements.passwordMismatch.classList.add('d-none');
                elements.confirmPassword.classList.remove('is-invalid');
            }
            
            validateForm();
        }
        
        // Validate entire form
        function validateForm() {
            const newPassword = elements.newPassword.value;
            const confirmPassword = elements.confirmPassword.value;
            
            // Check if all requirements are met
            const isPasswordValid = Object.values(requirements).every(regex => regex.test(newPassword));
            const isPasswordMatch = newPassword === confirmPassword;
            
            elements.submitButton.disabled = !(isPasswordValid && isPasswordMatch);
        }
        
        // Auto-hide success modal and redirect
        document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
            window.location.href = '../login.php';
        });
    </script>
</body>
</html>