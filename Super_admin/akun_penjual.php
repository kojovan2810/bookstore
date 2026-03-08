<?php
session_start();
include "../Src/config.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['email_admin'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../login.php'</script>";
    exit();
}

$email_admin = $_SESSION['email_admin'];
$data = $conn->query("SELECT * FROM super_admin WHERE email_admin = '$email_admin'")->fetch_assoc();

// PROSES DELETE PENJUAL (dengan Logging)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_penjual'])) {
    $email_penjual = $_POST['email_penjual'];
    
    // Cek status penjual (hanya boleh hapus jika offline)
    $check_status = $conn->query("SELECT status, nama_penjual FROM penjual WHERE email_penjual = '$email_penjual'")->fetch_assoc();
    
    if (!$check_status) {
        echo json_encode(['success' => false, 'message' => 'Penjual tidak ditemukan']);
        exit();
    }
    
    if ($check_status['status'] == 'Online') {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus penjual yang sedang online']);
        exit();
    }
    
    // Mulai transaksi
    $conn->begin_transaction();
    $deleted_data = [];
    
    try {
        // Hitung dan hapus pesanan pending
        $pesanan_pending = $conn->query("SELECT COUNT(*) as total FROM pesanan WHERE email_penjual = '$email_penjual' AND approve IS NULL");
        $pesanan_data = $pesanan_pending->fetch_assoc();
        $total_pesanan_pending = $pesanan_data['total'];
        
        $conn->query("DELETE FROM pesanan WHERE email_penjual = '$email_penjual' AND approve IS NULL");
        if ($total_pesanan_pending > 0) {
            $deleted_data[] = $total_pesanan_pending . " pesanan pending";
        }
        
        // Hitung dan hapus produk
        $produk_query = $conn->query("SELECT COUNT(*) as total FROM produk_buku WHERE email_penjual = '$email_penjual'");
        $produk_data = $produk_query->fetch_assoc();
        $total_produk = $produk_data['total'];
        
        $conn->query("DELETE FROM produk_buku WHERE email_penjual = '$email_penjual'");
        if ($total_produk > 0) {
            $deleted_data[] = $total_produk . " produk";
        }
        
        // Hapus penjual itu sendiri
        $delete_penjual = $conn->query("DELETE FROM penjual WHERE email_penjual = '$email_penjual'");
        
        if ($delete_penjual) {
            $conn->commit();
            
            // Buat pesan konfirmasi
            $message = "Penjual '{$check_status['nama_penjual']}' berhasil dihapus";
            if (!empty($deleted_data)) {
                $message .= " beserta: " . implode(", ", $deleted_data);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'deleted' => [
                    'pesanan_pending' => $total_pesanan_pending,
                    'produk' => $total_produk
                ]
            ]);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus penjual']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting penjual {$email_penjual}: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    }
    exit();
}

// PROSES UPDATE STATUS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $email_penjual = $_POST['email_penjual'];
    
    // Ambil status saat ini
    $current_status = $conn->query("SELECT status, nama_penjual FROM penjual WHERE email_penjual = '$email_penjual'")->fetch_assoc();
    $new_status = ($current_status['status'] == 'Online') ? 'Offline' : 'Online';
    
    if ($conn->query("UPDATE penjual SET status = '$new_status' WHERE email_penjual = '$email_penjual'")) {
        echo json_encode([
            'success' => true, 
            'message' => 'Status ' . $current_status['nama_penjual'] . ' berhasil diubah menjadi ' . $new_status,
            'new_status' => $new_status,
            'status_text' => $new_status,
            'status_class' => ($new_status == 'Online') ? 'status-online' : 'status-offline'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah status penjual']);
    }
    exit();
}

// PROSES UPDATE DATA PENJUAL dengan cek duplikat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_data_penjual'])) {
    $email_penjual = $_POST['email_penjual'];
    $nama_penjual = $_POST['nama_penjual'];
    $nik_penjual = $_POST['nik_penjual'];
    $alamat_penjual = $_POST['alamat_penjual'];
    
    // Cek apakah NIK sudah digunakan oleh penjual lain
    if (!empty($nik_penjual)) {
        $check_nik = $conn->query("SELECT email_penjual FROM penjual WHERE nik_penjual = '$nik_penjual' AND email_penjual != '$email_penjual'");
        if ($check_nik->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'NIK sudah digunakan oleh penjual lain']);
            exit();
        }
    }
    
    // Cek apakah nama sudah digunakan oleh penjual lain
    $check_nama = $conn->query("SELECT email_penjual FROM penjual WHERE nama_penjual = '$nama_penjual' AND email_penjual != '$email_penjual'");
    if ($check_nama->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Nama sudah digunakan oleh penjual lain']);
        exit();
    }
    
    $update_query = "UPDATE penjual SET 
                    nama_penjual = '$nama_penjual',
                    nik_penjual = '$nik_penjual',
                    alamat_penjual = '$alamat_penjual'
                    WHERE email_penjual = '$email_penjual'";
    
    if ($conn->query($update_query)) {
        echo json_encode(['success' => true, 'message' => 'Data penjual berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data penjual']);
    }
    exit();
}

