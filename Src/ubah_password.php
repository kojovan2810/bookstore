<?php
session_start();
include "../src/config.php";

// Tentukan role
if (isset($_SESSION['email_admin'])) {
    // PETUGAS
    $id = $_SESSION['email_admin'];
    $table = "super_admin";
    $key = "email_admin";
    $username = "nama_admin";
} elseif (isset($_SESSION['email_penjual'])) {
    // ADMIN
    $id = $_SESSION['email_penjual'];
    $table = "penjual";
    $key = "email_penjual";
    $username = "nama_penjual";
} elseif (isset($_SESSION['email_pembeli'])) {
    // ADMIN
    $id = $_SESSION['email_pembeli'];
    $table = "pembeli";
    $key = "email_pembeli";
    $username = "nama_pembeli";
} else {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../login.php'</script>";
    exit();
}

// Ambil data
$data = $conn->query("SELECT * FROM $table WHERE $key = '$id'")->fetch_assoc();
$username = $data[$username];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - BukuBook</title>
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
        
        .password-section {
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
            cursor: pointer;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            background-color: var(--light-gray);
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background-color: white;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .requirement i {
            margin-right: 0.5rem;
            width: 16px;
        }
        
        .requirement.valid {
            color: var(--success);
        }
        
        .requirement.invalid {
            color: var(--gray);
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
        
        .text-muted {
            color: var(--gray) !important;
        }
        
        .fw-bold {
            color: var(--dark);
        }
        
        .container-fluid {
            max-width: 1200px;
        }
        
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-bottom: none;
        }
        
        .modal-footer {
            border-top: none;
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
                <?php 
                $backLink = "";
                if(isset($_SESSION['email_admin'])) {
                    $backLink = "../Super_admin/beranda.php";
                } elseif(isset($_SESSION['email_pembeli'])) {
                    $backLink = "../Pembeli/beranda.php";
                } elseif(isset($_SESSION['email_penjual'])) {
                    $backLink = "../Penjual/beranda.php";
                }
                ?>
                <a class="nav-link" href="<?php echo $backLink; ?>">
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
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profil Saya</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../src/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8 col-lg-10">
                <!-- Main Card -->
                <div class="card fade-in">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h3 class="mb-0"><i class="fas fa-key me-2"></i>UBAH PASSWORD</h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="password-section">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <form id="changePasswordForm">
                                    <div class="mb-4">
                                        <label for="currentPassword" class="form-label">Password Saat Ini</label>
                                        <div class="password-input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="currentPassword" 
                                                   placeholder="Masukkan password saat ini" 
                                                   required>
                                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('currentPassword', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="newPassword" class="form-label">Password Baru</label>
                                        <div class="password-input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="newPassword" 
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
                                        <div class="password-requirements mt-3">
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
                                                   class="form-control" 
                                                   id="confirmPassword" 
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
                                        <a href="profile.php" class="btn btn-outline-primary">
                                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Profil
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="submitButton" disabled>
                                            <i class="fas fa-save me-2"></i>Simpan Password Baru
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-success" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Berhasil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                    <h5>Password berhasil diubah!</h5>
                    <p class="text-muted">Password Anda telah berhasil diperbarui.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger" id="errorModalLabel">
                        <i class="fas fa-exclamation-circle me-2"></i>Error
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                    <h5 id="errorMessage">Terjadi kesalahan</h5>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cache DOM elements
        const elements = {
            form: document.getElementById('changePasswordForm'),
            currentPassword: document.getElementById('currentPassword'),
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
            const currentPassword = elements.currentPassword.value;
            const newPassword = elements.newPassword.value;
            const confirmPassword = elements.confirmPassword.value;
            
            // Check if all requirements are met
            const isPasswordValid = Object.values(requirements).every(regex => regex.test(newPassword));
            const isPasswordMatch = newPassword === confirmPassword;
            const isCurrentPasswordFilled = currentPassword.length > 0;
            
            elements.submitButton.disabled = !(isPasswordValid && isPasswordMatch && isCurrentPasswordFilled);
        }
        
        // Handle form submission
        elements.form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const currentPassword = elements.currentPassword.value;
            const newPassword = elements.newPassword.value;
            
            // Show loading state
            const originalText = elements.submitButton.innerHTML;
            elements.submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            elements.submitButton.disabled = true;
            
            try {
                const response = await fetch("update_password_profile.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `current=${encodeURIComponent(currentPassword)}&new=${encodeURIComponent(newPassword)}`
                });
                
                const data = await response.json();
                
                if (data.status === "success") {
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    elements.form.reset();
                    elements.passwordStrengthBar.style.width = '0';
                    
                    // Reset requirement indicators
                    document.querySelectorAll('.requirement').forEach(el => {
                        el.classList.replace('valid', 'invalid');
                        const icon = el.querySelector('i');
                        icon.classList.replace('fa-check-circle', 'fa-circle');
                    });
                } else {
                    showError(data.msg || 'Terjadi kesalahan');
                }
            } catch (error) {
                showError('Terjadi error: ' + error.message);
            } finally {
                elements.submitButton.innerHTML = originalText;
                elements.submitButton.disabled = false;
            }
        });
        
        // Show error modal
        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        }
        
        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.fade-in');
            animatedElements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>