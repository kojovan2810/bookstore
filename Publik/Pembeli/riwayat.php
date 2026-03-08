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

// PROSES HIDDEN PESANAN JIKA ADA REQUEST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['hide_order'])) {
        $kode_pesanan = $_POST['kode_pesanan'];
        
        // Update status menjadi hidden (tidak delete dari database)
        $update_query = "UPDATE pesanan SET status = 'Hidden' WHERE kode_pesanan = '$kode_pesanan' AND email_pembeli = '$email_pembeli'";
        
        if ($conn->query($update_query)) {
            echo "<script>alert('Pesanan berhasil disembunyikan dari riwayat');</script>";
            echo "<script>window.location.href='riwayat.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menyembunyikan pesanan');</script>";
        }
    }
}

// Ambil parameter filter
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query dasar untuk mengambil riwayat pesanan (hanya yang status Diterima atau Refund)
$query = "SELECT * FROM pesanan WHERE email_pembeli = '$email_pembeli' AND (status = 'Diterima' OR status = 'Refund')";

// Filter pencarian
if (!empty($search)) {
    $query .= " AND (kode_pesanan LIKE '%$search%' OR judul_buku LIKE '%$search%' OR nama_penjual LIKE '%$search%')";
}

// Filter bulan
if (!empty($filter_month) && $filter_month != 'semua') {
    $query .= " AND MONTH(tanggal_pesanan) = '$filter_month'";
}

// Filter tahun
if (!empty($filter_year) && $filter_year != 'semua') {
    $query .= " AND YEAR(tanggal_pesanan) = '$filter_year'";
}

// Urutkan berdasarkan tanggal terbaru
$query .= " ORDER BY 
    CASE 
        WHEN status = 'Refund' THEN 0 
        WHEN status = 'Diterima' THEN 1 
        ELSE 2 
    END,
    tanggal_pesanan DESC";

// Eksekusi query
$riwayat_query = $conn->query($query);

// Hitung total riwayat
$total_riwayat = $riwayat_query->num_rows;

// Ambil data riwayat untuk ditampilkan
$riwayat_list = [];
$total_harga_all = 0;
$total_items_all = 0;
$riwayat_refund = []; // Data pesanan yang berstatus refund

while ($riwayat = $riwayat_query->fetch_assoc()) {
    $riwayat_list[] = $riwayat;
    
    // Hitung total hanya untuk yang status Diterima
    if ($riwayat['status'] == 'Diterima') {
        $total_harga_all += $riwayat['total_harga'];
        $total_items_all += $riwayat['qty'];
    }
    
    // Simpan data refund untuk peringatan
    if ($riwayat['status'] == 'Refund') {
        $riwayat_refund[] = $riwayat;
    }
}

// Hitung statistik - HANYA untuk status Diterima
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_pesanan,
        SUM(total_harga) as total_pengeluaran,
        SUM(qty) as total_buku,
        COUNT(DISTINCT email_penjual) as total_penjual,
        COUNT(DISTINCT MONTH(tanggal_pesanan)) as total_bulan,
        COUNT(DISTINCT YEAR(tanggal_pesanan)) as total_tahun
    FROM pesanan 
    WHERE email_pembeli = '$email_pembeli' 
    AND status = 'Diterima'
")->fetch_assoc();

// Hitung total refund
$refund_stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_refund,
        SUM(total_harga) as total_refund_amount
    FROM pesanan 
    WHERE email_pembeli = '$email_pembeli' 
    AND status = 'Refund'
")->fetch_assoc();


    

