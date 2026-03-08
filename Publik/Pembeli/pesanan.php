<?php
session_start();
include "../../Src/config.php";
include "hitung_notif_keranjang.php";
include "hitung_notif_chat.php"; // tambahkan ini untuk chat
$total_keranjang_notif = getCartCount($conn, $email_pembeli);
$total_chat_notif = getChatCount($conn, $email_pembeli); // tambahkan ini
$chat_notifications = getChatNotifications($conn, $email_pembeli); // detail notifikasi
// Cek apakah pembeli sudah login
if (!isset($_SESSION['email_pembeli'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

// Ambil data pembeli dari session
$email_pembeli = $_SESSION['email_pembeli'];
$data_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email_pembeli'")->fetch_assoc();

// PROSES UPDATE STATUS PESANAN JIKA ADA REQUEST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $kode_pesanan = $_POST['kode_pesanan'];
        $new_status = 'Diterima';
        
        // Update status pesanan
        $update_query = "UPDATE pesanan SET status = '$new_status' WHERE kode_pesanan = '$kode_pesanan' AND email_pembeli = '$email_pembeli'";
        
        if ($conn->query($update_query)) {
            echo "<script>alert('Status pesanan berhasil diubah menjadi Diterima');</script>";
            echo "<script>window.location.href='pesanan.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal mengupdate status pesanan');</script>";
        }
    }
}

// Ambil parameter filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// QUERY DASAR: Tampilkan semua pesanan berdasarkan email pembeli
$query = "SELECT * FROM pesanan WHERE email_pembeli = '$email_pembeli'";

// Filter pencarian
if (!empty($search)) {
    $query .= " AND (kode_pesanan LIKE '%$search%' OR judul_buku LIKE '%$search%' OR nama_penjual LIKE '%$search%')";
}

// Filter status
if (!empty($filter_status) && $filter_status != 'semua') {
    if ($filter_status == 'Menunggu Konfirmasi') {
        // Status NULL atau kosong DAN approve = Disetujui
        $query .= " AND ((status IS NULL OR status = '') AND approve = 'Disetujui')";
    } elseif ($filter_status == 'Menunggu Verifikasi') {
        // Approve NULL atau kosong
        $query .= " AND (approve IS NULL OR approve = '')";
    } else {
        $query .= " AND status = '$filter_status'";
    }
} else {
    // Default: jangan tampilkan yang statusnya Diterima
    $query .= " AND (status != 'Diterima' OR status IS NULL)";
}

// Urutkan berdasarkan tanggal terbaru
$query .= " ORDER BY tanggal_pesanan DESC";

// Eksekusi query
$pesanan_query = $conn->query($query);

// Hitung total pesanan
$total_pesanan = $pesanan_query->num_rows;

// Ambil data pesanan untuk ditampilkan
$pesanan_list = [];
$total_harga_all = 0;

while ($pesanan = $pesanan_query->fetch_assoc()) {
    $pesanan_list[] = $pesanan;
    $total_harga_all += $pesanan['total_harga'];
}

// Hitung statistik
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN approve IS NULL THEN 1 ELSE 0 END) as menunggu_verifikasi,
        SUM(CASE WHEN approve = 'Disetujui' AND (status IS NULL OR status = '') THEN 1 ELSE 0 END) as menunggu_konfirmasi,
        SUM(CASE WHEN status = 'Dikirim' THEN 1 ELSE 0 END) as dikirim,
        SUM(CASE WHEN status = 'Refund' THEN 1 ELSE 0 END) as refund
    FROM pesanan 
    WHERE email_pembeli = '$email_pembeli'
    AND status != 'Diterima'
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Pesanan Saya</title>
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
            --purple: #6f42c1;
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

        .search-container {
            flex: 1;
            max-width: 500px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
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
        }

        .welcome-message {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 30px;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
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
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .stat-icon.verification {
            background: linear-gradient(135deg, #ff922b 0%, #f76707 100%);
        }

        .stat-icon.waiting {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
        }

        .stat-icon.shipped {
            background: linear-gradient(135deg, #4dabf7 0%, #339af0 100%);
        }

        .stat-icon.refund {
            background: linear-gradient(135deg, var(--purple) 0%, #59359a 100%);
        }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        /* Filter Section */
        .filter-section {
            background-color: white;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .filter-select, .filter-input {
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background-color: white;
            transition: all 0.3s;
            width: 100%;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .filter-button {
            padding: 12px 25px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .filter-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .filter-button i {
            font-size: 14px;
        }

        .reset-button {
            padding: 12px 25px;
            background-color: var(--light-gray);
            color: var(--dark);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .reset-button:hover {
            background-color: #dee2e6;
        }

        /* Results Info */
        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .total-orders {
            font-size: 16px;
            color: var(--gray);
        }

        .total-orders strong {
            color: var(--primary);
        }

        .total-price-info {
            font-size: 16px;
            font-weight: 600;
            color: var(--success);
        }

        /* Orders Table */
        .orders-container {
            background-color: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .table-container {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .orders-table thead {
            background-color: #f8f9fa;
        }

        .orders-table th {
            padding: 18px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--light-gray);
            white-space: nowrap;
        }

        .orders-table td {
            padding: 20px 16px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .orders-table tbody tr {
            transition: all 0.3s;
        }

        .orders-table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }

        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-header {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-code {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
        }

        .order-date {
            font-size: 13px;
            color: var(--gray);
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .product-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark);
            line-height: 1.4;
        }

        .product-seller {
            font-size: 13px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .product-seller i {
            font-size: 12px;
            color: var(--primary);
        }

        .price-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .total-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--success);
        }

        .price-details {
            font-size: 13px;
            color: var(--gray);
        }

        .status-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            min-width: 80px;
        }

        .status-value {
            font-size: 13px;
        }

        .status-badge, .approve-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.menunggu-konfirmasi {
            background-color: rgba(255, 193, 7, 0.15);
            color: #e0a800;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-badge.dikirim {
            background-color: rgba(77, 171, 247, 0.15);
            color: #339af0;
            border: 1px solid rgba(77, 171, 247, 0.3);
        }

        .status-badge.refund {
            background-color: rgba(111, 66, 193, 0.15);
            color: #59359a;
            border: 1px solid rgba(111, 66, 193, 0.3);
        }

        .approve-badge.disetujui {
            background-color: rgba(40, 167, 69, 0.15);
            color: #218838;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .approve-badge.belum-diverifikasi {
            background-color: rgba(255, 146, 43, 0.15);
            color: #f76707;
            border: 1px solid rgba(255, 146, 43, 0.3);
        }

        /* Ekspedisi Link */
        .ekspedisi-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .ekspedisi-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 140px;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            text-decoration: none;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 12px;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
            border: 2px solid var(--success);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.3);
        }

        .btn-outline {
            background-color: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.3);
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            animation: fadeIn 0.3s ease;
        }

        .modal-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 2001;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 25px 30px 15px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--gray);
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-content {
            padding: 30px;
        }

        .order-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .detail-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .detail-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--dark);
            min-width: 120px;
        }

        .detail-value {
            color: var(--gray);
            text-align: right;
            flex: 1;
        }

        .detail-value.success {
            color: var(--success);
            font-weight: 600;
        }

        /* Bukti Pembayaran Section */
        .bukti-section {
            grid-column: span 2;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .bukti-image-container {
            margin-top: 15px;
            text-align: center;
        }

        .bukti-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .bukti-placeholder {
            padding: 40px;
            text-align: center;
            color: var(--gray);
            background-color: #f1f3f5;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }

        .bukti-placeholder i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 60px 40px;
            color: var(--gray);
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .no-data i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-data h4 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .no-data p {
            font-size: 16px;
            margin-bottom: 20px;
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
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .results-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .modal-actions {
                justify-content: center;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
            
            .bukti-section {
                grid-column: span 1;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .btn {
                font-size: 12px;
                padding: 8px 12px;
            }
            
            .modal-container {
                width: 95%;
                padding: 15px;
            }
            
            .modal-content {
                padding: 20px;
            }
        }

        .menu-toggle {
            display: none;
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

        <!-- Menu SHOPPING -->
        <div class="nav-section">
            <div class="section-title">SHOPPING</div>
            <ul class="nav-links">
                <li>
                    <a href="produk.php" class="nav-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="nav-text">Belanja</span>
                    </a>
                </li>
                <li>
                <a href="keranjang.php" class="nav-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="nav-text">Keranjang</span>
                        <?php if ($total_keranjang_notif > 0): ?>
                        <span class="cart-badge" id="cartBadge">
                            <?php echo ($total_keranjang_notif > 99) ? '99+' : $total_keranjang_notif; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                </li>
                <li>
                    <a href="pesanan.php" class="nav-item active">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="nav-text">Pesanan Saya</span>
                    </a>
                </li>
                <li>
                    <a href="riwayat.php" class="nav-item">
                        <i class="fas fa-history"></i>
                        <span class="nav-text">Riwayat Belanja</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Menu SOCIAL -->
        <div class="nav-section">
            <div class="section-title">SOCIAL</div>
            <ul class="nav-links">
                <!-- <li>
                    <a href="penjual_lain.php" class="nav-item">
                        <i class="fas fa-store"></i>
                        <span class="nav-text">Toko Penjual</span>
                    </a>
                </li> -->
                <li>
                <a href="room_chat.php" class="nav-item">
                <i class="fas fa-comment"></i>
                <span class="nav-text">Chat</span>
                <!-- Notifikasi Chat -->
                <?php if ($total_chat_notif > 0): ?>
                    <span class="chat-badge" id="chatBadge">
                        <?php echo ($total_chat_notif > 99) ? '99+' : $total_chat_notif; ?>
                    </span>
                <?php endif; ?>
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
            
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari kode pesanan, judul buku, atau penjual..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onkeypress="handleSearch(event)">
                </div>
            </div>

            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?></div>
                    <div class="user-role">Pembeli</div>
                </div>
                <?php 
                $foto = isset($data_pembeli['foto']) ? $data_pembeli['foto'] : null;
                $src = $foto ? "../../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($data_pembeli['nama_pembeli']) . "&background=4361ee&color=fff&size=120";
                ?>
                <img src="<?php echo $src; ?>" 
                     alt="Profile" 
                     class="user-avatar"
                     onclick="window.location.href='../../Src/profile.php'">
            </div>
        </header>

        <!-- Content Area -->
        <main class="content">
            <h1 class="page-title">Pesanan Saya</h1>
            <p class="welcome-message">
                Lihat dan kelola pesanan yang sedang berlangsung.
            </p>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Pesanan</div>
                        <div class="stat-value"><?php echo $stats_query['total'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon verification">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Menunggu Verifikasi</div>
                        <div class="stat-value"><?php echo $stats_query['menunggu_verifikasi'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon waiting">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Menunggu Konfirmasi</div>
                        <div class="stat-value"><?php echo $stats_query['menunggu_konfirmasi'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon shipped">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Dikirim</div>
                        <div class="stat-value"><?php echo $stats_query['dikirim'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon refund">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Refund</div>
                        <div class="stat-value"><?php echo $stats_query['refund'] ?? 0; ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" id="filterForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <div class="filter-label">Cari Pesanan</div>
                            <input type="text" name="search" class="filter-input" 
                                   placeholder="Kode, judul buku, atau penjual..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <div class="filter-label">Status Pesanan</div>
                            <select name="status" class="filter-select">
                                <option value="semua">Semua Status</option>
                                <option value="Menunggu Verifikasi" <?php echo ($filter_status == 'Menunggu Verifikasi') ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                                <option value="Menunggu Konfirmasi" <?php echo ($filter_status == 'Menunggu Konfirmasi') ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                <option value="Dikirim" <?php echo ($filter_status == 'Dikirim') ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="Refund" <?php echo ($filter_status == 'Refund') ? 'selected' : ''; ?>>Refund</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="filter-button">
                                <i class="fas fa-filter"></i> Terapkan Filter
                            </button>
                        </div>
                        
                        <div class="filter-group">
                            <button type="button" class="reset-button" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                <div class="total-orders">
                    Menampilkan <strong><?php echo $total_pesanan; ?></strong> pesanan
                    <?php if (!empty($search)): ?>
                        untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                </div>
                <div class="total-price-info">
                    Total Nilai Pesanan: <strong>Rp <?php echo number_format($total_harga_all, 0, ',', '.'); ?></strong>
                </div>
            </div>

            <?php if ($total_pesanan > 0): ?>
                <!-- Orders Table -->
                <div class="orders-container">
                    <div class="table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>KODE & TANGGAL</th>
                                    <th>PRODUK & PENJUAL</th>
                                    <th>HARGA</th>
                                    <th>STATUS</th>
                                    <th>AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pesanan_list as $pesanan): 
                                    $tanggal = date('d M Y, H:i', strtotime($pesanan['tanggal_pesanan']));
                                    $buktiPembayaran = htmlspecialchars($pesanan['bukti_pembayaran'] ?? '');
                                    $alamatPembeli = htmlspecialchars($pesanan['alamat_pembeli'] ?? 'Alamat tidak tersedia');
                                    
                                    // LOGIKA STATUS UTAMA:
                                    // 1. Jika approve NULL = "Menunggu Verifikasi"
                                    // 2. Jika approve = 'Disetujui' dan status NULL/kosong = "Menunggu Konfirmasi"
                                    // 3. Jika status = 'Dikirim' = "Dikirim"
                                    // 4. Jika status = 'Refund' = "Refund"
                                    
                                    if (is_null($pesanan['approve'])) {
                                        $statusDisplay = 'Menunggu Verifikasi';
                                        $statusClass = 'menunggu-konfirmasi';
                                        $approveDisplay = 'Belum Diverifikasi';
                                        $approveClass = 'belum-diverifikasi';
                                    } elseif ($pesanan['approve'] == 'Disetujui' && (is_null($pesanan['status']) || $pesanan['status'] == '')) {
                                        $statusDisplay = 'Menunggu Pengiriman';
                                        $statusClass = 'menunggu-konfirmasi';
                                        $approveDisplay = 'Disetujui';
                                        $approveClass = 'disetujui';
                                    } elseif ($pesanan['status'] == 'Dikirim') {
                                        $statusDisplay = 'Dikirim';
                                        $statusClass = 'dikirim';
                                        $approveDisplay = 'Disetujui';
                                        $approveClass = 'disetujui';
                                    } elseif ($pesanan['status'] == 'Refund') {
                                        $statusDisplay = 'Refund';
                                        $statusClass = 'refund';
                                        $approveDisplay = $pesanan['approve'];
                                        $approveClass = strtolower($pesanan['approve']);
                                    } else {
                                        $statusDisplay = $pesanan['status'] ?? 'Menunggu Konfirmasi';
                                        $statusClass = str_replace(' ', '-', strtolower($statusDisplay));
                                        $approveDisplay = $pesanan['approve'] ?? 'Belum Diverifikasi';
                                        $approveClass = strtolower($approveDisplay);
                                    }
                                    
                                    // Link ekspedisi
                                    $ekspedisi_links = [
                                        'J&T Express' => 'https://jet.co.id/',
                                        'JNE' => 'https://www.jne.co.id/',
                                        'SiCepat' => 'https://www.sicepat.com/',
                                        'Anteraja' => 'https://anteraja.id/',
                                        'Ninja Xpress' => 'https://www.ninjaxpress.co/',
                                        'Lion Parcel' => 'https://lionparcel.com/',
                                        'Pos Indonesia' => 'https://www.posindonesia.co.id/',
                                        'SAP Express' => 'https://www.sap-express.id/',
                                        'ID Express' => 'https://idexpress.com/',
                                        'REX Express' => 'https://rex.co.id/',
                                        'Tiki' => 'https://tiki.id/',
                                        'Wahana' => 'https://www.wahana.com/',
                                        'Pandu Logistics' => 'https://www.pandulogistics.com/',
                                        'JET Express' => 'https://jet.co.id/',
                                        'Deliveree' => 'https://www.deliveree.com/id/',
                                        'GrabExpress' => 'https://www.grab.com/id/',
                                        'GoSend' => 'https://www.gojek.com/gosend/'
                                    ];
                                    
                                    $ekspedisi_url = $ekspedisi_links[$pesanan['ekspedisi']] ?? '#';
                                ?>
                                    <tr data-order-code="<?php echo $pesanan['kode_pesanan']; ?>">
                                        <td>
                                            <div class="order-header">
                                                <div class="order-code"><?php echo htmlspecialchars($pesanan['kode_pesanan']); ?></div>
                                                <div class="order-date"><?php echo $tanggal; ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="product-info">
                                                <div class="product-title"><?php echo htmlspecialchars($pesanan['judul_buku']); ?></div>
                                                <div class="product-seller">
                                                    <i class="fas fa-store"></i>
                                                    <?php echo htmlspecialchars($pesanan['nama_penjual']); ?>
                                                </div>
                                                <div class="price-details">
                                                    <span><?php echo $pesanan['qty']; ?> × Rp <?php echo number_format($pesanan['harga_satuan'], 0, ',', '.'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="price-info">
                                                <div class="total-price">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></div>
                                                <div class="price-details">
                                                    <span>Metode: <?php echo htmlspecialchars($pesanan['metode_bayar']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="status-section">
                                                <div class="status-item">
                                                    <span class="status-label">Persetujuan:</span>
                                                    <span class="status-value">
                                                        <span class="approve-badge <?php echo $approveClass; ?>">
                                                            <?php echo htmlspecialchars($approveDisplay); ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="status-item">
                                                    <span class="status-label">Status:</span>
                                                    <span class="status-value">
                                                        <span class="status-badge <?php echo $statusClass; ?>">
                                                            <?php echo htmlspecialchars($statusDisplay); ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                <?php if ($pesanan['status'] == 'Dikirim' && $pesanan['ekspedisi'] && $pesanan['no_resi']): ?>
                                                    <div class="status-item">
                                                        <span class="status-label">Ekspedisi:</span>
                                                        <span class="status-value">
                                                            <a href="<?php echo $ekspedisi_url; ?>" target="_blank" class="ekspedisi-link">
                                                                <i class="fas fa-external-link-alt"></i>
                                                                <?php echo htmlspecialchars($pesanan['ekspedisi']); ?>
                                                            </a>
                                                        </span>
                                                    </div>
                                                    <div class="status-item">
                                                        <span class="status-label">No Resi:</span>
                                                        <span class="status-value">
                                                            <strong><?php echo htmlspecialchars($pesanan['no_resi']); ?></strong>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($pesanan['status'] == 'Dikirim'): ?>
                                                    <form method="POST" action="" onsubmit="return confirmUpdateStatus()" style="display: inline;">
                                                        <input type="hidden" name="kode_pesanan" value="<?php echo $pesanan['kode_pesanan']; ?>">
                                                        <button type="submit" name="update_status" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check"></i> Pesanan Diterima
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-primary btn-sm" onclick="showOrderDetail(
                                                    '<?php echo htmlspecialchars($pesanan['kode_pesanan']); ?>',
                                                    '<?php echo htmlspecialchars($pesanan['judul_buku']); ?>',
                                                    '<?php echo htmlspecialchars($pesanan['nama_penjual']); ?>',
                                                    '<?php echo htmlspecialchars($pesanan['email_penjual']); ?>',
                                                    <?php echo $pesanan['total_harga']; ?>,
                                                    <?php echo $pesanan['harga_satuan']; ?>,
                                                    <?php echo $pesanan['qty']; ?>,
                                                    '<?php echo $pesanan['tanggal_pesanan']; ?>',
                                                    '<?php echo htmlspecialchars($statusDisplay); ?>',
                                                    '<?php echo htmlspecialchars($approveDisplay); ?>',
                                                    '<?php echo htmlspecialchars($pesanan['metode_bayar']); ?>',
                                                    '<?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?>',
                                                    '<?php echo htmlspecialchars($data_pembeli['email_pembeli']); ?>',
                                                    '<?php echo $alamatPembeli; ?>',
                                                    '<?php echo $buktiPembayaran; ?>',
                                                    '<?php echo htmlspecialchars($pesanan['ekspedisi'] ?? ''); ?>',
                                                    '<?php echo htmlspecialchars($pesanan['no_resi'] ?? ''); ?>'
                                                )">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Data -->
                <div class="no-data">
                    <i class="fas fa-clipboard-list"></i>
                    <h4>Tidak Ada Pesanan Aktif</h4>
                    <p>Tidak ada pesanan yang sedang berlangsung. Pesanan yang sudah selesai dapat dilihat di halaman Riwayat Belanja.</p>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="produk.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Mulai Belanja
                        </a>
                        <a href="riwayat.php" class="btn btn-outline">
                            <i class="fas fa-history"></i> Lihat Riwayat
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Pesanan Saya</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal-overlay" id="orderModalOverlay"></div>
    <div class="modal-container" id="orderModal" style="display: none;">
        <div class="modal-header">
            <h2 class="modal-title">Detail Pesanan</h2>
            <button class="modal-close" onclick="closeOrderModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-content" id="orderModalContent">
            <!-- Content akan diisi oleh JavaScript -->
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Handle search from topbar
        function handleSearch(event) {
            if (event.key === 'Enter') {
                const searchTerm = document.getElementById('searchInput').value.trim();
                if (searchTerm) {
                    window.location.href = 'pesanan.php?search=' + encodeURIComponent(searchTerm);
                }
            }
        }

        // Reset filters
        function resetFilters() {
            window.location.href = 'pesanan.php';
        }

        // Confirm update status
        function confirmUpdateStatus() {
            return confirm('Apakah Anda yakin pesanan sudah diterima?\n\nSetelah dikonfirmasi, status akan berubah menjadi "Diterima" dan pesanan akan berpindah ke halaman Riwayat Belanja.');
        }

        // Show order detail
        function showOrderDetail(kodePesanan, judulBuku, namaPenjual, emailPenjual, totalHarga, hargaSatuan, qty, tanggalPesanan, status, approve, metodeBayar, namaPembeli, emailPembeli, alamatPembeli, buktiPembayaran, ekspedisi, noResi) {
            const tanggal = new Date(tanggalPesanan).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Format status class
            const statusClass = status.replace(/ /g, '-').toLowerCase();
            const approveClass = approve.replace(/ /g, '-').toLowerCase();
            
            // URL ekspedisi
            const ekspedisiLinks = {
                'J&T Express': 'https://jet.co.id/',
                'JNE': 'https://www.jne.co.id/',
                'SiCepat': 'https://www.sicepat.com/',
                'Anteraja': 'https://anteraja.id/',
                'Ninja Xpress': 'https://www.ninjaxpress.co/',
                'Lion Parcel': 'https://lionparcel.com/',
                'Pos Indonesia': 'https://www.posindonesia.co.id/',
                'SAP Express': 'https://www.sap-express.id/',
                'ID Express': 'https://idexpress.com/',
                'REX Express': 'https://rex.co.id/',
                'Tiki': 'https://tiki.id/',
                'Wahana': 'https://www.wahana.com/',
                'Pandu Logistics': 'https://www.pandulogistics.com/',
                'JET Express': 'https://jet.co.id/',
                'Deliveree': 'https://www.deliveree.com/id/',
                'GrabExpress': 'https://www.grab.com/id/',
                'GoSend': 'https://www.gojek.com/gosend/'
            };
            
            const ekspedisiUrl = ekspedisi && ekspedisiLinks[ekspedisi] ? ekspedisiLinks[ekspedisi] : '#';
            
            // Buat HTML untuk bukti pembayaran
            let buktiHtml = '';
            if (buktiPembayaran && buktiPembayaran !== '') {
                buktiHtml = `
                    <div class="bukti-image-container">
                        <img src="../../Src/uploads/bukti_pembayaran/${buktiPembayaran}" 
                             alt="Bukti Pembayaran" 
                             class="bukti-image"
                             onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Gambar+Tidak+Ditemukan'">
                    </div>
                `;
            } else {
                buktiHtml = `
                    <div class="bukti-placeholder">
                        <i class="fas fa-receipt"></i>
                        <p>Tidak ada bukti pembayaran yang diunggah</p>
                    </div>
                `;
            }
            
            // HTML untuk informasi pengiriman
            let pengirimanHtml = '';
            if (status === 'Dikirim' && ekspedisi && noResi) {
                pengirimanHtml = `
                    <div class="detail-item">
                        <span class="detail-label">Ekspedisi</span>
                        <span class="detail-value">
                            <a href="${ekspedisiUrl}" target="_blank" class="ekspedisi-link">
                                <i class="fas fa-external-link-alt"></i> ${ekspedisi}
                            </a>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">No Resi</span>
                        <span class="detail-value"><strong>${noResi}</strong></span>
                    </div>
                `;
            }
            
            document.getElementById('orderModalContent').innerHTML = `
                <div class="order-detail-grid">
                    <div class="detail-section">
                        <div class="detail-title">
                            <i class="fas fa-info-circle"></i> Informasi Pesanan
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Kode Pesanan</span>
                            <span class="detail-value">${kodePesanan}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Tanggal</span>
                            <span class="detail-value">${tanggal}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Persetujuan</span>
                            <span class="detail-value">
                                <span class="approve-badge ${approveClass}">
                                    ${approve}
                                </span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="status-badge ${statusClass}">
                                    ${status}
                                </span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Metode Bayar</span>
                            <span class="detail-value">${metodeBayar}</span>
                        </div>
                        ${pengirimanHtml}
                    </div>
                    
                    <div class="detail-section">
                        <div class="detail-title">
                            <i class="fas fa-book"></i> Detail Produk
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Judul Buku</span>
                            <span class="detail-value">${judulBuku}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Harga Satuan</span>
                            <span class="detail-value">Rp ${formatRupiah(hargaSatuan)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Quantity</span>
                            <span class="detail-value">${qty}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Harga</span>
                            <span class="detail-value success">Rp ${formatRupiah(totalHarga)}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="detail-title">
                            <i class="fas fa-store"></i> Informasi Penjual
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nama Penjual</span>
                            <span class="detail-value">${namaPenjual}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email Penjual</span>
                            <span class="detail-value">${emailPenjual}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="detail-title">
                            <i class="fas fa-user"></i> Informasi Pembeli
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nama Pembeli</span>
                            <span class="detail-value">${namaPembeli}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email Pembeli</span>
                            <span class="detail-value">${emailPembeli}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Alamat</span>
                            <span class="detail-value">${alamatPembeli}</span>
                        </div>
                    </div>
                    
                    <div class="bukti-section">
                        <div class="detail-title">
                            <i class="fas fa-receipt"></i> Bukti Pembayaran
                        </div>
                        ${buktiHtml}
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="btn btn-outline" onclick="closeOrderModal()">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                    ${status === 'Dikirim' ? `
                        <form method="POST" action="pesanan.php" onsubmit="return confirmUpdateStatus()" style="display: inline;">
                            <input type="hidden" name="kode_pesanan" value="${kodePesanan}">
                            <button type="submit" name="update_status" class="btn btn-success">
                                <i class="fas fa-check"></i> Konfirmasi Diterima
                            </button>
                        </form>
                    ` : ''}
                </div>
            `;
            
            // Tampilkan modal
            document.getElementById('orderModalOverlay').style.display = 'block';
            document.getElementById('orderModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeOrderModal() {
            document.getElementById('orderModalOverlay').style.display = 'none';
            document.getElementById('orderModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking overlay
        document.getElementById('orderModalOverlay').addEventListener('click', closeOrderModal);

        // Prevent modal close when clicking inside modal
        document.getElementById('orderModal').addEventListener('click', function(event) {
            event.stopPropagation();
        });

        // Format number to Rupiah
        function formatRupiah(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Update search input from URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            if (searchParam) {
                document.getElementById('searchInput').value = decodeURIComponent(searchParam);
            }
        });

        // Status badge hover effects
        document.querySelectorAll('.status-badge, .approve-badge').forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.transition = 'transform 0.2s';
            });
            
            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>