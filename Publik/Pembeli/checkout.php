<?php
session_start();
include "../../Src/config.php";

// Cek apakah pembeli sudah login
if (!isset($_SESSION['email_pembeli'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

// Ambil data pembeli dari session
$email_pembeli = $_SESSION['email_pembeli'];
$data_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email_pembeli'")->fetch_assoc();

// Ambil data keranjang dengan GROUP BY penjual
$keranjang_query = $conn->query("
    SELECT 
        k.*, 
        pb.judul_buku, 
        pb.harga_buku, 
        pb.stok, 
        pb.email_penjual, 
        pb.nama_penjual,
        pb.foto as foto_produk, 
        pb.kategori_buku,
        pj.no_rekening,
        pj.debit
    FROM keranjang k
    JOIN produk_buku pb ON k.id_buku = pb.id_buku
    JOIN penjual pj ON pb.email_penjual = pj.email_penjual
    WHERE k.email_pembeli = '$email_pembeli' AND pb.status = 'Aktif'
");

// Hitung total keranjang dan kelompokkan berdasarkan penjual
$total_items = $keranjang_query->num_rows;
$total_harga = 0;
$cart_items = [];
$penjual_groups = [];

while ($item = $keranjang_query->fetch_assoc()) {
    $subtotal = $item['harga'] * $item['qty'];
    $total_harga += $subtotal;
    $item['subtotal'] = $subtotal;
    $cart_items[] = $item;
    
    // Kelompokkan berdasarkan penjual
    $email_penjual = $item['email_penjual'];
    if (!isset($penjual_groups[$email_penjual])) {
        $penjual_groups[$email_penjual] = [
            'nama_penjual' => $item['nama_penjual'],
            'email_penjual' => $email_penjual,
            'no_rekening' => $item['no_rekening'],
            'debit' => $item['debit'],
            'items' => [],
            'subtotal' => 0,
            'bukti_field_name' => 'bukti_pembayaran_' . md5($email_penjual),
            'kode_pesanan' => 'ORD' . date('YmdHis') . rand(100, 999) . substr(md5($email_penjual), 0, 6)
        ];
    }
    
    $penjual_groups[$email_penjual]['items'][] = $item;
    $penjual_groups[$email_penjual]['subtotal'] += $subtotal;
}

// PROSES CHECKOUT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $metode_bayar = 'Transfer'; // Hanya transfer bank
    
    // Validasi
    if (empty($cart_items)) {
        $error = 'Keranjang kosong';
    } else {
        // Cek apakah semua bukti pembayaran untuk setiap penjual diupload
        $all_files_uploaded = true;
        $uploaded_files = [];
        $errors_upload = [];
        $success_count = 0;
        $generated_kode_pesanan = []; // Simpan semua kode pesanan yang dibuat
        
        foreach ($penjual_groups as $email_penjual => $penjual) {
            $bukti_field_name = $penjual['bukti_field_name'];
            
            if (isset($_FILES[$bukti_field_name]) && $_FILES[$bukti_field_name]['error'] == 0) {
                $bukti_pembayaran = $_FILES[$bukti_field_name];
                
                // Validasi file
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($bukti_pembayaran['type'], $allowed_types)) {
                    $errors_upload[$email_penjual] = 'Format file tidak didukung. Hanya JPG, JPEG, PNG, GIF, PDF';
                    $all_files_uploaded = false;
                } elseif ($bukti_pembayaran['size'] > $max_size) {
                    $errors_upload[$email_penjual] = 'Ukuran file terlalu besar. Maksimal 5MB';
                    $all_files_uploaded = false;
                } else {
                    // Generate nama file unik
                    $file_ext = pathinfo($bukti_pembayaran['name'], PATHINFO_EXTENSION);
                    $file_name = 'bukti_' . time() . '_' . uniqid() . '_' . substr(md5($email_penjual), 0, 8) . '.' . $file_ext;
                    $upload_path = '../../Src/uploads/bukti_pembayaran/';
                    
                    // Buat folder jika belum ada
                    if (!file_exists($upload_path)) {
                        mkdir($upload_path, 0777, true);
                    }
                    
                    // Pindahkan file
                    if (move_uploaded_file($bukti_pembayaran['tmp_name'], $upload_path . $file_name)) {
                        $uploaded_files[$email_penjual] = $file_name;
                        $success_count++;
                    } else {
                        $errors_upload[$email_penjual] = 'Gagal mengupload bukti pembayaran';
                        $all_files_uploaded = false;
                    }
                }
            } else {
                $errors_upload[$email_penjual] = 'Harap upload bukti pembayaran untuk penjual: ' . $penjual['nama_penjual'];
                $all_files_uploaded = false;
            }
        }
        
        if ($all_files_uploaded && $success_count == count($penjual_groups)) {
            // Mulai transaksi
            $conn->begin_transaction();
            
            try {
                // Simpan setiap item di keranjang ke tabel pesanan per penjual
                foreach ($penjual_groups as $email_penjual => $penjual) {
                    // Generate kode pesanan unik per penjual
                    $kode_pesanan = $penjual['kode_pesanan'];
                    $generated_kode_pesanan[] = $kode_pesanan;
                    
                    // Get bukti pembayaran untuk penjual ini
                    $bukti_file = $uploaded_files[$email_penjual];
                    
                    // Insert semua produk dari penjual ini dalam satu kode pesanan
                    foreach ($penjual['items'] as $item) {
                        // Hitung total harga untuk item ini
                        $item_total = $item['harga'] * $item['qty'];
                        
                        // Insert ke tabel pesanan
                        $insert_query = "INSERT INTO pesanan (
                            kode_pesanan, id_buku, judul_buku, harga_satuan, qty, total_harga, 
                            metode_bayar, email_penjual, nama_penjual, alamat_pembeli, 
                            email_pembeli, nama_pembeli, bukti_pembayaran, tanggal_pesanan
                        ) VALUES (
                            '$kode_pesanan',
                            '" . $item['id_buku'] . "',
                            '" . $conn->real_escape_string($item['judul_buku']) . "',
                            '" . $item['harga'] . "',
                            '" . $item['qty'] . "',
                            '" . $item_total . "',
                            '$metode_bayar',
                            '" . $conn->real_escape_string($item['email_penjual']) . "',
                            '" . $conn->real_escape_string($item['nama_penjual']) . "',
                            '" . $conn->real_escape_string($data_pembeli['alamat_pembeli']) . "',
                            '$email_pembeli',
                            '" . $conn->real_escape_string($data_pembeli['nama_pembeli']) . "',
                            '$bukti_file',
                            NOW()
                        )";
                        
                        if (!$conn->query($insert_query)) {
                            throw new Exception("Gagal menyimpan pesanan untuk penjual: " . $penjual['nama_penjual']);
                        }
                    }
                    
                    // Log aktivitas checkout per penjual
                    $log_message = "CHECKOUT_SUCCESS - Pembeli: $email_pembeli, Penjual: $email_penjual, Kode Pesanan: $kode_pesanan";
                    error_log($log_message);
                }
                
                // Hapus semua item dari keranjang setelah checkout berhasil
                $delete_cart = "DELETE FROM keranjang WHERE email_pembeli = '$email_pembeli'";
                $conn->query($delete_cart);
                
                // Commit transaksi
                $conn->commit();
                
                // Simpan semua kode pesanan ke session untuk invoice
                $_SESSION['checkout_success'] = true;
                $_SESSION['generated_kode_pesanan'] = $generated_kode_pesanan;
                $_SESSION['total_pesanan'] = count($generated_kode_pesanan);
                
                // Redirect ke halaman invoice dengan kode pesanan pertama
                header("Location: invoice.php?kode_pesanan=" . urlencode($generated_kode_pesanan[0]) . "&multi=" . (count($generated_kode_pesanan) > 1 ? 'true' : 'false'));
                exit();
                
            } catch (Exception $e) {
                // Rollback transaksi jika ada error
                $conn->rollback();
                
                // Hapus file yang sudah diupload jika ada error
                foreach ($uploaded_files as $file_name) {
                    $file_path = '../../Src/uploads/bukti_pembayaran/' . $file_name;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                $error = 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage();
                error_log("CHECKOUT_ERROR - Pembeli: $email_pembeli, Error: " . $e->getMessage());
            }
        } else {
            // Tampilkan error untuk setiap penjual
            $error = '<strong>Terjadi kesalahan dalam upload bukti pembayaran:</strong><br>';
            foreach ($errors_upload as $email_penjual => $err_msg) {
                $penjual_nama = $penjual_groups[$email_penjual]['nama_penjual'];
                $error .= "• <strong>{$penjual_nama}</strong>: {$err_msg}<br>";
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
    <title>BukuBook - Checkout</title>
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

        /* Checkout Container */
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 992px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }

        /* Order Summary */
        .order-summary {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .order-items {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        /* Group by Seller */
        .seller-group {
            margin-bottom: 25px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            overflow: hidden;
        }

        .seller-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seller-name {
            font-weight: 600;
            color: var(--primary);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .seller-item-count {
            background-color: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .seller-bank-info {
            background-color: #e9ecef;
            padding: 15px;
            margin: 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .bank-info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }

        .bank-label {
            color: var(--gray);
            font-weight: 500;
        }

        .bank-value {
            font-weight: 600;
            color: var(--dark);
        }

        .seller-items {
            padding: 15px;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            background-color: #f8f9fa;
            margin-right: 15px;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .item-meta {
            font-size: 14px;
            color: var(--gray);
            display: flex;
            justify-content: space-between;
        }

        .item-price {
            font-size: 16px;
            font-weight: 600;
            color: var(--success);
            margin-top: 10px;
        }

        .seller-subtotal {
            text-align: right;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px dashed #dee2e6;
        }

        .subtotal-label {
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }

        .subtotal-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--success);
        }

        .order-total {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .total-label {
            font-weight: 600;
            color: var(--dark);
        }

        .total-value {
            font-weight: 600;
            color: var(--dark);
        }

        .grand-total {
            font-size: 20px;
            color: var(--success);
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
            padding-top: 10px;
        }

        /* Payment Form */
        .payment-form {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .form-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background-color: white;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .readonly {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        /* Metode Pembayaran */
        .payment-method-single {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-method-icon {
            font-size: 32px;
            color: var(--primary);
        }

        .payment-method-info {
            margin: 0;
            font-size: 18px;
            color: var(--dark);
        }

        .payment-method-info p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: var(--gray);
        }

        /* Instruksi Pembayaran */
        .payment-instructions {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }

        .instructions-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instructions-content {
            font-size: 14px;
            color: var(--gray);
            line-height: 1.6;
        }

        .instruction-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 8px;
        }

        .step-number {
            background-color: var(--primary);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }

        /* Bukti Pembayaran per Penjual */
        .seller-payment-section {
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .seller-payment-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .seller-payment-title {
            font-weight: 600;
            color: var(--primary);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .seller-payment-content {
            padding: 20px;
        }

        .seller-bank-detail {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .seller-total {
            display: flex;
            justify-content: space-between;
            background-color: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 600;
            border: 1px solid #dee2e6;
        }

        .seller-total-label {
            color: var(--dark);
        }

        .seller-total-value {
            color: var(--success);
        }

        /* File Upload */
        .file-upload {
            position: relative;
            border: 2px dashed var(--light-gray);
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }

        .file-upload input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 48px;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .upload-text {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .upload-hint {
            font-size: 14px;
            color: var(--gray);
        }

        .preview-container {
            text-align: center;
            margin-top: 20px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .file-name {
            margin-top: 10px;
            font-size: 14px;
            color: var(--primary);
            font-weight: 600;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            text-decoration: none;
            flex: 1;
        }

        .btn-outline {
            background-color: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
            border: 2px solid var(--success);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-success:disabled {
            background-color: #6c757d;
            border-color: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Error Message */
        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 18px;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 40px;
            color: var(--gray);
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            grid-column: 1 / -1;
        }

        .empty-cart i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-cart h4 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .empty-cart p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        /* Multi Seller Warning */
        .multi-seller-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .multi-seller-warning i {
            font-size: 20px;
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

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
        }

        .menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div style="color: white; margin-left: 15px; font-size: 16px;">
            Memproses pesanan Anda...
        </div>
    </div>

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
                    <input type="text" id="searchInput" placeholder="Cari buku, penulis, atau kategori...">
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
            <h1 class="page-title">Checkout Pembayaran</h1>
            
            <?php if(count($penjual_groups) > 1): ?>
            <div class="multi-seller-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Perhatian!</strong> Anda membeli dari <?php echo count($penjual_groups); ?> penjual berbeda. 
                    Harap transfer ke masing-masing penjual sesuai jumlah yang tertera dan upload bukti transfer untuk setiap penjual.
                </div>
            </div>
            <?php endif; ?>

            <p class="welcome-message">
                Lengkapi informasi pembayaran untuk menyelesaikan pesanan Anda. 
                Pastikan Anda sudah transfer ke masing-masing penjual sesuai dengan informasi bank yang tercantum.
            </p>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($total_items > 0): ?>
                <div class="checkout-container">
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h3 class="summary-title">
                            <i class="fas fa-shopping-cart"></i>
                            Ringkasan Pesanan
                            <span style="float: right; font-size: 14px; color: var(--gray);">
                                <?php echo $total_items; ?> item
                            </span>
                        </h3>
                        
                        <div class="order-items">
                            <?php foreach ($penjual_groups as $email_penjual => $penjual): ?>
                                <div class="seller-group" id="seller-group-<?php echo md5($email_penjual); ?>">
                                    <div class="seller-header">
                                        <div class="seller-name">
                                            <i class="fas fa-store"></i>
                                            <?php echo htmlspecialchars($penjual['nama_penjual']); ?>
                                            <span class="seller-item-count">
                                                <?php echo count($penjual['items']); ?>
                                            </span>
                                        </div>
                                        <div style="color: var(--primary); font-size: 14px; font-weight: 600;">
                                            Kode: <?php echo $penjual['kode_pesanan']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="seller-bank-info">
                                        <div class="bank-info-row">
                                            <span class="bank-label">Bank:</span>
                                            <span class="bank-value"><?php echo htmlspecialchars($penjual['debit']); ?></span>
                                        </div>
                                        <div class="bank-info-row">
                                            <span class="bank-label">No. Rekening:</span>
                                            <span class="bank-value"><?php echo htmlspecialchars($penjual['no_rekening']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="seller-items">
                                        <?php foreach ($penjual['items'] as $index => $item): 
                                            $foto_produk = !empty($item['foto_produk']) ? "../../Src/uploads/produk/" . $item['foto_produk'] : "https://via.placeholder.com/60x80/cccccc/666666?text=Buku";
                                        ?>
                                            <div class="order-item">
                                                <img src="<?php echo $foto_produk; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['judul_buku']); ?>"
                                                     class="item-image"
                                                     onerror="this.src='https://via.placeholder.com/60x80/cccccc/666666?text=Buku'">
                                                <div class="item-details">
                                                    <h4 class="item-title"><?php echo htmlspecialchars($item['judul_buku']); ?></h4>
                                                    <div class="item-meta">
                                                        <span>Qty: <?php echo $item['qty']; ?> × Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></span>
                                                        <span>Kategori: <?php echo htmlspecialchars($item['kategori_buku']); ?></span>
                                                    </div>
                                                    <div class="item-price">
                                                        Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="seller-subtotal">
                                            <span class="subtotal-label">Subtotal untuk <?php echo htmlspecialchars($penjual['nama_penjual']); ?>:</span>
                                            <div class="subtotal-value">
                                                Rp <?php echo number_format($penjual['subtotal'], 0, ',', '.'); ?>
                                            </div>
                                            <small style="color: var(--gray); font-size: 12px;">
                                                *Transfer tepat sesuai jumlah ini
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-total">
                            <div class="total-row">
                                <span class="total-label">Total Penjual:</span>
                                <span class="total-value"><?php echo count($penjual_groups); ?> penjual</span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Total Items:</span>
                                <span class="total-value"><?php echo $total_items; ?> item</span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Total Semua Penjual:</span>
                                <span class="total-value">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span class="total-label">Total Pembayaran:</span>
                                <span class="total-value">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="payment-form">
                        <h3 class="form-title">
                            <i class="fas fa-credit-card"></i>
                            Informasi Pembayaran
                        </h3>
                        
                        <form method="POST" action="" enctype="multipart/form-data" id="checkoutForm">
                            <input type="hidden" name="metode_bayar" value="Transfer">
                            
                            <!-- Informasi Pembeli -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Nama Pembeli
                                </label>
                                <input type="text" class="form-control readonly" 
                                       value="<?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Pembeli
                                </label>
                                <input type="email" class="form-control readonly" 
                                       value="<?php echo htmlspecialchars($email_pembeli); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Alamat Pengiriman
                                </label>
                                <textarea class="form-control readonly" rows="3" readonly><?php echo htmlspecialchars($data_pembeli['alamat_pembeli']); ?></textarea>
                            </div>

                            <!-- Metode Pembayaran -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-money-check-alt"></i>
                                    Metode Pembayaran
                                </label>
                                <div class="payment-method-single">
                                    <div class="payment-method-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="payment-method-info">
                                        <h4>Transfer Bank</h4>
                                        <p>Transfer ke rekening masing-masing penjual</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Instruksi Pembayaran -->
                            <div class="payment-instructions">
                                <div class="instructions-title">
                                    <i class="fas fa-info-circle"></i>
                                    Instruksi Pembayaran
                                </div>
                                <div class="instructions-content">
                                    <div class="instruction-step">
                                        <div class="step-number">1</div>
                                        <div>Transfer sesuai jumlah yang tertera untuk masing-masing penjual</div>
                                    </div>
                                    <div class="instruction-step">
                                        <div class="step-number">2</div>
                                        <div>Pastikan Anda transfer ke nomor rekening yang benar</div>
                                    </div>
                                    <div class="instruction-step">
                                        <div class="step-number">3</div>
                                        <div>Upload bukti transfer untuk setiap penjual di bawah ini</div>
                                    </div>
                                    <div class="instruction-step">
                                        <div class="step-number">4</div>
                                        <div>Setelah upload, pesanan Anda akan diproses oleh penjual</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bukti Pembayaran untuk Masing-Masing Penjual -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-file-upload"></i>
                                    Upload Bukti Pembayaran per Penjual
                                </label>
                                
                                <?php foreach ($penjual_groups as $email_penjual => $penjual): 
                                    $bukti_field_name = $penjual['bukti_field_name'];
                                    $penjual_id = md5($email_penjual);
                                ?>
                                    <div class="seller-payment-section" id="payment-section-<?php echo $penjual_id; ?>">
                                        <div class="seller-payment-header">
                                            <div class="seller-payment-title">
                                                <i class="fas fa-user-tie"></i>
                                                <?php echo htmlspecialchars($penjual['nama_penjual']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="seller-payment-content">
                                            <div class="seller-bank-detail">
                                                <div style="margin-bottom: 10px; color: var(--primary);">
                                                    <strong><i class="fas fa-university"></i> Informasi Bank:</strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dashed #dee2e6;">
                                                    <span>Bank:</span>
                                                    <strong><?php echo htmlspecialchars($penjual['debit']); ?></strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                                                    <span>No. Rekening:</span>
                                                    <strong style="color: var(--primary);"><?php echo htmlspecialchars($penjual['no_rekening']); ?></strong>
                                                </div>
                                            </div>
                                            
                                            <div class="seller-total">
                                                <span class="seller-total-label">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    Jumlah Transfer:
                                                </span>
                                                <span class="seller-total-value">
                                                    <i class="fas fa-coins"></i>
                                                    Rp <?php echo number_format($penjual['subtotal'], 0, ',', '.'); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="form-label">
                                                <i class="fas fa-receipt"></i>
                                                Bukti Pembayaran untuk <?php echo htmlspecialchars($penjual['nama_penjual']); ?>
                                            </div>
                                            <div class="file-upload" id="fileUploadArea_<?php echo $penjual_id; ?>">
                                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                                <div class="upload-text" id="uploadText_<?php echo $penjual_id; ?>">Klik untuk upload bukti pembayaran</div>
                                                <div class="upload-hint" id="uploadHint_<?php echo $penjual_id; ?>">Format: JPG, PNG, GIF, PDF (Maks. 5MB)</div>
                                                <input type="file" name="<?php echo $bukti_field_name; ?>" 
                                                       accept="image/*,.pdf" 
                                                       required
                                                       data-penjual-id="<?php echo $penjual_id; ?>"
                                                       id="fileInput_<?php echo $penjual_id; ?>">
                                            </div>
                                            <div class="preview-container" id="previewContainer_<?php echo $penjual_id; ?>">
                                                <img id="previewImage_<?php echo $penjual_id; ?>" 
                                                     class="preview-image" 
                                                     src="" 
                                                     alt="Preview">
                                                <div id="fileName_<?php echo $penjual_id; ?>" class="file-name"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Progress Indicator -->
                            <div id="uploadProgress" style="display: none;">
                                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>Upload Progress:</span>
                                        <span id="progressCount">0/<?php echo count($penjual_groups); ?></span>
                                    </div>
                                    <div style="height: 10px; background-color: #e9ecef; border-radius: 5px; overflow: hidden;">
                                        <div id="progressBar" style="height: 100%; background-color: var(--primary); width: 0%; transition: width 0.3s;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <a href="keranjang.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Keranjang
                                </a>
                                <button type="submit" name="checkout" class="btn btn-success" id="submitButton">
                                    <i class="fas fa-check-circle"></i> Selesaikan Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Keranjang Belanja Kosong</h4>
                    <p>Tambahkan produk ke keranjang terlebih dahulu sebelum checkout.</p>
                    <a href="produk.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Mulai Belanja
                    </a>
                </div>
            <?php endif; ?>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <a href="keranjang.php" class="breadcrumb-item">Keranjang</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Checkout</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = 'produk.php?search=' + encodeURIComponent(searchTerm);
                }
            }
        });

        // Show loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Preview image sebelum upload untuk semua file input
        const fileInputs = document.querySelectorAll('input[type="file"][name^="bukti_pembayaran_"]');
        const totalPenjual = <?php echo count($penjual_groups); ?>;
        
        // Track uploaded files per penjual
        const uploadedFiles = {};
        
        fileInputs.forEach(fileInput => {
            const penjualId = fileInput.getAttribute('data-penjual-id');
            const previewImage = document.getElementById('previewImage_' + penjualId);
            const fileName = document.getElementById('fileName_' + penjualId);
            const fileUploadArea = document.getElementById('fileUploadArea_' + penjualId);
            const uploadText = document.getElementById('uploadText_' + penjualId);
            const uploadHint = document.getElementById('uploadHint_' + penjualId);
            
            // Initialize tracking
            uploadedFiles[penjualId] = false;
            
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Cek tipe file
                    if (!file.type.match('image.*') && file.type !== 'application/pdf') {
                        alert('Hanya file gambar dan PDF yang diperbolehkan');
                        this.value = '';
                        if (previewImage) previewImage.style.display = 'none';
                        if (fileName) fileName.textContent = '';
                        uploadedFiles[penjualId] = false;
                        updateUploadProgress();
                        return;
                    }
                    
                    // Cek ukuran file (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar. Maksimal 5MB');
                        this.value = '';
                        if (previewImage) previewImage.style.display = 'none';
                        if (fileName) fileName.textContent = '';
                        uploadedFiles[penjualId] = false;
                        updateUploadProgress();
                        return;
                    }
                    
                    // Update tracking
                    uploadedFiles[penjualId] = true;
                    
                    // Tampilkan preview untuk gambar
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            if (previewImage) {
                                previewImage.src = e.target.result;
                                previewImage.style.display = 'block';
                                previewImage.style.maxWidth = '200px';
                                previewImage.style.maxHeight = '200px';
                            }
                        }
                        
                        reader.readAsDataURL(file);
                    } else {
                        // Untuk PDF, tampilkan icon PDF
                        if (previewImage) {
                            previewImage.src = 'https://cdn-icons-png.flaticon.com/512/337/337946.png';
                            previewImage.style.display = 'block';
                            previewImage.style.maxWidth = '100px';
                            previewImage.style.maxHeight = '100px';
                            previewImage.style.padding = '20px';
                            previewImage.style.backgroundColor = '#f8f9fa';
                        }
                    }
                    
                    // Update text di upload area
                    if (uploadText) uploadText.textContent = 'File terpilih: ' + file.name;
                    if (uploadHint) uploadHint.textContent = 
                        'Ukuran: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB • Format: ' + file.type;
                    if (fileName) {
                        fileName.textContent = file.name;
                        fileName.style.display = 'block';
                    }
                    
                    // Update progress
                    updateUploadProgress();
                    
                    // Highlight file upload area
                    if (fileUploadArea) {
                        fileUploadArea.style.borderColor = 'var(--success)';
                        fileUploadArea.style.backgroundColor = 'rgba(40, 167, 69, 0.05)';
                    }
                    
                    // Highlight seller group
                    const sellerGroup = document.getElementById('seller-group-' + penjualId);
                    if (sellerGroup) {
                        sellerGroup.style.borderColor = 'var(--success)';
                    }
                } else {
                    uploadedFiles[penjualId] = false;
                    if (previewImage) previewImage.style.display = 'none';
                    if (fileName) fileName.textContent = '';
                    updateUploadProgress();
                    
                    // Reset highlight
                    if (fileUploadArea) {
                        fileUploadArea.style.borderColor = 'var(--light-gray)';
                        fileUploadArea.style.backgroundColor = '';
                    }
                }
            });
        });

        // Update upload progress
        function updateUploadProgress() {
            const uploadedCount = Object.values(uploadedFiles).filter(Boolean).length;
            const progressBar = document.getElementById('progressBar');
            const progressCount = document.getElementById('progressCount');
            const uploadProgress = document.getElementById('uploadProgress');
            const submitButton = document.getElementById('submitButton');
            
            if (progressBar && progressCount && uploadProgress) {
                const percentage = (uploadedCount / totalPenjual) * 100;
                progressBar.style.width = percentage + '%';
                progressCount.textContent = uploadedCount + '/' + totalPenjual;
                
                if (uploadedCount > 0) {
                    uploadProgress.style.display = 'block';
                } else {
                    uploadProgress.style.display = 'none';
                }
                
                // Update button text
                if (submitButton) {
                    if (uploadedCount === totalPenjual) {
                        submitButton.innerHTML = '<i class="fas fa-check-circle"></i> Semua Bukti Terupload - Lanjutkan';
                        submitButton.disabled = false;
                    } else {
                        submitButton.innerHTML = `<i class="fas fa-upload"></i> Upload ${uploadedCount}/${totalPenjual} Bukti`;
                        submitButton.disabled = false;
                    }
                }
            }
        }

        // Drag and drop untuk file upload
        const fileUploadAreas = document.querySelectorAll('.file-upload');
        
        fileUploadAreas.forEach(area => {
            const fileInput = area.querySelector('input[type="file"]');
            const penjualId = fileInput ? fileInput.getAttribute('data-penjual-id') : null;
            
            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--primary)';
                this.style.backgroundColor = 'rgba(67, 97, 238, 0.05)';
            });
            
            area.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--light-gray)';
                this.style.backgroundColor = '';
            });
            
            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--light-gray)';
                this.style.backgroundColor = '';
                
                const files = e.dataTransfer.files;
                if (files.length > 0 && fileInput) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        });

        // Validasi form sebelum submit
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const fileInputs = document.querySelectorAll('input[type="file"][name^="bukti_pembayaran_"]');
            
            // Cek semua file sudah diupload
            let allFilesUploaded = true;
            fileInputs.forEach(input => {
                if (!input.files || input.files.length === 0) {
                    allFilesUploaded = false;
                    
                    // Highlight yang belum diupload
                    const penjualId = input.getAttribute('data-penjual-id');
                    const sellerGroup = document.getElementById('seller-group-' + penjualId);
                    const paymentSection = document.getElementById('payment-section-' + penjualId);
                    
                    if (sellerGroup) {
                        sellerGroup.style.borderColor = 'var(--danger)';
                        sellerGroup.style.animation = 'none';
                        sellerGroup.offsetHeight; // Trigger reflow
                        sellerGroup.style.animation = 'flash 0.5s 3';
                    }
                    
                    if (paymentSection) {
                        paymentSection.style.borderColor = 'var(--danger)';
                    }
                }
            });
            
            if (!allFilesUploaded) {
                e.preventDefault();
                
                // Scroll ke penjual pertama yang belum diupload
                const missingUploads = Array.from(fileInputs).filter(input => !input.files || input.files.length === 0);
                if (missingUploads.length > 0) {
                    const firstMissing = missingUploads[0];
                    const penjualId = firstMissing.getAttribute('data-penjual-id');
                    const paymentSection = document.getElementById('payment-section-' + penjualId);
                    
                    if (paymentSection) {
                        paymentSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                
                const penjualCount = totalPenjual;
                const uploadedCount = Object.values(uploadedFiles).filter(Boolean).length;
                
                alert(`Harap upload bukti pembayaran untuk semua penjual!\n\nStatus: ${uploadedCount}/${penjualCount} penjual sudah diupload.`);
                return false;
            }
            
            // Konfirmasi sebelum submit
            const penjualCount = totalPenjual;
            let confirmMessage;
            
            if (penjualCount > 1) {
                confirmMessage = `PESANAN DARI ${penjualCount} PENJUAL BERBEDA!\n\n` +
                               `Pastikan Anda sudah:\n` +
                               `1. Transfer ke ${penjualCount} penjual berbeda\n` +
                               `2. Jumlah transfer sesuai dengan yang tertera\n` +
                               `3. Bukti transfer sudah benar untuk semua penjual\n\n` +
                               `Apakah Anda yakin ingin melanjutkan?`;
            } else {
                confirmMessage = `Apakah Anda yakin ingin menyelesaikan pembayaran?`;
            }
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading
            showLoading();
        });

        // Add flash animation for highlighting
        const style = document.createElement('style');
        style.textContent = `
            @keyframes flash {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
        `;
        document.head.appendChild(style);

        // Initialize upload progress
        document.addEventListener('DOMContentLoaded', function() {
            updateUploadProgress();
            
            // Add copy bank account functionality
            document.querySelectorAll('.seller-bank-detail').forEach(bankDetail => {
                bankDetail.style.cursor = 'pointer';
                bankDetail.title = 'Klik untuk copy nomor rekening';
                bankDetail.addEventListener('click', function() {
                    const bankText = this.innerText;
                    const rekeningMatch = bankText.match(/Rekening:\s*([\d\-]+)/i);
                    
                    if (rekeningMatch && rekeningMatch[1]) {
                        const rekening = rekeningMatch[1];
                        navigator.clipboard.writeText(rekening)
                            .then(() => {
                                const originalColor = this.style.backgroundColor;
                                this.style.backgroundColor = 'var(--success)';
                                this.style.color = 'white';
                                
                                setTimeout(() => {
                                    this.style.backgroundColor = originalColor;
                                    this.style.color = '';
                                }, 1000);
                            })
                            .catch(err => {
                                console.error('Gagal copy: ', err);
                            });
                    }
                });
            });
        });
    </script>
</body>
</html>