// Ambil bulan unik untuk filter
$months_query = $conn->query("
    SELECT DISTINCT MONTH(tanggal_pesanan) as bulan, 
           MONTHNAME(tanggal_pesanan) as nama_bulan
    FROM pesanan 
    WHERE email_pembeli = '$email_pembeli' 
    AND (status = 'Diterima' OR status = 'Refund')
    ORDER BY bulan DESC
");

// Ambil tahun unik untuk filter
$years_query = $conn->query("
    SELECT DISTINCT YEAR(tanggal_pesanan) as tahun
    FROM pesanan 
    WHERE email_pembeli = '$email_pembeli' 
    AND (status = 'Diterima' OR status = 'Refund')
    ORDER BY tahun DESC
");

// Buat array bulan
$bulan_list = [];
$bulan_options = '<option value="semua">Semua Bulan</option>';
while ($row = $months_query->fetch_assoc()) {
    $bulan_list[] = $row;
    $selected = ($filter_month == $row['bulan']) ? 'selected' : '';
    $bulan_options .= "<option value='{$row['bulan']}' $selected>{$row['nama_bulan']}</option>";
}

// Buat array tahun
$tahun_list = [];
$tahun_options = '<option value="semua">Semua Tahun</option>';
while ($row = $years_query->fetch_assoc()) {
    $tahun_list[] = $row['tahun'];
    $selected = ($filter_year == $row['tahun']) ? 'selected' : '';
    $tahun_options .= "<option value='{$row['tahun']}' $selected>{$row['tahun']}</option>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Riwayat Belanja</title>
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
            --cyan: #20c997;
            --pink: #e83e8c;
            --teal: #20c997;
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

        .stat-icon.spent {
            background: linear-gradient(135deg, var(--success) 0%, #218838 100%);
        }

        .stat-icon.books {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
        }

        .stat-icon.sellers {
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
        }

        .stat-icon.refund {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
        }

        .stat-icon.refund-amount {
            background: linear-gradient(135deg, var(--pink) 0%, #c2185b 100%);
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

        /* Warning Banner untuk Refund */
        .warning-banner {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }

        .warning-icon {
            width: 50px;
            height: 50px;
            background-color: #ffc107;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
        }

        .warning-content {
            flex: 1;
        }

        .warning-title {
            font-size: 18px;
            font-weight: 700;
            color: #856404;
            margin-bottom: 8px;
        }

        .warning-message {
            font-size: 15px;
            color: #856404;
            line-height: 1.5;
        }

        .warning-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .btn-warning {
            padding: 8px 16px;
            background-color: #ffc107;
            color: #212529;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
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

        /* Export Button Group */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: flex-end;
        }

        .btn-export {
            padding: 10px 20px;
            background-color: var(--success);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        .btn-export.pdf {
            background-color: var(--danger);
        }

        .btn-export.pdf:hover {
            background-color: #c82333;
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        }

        .btn-export.excel {
            background-color: var(--success);
        }

        .btn-export.excel:hover {
            background-color: #218838;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        /* History Table */
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

        /* Style khusus untuk baris refund */
        .orders-table tbody tr.refund-row {
            background-color: rgba(220, 53, 69, 0.05);
        }

        .orders-table tbody tr.refund-row:hover {
            background-color: rgba(220, 53, 69, 0.1);
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

        .status-badge.diterima {
            background-color: rgba(23, 162, 184, 0.15);
            color: #138496;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }

        .status-badge.refund {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.3);
            position: relative;
            overflow: hidden;
        }

        .status-badge.refund::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, 
                transparent 25%, 
                rgba(255, 255, 255, 0.1) 25%, 
                rgba(255, 255, 255, 0.1) 50%, 
                transparent 50%, 
                transparent 75%, 
                rgba(255, 255, 255, 0.1) 75%);
            background-size: 20px 20px;
            animation: refundShine 2s linear infinite;
        }

        .status-badge.refund-selesai {
            background-color: rgba(40, 167, 69, 0.15);
            color: #218838;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        @keyframes refundShine {
            0% { background-position: 0 0; }
            100% { background-position: 20px 20px; }
        }

        .approve-badge.disetujui {
            background-color: rgba(40, 167, 69, 0.15);
            color: #218838;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .refund-time-info {
            font-size: 12px;
            color: var(--danger);
            font-weight: 600;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .refund-time-info.success {
            color: var(--success);
        }

        .refund-time-info i {
            font-size: 11px;
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
            min-width: 120px;
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

        .btn-warning {
            background-color: var(--warning);
            color: var(--dark);
            border: 2px solid var(--warning);
        }

        .btn-warning:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(255, 193, 7, 0.3);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
            border: 2px solid var(--danger);
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
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

        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark);
            border: 2px solid var(--light-gray);
        }

        .btn-secondary:hover {
            background-color: #dee2e6;
            transform: translateY(-2px);
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
            display: flex;
            align-items: center;
            gap: 10px;
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

        /* Modal untuk peringatan refund */
        .refund-warning-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: none;
            z-index: 2000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .refund-warning-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease;
        }

        .refund-warning-icon {
            font-size: 64px;
            color: var(--danger);
            margin-bottom: 20px;
        }

        .refund-warning-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--danger);
            margin-bottom: 15px;
        }

        .refund-warning-message {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .refund-warning-detail {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }

        .refund-warning-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .refund-warning-detail-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .refund-warning-detail-label {
            font-weight: 600;
            color: var(--dark);
        }

        .refund-warning-detail-value {
            color: var(--danger);
            font-weight: 600;
        }

        .refund-warning-timer {
            font-size: 18px;
            font-weight: 700;
            color: var(--danger);
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
        }

        .refund-warning-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        /* Notification Styles */
        .custom-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 3000;
            animation: slideInRight 0.3s ease;
            max-width: 400px;
        }

        .custom-notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .custom-notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .custom-notification .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .custom-notification .notification-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 14px;
            padding: 5px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .custom-notification .notification-close:hover {
            opacity: 1;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
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

        /* Menu Toggle untuk Mobile */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 22px;
            color: var(--primary);
            cursor: pointer;
            margin-right: 15px;
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
            
            .export-buttons {
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .modal-actions {
                justify-content: center;
            }
            
            .refund-warning-actions {
                flex-direction: column;
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
            
            .warning-banner {
                flex-direction: column;
                text-align: center;
            }
            
            .warning-actions {
                justify-content: center;
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
            
            .orders-table th,
            .orders-table td {
                padding: 12px 8px;
                font-size: 13px;
            }
        }

        /* Utility Classes */
        .text-success {
            color: var(--success) !important;
        }
        
        .text-danger {
            color: var(--danger) !important;
        }
        
        .text-warning {
            color: var(--warning) !important;
        }
        
        .text-primary {
            color: var(--primary) !important;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-10 {
            margin-top: 10px;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .mb-10 {
            margin-bottom: 10px;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .flex {
            display: flex;
        }
        
        .flex-column {
            flex-direction: column;
        }
        
        .align-center {
            align-items: center;
        }
        
        .justify-center {
            justify-content: center;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .gap-10 {
            gap: 10px;
        }
        
        .gap-20 {
            gap: 20px;
        }
        
        .w-100 {
            width: 100%;
        }
        
        .hidden {
            display: none;
        }
        
        .visible {
            display: block;
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
                    <a href="pesanan.php" class="nav-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="nav-text">Pesanan Saya</span>
                    </a>
                </li>
                <li>
                    <a href="riwayat.php" class="nav-item active">
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
                <li>
                <a href="room_chat.php" class="nav-item ">
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
            <h1 class="page-title">Riwayat Belanja</h1>
            <p class="welcome-message">
                Lihat semua transaksi belanja yang telah Anda selesaikan.
            </p>

            <?php 
            // Cek apakah ada pesanan refund dan perlu peringatan
            $refund_warning_needed = false;
            $refund_warning_data = [];
            
            foreach ($riwayat_refund as $refund) {
                $tanggal_refund = strtotime($refund['tanggal_pesanan']);
                $sekarang = time();
                $selisih_jam = floor(($sekarang - $tanggal_refund) / 3600);
                
                // Jika masih dalam 24 jam
                if ($selisih_jam < 24) {
                    $refund_warning_needed = true;
                    $sisa_waktu = 24 - $selisih_jam;
                    $sisa_menit = 60 - (($sekarang - $tanggal_refund) % 3600) / 60;
                    $refund_warning_data[] = [
                        'kode_pesanan' => $refund['kode_pesanan'],
                        'judul_buku' => $refund['judul_buku'],
                        'sisa_waktu' => $sisa_waktu,
                        'sisa_menit' => floor($sisa_menit)
                    ];
                }
            }
            
            // Tampilkan peringatan jika ada refund dalam 24 jam
            if ($refund_warning_needed && count($refund_warning_data) > 0): 
            ?>
            <div class="warning-banner">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="warning-content">
                    <div class="warning-title">PERINGATAN PESANAN REFUND</div>
                    <div class="warning-message">
                        Anda memiliki <?php echo count($refund_warning_data); ?> pesanan berstatus Refund yang harus diselesaikan dalam 24 jam sejak pemesanan. 
                        Setelah 24 jam, pesanan akan otomatis dibatalkan.
                    </div>
                    <div class="warning-actions">
                        <?php foreach ($refund_warning_data as $warning): ?>
                        <button class="btn-warning" onclick="showRefundWarning(
                            '<?php echo $warning['kode_pesanan']; ?>',
                            '<?php echo htmlspecialchars($warning['judul_buku']); ?>',
                            <?php echo $warning['sisa_waktu']; ?>,
                            <?php echo $warning['sisa_menit']; ?>
                        )">
                            <i class="fas fa-clock"></i> <?php echo $warning['kode_pesanan']; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards dengan Refund -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Transaksi Diterima</div>
                        <div class="stat-value"><?php echo $stats_query['total_pesanan'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon spent">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Pengeluaran</div>
                        <div class="stat-value">Rp <?php echo number_format($stats_query['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon books">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Buku Dibeli</div>
                        <div class="stat-value"><?php echo $stats_query['total_buku'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon sellers">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Penjual Berbeda</div>
                        <div class="stat-value"><?php echo $stats_query['total_penjual'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon refund">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Refund</div>
                        <div class="stat-value"><?php echo $refund_stats_query['total_refund'] ?? 0; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon refund-amount">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Dana Refund</div>
                        <div class="stat-value">Rp <?php echo number_format($refund_stats_query['total_refund_amount'] ?? 0, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" id="filterForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <div class="filter-label">Cari Transaksi</div>
                            <input type="text" name="search" class="filter-input" 
                                   placeholder="Kode, judul buku, atau penjual..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <div class="filter-label">Filter Bulan</div>
                            <select name="month" class="filter-select">
                                <?php echo $bulan_options; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <div class="filter-label">Filter Tahun</div>
                            <select name="year" class="filter-select">
                                <?php echo $tahun_options; ?>
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

            <!-- Export Buttons -->
            <?php if ($total_riwayat > 0): ?>
            <div class="export-buttons">
                <button class="btn-export pdf" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
            <?php endif; ?>

            <!-- Results Info -->
            <div class="results-info">
                <div class="total-orders">
                    Menampilkan <strong><?php echo $total_riwayat; ?></strong> transaksi
                    <?php if (!empty($search)): ?>
                        untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                    (<?php echo $refund_stats_query['total_refund'] ?? 0; ?> refund)
                </div>
                <div class="total-price-info">
                    Total Nilai: <strong>Rp <?php echo number_format($total_harga_all, 0, ',', '.'); ?></strong>
                </div>
            </div>

            <?php if ($total_riwayat > 0): ?>
                <!-- History Table -->
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
                                <?php foreach ($riwayat_list as $riwayat): 
                                    $tanggal = date('d M Y, H:i', strtotime($riwayat['tanggal_pesanan']));
                                    $buktiPembayaran = htmlspecialchars($riwayat['bukti_pembayaran'] ?? '');
                                    $alamatPembeli = htmlspecialchars($riwayat['alamat_pembeli'] ?? 'Alamat tidak tersedia');
                                    $is_refund = ($riwayat['status'] == 'Refund');
                                    
                                    // Hitung sisa waktu untuk refund
                                    $sisa_waktu_html = '';
                                    $selisih_jam = 0;
                                    $sisa_jam = 0;
                                    $sisa_menit = 0;
                                    
                                    if ($is_refund) {
                                        $tanggal_pesanan = strtotime($riwayat['tanggal_pesanan']);
                                        $sekarang = time();
                                        $selisih_jam = floor(($sekarang - $tanggal_pesanan) / 3600);
                                        
                                        if ($selisih_jam < 24) {
                                            $sisa_jam = 24 - $selisih_jam;
                                            $sisa_menit = 60 - (($sekarang - $tanggal_pesanan) % 3600) / 60;
                                            $sisa_waktu_html = '<div class="refund-time-info"><i class="fas fa-clock"></i> Selesaikan dalam ' . $sisa_jam . ' jam ' . floor($sisa_menit) . ' menit</div>';
                                        } else {
                                            $sisa_waktu_html = '<div class="refund-time-info" style="color: var(--gray);"><i class="fas fa-clock"></i> Waktu habis</div>';
                                        }
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
                                    
                                    $ekspedisi_url = $riwayat['ekspedisi'] && isset($ekspedisi_links[$riwayat['ekspedisi']]) ? 
                                                   $ekspedisi_links[$riwayat['ekspedisi']] : '#';
                                ?>
                                    <tr data-order-code="<?php echo $riwayat['kode_pesanan']; ?>" class="<?php echo $is_refund ? 'refund-row' : ''; ?>">
                                        <td>
                                            <div class="order-header">
                                                <div class="order-code"><?php echo htmlspecialchars($riwayat['kode_pesanan']); ?></div>
                                                <div class="order-date"><?php echo $tanggal; ?></div>
                                                <?php echo $sisa_waktu_html; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="product-info">
                                                <div class="product-title"><?php echo htmlspecialchars($riwayat['judul_buku']); ?></div>
                                                <div class="product-seller">
                                                    <i class="fas fa-store"></i>
                                                    <?php echo htmlspecialchars($riwayat['nama_penjual']); ?>
                                                </div>
                                                <div class="price-details">
                                                    <span><?php echo $riwayat['qty']; ?> × Rp <?php echo number_format($riwayat['harga_satuan'], 0, ',', '.'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="price-info">
                                                <div class="total-price">Rp <?php echo number_format($riwayat['total_harga'], 0, ',', '.'); ?></div>
                                                <div class="price-details">
                                                    <span>Metode: <?php echo htmlspecialchars($riwayat['metode_bayar']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="status-section">
                                                <div class="status-item">
                                                    <span class="status-label">Persetujuan:</span>
                                                    <span class="status-value">
                                                        <span class="approve-badge <?php echo strtolower($riwayat['approve']); ?>">
                                                            <?php echo htmlspecialchars($riwayat['approve']); ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="status-item">
                                                    <span class="status-label">Status:</span>
                                                    <span class="status-value">
                                                        <span class="status-badge <?php echo strtolower($riwayat['status']); ?>">
                                                            <?php echo htmlspecialchars($riwayat['status']); ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                <?php if (!$is_refund && $riwayat['ekspedisi'] && $riwayat['no_resi']): ?>
                                                    <div class="status-item">
                                                        <span class="status-label">Ekspedisi:</span>
                                                        <span class="status-value">
                                                            <a href="<?php echo $ekspedisi_url; ?>" target="_blank" class="ekspedisi-link">
                                                                <i class="fas fa-external-link-alt"></i>
                                                                <?php echo htmlspecialchars($riwayat['ekspedisi']); ?>
                                                            </a>
                                                        </span>
                                                    </div>
                                                    <div class="status-item">
                                                        <span class="status-label">No Resi:</span>
                                                        <span class="status-value">
                                                            <strong><?php echo htmlspecialchars($riwayat['no_resi']); ?></strong>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm" onclick="showOrderDetail(
                                                    '<?php echo htmlspecialchars($riwayat['kode_pesanan']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['judul_buku']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['nama_penjual']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['email_penjual']); ?>',
                                                    <?php echo $riwayat['total_harga']; ?>,
                                                    <?php echo $riwayat['harga_satuan']; ?>,
                                                    <?php echo $riwayat['qty']; ?>,
                                                    '<?php echo $riwayat['tanggal_pesanan']; ?>',
                                                    '<?php echo htmlspecialchars($riwayat['status']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['approve']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['metode_bayar']); ?>',
                                                    '<?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?>',
                                                    '<?php echo htmlspecialchars($data_pembeli['email_pembeli']); ?>',
                                                    '<?php echo $alamatPembeli; ?>',
                                                    '<?php echo $buktiPembayaran; ?>',
                                                    '<?php echo htmlspecialchars($riwayat['ekspedisi'] ?? ''); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['no_resi'] ?? ''); ?>'
                                                )">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                
                                                <!-- Tombol Download PDF untuk setiap transaksi -->
                                                <button class="btn btn-success btn-sm" onclick="downloadSinglePDF(
                                                    '<?php echo htmlspecialchars($riwayat['kode_pesanan']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['judul_buku']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['nama_penjual']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['email_penjual']); ?>',
                                                    <?php echo $riwayat['total_harga']; ?>,
                                                    <?php echo $riwayat['harga_satuan']; ?>,
                                                    <?php echo $riwayat['qty']; ?>,
                                                    '<?php echo date('d M Y, H:i', strtotime($riwayat['tanggal_pesanan'])); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['status']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['approve']); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['metode_bayar']); ?>',
                                                    '<?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?>',
                                                    '<?php echo htmlspecialchars($data_pembeli['email_pembeli']); ?>',
                                                    '<?php echo $alamatPembeli; ?>',
                                                    '<?php echo htmlspecialchars($riwayat['ekspedisi'] ?? ''); ?>',
                                                    '<?php echo htmlspecialchars($riwayat['no_resi'] ?? ''); ?>'
                                                )">
                                                    <i class="fas fa-file-pdf"></i> PDF
                                                </button>
                                                
                                                <!-- Tombol khusus untuk refund jika masih dalam 24 jam -->
                                                <?php if ($is_refund && $selisih_jam < 24): ?>
                                                <button class="btn btn-warning btn-sm" onclick="showRefundWarning(
                                                    '<?php echo $riwayat['kode_pesanan']; ?>',
                                                    '<?php echo htmlspecialchars($riwayat['judul_buku']); ?>',
                                                    <?php echo $sisa_jam; ?>,
                                                    <?php echo floor($sisa_menit); ?>
                                                )">
                                                    <i class="fas fa-check-circle"></i> Refund Selesai
                                                </button>
                                                <?php endif; ?>
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
                    <i class="fas fa-history"></i>
                    <h4>Belum Ada Riwayat Belanja</h4>
                    <p>Belum ada transaksi yang telah selesai. Pesanan akan muncul di sini setelah statusnya menjadi "Diterima" atau "Refund".</p>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="produk.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Mulai Belanja
                        </a>
                        <a href="pesanan.php" class="btn btn-outline">
                            <i class="fas fa-clipboard-list"></i> Lihat Pesanan
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
                <span class="breadcrumb-item active">Riwayat Belanja</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Modal untuk Peringatan Refund -->
    <div class="refund-warning-modal" id="refundWarningModal">
        <div class="refund-warning-content" id="refundWarningContent">
            <!-- Content akan diisi oleh JavaScript -->
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal-overlay" id="orderModalOverlay"></div>
    <div class="modal-container" id="orderModal" style="display: none;">
        <div class="modal-header">
            <h2 class="modal-title">Detail Transaksi</h2>
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
                    window.location.href = 'riwayat.php?search=' + encodeURIComponent(searchTerm);
                }
            }
        }

        // Reset filters
        function resetFilters() {
            window.location.href = 'riwayat.php';
        }

        // Download PDF untuk semua riwayat
        function downloadPDF() {
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search') || '';
            const month = urlParams.get('month') || '';
            const year = urlParams.get('year') || '';
            
            window.location.href = `generate_pdf_riwayat.php?search=${encodeURIComponent(search)}&month=${month}&year=${year}`;
        }

        // Download PDF untuk transaksi tunggal
        function downloadSinglePDF(kodePesanan, judulBuku, namaPenjual, emailPenjual, totalHarga, hargaSatuan, qty, tanggal, status, approve, metodeBayar, namaPembeli, emailPembeli, alamatPembeli, ekspedisi, noResi) {
            window.location.href = `generate_pdf_single.php?kode=${encodeURIComponent(kodePesanan)}`;
        }

        // Show refund warning modal
        function showRefundWarning(kodePesanan, judulBuku, sisaJam, sisaMenit) {
            const modalContent = document.getElementById('refundWarningContent');
            const modal = document.getElementById('refundWarningModal');
            
            let timerInterval;
            let totalSeconds = (sisaJam * 3600) + (sisaMenit * 60);
            
            function updateTimer() {
                if (totalSeconds <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('refundTimer').innerHTML = 'WAKTU HABIS!';
                    document.getElementById('refundTimer').style.color = 'var(--danger)';
                    // Nonaktifkan tombol jika waktu habis
                    const takeButton = document.querySelector('#refundWarningContent .btn-success');
                    if (takeButton) {
                        takeButton.disabled = true;
                        takeButton.innerHTML = '<i class="fas fa-ban"></i> Waktu Habis';
                        takeButton.style.opacity = '0.5';
                        takeButton.style.cursor = 'not-allowed';
                    }
                    return;
                }
                
                totalSeconds--;
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                
                document.getElementById('refundTimer').innerHTML = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            modalContent.innerHTML = `
                <div class="refund-warning-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="refund-warning-title">REFUND SELESAI</div>
                <div class="refund-warning-message">
                    Konfirmasi penyelesaian refund. Pesanan akan dihapus dari database namun tetap tercatat sebagai "Refund Selesai" di riwayat.
                </div>
                
                <div class="refund-warning-detail">
                    <div class="refund-warning-detail-item">
                        <span class="refund-warning-detail-label">Kode Pesanan:</span>
                        <span class="refund-warning-detail-value">${kodePesanan}</span>
                    </div>
                    <div class="refund-warning-detail-item">
                        <span class="refund-warning-detail-label">Produk:</span>
                        <span class="refund-warning-detail-value">${judulBuku}</span>
                    </div>
                    <div class="refund-warning-detail-item">
                        <span class="refund-warning-detail-label">Sisa Waktu:</span>
                        <span class="refund-warning-detail-value" id="refundTimer">
                            ${sisaJam.toString().padStart(2, '0')}:${sisaMenit.toString().padStart(2, '0')}:00
                        </span>
                    </div>
                </div>
                
                <div class="refund-warning-actions">
                    <button class="btn btn-success" onclick="takeRefund('${kodePesanan}')">
                        <i class="fas fa-check-circle"></i> Selesaikan Refund
                    </button>
                    <button class="btn btn-secondary" onclick="closeRefundWarning()">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                </div>
                
                <div style="margin-top: 15px; font-size: 12px; color: var(--gray); text-align: center;">
                    <i class="fas fa-info-circle"></i> Pesanan akan dihapus dari database tetapi statusnya akan tetap ditampilkan sebagai "Refund Selesai"
                </div>
            `;
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Start timer
            timerInterval = setInterval(updateTimer, 1000);
            
            // Simpan interval untuk dibersihkan nanti
            modal.dataset.timerInterval = timerInterval;
        }

        function closeRefundWarning() {
            const modal = document.getElementById('refundWarningModal');
            clearInterval(modal.dataset.timerInterval);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Function untuk menghapus pesanan refund
        function takeRefund(kodePesanan) {
            if (!confirm('Yakin ingin menyelesaikan refund?')) return;

    fetch('proses_hapus_refund.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'aksi=refund&kode=' + encodeURIComponent(kodePesanan)
    })
    .then(res => res.text())
    .then(data => {
        alert(data);
        location.reload(); // optional
    });
        }

        // Function untuk update status refund di tampilan
        function updateRefundStatus(kodePesanan, status) {
            // Cari baris dengan kode pesanan tersebut
            const rows = document.querySelectorAll(`tr[data-order-code="${kodePesanan}"]`);
            
            rows.forEach(row => {
                if (status === 'completed') {
                    // Ubah status menjadi "Refund Selesai"
                    const statusBadge = row.querySelector('.status-badge.refund');
                    if (statusBadge) {
                        statusBadge.textContent = 'Refund Selesai';
                        statusBadge.classList.remove('refund');
                        statusBadge.classList.add('refund-selesai');
                        statusBadge.style.backgroundImage = 'none';
                    }
                    
                    // Hapus timer
                    const timerInfo = row.querySelector('.refund-time-info');
                    if (timerInfo) {
                        timerInfo.innerHTML = '<i class="fas fa-check-circle"></i> Refund telah diselesaikan';
                        timerInfo.classList.remove('refund-time-info');
                        timerInfo.classList.add('refund-time-info', 'success');
                    }
                    
                    // Hapus tombol "Refund Selesai"
                    const refundButton = row.querySelector('.btn-warning');
                    if (refundButton && refundButton.textContent.includes('Refund Selesai')) {
                        refundButton.remove();
                    }
                    
                    // Update baris menjadi tidak ada background refund lagi
                    row.classList.remove('refund-row');
                }
            });
        }

        // Function untuk menampilkan notifikasi
        function showNotification(type, message) {
            // Hapus notifikasi sebelumnya
            const existingNotification = document.querySelector('.custom-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            // Buat notifikasi baru
            const notification = document.createElement('div');
            notification.className = `custom-notification ${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-hide setelah 5 detik
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }

        // Show order detail modal
        function showOrderDetail(kodePesanan, judulBuku, namaPenjual, emailPenjual, totalHarga, hargaSatuan, qty, tanggalPesanan, status, approve, metodeBayar, namaPembeli, emailPembeli, alamatPembeli, buktiPembayaran, ekspedisi, noResi) {
            const modal = document.getElementById('orderModal');
            const modalContent = document.getElementById('orderModalContent');
            const modalOverlay = document.getElementById('orderModalOverlay');
            
            // Format tanggal
            const tanggalFormatted = new Date(tanggalPesanan).toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Tampilkan modal
            modal.style.display = 'block';
            modalOverlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Isi konten modal
            modalContent.innerHTML = `
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
                            <span class="detail-label">Tanggal Pesanan</span>
                            <span class="detail-value">${tanggalFormatted}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="status-badge ${status.toLowerCase()}">${status}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Persetujuan</span>
                            <span class="detail-value">
                                <span class="approve-badge ${approve.toLowerCase()}">${approve}</span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="detail-title">
                            <i class="fas fa-user"></i> Informasi Pembeli
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nama</span>
                            <span class="detail-value">${namaPembeli}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">${emailPembeli}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Alamat</span>
                            <span class="detail-value">${alamatPembeli}</span>
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
                            <i class="fas fa-money-bill-wave"></i> Informasi Pembayaran
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Produk</span>
                            <span class="detail-value">${judulBuku}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Jumlah</span>
                            <span class="detail-value">${qty} item</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Harga Satuan</span>
                            <span class="detail-value">Rp ${formatRupiah(hargaSatuan)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Metode Pembayaran</span>
                            <span class="detail-value">${metodeBayar}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Harga</span>
                            <span class="detail-value success">Rp ${formatRupiah(totalHarga)}</span>
                        </div>
                    </div>
                    
                    ${ekspedisi && noResi ? `
                    <div class="detail-section">
                        <div class="detail-title">
                            <i class="fas fa-shipping-fast"></i> Informasi Pengiriman
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ekspedisi</span>
                            <span class="detail-value">${ekspedisi}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">No Resi</span>
                            <span class="detail-value">${noResi}</span>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${buktiPembayaran ? `
                    <div class="bukti-section">
                        <div class="detail-title">
                            <i class="fas fa-receipt"></i> Bukti Pembayaran
                        </div>
                        <div class="bukti-image-container">
                            <img src="../../Src/uploads/${buktiPembayaran}" alt="Bukti Pembayaran" class="bukti-image">
                        </div>
                    </div>
                    ` : `
                    <div class="bukti-section">
                        <div class="detail-title">
                            <i class="fas fa-receipt"></i> Bukti Pembayaran
                        </div>
                        <div class="bukti-placeholder">
                            <i class="fas fa-receipt"></i>
                            <p>Bukti pembayaran tidak tersedia</p>
                        </div>
                    </div>
                    `}
                </div>
                
                <div class="modal-actions">
                    <button class="btn btn-primary" onclick="closeOrderModal()">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                </div>
            `;
        }

        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            const modalOverlay = document.getElementById('orderModalOverlay');
            
            modal.style.display = 'none';
            modalOverlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Format number to Rupiah
        function formatRupiah(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Close modal when clicking outside
        document.getElementById('refundWarningModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeRefundWarning();
            }
        });

        document.getElementById('orderModalOverlay').addEventListener('click', function() {
            closeOrderModal();
        });

        // Auto-show warning modal jika ada refund yang mendekati batas waktu
        document.addEventListener('DOMContentLoaded', function() {
            // Cek apakah ada refund yang kurang dari 1 jam
            const refundRows = document.querySelectorAll('.refund-row');
            let urgentRefund = null;
            
            refundRows.forEach(row => {
                const timeText = row.querySelector('.refund-time-info');
                if (timeText && timeText.textContent.includes('Selesaikan dalam')) {
                    const match = timeText.textContent.match(/Selesaikan dalam (\d+) jam (\d+) menit/);
                    if (match) {
                        const hours = parseInt(match[1]);
                        const minutes = parseInt(match[2]);
                        if (hours === 0 && minutes < 30) {
                            const kode = row.dataset.orderCode;
                            const judul = row.querySelector('.product-title').textContent;
                            urgentRefund = {
                                kode: kode,
                                judul: judul,
                                jam: hours,
                                menit: minutes
                            };
                        }
                    }
                }
            });
            
            // Tampilkan modal peringatan jika ada refund mendesak
            if (urgentRefund) {
                setTimeout(() => {
                    showRefundWarning(urgentRefund.kode, urgentRefund.judul, urgentRefund.jam, urgentRefund.menit);
                }, 1000);
            }
        });
    </script>
</body>
</html>