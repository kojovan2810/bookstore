<?php
session_start();
include '../Src/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Ambil email berdasarkan mode
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mode OTP Login
    $email = trim($_POST['email']);
    $mode = 'login';
} elseif (isset($_SESSION['reset_email']) && isset($_GET['type']) && $_GET['type'] == 'forgot') {
    // Mode Forgot Password
    $email = $_SESSION['reset_email'];
    $mode = 'forgot';
} else {
    // Tidak ada data valid, kembali ke login
    header('Location: ../login.php');
    exit;
}

// Cek apakah email valid di database (petugas / admin)
$cek_penjual = $conn->query("SELECT * FROM penjual WHERE email_penjual='$email' LIMIT 1");
$cek_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli='$email' LIMIT 1");
$cek_admin = $conn->query("SELECT * FROM super_admin WHERE email_admin='$email' LIMIT 1");


if ($cek_penjual->num_rows == 0 && $cek_pembeli->num_rows == 0 && $cek_admin->num_rows == 0) {
    echo "<script>alert('Email tidak terdaftar!'); location.href='login.php';</script>";
    exit;
}

// Buat OTP
$otp = rand(100000, 999999);
$_SESSION['otp'] = $otp;
$_SESSION['email_otp'] = $email;
$_SESSION['otp_expiry'] = time() + 300; // Berlaku 5 menit

// Konfigurasi PHPMailer
$mail = new phpmailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jovandwilly28@gmail.com'; // ganti dengan email kamu
    $mail->Password   = 'gzwyowkqqwdhtidl';       // gunakan App Password Gmail
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('jovandwilly28@gmail.com', 'BukuBook Security');
    $mail->addAddress($email);

    $mail->isHTML(true);

    if ($mode == 'forgot') {
        $mail->Subject = 'Kode OTP Reset Password Anda';
        $mail->Body = "
            <h3>Halo!</h3>
            <p>Berikut kode OTP untuk mengatur ulang password Anda:</p>
            <h2>$otp</h2>
            <p>Kode ini berlaku selama 5 menit.</p>
            <hr>
            <small>Jangan bagikan kode ini kepada siapa pun.</small>
        ";
    } else {
        $mail->Subject = 'Kode OTP Login Anda';
        $mail->Body = "
            <h3>Halo!</h3>
            <p>Berikut kode OTP untuk login Anda:</p>
            <h2>$otp</h2>
            <p>Kode ini berlaku selama 5 menit.</p>
            <hr>
            <small>Jangan bagikan kode ini ke siapa pun.</small>
        ";
    }

    $mail->send();

    if ($mode == 'forgot') {
        echo "<script>alert('Kode OTP untuk reset password telah dikirim ke email Anda.'); location.href='fp_validasi_otp.php';</script>";
    } else {
        echo "<script>alert('Kode OTP untuk login telah dikirim ke email Anda.'); location.href='fp_validasi_otp.php';</script>";
    }

} catch (Exception $e) {
    echo "<script>alert('Gagal mengirim email. Error: {$mail->ErrorInfo}'); history.back();</script>";
}
?>