// Hitung statistik untuk penjual
$total_penjual = $conn->query("SELECT COUNT(*) as total FROM penjual")->fetch_assoc()['total'];
$penjual_online = $conn->query("SELECT COUNT(*) as total FROM penjual WHERE status = 'Online'")->fetch_assoc()['total'];
$penjual_offline = $conn->query("SELECT COUNT(*) as total FROM penjual WHERE status = 'Offline'")->fetch_assoc()['total'];

// Query data penjual dengan pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "";
if (!empty($search)) {
    $where_clause = "WHERE nama_penjual LIKE '%$search%' OR email_penjual LIKE '%$search%' OR nik_penjual LIKE '%$search%'";
}

$query = "SELECT * FROM penjual $where_clause ORDER BY nama_penjual LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Hitung total halaman
$total_data_query = "SELECT COUNT(*) as total FROM penjual $where_clause";
$total_data = $conn->query($total_data_query)->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penjual - BukuBook</title>
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
            --sidebar-width: 260px;
            --topbar-height: 70px;
            --bottombar-height: 50px;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar */
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
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
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

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Topbar */
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

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-role {
            font-size: 13px;
            color: var(--gray);
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

        /* Content */
        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            color: var(--gray);
            font-size: 14px;
        }

        .breadcrumb-separator {
            color: #adb5bd;
        }

        .breadcrumb-item {
            color: var(--gray);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--primary);
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
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
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .stat-3 .stat-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .stat-4 .stat-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
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

        /* Table Section */
        .table-section {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .table-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }

        .table-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-small {
            position: relative;
        }

        .search-small input {
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
        }

        .search-small i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
        }

        .add-btn {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .add-btn:hover {
            opacity: 0.9;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid var(--light-gray);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
            white-space: nowrap;
        }

        td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--light-gray);
            font-size: 15px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .status-online {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success);
            border-color: rgba(40, 167, 69, 0.3);
        }

        .status-offline {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger);
            border-color: rgba(220, 53, 69, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            font-size: 16px;
            transition: all 0.3s;
        }

        .action-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            transform: none;
        }

        .action-btn:disabled:hover {
            background-color: inherit;
            transform: none;
        }

        .btn-view {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .btn-view:hover {
            background-color: rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }

        .btn-edit {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .btn-edit:hover {
            background-color: rgba(40, 167, 69, 0.2);
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .btn-delete:hover {
            background-color: rgba(220, 53, 69, 0.2);
            transform: translateY(-2px);
        }

        .btn-toggle {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .btn-toggle:hover {
            background-color: rgba(255, 193, 7, 0.2);
            transform: translateY(-2px);
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 15px;
        }

        .pagination {
            display: flex;
            list-style: none;
            gap: 8px;
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

        /* Footer */
        .footer {
            height: 50px;
            background-color: white;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--light-gray);
            color: var(--gray);
            font-size: 14px;
        }

        .copyright {
            font-size: 13px;
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
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        

        .modal-container {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--gray);
            cursor: pointer;
            padding: 5px;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-content {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-size: 15px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background-color: #dee2e6;
            transform: translateY(-2px);
        }

        /* Detail Modal Styles */
        .detail-product-info {
            padding: 10px 5px;
        }

        .detail-section {
            margin-bottom: 25px;
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

        .detail-value.email {
            color: var(--primary);
        }

        .detail-value.status-online {
            color: var(--success);
        }

        .detail-value.status-offline {
            color: var(--danger);
        }

        /* Header Detail dengan Foto */
        .detail-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .detail-photo {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }

        .detail-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .detail-title {
            flex: 1;
        }

        .detail-title h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 3000;
            display: none;
            align-items: center;
            gap: 10px;
            animation: toastSlideIn 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .toast-success {
            background-color: var(--success);
        }

        .toast-error {
            background-color: var(--danger);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
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
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px;
            }
            
            .table-controls {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-small input {
                width: 100%;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .footer {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
            
            .modal-container {
                width: 95%;
                margin: 20px;
            }
            
            .detail-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .detail-photo {
                width: 80px;
                height: 80px;
            }
            
            .detail-title h3 {
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        .menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <!-- Loading Overlay -->
    <div class="modal-overlay" id="loadingOverlay">
        <div style="color: white; font-size: 20px;">
            <i class="fas fa-spinner fa-spin"></i> Memproses...
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Detail Penjual</h2>
                <button class="modal-close" onclick="closeModal('detailModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content" id="detailModalContent">
                <!-- Content akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Edit Penjual</h2>
                <button class="modal-close" onclick="closeModal('editModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm" onsubmit="updatePenjual(event)">
                <div class="modal-content">
                    <input type="hidden" id="editEmail" name="email_penjual">
                    <div class="form-group">
                        <label class="form-label">Nama Penjual</label>
                        <input type="text" id="editNama" name="nama_penjual" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="editEmailDisplay" class="form-control" readonly>
                        <small style="color: var(--gray);">Email tidak dapat diubah</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIK</label>
                        <input type="text" id="editNik" name="nik_penjual" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea id="editAlamat" name="alamat_penjual" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Delete -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Hapus Penjual</h2>
                <button class="modal-close" onclick="closeModal('deleteModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content">
                <p id="deleteMessage"></p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ubah Status -->
    <div class="modal-overlay" id="statusModal">
        <div class="modal-container" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Ubah Status Penjual</h2>
                <button class="modal-close" onclick="closeModal('statusModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content">
                <p id="statusMessage">Pilih status baru untuk penjual:</p>
                <div class="form-group">
                    <select id="newStatusSelect" class="form-control">
                        <option value="Online">Online</option>
                        <option value="Offline">Offline</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" onclick="confirmStatusChange()">
                        <i class="fas fa-sync-alt"></i> Ubah Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-book"></i>
                <span>BukuBook</span>
            </div>
        </div>

        <div class="nav-section">
            <div class="section-title">MAIN</div>
            <ul class="nav-links">
                <li><a href="beranda.php" class="nav-item"><i class="fas fa-home"></i> <span class="nav-text">Dashboard</span></a></li>
            </ul>
        </div>

        <div class="nav-section">
            <div class="section-title">MANAGEMENT</div>
            <ul class="nav-links">
                <li>
                    <a href="kategori.php" class="nav-item">
                        <i class="fas fa-tags"></i>
                        <span class="nav-text">Categories</span>
                    </a>
                </li>
                <li>
                    <a href="akun_pembeli.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Pembeli</span>
                    </a>
                </li>
                <li>
                    <a href="akun_penjual.php" class="nav-item active">
                        <i class="fas fa-user-cog"></i>
                        <span class="nav-text">Penjual</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <div class="section-title">SUPPORT</div>
            <ul class="nav-links">
                <li><a href="help_center.php" class="nav-item"><i class="fas fa-question-circle"></i> <span class="nav-text">Help Center</span></a></li>
            </ul>
        </div>
        
        <div class="nav-section">
            <div class="section-title">SETTING</div>
            <ul class="nav-links">
                <li>
                    <a href="../Src/logout.php" class="nav-item logout-link" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Log Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="topSearch" placeholder="Type here to search..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onkeypress="handleTopSearch(event)">
                </div>
            </div>

            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['nama_admin'] ?? 'Admin'); ?></div>
                    <div class="user-role">Super Admin</div>
                </div>
                <?php 
                $foto = isset($data['foto']) ? $data['foto'] : null;
                $nama_admin = $data['nama_admin'] ?? 'Admin';
                $src = $foto ? "../Src/uploads/$foto" : "https://ui-avatars.com/api/?name=" . urlencode($nama_admin) . "&background=4361ee&color=fff&size=120";
                ?>
                <img src="<?php echo $src; ?>" 
                     alt="Profile" 
                     class="user-avatar"
                     onclick="window.location.href='../Src/profile.php'">
            </div>
        </header>

        <!-- Content -->
        <main class="content">
            <h1 class="page-title">Data Penjual</h1>
            
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Penjual</span>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-1">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $penjual_online; ?></h3>
                        <p>Penjual Online</p>
                    </div>
                </div>
                
                <div class="stat-card stat-2">
                    <div class="stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $penjual_offline; ?></h3>
                        <p>Penjual Offline</p>
                    </div>
                </div>
                
                <div class="stat-card stat-3">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_penjual; ?></h3>
                        <p>Total Penjual</p>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-section">
                <div class="table-header">
                    <div class="table-title">Daftar Penjual</div>
                    <div class="table-controls">
                        <form method="GET" action="" class="search-small" id="searchForm">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari Penjual..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   id="searchInput">
                            <button type="submit" style="display: none;"></button>
                        </form>
                        
                        <?php if ($search): ?>
                            <a href="akun_penjual.php" class="add-btn" style="background: var(--danger);">
                                <i class="fas fa-times"></i> Clear Search
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-container">
                    <table id="penjualTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Alamat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php
                            if ($result->num_rows > 0) {
                                $no = $offset + 1;
                                while($row = $result->fetch_assoc()) {
                                    $foto_penjual = !empty($row['foto']) ? "../Src/uploads/" . $row['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($row['nama_penjual']) . "&background=4361ee&color=fff&size=120";
                                    $status_class = ($row['status'] == 'Online') ? 'status-online' : 'status-offline';
                                    $is_online = ($row['status'] == 'Online');
                                    
                                    echo "<tr id='row-" . $row['email_penjual'] . "'>";
                                    echo "<td>" . $no . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nik_penjual'] ?? '-') . "</td>";
                                    echo "<td>";
                                    echo "<div style='display: flex; align-items: center; gap: 10px;'>";
                                    echo "<img src='" . $foto_penjual . "' alt='" . htmlspecialchars($row['nama_penjual']) . "' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover;'>";
                                    echo "<span>" . htmlspecialchars($row['nama_penjual']) . "</span>";
                                    echo "</div>";
                                    echo "</td>";
                                    echo "<td>" . htmlspecialchars($row['email_penjual']) . "</td>";
                                    echo "<td>" . htmlspecialchars(substr($row['alamat_penjual'], 0, 50)) . (strlen($row['alamat_penjual']) > 50 ? '...' : '') . "</td>";
                                    
                                    echo "<td>";
                                    echo "<span class='status-badge " . $status_class . "' id='status-" . $row['email_penjual'] . "' ";
                                    echo "onclick='showStatusModal(\"" . $row['email_penjual'] . "\", \"" . $row['status'] . "\", \"" . addslashes($row['nama_penjual']) . "\")'>";
                                    echo ($row['status'] == 'Online') ? 'Online' : 'Offline';
                                    echo "</span>";
                                    echo "</td>";
                                    
                                    echo "<td>";
                                    echo "<div class='action-buttons'>";
                                    echo "<button class='action-btn btn-view' onclick='viewPenjual(\"" . $row['email_penjual'] . "\", \"" . addslashes($row['nama_penjual']) . "\", \"" . addslashes($row['nik_penjual']) . "\", \"" . addslashes($row['alamat_penjual']) . "\", \"" . $row['status'] . "\", \"" . $row['created_at']  . "\", \"" . $foto_penjual . "\")' title='Lihat Detail'>";
                                    echo "<i class='fas fa-eye'></i>";
                                    echo "</button>";
                                    
                                    // Tombol edit disabled jika status online
                                    if ($is_online) {
                                        echo "<button class='action-btn btn-edit' disabled title='Tidak dapat mengedit penjual yang sedang online'>";
                                        echo "<i class='fas fa-edit'></i>";
                                        echo "</button>";
                                    } else {
                                        echo "<button class='action-btn btn-edit' onclick='editPenjual(\"" . $row['email_penjual'] . "\", \"" . addslashes($row['nama_penjual']) . "\", \"" . addslashes($row['nik_penjual']) . "\", \"" . addslashes($row['alamat_penjual']) . "\")' title='Edit Penjual'>";
                                        echo "<i class='fas fa-edit'></i>";
                                        echo "</button>";
                                    }
                                    
                                    echo "<button class='action-btn btn-toggle' onclick='showStatusModal(\"" . $row['email_penjual'] . "\", \"" . $row['status'] . "\", \"" . addslashes($row['nama_penjual']) . "\")' title='Ubah Status'>";
                                    echo "<i class='fas fa-sync-alt'></i>";
                                    echo "</button>";
                                    
                                    // Tombol delete disabled jika status online
                                    if ($is_online) {
                                        echo "<button class='action-btn btn-delete' disabled title='Tidak dapat menghapus penjual yang sedang online'>";
                                        echo "<i class='fas fa-trash'></i>";
                                        echo "</button>";
                                    } else {
                                        echo "<button class='action-btn btn-delete' onclick='showDeleteModal(\"" . $row['email_penjual'] . "\", \"" . addslashes($row['nama_penjual']) . "\")' title='Hapus Penjual'>";
                                        echo "<i class='fas fa-trash'></i>";
                                        echo "</button>";
                                    }
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align: center; padding: 40px;'>Tidak ada data penjual" . ($search ? " untuk pencarian '$search'" : "") . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div>
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_data); ?> of <?php echo $total_data; ?> entries
                    <?php if ($search): ?>
                        (Hasil pencarian: "<?php echo htmlspecialchars($search); ?>")
                    <?php endif; ?>
                </div>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
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
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    $start_page = max(1, $end_page - 4);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
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

            <!-- Footer -->
            <footer class="footer">
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> BukuBook. All rights reserved.
                </div>
                <div style="font-size: 13px;">
                    Total Penjual: <?php echo $total_penjual; ?> | Online: <?php echo $penjual_online; ?> | Offline: <?php echo $penjual_offline; ?>
                </div>
            </footer>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Search functionality
        document.getElementById('topSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                window.location.href = 'akun_penjual.php?search=' + encodeURIComponent(searchTerm);
            }
        });

        // Auto submit search form when typing stops
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        });

        // Format date
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            
            toastMessage.textContent = message;
            toast.className = 'toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
            toast.style.display = 'flex';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        // Show loading
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        // Hide loading
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Open modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        // View penjual detail
        function viewPenjual(email, nama, nik, alamat, status, createdAt, foto) {
            const createdDate = formatDate(createdAt);
            const statusClass = status === 'Online' ? 'status-online' : 'status-offline';
            const statusText = status === 'Online' ? 'Online' : 'Offline';
            
            const detailHtml = `
                <div class="detail-product-info">
                    <div class="detail-header">
                        <div class="detail-photo">
                            <img src="${foto}" alt="Foto ${nama}">
                        </div>
                        <div class="detail-title">
                            <h3>${nama}</h3>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-user"></i> Informasi Penjual</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value email">${email}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">NIK</div>
                                <div class="detail-value">${nik || '-'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value ${statusClass}">${statusText}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Bergabung</div>
                                <div class="detail-value">${createdDate}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Alamat Penjual</h4>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary);">
                            <div style="font-size: 16px; line-height: 1.6; color: var(--dark);">
                                ${alamat || 'Alamat belum diisi'}
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-info-circle"></i> Informasi Status</h4>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid ${status === 'Online' ? 'var(--success)' : 'var(--danger)'};">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas ${status === 'Online' ? 'fa-wifi' : 'fa-power-off'}" 
                                   style="color: ${status === 'Online' ? 'var(--success)' : 'var(--danger)'}; font-size: 20px;"></i>
                                <div>
                                    <div style="font-weight: 600; color: var(--dark);">Status: ${statusText}</div>
                                    <div style="font-size: 14px; color: var(--gray); margin-top: 3px;">
                                        ${status === 'Online' 
                                            ? 'Penjual sedang aktif dan dapat menerima pesanan.' 
                                            : 'Penjual sedang offline dan tidak dapat menerima pesanan.'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detailModalContent').innerHTML = detailHtml;
            openModal('detailModal');
        }

        // Edit penjual
        let currentEditEmail = '';
        function editPenjual(email, nama, nik, alamat) {
            currentEditEmail = email;
            document.getElementById('editEmail').value = email;
            document.getElementById('editEmailDisplay').value = email;
            document.getElementById('editNama').value = nama;
            document.getElementById('editNik').value = nik || '';
            document.getElementById('editAlamat').value = alamat || '';
            openModal('editModal');
        }

        // Update penjual
        function updatePenjual(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('editForm'));
            formData.append('update_data_penjual', '1');
            
            showLoading();
            fetch('akun_penjual.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast(data.message);
                    closeModal('editModal');
                    
                    // Update row in table
                    const row = document.getElementById('row-' + currentEditEmail);
                    if (row) {
                        const namaCell = row.cells[2];
                        const nama = formData.get('nama_penjual');
                        const imgSrc = namaCell.querySelector('img').src;
                        
                        namaCell.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="${imgSrc}" alt="${nama}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <span>${nama}</span>
                            </div>
                        `;
                        row.cells[1].textContent = formData.get('nik_penjual') || '-';
                        
                        const alamatText = formData.get('alamat_penjual') || '';
                        row.cells[4].textContent = alamatText.substring(0, 50) + (alamatText.length > 50 ? '...' : '');
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showToast('Network error', 'error');
            });
        }

        // Status modal
        let currentStatusEmail = '';
        let currentStatusName = '';
        function showStatusModal(email, currentStatus, nama) {
            currentStatusEmail = email;
            currentStatusName = nama;
            document.getElementById('newStatusSelect').value = currentStatus;
            document.getElementById('statusMessage').textContent = `Pilih status baru untuk penjual "${nama}":`;
            openModal('statusModal');
        }

        // Confirm status change
        function confirmStatusChange() {
            const newStatus = document.getElementById('newStatusSelect').value;
            
            showLoading();
            fetch('akun_penjual.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'update_status=1&email_penjual=' + encodeURIComponent(currentStatusEmail)
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast(data.message);
                    closeModal('statusModal');
                    
                    // Update status badge
                    const statusBadge = document.getElementById('status-' + currentStatusEmail);
                    if (statusBadge) {
                        statusBadge.className = 'status-badge ' + data.status_class;
                        statusBadge.textContent = data.status_text;
                    }
                    
                    // Update button states based on new status
                    const row = document.getElementById('row-' + currentStatusEmail);
                    if (row) {
                        const editBtn = row.querySelector('.btn-edit');
                        const deleteBtn = row.querySelector('.btn-delete');
                        
                        if (data.new_status === 'Online') {
                            // Disable edit and delete buttons
                            if (editBtn) {
                                editBtn.disabled = true;
                                editBtn.title = 'Tidak dapat mengedit penjual yang sedang online';
                            }
                            if (deleteBtn) {
                                deleteBtn.disabled = true;
                                deleteBtn.title = 'Tidak dapat menghapus penjual yang sedang online';
                            }
                        } else {
                            // Enable edit and delete buttons
                            if (editBtn) {
                                editBtn.disabled = false;
                                editBtn.title = 'Edit Penjual';
                                editBtn.onclick = function() { 
                                    editPenjual(
                                        currentStatusEmail,
                                        currentStatusName,
                                        row.cells[1].textContent,
                                        row.cells[4].textContent
                                    ); 
                                };
                            }
                            if (deleteBtn) {
                                deleteBtn.disabled = false;
                                deleteBtn.title = 'Hapus Penjual';
                                deleteBtn.onclick = function() { 
                                    showDeleteModal(currentStatusEmail, currentStatusName); 
                                };
                            }
                        }
                    }
                    
                    // Update stats
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showToast('Network error', 'error');
            });
        }

        // Show delete modal
        let deleteEmail = '';
        let deleteNama = '';
        function showDeleteModal(email, nama) {
            deleteEmail = email;
            deleteNama = nama;
            document.getElementById('deleteMessage').textContent = 
                'Apakah Anda yakin ingin menghapus penjual "' + nama + '"?\n\n' +
                'Aksi ini tidak dapat dibatalkan.';
            openModal('deleteModal');
        }

        // Confirm delete
        function confirmDelete() {
            showLoading();
            fetch('akun_penjual.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'delete_penjual=1&email_penjual=' + encodeURIComponent(deleteEmail)
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast(data.message);
                    closeModal('deleteModal');
                    
                    // Remove row from table
                    const row = document.getElementById('row-' + deleteEmail);
                    if (row) {
                        row.remove();
                    }
                    
                    // Update stats
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showToast('Network error', 'error');
            });
        }

        // Close modals when clicking overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>