<?php
session_start();
include '../Src/config.php'; // pastikan path benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dari session
    $email = $_SESSION['email_otp'] ?? '';
    $otp_input = trim($_POST['otp_code'] ?? '');
    $otp_session = $_SESSION['otp'] ?? '';
    $otp_expiry = $_SESSION['otp_expiry'] ?? 0;

    // Validasi input dasar
    if (empty($email) || empty($otp_input)) {
        echo "<script>alert('Email atau kode OTP tidak boleh kosong!');history.back();</script>";
        exit;
    }

    // Validasi OTP
    if ($otp_input != $otp_session) {
        echo "<script>alert('Kode OTP salah!');history.back();</script>";
        exit;
    }

    // Validasi waktu (pakai time() bukan string)
    if (time() > $otp_expiry) {
        echo "<script>alert('Kode OTP sudah kedaluwarsa! Silakan kirim ulang.');history.back();</script>";
        exit;
    }

    // Jika valid, simpan email untuk ganti password
    $_SESSION['reset_email'] = $email;

    // Hapus data OTP agar tidak bisa dipakai ulang
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    unset($_SESSION['email_otp']);

    echo "<script>
        alert('Kode OTP benar! Silakan ubah password Anda.');
        window.location.href = 'fp_reset_password.php';
    </script>";
    exit;
} else {
    header('Location: fp_validasi_email.php');
    exit;
}
?>
