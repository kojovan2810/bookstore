<?php
session_start();
include "../../Src/config.php";
include "hitung_notif_chat_penjual.php";
include "hitung_notif_pesanan.php";
// pesanan
$total_order_notif_penjual = getTotalOrderNotificationsPenjual($conn, $email_penjual);
$order_notifications_detail = getOrderNotificationsDetailPenjual($conn, $email_penjual);
// chat
$total_chat_notif_penjual = getChatCountPenjual($conn, $email_penjual);
// Cek apakah penjual sudah login
if (!isset($_SESSION['email_penjual'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

$email_penjual = $_SESSION['email_penjual'];
$data_penjual = $conn->query("SELECT * FROM penjual WHERE email_penjual = '$email_penjual'")->fetch_assoc();
if(!$data_penjual){
    header("Location: ../../login.php");
}

// Daftar ekspedisi
$ekspedisi_list = [
    'J&T Express',
    'JNE',
    'SiCepat',
    'Anteraja',
    'Ninja Xpress',
    'Lion Parcel',
    'Pos Indonesia',
    'SAP Express',
    'ID Express',
    'REX Express',
    'Tiki',
    'Wahana',
    'Pandu Logistics',
    'JET Express',
    'Deliveree',
    'GrabExpress',
    'GoSend',
    'Lainnya'
];

// Handle approve pesanan
if (isset($_GET['approve'])) {
    $kode_pesanan = mysqli_real_escape_string($conn, $_GET['approve']);
    $action = mysqli_real_escape_string($conn, $_GET['action']);
    
    // Validasi bahwa pesanan ini milik penjual yang login
    $check = $conn->query("SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan' AND email_penjual = '$email_penjual'");
    
    if ($check->num_rows > 0) {
        $pesanan_data = $check->fetch_assoc();
        
        if ($action == 'setuju') {
            // Cek stok produk sebelum menyetujui
            $judul_buku = $pesanan_data['judul_buku'];
            $qty = intval($pesanan_data['qty']);
            
            // Ambil stok produk dari tabel produk_buku
            $produk_check = $conn->query("SELECT stok FROM produk_buku WHERE judul_buku = '$judul_buku' AND email_penjual = '$email_penjual'");
            
            if ($produk_check->num_rows > 0) {
                $produk_data = $produk_check->fetch_assoc();
                $stok_sekarang = intval($produk_data['stok']);
                
                if ($stok_sekarang >= $qty) {
                    // Kurangi stok
                    $stok_baru = $stok_sekarang - $qty;
                    $conn->query("UPDATE produk_buku SET stok = $stok_baru WHERE judul_buku = '$judul_buku' AND email_penjual = '$email_penjual'");
                    
                    // Update status pesanan
                    $update = $conn->query("UPDATE pesanan SET approve = 'Disetujui' WHERE kode_pesanan = '$kode_pesanan'");
                    
                    if ($update) {
                        echo "<script>
                                alert('Pesanan berhasil disetujui! Stok produk telah dikurangi.');
                                window.location.href = 'pesanan.php';
                              </script>";
                    }
                } else {
                    echo "<script>
                            alert('Stok produk tidak mencukupi! Stok tersedia: $stok_sekarang, dibutuhkan: $qty');
                            window.location.href = 'pesanan.php';
                          </script>";
                }
            } else {
                echo "<script>alert('Produk tidak ditemukan!');</script>";
            }
        } elseif ($action == 'tolak') {
            $update = $conn->query("UPDATE pesanan SET approve = 'Ditolak', status = 'Refund' WHERE kode_pesanan = '$kode_pesanan'");
            if ($update) {
                echo "<script>
                        alert('Pesanan ditolak!');
                        window.location.href = 'pesanan.php';
                      </script>";
            }
        }
    } else {
        echo "<script>alert('Anda tidak memiliki akses ke pesanan ini!');</script>";
    }
}

// Handle input ekspedisi dan resi
if (isset($_POST['input_ekspedisi_resi'])) {
    $kode_pesanan = mysqli_real_escape_string($conn, $_POST['kode_pesanan']);
    $ekspedisi = mysqli_real_escape_string($conn, $_POST['ekspedisi']);
    $no_resi = mysqli_real_escape_string($conn, $_POST['no_resi']);
    
    // Validasi bahwa pesanan ini milik penjual yang login dan sudah disetujui
    $check = $conn->query("SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan' AND email_penjual = '$email_penjual' AND approve = 'Disetujui'");
    
    if ($check->num_rows > 0) {
        // Jika ekspedisi "Lainnya", ambil dari input lainnya
        if ($ekspedisi == 'Lainnya' && isset($_POST['ekspedisi_lainnya'])) {
            $ekspedisi = mysqli_real_escape_string($conn, $_POST['ekspedisi_lainnya']);
        }
        
        $update = $conn->query("UPDATE pesanan SET ekspedisi = '$ekspedisi', no_resi = '$no_resi', status = 'Dikirim' WHERE kode_pesanan = '$kode_pesanan'");
        if ($update) {
            echo "<script>
                    alert('Ekspedisi dan nomor resi berhasil diinput! Status berubah menjadi Dikirim.');
                    window.location.href = 'pesanan.php';
                  </script>";
        } else {
            echo "<script>alert('Gagal menginput data pengiriman!');</script>";
        }
    } else {
        echo "<script>alert('Pesanan tidak ditemukan atau belum disetujui!');</script>";
    }
}

// Handle delete pesanan
if (isset($_GET['delete'])) {
    $kode_pesanan = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Validasi bahwa pesanan ini milik penjual yang login dan ditolak
    $check = $conn->query("SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan' AND email_penjual = '$email_penjual' AND approve = 'Ditolak'");
    
    if ($check->num_rows > 0) {
        $delete = $conn->query("DELETE FROM pesanan WHERE kode_pesanan = '$kode_pesanan'");
        if ($delete) {
            echo "<script>
                    alert('Pesanan berhasil dihapus!');
                    window.location.href = 'pesanan.php';
                  </script>";
        } else {
            echo "<script>alert('Gagal menghapus pesanan!');</script>";
        }
    } else {
        echo "<script>alert('Pesanan tidak ditemukan atau tidak bisa dihapus!');</script>";
    }
}

// Pagination setup
$limit = 5; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Query dengan filter dan pagination
$where_clause = "WHERE p.email_penjual = '$email_penjual'";

if ($filter == 'pending') {
    $where_clause .= " AND p.approve IS NULL";
} elseif ($filter == 'approved') {
    $where_clause .= " AND p.approve = 'Disetujui' AND p.status IS NULL";
} elseif ($filter == 'shipped') {
    $where_clause .= " AND p.status = 'Dikirim'";
} elseif ($filter == 'rejected') {
    $where_clause .= " AND p.approve = 'Ditolak'";
}

$pesanan_query = $conn->query("
    SELECT p.*, pb.kategori_buku, pb.harga_buku, pb.stok 
    FROM pesanan p
    LEFT JOIN produk_buku pb ON p.judul_buku = pb.judul_buku AND p.email_penjual = pb.email_penjual
    $where_clause
    ORDER BY 
        CASE 
            WHEN p.approve IS NULL THEN 0
            WHEN p.approve = 'Disetujui' AND p.status IS NULL THEN 1
            WHEN p.approve = 'Disetujui' AND p.status = 'Dikirim' THEN 2
            WHEN p.approve = 'Ditolak' THEN 3
            ELSE 4
        END,
        p.kode_pesanan DESC
    LIMIT $limit OFFSET $offset
");

// Hitung total data untuk pagination
$total_data_query = $conn->query("
    SELECT COUNT(*) as total 
    FROM pesanan p
    WHERE p.email_penjual = '$email_penjual'
");
$total_data = $total_data_query->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Hitung statistik pesanan
$total_pesanan = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE email_penjual = '$email_penjual'")->fetch_assoc()['total'];

$pesanan_pending = $conn->query("
    SELECT COUNT(*) as total 
    FROM pesanan 
    WHERE email_penjual = '$email_penjual' 
    AND approve IS NULL
")->fetch_assoc()['total'];

$pesanan_disetujui = $conn->query("
    SELECT COUNT(*) as total 
    FROM pesanan 
    WHERE email_penjual = '$email_penjual' 
    AND approve = 'Disetujui'
    AND status IS NULL
")->fetch_assoc()['total'];

$pesanan_dikirim = $conn->query("
    SELECT COUNT(*) as total 
    FROM pesanan 
    WHERE email_penjual = '$email_penjual' 
    AND status = 'Dikirim'
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Kelola Pesanan</title>
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
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
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
            overflow-y: auto;
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
        

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
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
            text-align: right;
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
        /* Penambahan Style Notifikasi Lonceng */
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            /* margin-left: 54rem; */
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            font-size: 20px;
            color: var(--gray);
            transition: color 0.3s;
        }

        .notification-bell:hover {
            color: var(--primary);
        }

        .bell-badge {
            position: absolute;
            top: -11px;
            right: -20px;
            background-color: var(--danger);
            color: white;
            font-size: 11px;
            padding: 1px 5px;
            border-radius: 50%;
            border: 2px solid white;
            font-weight: bold;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-message {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 30px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-1 .stat-icon {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .stat-2 .stat-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .stat-3 .stat-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .stat-4 .stat-icon {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .stat-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-content p {
            font-size: 15px;
            color: var(--gray);
        }

        /* Table Container */
        .table-container {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }

        /* Table Styles - DIPERBAIKI untuk kolom lebih sedikit */
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Minimal width untuk semua kolom */
            table-layout: fixed;
        }

        .table th {
            text-align: left;
            padding: 15px 12px;
            background-color: #f8f9fa;
            color: var(--gray);
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid var(--light-gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
            overflow: hidden;
            text-overflow: ellipsis;
            word-wrap: break-word;
        }

        /* Atur lebar kolom - HANYA 6 KOLOM */
        .table th:nth-child(1),
        .table td:nth-child(1) { /* Kode Pesanan */
            width: 150px;
            max-width: 150px;
            min-width: 150px;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) { /* Judul Buku */
            width: 250px;
            max-width: 250px;
            min-width: 250px;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) { /* Status */
            width: 140px;
            max-width: 140px;
            min-width: 140px;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) { /* Ekspedisi */
            width: 140px;
            max-width: 140px;
            min-width: 140px;
        }

        .table th:nth-child(5),
        .table td:nth-child(5) { /* No Resi */
            width: 160px;
            max-width: 160px;
            min-width: 160px;
        }

        .table th:nth-child(6),
        .table td:nth-child(6) { /* Aksi */
            width: 200px;
            max-width: 200px;
            min-width: 200px;
            text-align: center;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        /* Styling untuk konten dalam sel */
        .kode-pesanan {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .judul-buku {
            font-size: 14px;
            font-weight: 500;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
            max-height: 2.8em;
        }

        /* Status Badges */
        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
            text-align: center;
            width: 100%;
            max-width: 110px;
        }

        .badge-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .badge-approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .badge-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .badge-shipped {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .badge-accepted {
            background-color: var(--success);
            color: white;
        }

        .badge-refund {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }

        /* Ekspedisi Badge */
        .ekspedisi-badge {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            display: block;
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }

        .btn {
            padding: 7px 9px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            white-space: nowrap;
            min-width: 34px;
            height: 34px;
            flex-shrink: 0;
        }

        /* Tombol dengan teks */
        .btn-with-text {
            min-width: 70px;
        }

        .btn i {
            font-size: 12px;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-info {
            background-color: var(--info);
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
        }

        .btn-warning {
            background-color: var(--warning);
            color: var(--dark);
        }

        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--danger);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
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

        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

        /* Input lainnya untuk ekspedisi */
        .lainnya-input {
            margin-top: 10px;
            display: none;
        }

        .lainnya-input.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Bukti Pembayaran Modal */
        .bukti-modal .modal {
            max-width: 700px;
        }

        .bukti-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid var(--light-gray);
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 20px;
            background-color: white;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
            color: var(--dark);
            display: inline-block;
        }

        .tab:hover {
            background-color: #f8f9fa;
        }

        .tab.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Detail Product Info */
        .detail-product-info {
            padding: 20px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        /* Modal detail agar bisa scroll */
        #detailModal .modal {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        #detailModal #detailContent {
            overflow-y: auto;
            max-height: 65vh;
            padding-right: 10px;
        }

        .detail-section h4 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        .detail-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .detail-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .detail-value.price {
            color: var(--success);
        }

        .detail-value.stock {
            color: var(--warning);
        }

        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .pagination {
            display: flex;
            list-style: none;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
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
            flex-wrap: wrap;
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
                width: 100%;
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
            
            .table {
                min-width: 700px;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 0 15px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table-container {
                padding: 15px;
                margin-left: -15px;
                margin-right: -15px;
                border-radius: 0;
                width: calc(100% + 30px);
            }
            
            .table {
                min-width: 600px;
            }
            
            .table th, .table td {
                padding: 12px 8px;
                font-size: 12px;
            }
            
            /* Perbaikan action buttons untuk mobile */
            .action-buttons {
                gap: 4px;
            }
            
            .btn {
                padding: 5px 7px;
                font-size: 11px;
                height: 30px;
                min-width: 30px;
            }
            
            .btn i {
                font-size: 11px;
            }
            
            .btn-with-text {
                min-width: 60px;
            }
            
            /* Perkecil ukuran kolom untuk mobile */
            .table th:nth-child(1),
            .table td:nth-child(1) { /* Kode Pesanan */
                width: 120px;
                max-width: 120px;
                min-width: 120px;
            }
            
            .table th:nth-child(6),
            .table td:nth-child(6) { /* Aksi */
                width: 180px;
                max-width: 180px;
                min-width: 180px;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .page-link {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-tabs {
                flex-direction: column;
            }
            
            .tab {
                width: 100%;
                text-align: center;
            }
            
            .modal {
                padding: 20px;
                width: 95%;
                margin: 10px;
            }
            
            .table {
                min-width: 550px;
            }
            
            /* Perkecil lagi untuk layar sangat kecil */
            .table th:nth-child(1),
            .table td:nth-child(1) { /* Kode Pesanan */
                width: 100px;
                max-width: 100px;
                min-width: 100px;
            }
            
            .kode-pesanan {
                font-size: 11px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 3px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            /* Perbaikan untuk tampilan sangat kecil - sembunyikan teks pada tombol */
            .btn span:not(.fa) {
                display: none;
            }
            
            .btn-with-text {
                min-width: 34px;
                width: 34px;
                justify-content: center;
            }
            
            .btn-with-text i {
                margin: 0;
            }
        }

        /* Tablet Landscape */
        @media (min-width: 768px) and (max-width: 1024px) {
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                min-width: 700px;
            }
            
            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .btn {
                min-width: 32px;
                height: 32px;
                font-size: 11px;
            }
        }

        /* Desktop kecil */
        @media (min-width: 1025px) and (max-width: 1366px) {
            .table {
                min-width: 800px;
            }
            
            .action-buttons {
                gap: 5px;
            }
            
            .btn {
                padding: 6px 8px;
                font-size: 11px;
                height: 32px;
            }
        }

        /* Scrollbar styling untuk tabel */
        .table-container::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Menu toggle untuk mobile */
        .menu-toggle {
            display: none;
        }

        /* Style untuk text-muted */
        .text-muted {
            color: var(--gray) !important;
            font-style: italic;
            font-size: 12px;
        }

        /* Format harga */
        .harga {
            font-weight: 600;
            color: var(--success);
            font-size: 13px;
        }

        /* No Resi styling */
        .no-resi {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Payment Method */
        .payment-method {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            white-space: nowrap;
            font-size: 13px;
            justify-content: center;
            width: 100%;
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
                    <a href="produk.php" class="nav-item">
                        <i class="fas fa-box"></i>
                        <span class="nav-text">Produk</span>
                    </a>
                </li>
                <li>
                    <a href="pesanan.php" class="nav-item active order-nav">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="nav-text">Pesanan</span>
                        <?php if ($total_order_notif_penjual > 0): ?>
                        <span class="order-badge-penjual combo" id="orderBadgePenjual" title="<?php echo $total_order_notif_penjual; ?> pesanan perlu ditindak">
                            <?php echo ($total_order_notif_penjual > 99) ? '99+' : $total_order_notif_penjual; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="laporan.php" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Laporan</span>
                    </a>
                </li>
                <li>
                <a href="room_chat_penjual.php" class="nav-item">
                    <i class="fas fa-comment"></i>
                    <span class="nav-text">Chat</span>
                    <?php if ($total_chat_notif_penjual > 0): ?>
                        <span class="chat-badge" id="chatBadgePenjual" title="<?php echo $total_chat_notif_penjual; ?> pesan belum dibaca">
                            <?php echo ($total_chat_notif_penjual > 99) ? '99+' : $total_chat_notif_penjual; ?>
                        </span>
                    <?php endif; ?>
                </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <div class="section-title">NETWORK</div>
            <ul class="nav-links">
                <li>
                    <a href="penjual_lain.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Penjual Lain</span>
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

        <!-- Menu SETTING -->
        <div class="nav-section">
            <div class="section-title">SETTING</div>
            <ul class="nav-links">
                <li>
                    <a href="../../Src/profile.php" class="nav-item">
                        <i class="fas fa-user"></i>
                        <span class="nav-text">Profil Saya</span>
                    </a>
                </li>
                <li>
                    <a href="../../Src/logout.php" class="nav-item logout-link" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
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


        <div class="user-info">
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($data_penjual['nama_penjual']); ?></div>
                <div class="user-role">Penjual</div>
            </div>
            <?php 
            $foto = isset($data_penjual['foto']) ? $data_penjual['foto'] : null;
            $src = $foto ? "../../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data_penjual['nama_penjual']) . "&background=4361ee&color=fff&size=120";
            ?>
            <img src="<?php echo $src; ?>" 
                alt="Profile" 
                class="user-avatar"
                onclick="window.location.href='../../Src/profile.php'">
        </div>
        <div class="topbar-actions">
            <?php 
            // Menghitung total gabungan chat dan pesanan
            $total_gabungan_notif = ($total_order_notif_penjual ?? 0) + ($total_chat_notif_penjual ?? 0);
            ?>
            <div class="notification-bell" onclick="window.location.href='pesanan.php'" title="Notifikasi Baru">
                <i class="fas fa-bell"></i>
                <?php if ($total_gabungan_notif > 0): ?>
                    <span class="bell-badge">
                        <?php echo ($total_gabungan_notif > 99) ? '99+' : $total_gabungan_notif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </header>

        <!-- Content Area -->
        <main class="content">
            <h1 class="page-title">
                <i class="fas fa-shopping-cart"></i>Kelola Pesanan
            </h1>
            <p class="welcome-message">
                Kelola pesanan dari pembeli, setujui pesanan, dan input ekspedisi serta nomor resi pengiriman.
            </p>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card stat-1">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_pesanan; ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>
                
                <div class="stat-card stat-2">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $pesanan_pending; ?></h3>
                        <p>Menunggu Persetujuan</p>
                    </div>
                </div>
                
                <div class="stat-card stat-3">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $pesanan_disetujui; ?></h3>
                        <p>Disetujui (Belum Dikirim)</p>
                    </div>
                </div>
                
                <div class="stat-card stat-4">
                    <div class="stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $pesanan_dikirim; ?></h3>
                        <p>Sudah Dikirim</p>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div style="margin-bottom: 20px;">
                <div style="position: relative; max-width: 400px;">
                    <input type="text" id="searchInput" placeholder="Cari pesanan..." 
                           style="width: 100%; padding: 12px 40px 12px 20px; border: 1px solid var(--light-gray); border-radius: 8px; font-size: 15px;">
                    <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--gray);"></i>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs" id="filterTabs">
                <a href="?filter=all&page=1" class="tab <?php echo ($filter == 'all') ? 'active' : ''; ?>">Semua Pesanan</a>
                <a href="?filter=pending&page=1" class="tab <?php echo ($filter == 'pending') ? 'active' : ''; ?>">Menunggu Persetujuan</a>
                <a href="?filter=approved&page=1" class="tab <?php echo ($filter == 'approved') ? 'active' : ''; ?>">Disetujui</a>
                <a href="?filter=shipped&page=1" class="tab <?php echo ($filter == 'shipped') ? 'active' : ''; ?>">Sudah Dikirim</a>
                <a href="?filter=rejected&page=1" class="tab <?php echo ($filter == 'rejected') ? 'active' : ''; ?>">Ditolak</a>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <?php if ($pesanan_query->num_rows > 0): ?>
                    <table class="table" id="pesananTable">
                        <thead>
                            <tr>
                                <th>Kode Pesanan</th>
                                <th>Judul Buku</th>
                                <th>Status</th>
                                <th>Ekspedisi</th>
                                <th>No Resi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pesanan = $pesanan_query->fetch_assoc()): ?>
                                <?php 
                                // Tentukan badge berdasarkan status
                                $badge_class = '';
                                $status_text = '';
                                
                                if ($pesanan['approve'] === 'Ditolak') {
                                    $badge_class = 'badge-rejected';
                                    $status_text = 'Ditolak';
                                } elseif ($pesanan['approve'] === 'Disetujui' && $pesanan['status'] === 'Dikirim') {
                                    $badge_class = 'badge-shipped';
                                    $status_text = 'Dikirim';
                                } elseif ($pesanan['approve'] === 'Disetujui' && $pesanan['status'] === 'Refund') {
                                    $badge_class = 'badge-refund';
                                    $status_text = 'Refund';
                                } elseif ($pesanan['approve'] === 'Disetujui' && $pesanan['status'] === 'Diterima') {
                                    $badge_class = 'badge-accepted';
                                    $status_text = 'Diterima';
                                } elseif ($pesanan['approve'] === 'Disetujui') {
                                    $badge_class = 'badge-approved';
                                    $status_text = 'Disetujui';
                                } else {
                                    $badge_class = 'badge-pending';
                                    $status_text = 'Menunggu';
                                }
                                ?>
                                <tr class="pesanan-row" 
                                    data-status="<?php echo strtolower($status_text); ?>"
                                    data-kode="<?php echo $pesanan['kode_pesanan']; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($pesanan['kode_pesanan']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($pesanan['judul_buku']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($pesanan['ekspedisi']): ?>
                                            <span class="ekspedisi-badge">
                                                <?php echo htmlspecialchars($pesanan['ekspedisi']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pesanan['no_resi']): ?>
                                            <strong><?php echo htmlspecialchars($pesanan['no_resi']); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- Tombol delete untuk pesanan yang ditolak -->
                                            <?php if ($pesanan['approve'] == 'Ditolak'): ?>
                                                <button class="btn btn-danger" onclick="deletePesanan('<?php echo $pesanan['kode_pesanan']; ?>')" title="Delete">
                                                    <i class="fas fa-trash"></i> 
                                                </button>
                                            <?php endif; ?>

                                            <!-- Tombol setujui/tolak untuk pesanan pending -->
                                            <?php if (!$pesanan['approve']): ?>
                                                <button class="btn btn-success" onclick="setujuiPesanan('<?php echo $pesanan['kode_pesanan']; ?>')" title="Setujui">
                                                    <i class="fas fa-check"></i> 
                                                </button>
                                                <button class="btn btn-danger" onclick="tolakPesanan('<?php echo $pesanan['kode_pesanan']; ?>')" title="Tolak">
                                                    <i class="fas fa-times"></i> 
                                                </button>
                                            <?php endif; ?>

                                            <!-- Tombol input ekspedisi dan resi untuk pesanan yang disetujui tapi belum dikirim -->
                                            <?php if ($pesanan['approve'] == 'Disetujui' && !$pesanan['status']): ?>
                                                <button class="btn btn-primary" onclick="inputEkspedisiResi('<?php echo $pesanan['kode_pesanan']; ?>')" title="Input Ekspedisi & Resi">
                                                    <i class="fas fa-truck"></i> Kirim
                                                </button>
                                            <?php endif; ?>

                                            <!-- Tombol lihat bukti pembayaran -->
                                            <?php if ($pesanan['bukti_pembayaran']): ?>
                                                <button class="btn btn-info" onclick="lihatBukti('<?php echo htmlspecialchars($pesanan['bukti_pembayaran']); ?>', '<?php echo htmlspecialchars($pesanan['kode_pesanan']); ?>')" title="Bukti">
                                                    <i class="fas fa-receipt"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Tombol lihat detail -->
                                            <button class="btn btn-info" onclick="lihatDetail(
                                                '<?php echo addslashes($pesanan['kode_pesanan']); ?>',
                                                '<?php echo addslashes($pesanan['judul_buku']); ?>',
                                                <?php echo intval($pesanan['qty']); ?>,
                                                '<?php echo addslashes($pesanan['nama_pembeli']); ?>',
                                                '<?php echo addslashes($pesanan['alamat_pembeli']); ?>',
                                                '<?php echo addslashes($pesanan['metode_bayar']); ?>',
                                                '<?php echo addslashes($status_text); ?>',
                                                '<?php echo addslashes($pesanan['kategori_buku'] ?? ''); ?>',
                                                <?php echo intval($pesanan['harga_buku'] ?? 0); ?>,
                                                <?php echo intval($pesanan['stok'] ?? 0); ?>,
                                                '<?php echo addslashes($pesanan['no_resi'] ?? ''); ?>',
                                                '<?php echo addslashes($pesanan['ekspedisi'] ?? ''); ?>',
                                                '<?php echo addslashes($pesanan['harga_satuan'] ?? ''); ?>',
                                                '<?php echo addslashes($pesanan['total_harga'] ?? ''); ?>'
                                            )" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div>
                            Menampilkan <?php echo min($offset + 1, $total_data); ?> - <?php echo min($offset + $limit, $total_data); ?> dari <?php echo $total_data; ?> pesanan
                        </div>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
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
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
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
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-shopping-cart"></i>
                        <h4>Belum ada pesanan</h4>
                        <p>Pesanan dari pembeli akan muncul di sini</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Pesanan</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Modal Input Ekspedisi dan Resi -->
    <div class="modal-overlay" id="ekspedisiResiModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-truck"></i>Input Ekspedisi & Nomor Resi</h3>
                <button class="close-modal" onclick="closeModal('ekspedisiResiModal')">&times;</button>
            </div>
            <form method="POST" action="" id="ekspedisiResiForm">
                <input type="hidden" id="ekspedisi_kode_pesanan" name="kode_pesanan">
                
                <div class="form-group">
                    <label for="ekspedisi">Pilih Ekspedisi</label>
                    <select class="form-select" id="ekspedisi" name="ekspedisi" required onchange="toggleLainnyaInput()">
                        <option value="">-- Pilih Ekspedisi --</option>
                        <?php foreach ($ekspedisi_list as $ekspedisi): ?>
                            <option value="<?php echo htmlspecialchars($ekspedisi); ?>">
                                <?php echo htmlspecialchars($ekspedisi); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group lainnya-input" id="ekspedisiLainnyaGroup">
                    <label for="ekspedisi_lainnya">Nama Ekspedisi Lainnya</label>
                    <input type="text" class="form-control" id="ekspedisi_lainnya" name="ekspedisi_lainnya" placeholder="Masukkan nama ekspedisi lainnya">
                </div>
                
                <div class="form-group">
                    <label for="no_resi">Nomor Resi Pengiriman</label>
                    <input type="text" class="form-control" id="no_resi" name="no_resi" required placeholder="Masukkan nomor resi">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal('ekspedisiResiModal')">Batal</button>
                    <button type="submit" class="btn btn-success" name="input_ekspedisi_resi">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail Pesanan -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i>Detail Pesanan</h3>
                <button class="close-modal" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div id="detailContent">
                <!-- Detail akan diisi via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('detailModal')">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Bukti Pembayaran -->
    <div class="modal-overlay bukti-modal" id="buktiModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i>Bukti Pembayaran</h3>
                <button class="close-modal" onclick="closeModal('buktiModal')">&times;</button>
            </div>
            <div id="buktiContent">
                <!-- Gambar bukti pembayaran akan diisi via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('buktiModal')">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            // Reset form saat modal dibuka
            if (modalId === 'ekspedisiResiModal') {
                document.getElementById('ekspedisi').value = '';
                document.getElementById('ekspedisiLainnyaGroup').classList.remove('show');
                document.getElementById('ekspedisi_lainnya').value = '';
                document.getElementById('no_resi').value = '';
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        // Toggle input ekspedisi lainnya
        function toggleLainnyaInput() {
            const ekspedisiSelect = document.getElementById('ekspedisi');
            const lainnyaGroup = document.getElementById('ekspedisiLainnyaGroup');
            
            if (ekspedisiSelect.value === 'Lainnya') {
                lainnyaGroup.classList.add('show');
            } else {
                lainnyaGroup.classList.remove('show');
                document.getElementById('ekspedisi_lainnya').value = '';
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.pesanan-row');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Setujui pesanan
        function setujuiPesanan(kodePesanan) {
            if (confirm('Apakah Anda yakin ingin menyetujui pesanan ini? Stok akan dikurangi sesuai quantity.')) {
                window.location.href = 'pesanan.php?approve=' + kodePesanan + '&action=setuju';
            }
        }

        // Tolak pesanan
        function tolakPesanan(kodePesanan) {
            if (confirm('Apakah Anda yakin ingin menolak pesanan ini?')) {
                window.location.href = 'pesanan.php?approve=' + kodePesanan + '&action=tolak';
            }
        }

        // Delete pesanan
        function deletePesanan(kodePesanan) {
            if (confirm('Apakah Anda yakin ingin menghapus pesanan ini? Tindakan ini tidak dapat dibatalkan.')) {
                window.location.href = 'pesanan.php?delete=' + kodePesanan;
            }
        }

        // Input ekspedisi dan resi
        function inputEkspedisiResi(kodePesanan) {
            document.getElementById('ekspedisi_kode_pesanan').value = kodePesanan;
            openModal('ekspedisiResiModal');
        }

        // Format number dengan titik sebagai pemisah ribuan
        function formatNumber(number) {
            if (isNaN(number) || !number) return '0';
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Lihat detail pesanan
        function lihatDetail(kodePesanan, judulBuku, qty, namaPembeli, alamatPembeli, metodeBayar, statusText, kategoriBuku, hargaBuku, stok, noResi, ekspedisi, hargaSatuan, totalHarga) {
            const detailContent = document.getElementById('detailContent');
            const hargaPerItem = hargaSatuan || hargaBuku;
            const total = totalHarga || (hargaPerItem * qty);
            
            detailContent.innerHTML = `
                <div class="detail-product-info">
                    <!-- Informasi Pesanan -->
                    <div class="detail-section">
                        <h4><i class="fas fa-shopping-cart"></i> Informasi Pesanan</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Kode Pesanan</div>
                                <div class="detail-value">${kodePesanan}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Judul Buku</div>
                                <div class="detail-value">${judulBuku}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Kategori</div>
                                <div class="detail-value">${kategoriBuku || '-'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">${statusText}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Harga & Kuantitas -->
                    <div class="detail-section">
                        <h4><i class="fas fa-tag"></i> Informasi Harga & Kuantitas</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Harga Satuan</div>
                                <div class="detail-value price">Rp ${formatNumber(hargaPerItem)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Jumlah (Qty)</div>
                                <div class="detail-value">${qty} item</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Total Harga</div>
                                <div class="detail-value price">Rp ${formatNumber(total)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Stok Tersedia</div>
                                <div class="detail-value stock">${stok} item</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Pembeli -->
                    <div class="detail-section">
                        <h4><i class="fas fa-user"></i> Informasi Pembeli</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Nama Pembeli</div>
                                <div class="detail-value">${namaPembeli}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Alamat Pengiriman</div>
                                <div class="detail-value">${alamatPembeli}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Metode Pembayaran</div>
                                <div class="detail-value">
                                    <span class="payment-method">
                                        ${metodeBayar === 'QRIS' ? '<i class="fas fa-qrcode"></i> QRIS' : 
                                          metodeBayar === 'Transfer' ? '<i class="fas fa-university"></i> Transfer' : 
                                          '<i class="fas fa-question"></i> ' + metodeBayar}
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email Penjual</div>
                                <div class="detail-value"><?php echo htmlspecialchars($email_penjual); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Pengiriman -->
                    <div class="detail-section">
                        <h4><i class="fas fa-truck"></i> Informasi Pengiriman</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Ekspedisi</div>
                                <div class="detail-value">${ekspedisi || 'Belum dipilih'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Nomor Resi</div>
                                <div class="detail-value">${noResi || 'Belum diinput'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status Pengiriman</div>
                                <div class="detail-value">
                                    <span class="badge ${statusText === 'Dikirim' ? 'badge-shipped' : 
                                                      statusText === 'Disetujui' ? 'badge-approved' : 
                                                      statusText === 'Menunggu' ? 'badge-pending' : 
                                                      statusText === 'Ditolak' ? 'badge-rejected' : 
                                                      statusText === 'Diterima' ? 'badge-accepted' : 'badge-refund'}">
                                        ${statusText}
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Tanggal Pesanan</div>
                                <div class="detail-value"><?php echo date('d/m/Y H:i'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Status -->
                    <div class="detail-section">
                        <h4><i class="fas fa-info-circle"></i> Informasi Status</h4>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary);">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <i class="fas fa-info-circle" style="color: var(--primary);"></i>
                                <div>
                                    <div style="font-weight: 600; color: var(--dark);">Status: ${statusText}</div>
                                    <div style="font-size: 14px; color: var(--gray);">
                                        ${getStatusInfo(statusText)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            openModal('detailModal');
        }

        // Fungsi untuk mendapatkan informasi status
        function getStatusInfo(statusText) {
            switch(statusText.toLowerCase()) {
                case 'menunggu':
                    return 'Pesanan menunggu persetujuan dari penjual. Silakan tinjau pesanan ini.';
                case 'disetujui':
                    return 'Pesanan telah disetujui. Silakan input ekspedisi dan nomor resi untuk melanjutkan pengiriman.';
                case 'dikirim':
                    return 'Pesanan telah dikirim. Pembeli dapat melacak pengiriman dengan nomor resi.';
                case 'ditolak':
                    return 'Pesanan ditolak. Dana akan dikembalikan ke pembeli.';
                case 'refund':
                    return 'Pesanan dalam proses refund/pengembalian dana.';
                case 'diterima':
                    return 'Pesanan telah diterima oleh pembeli. Transaksi selesai.';
                default:
                    return 'Status pesanan tidak diketahui.';
            }
        }

        // Lihat bukti pembayaran
        function lihatBukti(buktiFile, kodePesanan) {
            const buktiContent = document.getElementById('buktiContent');
            const imagePath = `../../Src/uploads/bukti_pembayaran/${buktiFile}`;
            
            buktiContent.innerHTML = `
                <div style="padding: 20px;">
                    <h4 style="color: var(--primary); margin-bottom: 10px;">Bukti Pembayaran - ${kodePesanan}</h4>
                    <p style="margin-bottom: 15px;"><strong>File:</strong> ${buktiFile}</p>
                    <img src="${imagePath}" 
                         alt="Bukti Pembayaran" 
                         class="bukti-image"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Gambar+Tidak+Ditemukan'">
                </div>
            `;
            openModal('buktiModal');
        }

        // Validasi form ekspedisi dan resi
        document.getElementById('ekspedisiResiForm').addEventListener('submit', function(e) {
            const ekspedisi = document.getElementById('ekspedisi').value;
            const noResi = document.getElementById('no_resi').value;
            
            if (!ekspedisi) {
                e.preventDefault();
                alert('Silakan pilih ekspedisi terlebih dahulu!');
                return false;
            }
            
            if (ekspedisi === 'Lainnya') {
                const ekspedisiLainnya = document.getElementById('ekspedisi_lainnya').value;
                if (!ekspedisiLainnya) {
                    e.preventDefault();
                    alert('Silakan masukkan nama ekspedisi lainnya!');
                    return false;
                }
            }
            
            if (!noResi.trim()) {
                e.preventDefault();
                alert('Silakan masukkan nomor resi!');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>