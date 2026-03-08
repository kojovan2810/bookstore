<?php
include "config.php";
session_start();
if(isset($_SESSION['email_penjual'])){
    $email = $_SESSION['email_penjual'];
    $update = $conn->query("UPDATE penjual SET status='Offline' WHERE email_penjual= '$email'");
    echo "<script>alert('Terima kasih Atas Kunjungannya!'); window.location='../login.php';</script>";
}elseif(isset($_SESSION['email_pembeli'])){
    $email = $_SESSION['email_pembeli'];
    $update = $conn->query("UPDATE pembeli SET status='Offline' WHERE email_pembeli= '$email'");
    echo "<script>alert('Terima kasih Atas Kunjungannya!'); window.location='../login.php';</script>";
}else{
    session_destroy();
    echo "<script>alert('Terima kasih Atas Kunjungannya!'); window.location='../login.php';</script>";
}

?>
