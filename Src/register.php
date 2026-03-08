<?php
include 'config.php';
session_start();

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $alamat = $_POST['alamat'];
    $peran = $_POST['peran'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $date = date("Y-m-d");
    
    // Tambahan untuk penjual
    if ($peran == 'Penjual') {
        $no_rekening = $_POST['no_rekening'] ?? '';
        $debit = $_POST['debit'] ?? '';
    }

    // Validasi input
    $errors = [];

    // Cek apakah email sudah terdaftar
    $cek_admin = $conn->query("SELECT * FROM super_admin WHERE email_admin = '$email'");
    $cek_penjual = $conn->query("SELECT * FROM penjual WHERE email_penjual = '$email'");
    $cek_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email'");

    if ($cek_admin->num_rows > 0 || $cek_penjual->num_rows > 0 || $cek_pembeli->num_rows > 0) {
        $errors[] = "Email sudah terdaftar!";
    }

    // Cek apakah NIK sudah terdaftar
    if ($peran == 'Penjual') {
        $cek_nik = $conn->query("SELECT * FROM penjual WHERE nik_penjual = '$nik'");
    } else {
        $cek_nik = $conn->query("SELECT * FROM pembeli WHERE nik_pembeli = '$nik'");
    }
    
    if ($cek_nik->num_rows > 0) {
        $errors[] = "NIK sudah terdaftar!";
    }

    // Validasi password
    if ($password !== $confirm_password) {
        $errors[] = "Password dan konfirmasi password tidak sama!";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($peran == 'Penjual') {
            // Validasi tambahan untuk penjual
            if (empty($no_rekening)) {
                $errors[] = "Nomor rekening harus diisi!";
            }
            if (empty($debit)) {
                $errors[] = "Bank/debit harus dipilih!";
            }
            
            if (empty($errors)) {
                // Validasi format nomor rekening
                if (!preg_match('/^\d{10,}$/', $no_rekening)) {
                    $errors[] = "Nomor rekening minimal 10 digit angka!";
                }
                
                if (empty($errors)) {
                    // Simpan sebagai penjual dengan data tambahan
                    $status = 'Offline'; // Status default untuk penjual baru
                    $query = "INSERT INTO penjual (nik_penjual, nama_penjual, email_penjual, alamat_penjual, password, no_rekening, debit, status, created_at) 
                             VALUES ('$nik', '$nama', '$email', '$alamat', '$hashed_password', '$no_rekening', '$debit', '$status', '$date')";
                    
                    if ($conn->query($query)) {
                        echo "<script>
                                alert('Registrasi berhasil! Silakan login.');
                                window.location.href = '../login.php';
                              </script>";
                        exit();
                    } else {
                        $errors[] = "Terjadi kesalahan saat menyimpan data: " . $conn->error;
                    }
                }
            }
        } else {
            // Simpan sebagai pembeli
            $status = 'Offline'; // Status default untuk pembeli baru
            $query = "INSERT INTO pembeli (nik_pembeli, nama_pembeli, email_pembeli, alamat_pembeli, password, status, created_at) 
                     VALUES ('$nik', '$nama', '$email', '$alamat', '$hashed_password', '$status', '$date')";
            
            if ($conn->query($query)) {
                echo "<script>
                        alert('Registrasi berhasil! Silakan login.');
                        window.location.href = '../login.php';
                      </script>";
                exit();
            } else {
                $errors[] = "Terjadi kesalahan saat menyimpan data: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - BukuBook</title>
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
    --success: #28a745;
    --danger: #dc3545;
    --warning: #ffc107;
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

.register-container {
    display: flex;
    width: 100%;
    max-width: 1200px;
    min-height: 700px;
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

/* Right Panel - Register Form */
.form-panel {
    flex: 1;
    background-color: white;
    padding: 50px;
    display: flex;
    flex-direction: column;
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
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

/* Form Content dengan overflow-y */
.form-content {
    flex: 1;
    overflow-y: auto;
    padding-right: 10px;
    max-height: 500px;
}

/* Styling scrollbar untuk form content */
.form-content::-webkit-scrollbar {
    width: 6px;
}

.form-content::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 10px;
}

.form-content::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 10px;
    opacity: 0.3;
}

.form-content::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
    opacity: 0.5;
}

/* Firefox */
.form-content {
    scrollbar-width: thin;
    scrollbar-color: var(--primary) #f8f9fa;
}

.register-form {
    width: 100%;
}

.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group label {
    display: block;
    font-weight: 500;
    color: var(--dark);
    font-size: 15px;
    margin-bottom: 8px;
}

.input-group label span {
    color: var(--danger);
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

.input-with-icon input,
.input-with-icon select {
    width: 100%;
    padding: 16px 20px 16px 50px;
    border: 1px solid var(--light-gray);
    border-radius: 10px;
    font-size: 16px;
    background-color: #f8f9fa;
    transition: all 0.3s;
}

.input-with-icon input:focus,
.input-with-icon select:focus {
    outline: none;
    border-color: var(--primary);
    background-color: white;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.password-strength {
    margin-top: 5px;
    font-size: 13px;
    color: var(--gray);
}

.strength-bar {
    height: 5px;
    background-color: var(--light-gray);
    border-radius: 3px;
    margin-top: 5px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    width: 0%;
    border-radius: 3px;
    transition: all 0.3s;
}

.strength-weak {
    background-color: var(--danger);
    width: 33%;
}

.strength-medium {
    background-color: var(--warning);
    width: 66%;
}

.strength-strong {
    background-color: var(--success);
    width: 100%;
}

.role-options {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.role-option {
    flex: 1;
    padding: 15px;
    border: 2px solid var(--light-gray);
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.role-option:hover {
    border-color: var(--primary);
    background-color: rgba(67, 97, 238, 0.05);
}

.role-option.selected {
    border-color: var(--primary);
    background-color: rgba(67, 97, 238, 0.1);
}

.role-option i {
    font-size: 24px;
    margin-bottom: 10px;
    display: block;
    color: var(--gray);
}

.role-option.selected i {
    color: var(--primary);
}

.role-option span {
    font-weight: 500;
    color: var(--dark);
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
    margin: 25px 0;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 15px rgba(67, 97, 238, 0.3);
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

/* Error Messages */
.error-messages {
    background-color: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}

.error-messages ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.error-messages li {
    color: var(--danger);
    font-size: 14px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.error-messages li:last-child {
    margin-bottom: 0;
}

.error-messages li i {
    font-size: 12px;
}

/* Success Messages */
.success-message {
    background-color: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    color: var(--success);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Custom styling untuk select dropdown */
select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 16px;
    padding-right: 45px;
    cursor: pointer;
}

select:focus {
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234361ee' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
}

/* Additional styles for bank selection */
.bank-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 8px;
    background-color: #f8f9fa;
    margin-top: 5px;
}

.bank-option i {
    font-size: 20px;
    color: var(--primary);
}

.bank-option span {
    font-size: 14px;
}

/* Styling untuk input tambahan penjual */
.seller-only {
    margin-top: 10px;
    padding-top: 15px;
    border-top: 1px dashed var(--light-gray);
}

.seller-only label {
    color: var(--primary-dark);
    font-weight: 600;
}

.info-text {
    font-size: 12px;
    color: var(--gray);
    margin-top: 3px;
    font-style: italic;
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); height: 0; }
    to { opacity: 1; transform: translateY(0); height: auto; }
}

.fade-in {
    animation: fadeIn 0.6s ease forwards;
}

.slide-in {
    animation: slideIn 0.4s ease forwards;
    overflow: hidden;
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

/* Responsive Design */
@media (max-width: 992px) {
    .register-container {
        flex-direction: column;
        max-width: 600px;
    }
    
    .brand-panel {
        padding: 40px 30px;
    }
    
    .form-panel {
        padding: 40px 30px;
    }
    
    .form-content {
        max-height: none;
        overflow-y: visible;
        padding-right: 0;
    }
}

@media (max-width: 576px) {
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
    
    .role-options {
        flex-direction: column;
    }
    
    .input-with-icon input,
    .input-with-icon select {
        padding: 14px 20px 14px 50px;
    }
    
    .form-content {
        max-height: none;
        padding-right: 0;
    }
}
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="logo fade-in">
                <div class="logo-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="logo-text">BukuBook</div>
            </div>
            
            <div class="brand-message fade-in delay-1">
                <h1>Bergabunglah Bersama Kami</h1>
                <p>Daftarkan diri Anda untuk mulai menjelajahi dunia literasi, menjual buku, atau membeli koleksi buku favorit Anda.</p>
            </div>
            
            <div class="features">
                <div class="feature fade-in delay-2">
                    <i class="fas fa-book-open"></i>
                    <p>Akses ribuan koleksi buku dari berbagai genre</p>
                </div>
                <div class="feature fade-in delay-2">
                    <i class="fas fa-shield-alt"></i>
                    <p>Keamanan data terjamin dengan enkripsi terbaru</p>
                </div>
                <div class="feature fade-in delay-2">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Transaksi mudah dan aman dengan berbagai metode pembayaran</p>
                </div>
                <div class="feature fade-in delay-2">
                    <i class="fas fa-money-bill-wave"></i>
                    <p>Penjual dapat menarik dana langsung ke rekening bank</p>
                </div>
            </div>
        </div>

        <!-- Right Panel - Register Form -->
        <div class="form-panel">
            <div class="form-header fade-in">
                <h2>Buat Akun Baru</h2>
                <p>Isi data diri Anda untuk mulai menggunakan BukuBook</p>
            </div>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
            <div class="error-messages fade-in">rm-
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="form-content">
            <form class="register-form" action="" method="POST">
                <!-- Pilih Peran -->
                <div class="input-group fade-in delay-2">
                    <label>Pilih Peran <span>*</span></label>
                    <div class="role-options">
                        <div class="role-option" data-value="Penjual" onclick="selectRole(this)" 
                             id="role-penjual">
                            <i class="fas fa-store"></i>
                            <span>Penjual</span>
                            <p style="font-size: 12px; color: var(--gray); margin-top: 5px;">Ingin menjual buku</p>
                        </div>
                        <div class="role-option" data-value="Pembeli" onclick="selectRole(this)"
                             id="role-pembeli">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Pembeli</span>
                            <p style="font-size: 12px; color: var(--gray); margin-top: 5px;">Ingin membeli buku</p>
                        </div>
                    </div>
                    <input type="hidden" id="peran" name="peran" required
                           value="<?php echo isset($_POST['peran']) ? htmlspecialchars($_POST['peran']) : ''; ?>">
                </div>
                <!-- Input NIK -->
                <div class="input-group fade-in delay-1">
                    <label for="nik">NIK <span>*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="nik" name="nik" placeholder="Masukkan NIK Anda" required 
                               pattern="[0-9]{16}" title="NIK harus 16 digit angka" 
                               oninput="validateNIK(this)"
                               value="<?php echo isset($_POST['nik']) ? htmlspecialchars($_POST['nik']) : ''; ?>">
                    </div>
                    <small class="password-strength" id="nik-help">Format: 16 digit angka</small>
                </div>
                
                <!-- Input Nama -->
                <div class="input-group fade-in delay-1">
                    <label for="nama">Nama Lengkap <span>*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap Anda" required
                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                    </div>
                </div>
                
                <!-- Input Email -->
                <div class="input-group fade-in delay-1">
                    <label for="email">Email <span>*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Masukkan email Anda" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <!-- Input Alamat -->
                <div class="input-group fade-in delay-1">
                    <label for="alamat">Alamat <span>*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-home"></i>
                        <input type="text" id="alamat" name="alamat" placeholder="Masukkan alamat lengkap Anda" required
                               value="<?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?>">
                    </div>
                </div>
                
                
                
                <!-- Input untuk penjual saja -->
                <div id="seller-fields" class="seller-only" style="display: none;">
                    <div class="input-group fade-in">
                        <label for="no_rekening">Nomor Rekening <span>*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-credit-card"></i>
                            <input type="text" id="no_rekening" name="no_rekening" 
                                   placeholder="Masukkan nomor rekening"
                                   value="<?php echo isset($_POST['no_rekening']) ? htmlspecialchars($_POST['no_rekening']) : ''; ?>">
                        </div>
                        <small class="info-text">Digunakan untuk penarikan dana dari penjualan (minimal 10 digit)</small>
                    </div>

                    <div class="input-group fade-in">
                        <label for="debit">Bank/Debit <span>*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-university"></i>
                            <select id="debit" name="debit">
                                <option value="">Pilih Bank</option>
                                <option value="BCA" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'BCA') ? 'selected' : ''; ?>>BCA</option>
                                <option value="Mandiri" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'Mandiri') ? 'selected' : ''; ?>>Mandiri</option>
                                <option value="BRI" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'BRI') ? 'selected' : ''; ?>>BRI</option>
                                <option value="BNI" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'BNI') ? 'selected' : ''; ?>>BNI</option>
                                <option value="BSI" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'BSI') ? 'selected' : ''; ?>>BSI</option>
                                <option value="CIMB Niaga" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'CIMB Niaga') ? 'selected' : ''; ?>>CIMB Niaga</option>
                                <option value="Danamon" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'Danamon') ? 'selected' : ''; ?>>Danamon</option>
                                <option value="Permata" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'Permata') ? 'selected' : ''; ?>>Permata</option>
                                <option value="Bank Lainnya" <?php echo (isset($_POST['debit']) && $_POST['debit'] == 'Bank Lainnya') ? 'selected' : ''; ?>>Bank Lainnya</option>
                            </select>
                        </div>
                        <small class="info-text">Pilih bank untuk penerimaan dana</small>
                    </div>
                </div>
                
                <!-- Input Password -->
                <div class="input-group fade-in delay-2">
                    <label for="password">Password <span>*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                    <div class="password-strength">
                        <span id="strength-text">Kekuatan password: -</span>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-bar"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Input Konfirmasi Password -->
                <div class="input-group fade-in delay-2">
                    <label for="confirm_password">Konfirmasi Password <span>*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Masukkan ulang password" required>
                    </div>
                    <small class="password-strength" id="password-match"></small>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-submit fade-in delay-3">
                    <i class="fas fa-user-plus"></i>
                    Daftar Sekarang
                </button>
                
                <!-- Link ke Login -->
                <div class="login-link fade-in delay-3">
                    Sudah punya akun? <a href="../login.php">Masuk di sini</a>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script>
        // Validasi NIK
        function validateNIK(input) {
            const nik = input.value;
            const helpText = document.getElementById('nik-help');
            
            if (/^\d{16}$/.test(nik)) {
                input.style.borderColor = 'var(--success)';
                helpText.style.color = 'var(--success)';
                helpText.textContent = 'Format NIK valid ✓';
            } else if (nik.length > 0) {
                input.style.borderColor = 'var(--danger)';
                helpText.style.color = 'var(--danger)';
                helpText.textContent = 'NIK harus 16 digit angka';
            } else {
                input.style.borderColor = '';
                helpText.style.color = 'var(--gray)';
                helpText.textContent = 'Format: 16 digit angka';
            }
        }

        // Pilih peran
        function selectRole(element) {
            const roleOptions = document.querySelectorAll('.role-option');
            roleOptions.forEach(option => {
                option.classList.remove('selected');
            });
            
            element.classList.add('selected');
            const selectedRole = element.getAttribute('data-value');
            document.getElementById('peran').value = selectedRole;
            
            // Tampilkan/sembunyikan input untuk penjual
            const sellerFields = document.getElementById('seller-fields');
            const noRekeningInput = document.getElementById('no_rekening');
            const debitSelect = document.getElementById('debit');
            
            if (selectedRole === 'Penjual') {
                sellerFields.style.display = 'block';
                // Tambahkan class untuk animasi
                sellerFields.classList.add('slide-in');
                sellerFields.classList.remove('fade-in');
                
                // Set required attribute
                noRekeningInput.required = true;
                debitSelect.required = true;
            } else {
                sellerFields.style.display = 'none';
                sellerFields.classList.remove('slide-in');
                
                // Hapus required attribute
                noRekeningInput.required = false;
                debitSelect.required = false;
                
                // Reset nilai
                noRekeningInput.value = '';
                debitSelect.value = '';
            }
        }

        // Cek kekuatan password
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Reset kelas
            strengthBar.className = 'strength-fill';
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Kekuatan password: -';
                strengthBar.style.backgroundColor = '';
            } else if (strength <= 1) {
                strengthBar.style.width = '33%';
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Kekuatan password: Lemah';
            } else if (strength <= 3) {
                strengthBar.style.width = '66%';
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Kekuatan password: Sedang';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Kekuatan password: Kuat';
            }
            
            checkPasswordMatch();
        }

        // Cek kecocokan password
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                matchText.style.color = 'var(--gray)';
            } else if (password === confirmPassword) {
                matchText.textContent = '✓ Password cocok';
                matchText.style.color = 'var(--success)';
            } else {
                matchText.textContent = '✗ Password tidak cocok';
                matchText.style.color = 'var(--danger)';
            }
        }

        // Event listener untuk password strength
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        // Event listener untuk konfirmasi password
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // Validasi form sebelum submit
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const peran = document.getElementById('peran').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validasi peran dipilih
            if (!peran) {
                e.preventDefault();
                alert('Silakan pilih peran (Penjual atau Pembeli)');
                return;
            }
            
            // Validasi tambahan untuk penjual
            if (peran === 'Penjual') {
                const noRekening = document.getElementById('no_rekening').value;
                const debit = document.getElementById('debit').value;
                
                if (!noRekening.trim()) {
                    e.preventDefault();
                    alert('Nomor rekening harus diisi untuk penjual!');
                    return;
                }
                
                if (!debit) {
                    e.preventDefault();
                    alert('Bank/debit harus dipilih untuk penjual!');
                    return;
                }
                
                // Validasi format nomor rekening
                if (!/^\d{10,}$/.test(noRekening)) {
                    e.preventDefault();
                    alert('Nomor rekening minimal 10 digit angka!');
                    return;
                }
            }
            
            // Validasi password
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak sama!');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return;
            }
            
            // Validasi NIK
            const nik = document.getElementById('nik').value;
            if (!/^\d{16}$/.test(nik)) {
                e.preventDefault();
                alert('NIK harus 16 digit angka!');
                return;
            }
        });

        // Efek interaktif pada input
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
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
            
            // Set peran default berdasarkan nilai yang sudah diisi sebelumnya
            const selectedPeran = document.getElementById('peran').value;
            if (selectedPeran) {
                const roleElement = document.querySelector(`.role-option[data-value="${selectedPeran}"]`);
                if (roleElement) {
                    selectRole(roleElement);
                }
            } else {
                // Set default ke Pembeli
                const defaultRole = document.querySelector('.role-option[data-value="Pembeli"]');
                if (defaultRole) {
                    selectRole(defaultRole);
                }
            }
            
            // Validasi NIK jika sudah ada nilai
            const nikInput = document.getElementById('nik');
            if (nikInput.value) {
                validateNIK(nikInput);
            }
            
            // Validasi password jika sudah ada nilai
            const passwordInput = document.getElementById('password');
            if (passwordInput.value) {
                checkPasswordStrength(passwordInput.value);
                checkPasswordMatch();
            }
        });
    </script>
</body>
</html>