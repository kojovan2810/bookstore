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

// PROSES TAMBAH KE KERANJANG JIKA ADA REQUEST POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $id_buku = $_POST['id_buku'];
    $quantity = $_POST['quantity'];
    
    // Ambil data produk
    $produk_query = $conn->query("SELECT pb.*, p.nama_penjual FROM produk_buku pb 
                                  JOIN penjual p ON pb.email_penjual = p.email_penjual 
                                  WHERE pb.id_buku = '$id_buku'");
    
    if ($produk_query->num_rows > 0) {
        $produk = $produk_query->fetch_assoc();
        
        // Hitung total harga
        $harga = $produk['harga_buku'];
        $total_harga = $harga * $quantity;
        
        // Cek apakah produk sudah ada di keranjang
        $check_cart = $conn->query("SELECT * FROM keranjang 
                                    WHERE email_pembeli = '$email_pembeli' 
                                    AND id_buku = '$id_buku'");
        
        if ($check_cart->num_rows > 0) {
            // Update quantity jika sudah ada
            $conn->query("UPDATE keranjang 
                         SET qty = qty + $quantity, 
                             total_harga = total_harga + $total_harga 
                         WHERE email_pembeli = '$email_pembeli' 
                         AND id_buku = '$id_buku'");
        } else {
            // Insert baru jika belum ada
            $conn->query("INSERT INTO keranjang (id_buku, judul_buku, harga, qty, total_harga, 
                         email_pembeli, nama_pembeli, email_penjual, nama_penjual) 
                         VALUES ('$id_buku', '{$produk['judul_buku']}', '$harga', '$quantity', 
                         '$total_harga', '$email_pembeli', '{$data_pembeli['nama_pembeli']}', 
                         '{$produk['email_penjual']}', '{$produk['nama_penjual']}')");
        }
        
        echo "<script>alert('Produk berhasil ditambahkan ke keranjang!'); window.location.href='keranjang.php'</script>";
    }
}

// Ambil parameter pencarian dan filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Query dasar
$query = "SELECT pb.*, p.foto as foto_penjual, p.nama_penjual 
          FROM produk_buku pb 
          LEFT JOIN penjual p ON pb.email_penjual = p.email_penjual 
          WHERE pb.status = 'Aktif'";

// Filter pencarian
if (!empty($search)) {
    $query .= " AND (pb.judul_buku LIKE '%$search%' OR pb.kategori_buku LIKE '%$search%' OR p.nama_penjual LIKE '%$search%')";
}

// Filter kategori
if (!empty($kategori) && $kategori != 'semua') {
    $query .= " AND pb.kategori_buku = '$kategori'";
}

// Sorting
switch ($sort) {
    case 'termurah':
        $query .= " ORDER BY pb.harga_buku ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY pb.harga_buku DESC";
        break;
    case 'terlaris':
        $query .= " ORDER BY pb.id_buku DESC";
        break;
    case 'terbaru':
    default:
        $query .= " ORDER BY pb.id_buku DESC";
        break;
}

// Eksekusi query
$produk_query = $conn->query($query);

// Hitung total produk
$total_produk = $produk_query->num_rows;

// Ambil kategori unik untuk filter
$kategori_query = $conn->query("SELECT DISTINCT kategori_buku FROM produk_buku WHERE status = 'Aktif' ORDER BY kategori_buku");
$kategori_list = [];
while ($row = $kategori_query->fetch_assoc()) {
    $kategori_list[] = $row['kategori_buku'];
}

// Simpan semua produk dalam array untuk modal
$all_products = [];
while ($row = $produk_query->fetch_assoc()) {
    $all_products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Belanja Buku</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse all styles from beranda.php */
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

        /* Reuse sidebar, topbar, main-content styles from beranda.php */
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
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .welcome-message {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 25px;
        }

        /* Filter Section */
        .filter-section {
            background-color: white;
            border-radius: 12px;
            padding: 18px 22px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .filter-select, .filter-input {
            padding: 10px 12px;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-size: 14px;
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
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .filter-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .filter-button i {
            font-size: 13px;
        }

        .reset-button {
            padding: 10px 20px;
            background-color: var(--light-gray);
            color: var(--dark);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .reset-button:hover {
            background-color: #dee2e6;
        }

        /* Results Info */
        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 12px 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
        }

        .total-products {
            font-size: 14px;
            color: var(--gray);
        }

        .total-products strong {
            color: var(--primary);
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .product-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 1px solid var(--light-gray);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }

        .product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 36px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            font-size: 11px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            line-height: 1.4;
            height: 38px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-price {
            font-size: 16px;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 8px;
        }

        .product-seller {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .product-seller i {
            font-size: 11px;
        }

        .product-stock {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 12px;
        }

        /* Quantity Control */
        .quantity-control-container {
            margin: 10px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid var(--light-gray);
        }

        .quantity-control-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 6px;
            display: block;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border-radius: 5px;
            border: 1px solid var(--light-gray);
            background-color: white;
            color: var(--dark);
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background-color: var(--light-gray);
            border-color: var(--primary);
            color: var(--primary);
        }

        .qty-input {
            width: 45px;
            height: 28px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }

        .stock-info {
            font-size: 11px;
            color: var(--gray);
            text-align: center;
            margin-top: 6px;
        }

        /* PERBAIKAN: Product Actions Responsif */
        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            flex-wrap: wrap;
        }

        .product-actions .btn,
        .product-actions .chat-link {
            flex: 1 0 calc(33.333% - 6px);
            min-width: 70px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            gap: 6px;
            border: 2px solid;
            box-sizing: border-box;
            height: 36px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-actions .chat-link {
            background-color: white;
            color: var(--primary);
            border-color: var(--primary);
        }

        .product-actions .chat-link:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.3);
        }

        /* Form dalam product-actions */
        .product-actions form {
            flex: 1 0 calc(33.333% - 6px);
            min-width: 70px;
            display: flex;
            height: 36px;
        }

        .cart-form button {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Tombol standar */
        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
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

        /* Hover effect konsisten untuk semua tombol */
        .product-actions .btn:hover,
        .product-actions .chat-link:hover {
            transform: translateY(-2px);
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 50px 30px;
            color: var(--gray);
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            grid-column: 1 / -1;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-data h4 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .no-data p {
            font-size: 14px;
            margin-bottom: 15px;
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
            max-width: 700px;
            max-height: 85vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 20px 25px 15px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 22px;
            color: var(--gray);
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-content {
            padding: 25px;
        }

        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .detail-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 48px;
        }

        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .detail-info h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .detail-category {
            font-size: 13px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .detail-price {
            font-size: 22px;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 15px;
        }

        .detail-description {
            margin-bottom: 20px;
        }

        .detail-description h4 {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .detail-description p {
            font-size: 14px;
            color: var(--gray);
            line-height: 1.6;
        }

        .detail-meta {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }

        .meta-item:last-child {
            border-bottom: none;
        }

        .meta-label {
            font-weight: 600;
            color: var(--dark);
        }

        .meta-value {
            color: var(--gray);
            text-align: right;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .modal-actions .btn {
            padding: 10px 15px;
            font-size: 13px;
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

        /* RESPONSIVE DESIGN */
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
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 12px;
                text-align: center;
                gap: 8px;
                height: auto;
            }
            
            .results-info {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
            
            .product-detail-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .detail-image {
                height: 200px;
            }
            
            .modal-container {
                width: 95%;
                max-height: 80vh;
            }
            
            /* Responsive untuk tombol produk */
            .product-actions .btn,
            .product-actions .chat-link,
            .product-actions form {
                flex: 1 0 calc(50% - 4px);
                min-width: 60px;
                padding: 7px 10px;
                font-size: 11px;
            }
            
            .btn-text {
                display: inline;
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            /* Responsif untuk layar sangat kecil */
            .product-actions .btn,
            .product-actions .chat-link,
            .product-actions form {
                flex: 1 0 100%;
                min-width: 100%;
                margin-bottom: 5px;
            }
            
            .product-actions .btn:last-child,
            .product-actions .chat-link:last-child,
            .product-actions form:last-child {
                margin-bottom: 0;
            }
            
            .btn {
                width: 100%;
                min-width: auto;
            }
        }

        @media (max-width: 360px) {
            /* Sembunyikan teks pada tombol untuk layar sangat kecil */
            .btn-text {
                display: none;
            }
            
            .product-actions .btn i,
            .product-actions .chat-link i {
                margin-right: 0;
                font-size: 14px;
            }
            
            .product-actions .btn,
            .product-actions .chat-link {
                min-width: 40px;
                padding: 8px 5px;
                justify-content: center;
            }
            
            .product-actions form {
                min-width: 40px;
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
                    <a href="produk.php" class="nav-item active">
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
                <li>
                    <a href="pesanan.php" class="nav-item">
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
                    <input type="text" id="searchInput" placeholder="Cari buku, penulis, atau kategori..." 
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
            <h1 class="page-title">Belanja Buku</h1>
            <p class="welcome-message">
                Temukan buku favorit Anda dari koleksi terbaik kami. Filter berdasarkan kategori atau cari judul spesifik.
            </p>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="" id="filterForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <div class="filter-label">Cari Produk</div>
                            <input type="text" name="search" class="filter-input" 
                                   placeholder="Judul, kategori, atau penjual..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <div class="filter-label">Kategori</div>
                            <select name="kategori" class="filter-select">
                                <option value="semua">Semua Kategori</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?php echo htmlspecialchars($kat); ?>" 
                                        <?php echo ($kategori == $kat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <div class="filter-label">Urutkan</div>
                            <select name="sort" class="filter-select">
                                <option value="terbaru" <?php echo ($sort == 'terbaru') ? 'selected' : ''; ?>>Terbaru</option>
                                <option value="termurah" <?php echo ($sort == 'termurah') ? 'selected' : ''; ?>>Termurah</option>
                                <option value="termahal" <?php echo ($sort == 'termahal') ? 'selected' : ''; ?>>Termahal</option>
                                <option value="terlaris" <?php echo ($sort == 'terlaris') ? 'selected' : ''; ?>>Terlaris</option>
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
                <div class="total-products">
                    Menampilkan <strong><?php echo $total_produk; ?></strong> produk
                    <?php if (!empty($search)): ?>
                        untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                    <?php if (!empty($kategori) && $kategori != 'semua'): ?>
                        dalam kategori "<strong><?php echo htmlspecialchars($kategori); ?></strong>"
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php if ($total_produk > 0): ?>
                    <?php foreach ($all_products as $index => $produk): 
                        $foto_produk = !empty($produk['foto']) ? "../../Src/uploads/produk/" . $produk['foto'] : "https://via.placeholder.com/300x200/cccccc/666666?text=Buku";
                        $foto_penjual = !empty($produk['foto']) ? "../../Src/uploads/produk/" . $produk['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($produk['nama_penjual']) . "&background=4361ee&color=fff&size=120";
                    ?>
                        <div class="product-card" data-product-index="<?php echo $index; ?>">
                            <div class="product-image">
                                <?php if (!empty($produk['foto'])): ?>
                                    <img src="<?php echo $foto_produk; ?>" 
                                         alt="<?php echo htmlspecialchars($produk['judul_buku']); ?>" 
                                         onerror="this.src='https://via.placeholder.com/300x200/cccccc/666666?text=Buku'">
                                <?php else: ?>
                                    <i class="fas fa-book"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($produk['kategori_buku']); ?></div>
                                <h3 class="product-title"><?php echo htmlspecialchars($produk['judul_buku']); ?></h3>
                                <div class="product-price">Rp <?php echo number_format($produk['harga_buku'], 0, ',', '.'); ?></div>
                                <div class="product-seller">
                                    <img src="<?php echo $foto_penjual; ?>" 
                                         alt="<?php $produk['nama_penjual']; ?>"
                                         style="width: 18px; height: 18px; border-radius: 50%; margin-right: 5px;">
                                    <?php echo $produk['nama_penjual'] ?>
                                </div>
                                <div class="product-stock">Stok: <?php echo $produk['stok']; ?> pcs</div>
                                
                                <!-- Quantity Control -->
                                <div class="quantity-control-container">
                                    <span class="quantity-control-label">Jumlah:</span>
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn minus" onclick="updateQuantity(<?php echo $index; ?>, -1)">-</button>
                                        <input type="number" 
                                               class="qty-input" 
                                               id="qty-<?php echo $produk['id_buku']; ?>"
                                               name="quantity"
                                               value="1" 
                                               min="1" 
                                               max="<?php echo $produk['stok']; ?>"
                                               onchange="validateQuantity(<?php echo $index; ?>, this.value)">
                                        <button type="button" class="qty-btn plus" onclick="updateQuantity(<?php echo $index; ?>, 1)">+</button>
                                    </div>
                                    <div class="stock-info">
                                        Stok tersedia: <?php echo $produk['stok']; ?>
                                    </div>
                                </div>
                                
                                <!-- Product Actions - PERBAIKAN STRUKTUR -->
                                <div class="product-actions">
                                    <!-- Form untuk tambah ke keranjang -->
                                    <form method="POST" action="" class="cart-form">
                                        <input type="hidden" name="id_buku" value="<?php echo $produk['id_buku']; ?>">
                                        <input type="hidden" name="quantity" id="form-qty-<?php echo $produk['id_buku']; ?>" value="1">
                                        <button type="submit" name="add_to_cart" class="btn btn-success" onclick="return prepareCartForm(<?php echo $produk['id_buku']; ?>, <?php echo $index; ?>)">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </form>
                                    
                                    <!-- Link Chat -->
                                    <a href="room_chat.php?chat_with=<?php echo urlencode($produk['email_penjual']); ?>" 
                                       class="chat-link">
                                        <i class="fas fa-comment"></i> <span class="btn-text">Chat</span>
                                    </a>
                                    
                                    <!-- Button Detail -->
                                    <button class="btn btn-outline detail-btn" onclick="showProductDetail(<?php echo $index; ?>)">
                                        <i class="fas fa-eye"></i> <span class="btn-text">Detail</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-search"></i>
                        <h4>Produk tidak ditemukan</h4>
                        <p>Tidak ada produk yang sesuai dengan kriteria pencarian Anda.</p>
                        <button class="btn btn-primary" onclick="resetFilters()">
                            <i class="fas fa-redo"></i> Reset Filter
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Belanja</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Product Detail Modal -->
    <div class="modal-overlay" id="productModalOverlay"></div>
    <div class="modal-container" id="productModal" style="display: none;">
        <div class="modal-header">
            <h2 class="modal-title">Detail Produk</h2>
            <button class="modal-close" onclick="closeProductModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-content" id="productModalContent">
            <!-- Content akan diisi oleh JavaScript -->
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Simpan semua produk dari PHP ke JavaScript
        const allProducts = <?php echo json_encode($all_products); ?>;
        
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Handle search from topbar
        function handleSearch(event) {
            if (event.key === 'Enter') {
                const searchTerm = document.getElementById('searchInput').value.trim();
                if (searchTerm) {
                    window.location.href = 'produk.php?search=' + encodeURIComponent(searchTerm);
                }
            }
        }

        // Reset filters
        function resetFilters() {
            window.location.href = 'produk.php';
        }

        // Product functions
        function showProductDetail(productIndex) {
            if (!allProducts[productIndex]) {
                alert('Produk tidak ditemukan');
                return;
            }
            
            const product = allProducts[productIndex];
            const modalContent = document.getElementById('productModalContent');
            
            let foto_produk = product.foto ? '../../Src/uploads/produk/' + product.foto : 'https://via.placeholder.com/300x200/cccccc/666666?text=Buku';
            let fotoPenjual = product.foto_penjual ? '../../Src/uploads/' + product.foto_penjual : `https://ui-avatars.com/api/?name=${encodeURIComponent(product.nama_penjual)}&background=4361ee&color=fff&size=120`;
            
            modalContent.innerHTML = `
                <div class="product-detail-grid">
                    <div class="detail-image-container">
                        <div class="detail-image">
                            <img src="${foto_produk}" 
                                 alt="${escapeHtml(product.judul_buku)}" 
                                 onerror="this.src='https://via.placeholder.com/300x200/cccccc/666666?text=Buku'">
                        </div>
                    </div>
                    <div class="detail-info">
                        <div class="detail-category">${escapeHtml(product.kategori_buku)}</div>
                        <h3>${escapeHtml(product.judul_buku)}</h3>
                        <div class="detail-price">Rp ${formatRupiah(product.harga_buku)}</div>
                        
                        <div class="detail-description">
                            <h4>Deskripsi</h4>
                            <p>${escapeHtml(product.deskripsi || 'Tidak ada deskripsi tersedia.')}</p>
                        </div>
                        
                        <div class="detail-meta">
                            <div class="meta-item">
                                <span class="meta-label">Penjual:</span>
                                <span class="meta-value">
                                    <img src="${fotoPenjual}" 
                                         alt="${escapeHtml(product.nama_penjual)}" 
                                         style="width: 22px; height: 22px; border-radius: 50%; vertical-align: middle; margin-right: 5px;">
                                    ${escapeHtml(product.nama_penjual)}
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Email Penjual:</span>
                                <span class="meta-value">${escapeHtml(product.email_penjual)}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Stok Tersedia:</span>
                                <span class="meta-value">${escapeHtml(product.stok)} pcs</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Modal:</span>
                                <span class="meta-value">Rp ${formatRupiah(product.modal)}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Status:</span>
                                <span class="meta-value">
                                    <span style="color: ${product.status === 'Aktif' ? '#28a745' : '#dc3545'}; font-weight: 600;">
                                        ${escapeHtml(product.status)}
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Quantity Control di Modal -->
                        <div class="quantity-control-container">
                            <span class="quantity-control-label">Jumlah:</span>
                            <div class="quantity-control">
                                <button class="qty-btn minus" onclick="updateQuantityInModal(${productIndex}, -1)">-</button>
                                <input type="number" 
                                       class="qty-input" 
                                       id="modal-qty-${product.id_buku}"
                                       value="1" 
                                       min="1" 
                                       max="${product.stok}">
                                <button class="qty-btn plus" onclick="updateQuantityInModal(${productIndex}, 1)">+</button>
                            </div>
                            <div class="stock-info">
                                Stok tersedia: ${escapeHtml(product.stok)} pcs
                            </div>
                        </div>
                        
                        <!-- Form untuk tambah ke keranjang dari modal -->
                        <form method="POST" action="" id="modalCartForm">
                            <input type="hidden" name="id_buku" value="${product.id_buku}">
                            <input type="hidden" name="quantity" id="modal-form-qty-${product.id_buku}" value="1">
                            <div class="modal-actions">
                                <button type="submit" name="add_to_cart" class="btn btn-success" onclick="return prepareModalCartForm(${product.id_buku}, ${productIndex})">
                                    <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                </button>
                                <button type="button" class="btn btn-outline" onclick="closeProductModal()">
                                    <i class="fas fa-times"></i> Tutup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            // Show modal
            document.getElementById('productModalOverlay').style.display = 'block';
            document.getElementById('productModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeProductModal() {
            document.getElementById('productModalOverlay').style.display = 'none';
            document.getElementById('productModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        }

        // Close modal when clicking overlay
        document.getElementById('productModalOverlay').addEventListener('click', closeProductModal);

        // Prevent modal close when clicking inside modal
        document.getElementById('productModal').addEventListener('click', function(event) {
            event.stopPropagation();
        });

        // Update quantity dengan button di halaman produk
        function updateQuantity(index, change) {
            const product = allProducts[index];
            const qtyInput = document.getElementById(`qty-${product.id_buku}`);
            let currentQty = parseInt(qtyInput.value) || 1;
            let newQty = currentQty + change;
            
            if (newQty < 1) {
                newQty = 1;
            }
            
            if (newQty > product.stok) {
                alert(`Jumlah melebihi stok tersedia. Stok: ${product.stok}`);
                newQty = product.stok;
            }
            
            qtyInput.value = newQty;
        }

        // Update quantity di modal
        function updateQuantityInModal(index, change) {
            const product = allProducts[index];
            const qtyInput = document.getElementById(`modal-qty-${product.id_buku}`);
            let currentQty = parseInt(qtyInput.value) || 1;
            let newQty = currentQty + change;
            
            if (newQty < 1) {
                newQty = 1;
            }
            
            if (newQty > product.stok) {
                alert(`Jumlah melebihi stok tersedia. Stok: ${product.stok}`);
                newQty = product.stok;
            }
            
            qtyInput.value = newQty;
        }

        // Validasi input quantity
        function validateQuantity(index, value) {
            const product = allProducts[index];
            let newQty = parseInt(value);
            
            if (isNaN(newQty) || newQty < 1) {
                alert('Jumlah minimal 1');
                document.getElementById(`qty-${product.id_buku}`).value = 1;
                return;
            }
            
            if (newQty > product.stok) {
                alert(`Jumlah melebihi stok tersedia. Stok: ${product.stok}`);
                document.getElementById(`qty-${product.id_buku}`).value = product.stok;
                return;
            }
        }

        // Persiapkan form cart sebelum submit (untuk tombol di halaman produk)
        function prepareCartForm(productId, index) {
            const product = allProducts[index];
            const qtyInput = document.getElementById(`qty-${productId}`);
            const formQtyInput = document.getElementById(`form-qty-${productId}`);
            let quantity = parseInt(qtyInput.value) || 1;
            
            if (quantity > product.stok) {
                alert(`Jumlah melebihi stok tersedia. Stok: ${product.stok}`);
                qtyInput.value = product.stok;
                return false;
            }
            
            formQtyInput.value = quantity;
            return true;
        }

        // Persiapkan form cart dari modal
        function prepareModalCartForm(productId, index) {
            const product = allProducts[index];
            const qtyInput = document.getElementById(`modal-qty-${productId}`);
            let quantity = parseInt(qtyInput.value) || 1;
            
            if (quantity > product.stok) {
                alert(`Jumlah melebihi stok tersedia. Stok: ${product.stok}`);
                qtyInput.value = product.stok;
                return false;
            }
            
            document.getElementById(`modal-form-qty-${productId}`).value = quantity;
            return true;
        }

        // Format number to Rupiah
        function formatRupiah(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Escape HTML untuk keamanan
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Product card hover effects
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Update search input from URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search');
            if (searchParam) {
                document.getElementById('searchInput').value = decodeURIComponent(searchParam);
            }
            
            // Handle responsive button text on load
            updateButtonText();
        });

        // Handle responsive button text on resize
        window.addEventListener('resize', updateButtonText);

        // Function to update button text based on screen size
        function updateButtonText() {
            const width = window.innerWidth;
            const btnTexts = document.querySelectorAll('.btn-text');
            
            if (width <= 360) {
                btnTexts.forEach(text => {
                    text.style.display = 'none';
                });
            } else {
                btnTexts.forEach(text => {
                    text.style.display = 'inline';
                });
            }
        }
    </script>
</body>
</html>