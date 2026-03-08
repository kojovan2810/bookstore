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

// Ambil data kategori dari tabel kategori
$kategori_list = $conn->query("SELECT * FROM kategori_produk ORDER BY nama_kategori");

// Handle tambah produk dengan cek duplikat
if (isset($_POST['tambah_produk'])) {
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $modal = mysqli_real_escape_string($conn, $_POST['modal']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    $nama_penjual = $data_penjual['nama_penjual'];
    
    // Validasi input
    if (empty($judul)) {
        echo "<script>alert('Judul produk tidak boleh kosong!');</script>";
    } elseif (!is_numeric($harga) || $harga <= 0) {
        echo "<script>alert('Harga harus berupa angka positif!');</script>";
    } elseif (!is_numeric($modal) || $modal < 0) {
        echo "<script>alert('Modal harus berupa angka non-negatif!');</script>";
    } elseif (!is_numeric($stok) || $stok < 0) {
        echo "<script>alert('Stok harus berupa angka non-negatif!');</script>";
    } else {
        // Cek apakah produk dengan judul yang sama sudah ada (case-insensitive, untuk penjual yang sama)
        $cek = $conn->query("SELECT * FROM produk_buku WHERE LOWER(judul_buku) = LOWER('$judul') AND email_penjual = '$email_penjual'");
        
        if ($cek->num_rows > 0) {
            echo "<script>
                    alert('Produk dengan judul \"$judul\" sudah ada dalam daftar produk Anda!');
                    window.history.back();
                  </script>";
        } else {
            // Handle upload foto
            $foto = '';
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $target_dir = "../../Src/uploads/produk/";
                
                // Create directory if not exists
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['foto']['name']);
                $target_file = $target_dir . $file_name;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Check if image file is actual image
                $check = getimagesize($_FILES['foto']['tmp_name']);
                if ($check !== false) {
                    // Check file size (max 5MB)
                    if ($_FILES['foto']['size'] > 5000000) {
                        echo "<script>alert('Maaf, ukuran file terlalu besar (max 5MB)');</script>";
                    } else {
                        // Allow certain file formats
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        if (in_array($imageFileType, $allowed_types)) {
                            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                                $foto = $file_name;
                            } else {
                                echo "<script>alert('Maaf, terjadi kesalahan saat mengupload file');</script>";
                            }
                        } else {
                            echo "<script>alert('Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan');</script>";
                        }
                    }
                } else {
                    echo "<script>alert('File yang diupload bukan gambar');</script>";
                }
            }
            
            // Insert produk baru
            $query = "INSERT INTO produk_buku (kategori_buku, judul_buku, harga_buku, modal, stok, email_penjual, nama_penjual, foto) 
                      VALUES ('$kategori', '$judul', '$harga', '$modal', '$stok', '$email_penjual', '$nama_penjual', '$foto')";
            
            if ($conn->query($query)) {
                echo "<script>
                        alert('Produk berhasil ditambahkan!');
                        window.location.href = 'produk.php';
                      </script>";
            } else {
                echo "<script>alert('Gagal menambahkan produk!');</script>";
            }
        }
    }
}

// Handle edit produk dengan cek duplikat
if (isset($_POST['edit_produk'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id_buku']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $modal = mysqli_real_escape_string($conn, $_POST['modal']);
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    
    // Validasi input
    if (empty($judul)) {
        echo "<script>alert('Judul produk tidak boleh kosong!');</script>";
    } elseif (!is_numeric($harga) || $harga <= 0) {
        echo "<script>alert('Harga harus berupa angka positif!');</script>";
    } elseif (!is_numeric($modal) || $modal < 0) {
        echo "<script>alert('Modal harus berupa angka non-negatif!');</script>";
    } elseif (!is_numeric($stok) || $stok < 0) {
        echo "<script>alert('Stok harus berupa angka non-negatif!');</script>";
    } else {
        // Cek apakah ada pesanan aktif untuk produk ini
        $cek_pesanan = $conn->query("
            SELECT COUNT(*) as total 
            FROM pesanan 
            WHERE email_penjual = '$email_penjual' 
            AND judul_buku IN (SELECT judul_buku FROM produk_buku WHERE id_buku = '$id')
            AND (approve IS NULL OR approve = 'Disetujui')
        ")->fetch_assoc();
        
        if ($cek_pesanan['total'] > 0) {
            echo "<script>
                    alert('Tidak dapat mengedit produk karena masih ada pesanan aktif!');
                    window.location.href = 'produk.php';
                  </script>";
            exit();
        }
        
        // Cek apakah judul produk sudah ada (kecuali untuk produk yang sedang diedit)
        $cek_duplikat = $conn->query("
            SELECT * FROM produk_buku 
            WHERE LOWER(judul_buku) = LOWER('$judul') 
            AND email_penjual = '$email_penjual'
            AND id_buku != '$id'
        ");
        
        if ($cek_duplikat->num_rows > 0) {
            echo "<script>
                    alert('Produk dengan judul \"$judul\" sudah ada dalam daftar produk Anda!');
                    window.history.back();
                  </script>";
        } else {
            // Handle upload foto baru jika ada
            $foto_update = '';
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $target_dir = "../../Src/uploads/produk/";
                
                // Create directory if not exists
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['foto']['name']);
                $target_file = $target_dir . $file_name;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Check if image file is actual image
                $check = getimagesize($_FILES['foto']['tmp_name']);
                if ($check !== false) {
                    // Check file size (max 5MB)
                    if ($_FILES['foto']['size'] > 5000000) {
                        echo "<script>alert('Maaf, ukuran file terlalu besar (max 5MB)');</script>";
                    } else {
                        // Allow certain file formats
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        if (in_array($imageFileType, $allowed_types)) {
                            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                                $foto_update = $file_name;
                                
                                // Hapus foto lama jika ada
                                $old_foto = $conn->query("SELECT foto FROM produk_buku WHERE id_buku = '$id'")->fetch_assoc()['foto'];
                                if (!empty($old_foto) && file_exists("../../Src/uploads/produk/" . $old_foto)) {
                                    unlink("../../Src/uploads/produk/" . $old_foto);
                                }
                            } else {
                                echo "<script>alert('Maaf, terjadi kesalahan saat mengupload file');</script>";
                            }
                        } else {
                            echo "<script>alert('Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan');</script>";
                        }
                    }
                } else {
                    echo "<script>alert('File yang diupload bukan gambar');</script>";
                }
            }
            
            // Update produk dengan atau tanpa foto baru
            if (!empty($foto_update)) {
                $query = "UPDATE produk_buku SET 
                          kategori_buku = '$kategori',
                          judul_buku = '$judul',
                          harga_buku = '$harga',
                          modal = '$modal',
                          stok = '$stok',
                          foto = '$foto_update'
                          WHERE id_buku = '$id' AND email_penjual = '$email_penjual'";
            } else {
                $query = "UPDATE produk_buku SET 
                          kategori_buku = '$kategori',
                          judul_buku = '$judul',
                          harga_buku = '$harga',
                          modal = '$modal',
                          stok = '$stok'
                          WHERE id_buku = '$id' AND email_penjual = '$email_penjual'";
            }
            
            if ($conn->query($query)) {
                echo "<script>
                        alert('Produk berhasil diupdate!');
                        window.location.href = 'produk.php';
                      </script>";
            } else {
                echo "<script>alert('Gagal mengupdate produk!');</script>";
            }
        }
    }
}

// Handle hapus produk
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // 1. Cek kepemilikan produk
    $check_ownership = $conn->query("SELECT * FROM produk_buku WHERE id_buku = '$id' AND email_penjual = '$email_penjual'");
    
    if ($check_ownership->num_rows == 0) {
        echo "<script>
                alert('Anda tidak memiliki akses untuk menghapus produk ini!');
                window.location.href = 'produk.php';
              </script>";
        exit();
    }
    
    // 2. Ambil data produk untuk pesan
    $produk_data = $check_ownership->fetch_assoc();
    $judul_buku = htmlspecialchars($produk_data['judul_buku']);
    
    // 3. Cek apakah ada pesanan aktif
    $check_pesanan = $conn->query("
        SELECT COUNT(*) as total 
        FROM pesanan 
        WHERE email_penjual = '$email_penjual' 
        AND judul_buku = '$judul_buku'
        AND (approve IS NULL OR approve = 'Disetujui')
    ")->fetch_assoc();
    
    if ($check_pesanan['total'] > 0) {
        echo "<script>
                alert('TIDAK BISA DIHAPUS!\\n\\nProduk \"$judul_buku\" masih memiliki pesanan aktif.\\n\\nHapus hanya diperbolehkan jika:\\n1. Tidak ada pesanan sama sekali\\n2. Semua pesanan sudah Ditolak dan Refund');
                window.location.href = 'produk.php';
              </script>";
        exit();
    }
    
    // 4. Jika semua validasi lolos, hapus produk
    try {
        // Mulai transaction
        mysqli_begin_transaction($conn);
        
        // Hapus pesanan yang sudah Ditolak dan Refund untuk produk ini
        $delete_pesanan = $conn->query("
            DELETE FROM pesanan 
            WHERE email_penjual = '$email_penjual' 
            AND judul_buku = '$judul_buku'
            AND approve = 'Ditolak' 
            AND status = 'Refund'
        ");
        
        // Hapus produk
        $delete_produk = $conn->query("DELETE FROM produk_buku WHERE id_buku = '$id' AND email_penjual = '$email_penjual'");
        
        if ($delete_produk) {
            // Commit transaction
            mysqli_commit($conn);
            
            // Hapus foto produk jika ada
            if (!empty($produk_data['foto'])) {
                $foto_path = "../../Src/uploads/produk/" . $produk_data['foto'];
                if (file_exists($foto_path)) {
                    unlink($foto_path);
                }
            }
            
            echo "<script>
                    alert('Produk \"$judul_buku\" berhasil dihapus!');
                    window.location.href = 'produk.php';
                  </script>";
        } else {
            mysqli_rollback($conn);
            echo "<script>
                    alert('Gagal menghapus produk!');
                    window.location.href = 'produk.php';
                  </script>";
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>
                alert('Terjadi kesalahan sistem!');
                window.location.href = 'produk.php';
              </script>";
    }
}

// Ambil data produk untuk edit (jika ada parameter edit)
$produk_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = mysqli_real_escape_string($conn, $_GET['edit']);
    $produk_edit = $conn->query("SELECT * FROM produk_buku WHERE id_buku = '$id_edit' AND email_penjual = '$email_penjual'")->fetch_assoc();
}

// Ambil data produk untuk detail (jika ada parameter detail)
$produk_detail = null;
if (isset($_GET['detail'])) {
    $id_detail = mysqli_real_escape_string($conn, $_GET['detail']);
    $produk_detail = $conn->query("SELECT * FROM produk_buku WHERE id_buku = '$id_detail' AND email_penjual = '$email_penjual'")->fetch_assoc();
}

// Hitung total produk
$total_produk = $conn->query("SELECT COUNT(*) as total FROM produk_buku WHERE email_penjual = '$email_penjual'")->fetch_assoc()['total'];

// Ambil data produk untuk ditampilkan
$produk = $conn->query("SELECT * FROM produk_buku WHERE email_penjual = '$email_penjual' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Kelola Produk</title>
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
            /* margin-left: 4rem; */
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

        /* Stats Card */
        .stats-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stats-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stats-content p {
            font-size: 15px;
            color: var(--gray);
        }

        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 0;
        }

        .search-box {
            position: relative;
            width: 300px;
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

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
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

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
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

        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            padding: 15px;
            background-color: #f8f9fa;
            color: var(--gray);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            border-bottom: 2px solid var(--light-gray);
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .product-title {
            font-weight: 500;
            color: var(--dark);
        }

        .product-price {
            font-weight: 600;
            color: var(--success);
        }

        .product-category {
            padding: 4px 12px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .product-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--light-gray);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
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
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-height: 90vh;        /* batas tinggi modal */
            overflow-y: auto; 
        }

        .modal-wide {
            max-width: 800px;
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

        .file-preview {
            margin-top: 10px;
            text-align: center;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            display: block;
            margin: 0 auto 10px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

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

        /* Detail Styles */
        .detail-container {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-photo {
            flex: 0 0 300px;
        }

        .detail-photo img {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid var(--light-gray);
            padding: 10px;
            background-color: white;
        }

        .detail-info {
            flex: 1;
        }

        .detail-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }

        .detail-value {
            font-size: 16px;
            color: var(--dark);
        }

        .detail-value.price {
            color: var(--success);
            font-weight: 700;
            font-size: 24px;
        }

        .detail-value.stock {
            color: var(--info);
            font-weight: 700;
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
            
            .action-bar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
            
            .table-container {
                padding: 15px;
            }
            
            .detail-container {
                flex-direction: column;
            }
            
            .detail-photo {
                flex: 0 0 auto;
            }
        }

        @media (max-width: 480px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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

        <!-- Menu MANAGEMENT -->
        <div class="nav-section">
            <div class="section-title">MANAGEMENT</div>
            <ul class="nav-links">
                <li>
                    <a href="produk.php" class="nav-item active">
                        <i class="fas fa-box"></i>
                        <span class="nav-text">Produk</span>
                    </a>
                </li>
                <li>
                <a href="pesanan.php" class="nav-item order-nav">
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
            <!-- <ul class="nav-links">
                <li>
                    <a href="admin.php" class="nav-item">
                        <i class="fas fa-user-cog"></i>
                        <span class="nav-text">Admin</span>
                    </a>
                </li>
            </ul> -->
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
                <i class="fas fa-box"></i>Kelola Produk
            </h1>
            <p class="welcome-message">
                Kelola produk buku Anda di sini. Tambah, edit, atau hapus produk sesuai kebutuhan.
            </p>

            <!-- Stats Card -->
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_produk; ?></h3>
                    <p>Total Produk Anda</p>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari produk...">
                </div>
                <button class="btn btn-primary" onclick="openModal('tambahModal')">
                    <i class="fas fa-plus"></i>Tambah Produk
                </button>
            </div>

            <!-- Table -->
            <div class="table-container">
                <?php if ($produk->num_rows > 0): ?>
                    <table class="table" id="produkTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Foto</th>
                                <th>Judul Buku</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Modal</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = $produk->fetch_assoc()): 
                                $foto_src = !empty($row['foto']) ? 
                                    "../../Src/uploads/produk/" . $row['foto'] : 
                                    "https://via.placeholder.com/50x75/cccccc/666666?text=No+Image";
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <img src="<?php echo $foto_src; ?>" 
                                             alt="<?php echo htmlspecialchars($row['judul_buku']); ?>" 
                                             class="product-photo"
                                             onerror="this.src='https://via.placeholder.com/50x75/cccccc/666666?text=No+Image'">
                                    </td>
                                    <td>
                                        <div class="product-title"><?php echo htmlspecialchars($row['judul_buku']); ?></div>
                                    </td>
                                    <td>
                                        <span class="product-category"><?php echo htmlspecialchars($row['kategori_buku']); ?></span>
                                    </td>
                                    <td class="product-price">Rp <?php echo number_format($row['harga_buku'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($row['modal'], 0, ',', '.'); ?></td>
                                    <td><?php echo $row['stok']; ?> pcs</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-info btn-sm" onclick="detailProduk(<?php echo $row['id_buku']; ?>)">
                                                <i class="fas fa-eye"></i>Detail
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="editProduk(<?php echo $row['id_buku']; ?>)">
                                                <i class="fas fa-edit"></i>Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id_buku']; ?>)">
                                                <i class="fas fa-trash"></i>Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-box-open"></i>
                        <h4>Belum ada produk</h4>
                        <p>Mulai tambahkan produk pertama Anda</p>
                        <button class="btn btn-primary mt-3" onclick="openModal('tambahModal')">
                            <i class="fas fa-plus"></i>Tambah Produk
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
                <span class="breadcrumb-item active">Produk</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Modal Tambah Produk -->
    <div class="modal-overlay" id="tambahModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i>Tambah Produk Baru</h3>
                <button class="close-modal" onclick="closeModal('tambahModal')">&times;</button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="kategori">Kategori Buku</label>
                    <select class="form-control" id="kategori" name="kategori" required>
                        <option value="">Pilih Kategori</option>
                        <?php 
                        if ($kategori_list->num_rows > 0):
                            mysqli_data_seek($kategori_list, 0);
                            while($kategori_row = $kategori_list->fetch_assoc()):
                        ?>
                            <option value="<?php echo htmlspecialchars($kategori_row['nama_kategori']); ?>">
                                <?php echo htmlspecialchars($kategori_row['nama_kategori']); ?>
                            </option>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <option value="">Tidak ada kategori tersedia</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="judul">Judul Buku</label>
                    <input type="text" class="form-control" id="judul" name="judul" required placeholder="Masukkan judul buku">
                </div>
                <div class="form-group">
                    <label for="harga">Harga Jual (Rp)</label>
                    <input type="number" class="form-control" id="harga" name="harga" required placeholder="Masukkan harga jual" min="0">
                </div>
                <div class="form-group">
                    <label for="modal">Modal (Rp)</label>
                    <input type="number" class="form-control" id="modal" name="modal" required placeholder="Masukkan modal" min="0">
                </div>
                <div class="form-group">
                    <label for="stok">Stok</label>
                    <input type="number" class="form-control" id="stok" name="stok" required placeholder="Masukkan jumlah stok" min="0">
                </div>
                <div class="form-group">
                    <label for="foto">Foto Produk</label>
                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                    <small class="text-muted">Ukuran maksimal 5MB. Format: JPG, JPEG, PNG, GIF</small>
                    <div class="file-preview" id="fotoPreview">
                        <!-- Preview akan muncul di sini -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal('tambahModal')">Batal</button>
                    <button type="submit" class="btn btn-success" name="tambah_produk">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Produk -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i>Edit Produk</h3>
                <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="editForm">
                <input type="hidden" id="edit_id" name="id_buku">
                <div class="form-group">
                    <label for="edit_kategori">Kategori Buku</label>
                    <select class="form-control" id="edit_kategori" name="kategori" required>
                        <option value="">Pilih Kategori</option>
                        <?php 
                        mysqli_data_seek($kategori_list, 0);
                        if ($kategori_list->num_rows > 0):
                            while($kategori_row = $kategori_list->fetch_assoc()):
                                $selected = ($produk_edit && $kategori_row['nama_kategori'] == $produk_edit['kategori_buku']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($kategori_row['nama_kategori']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($kategori_row['nama_kategori']); ?>
                            </option>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <option value="">Tidak ada kategori tersedia</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_judul">Judul Buku</label>
                    <input type="text" class="form-control" id="edit_judul" name="judul" required>
                </div>
                <div class="form-group">
                    <label for="edit_harga">Harga Jual (Rp)</label>
                    <input type="number" class="form-control" id="edit_harga" name="harga" required min="0">
                </div>
                <div class="form-group">
                    <label for="edit_modal">Modal (Rp)</label>
                    <input type="number" class="form-control" id="edit_modal" name="modal" required min="0">
                </div>
                <div class="form-group">
                    <label for="edit_stok">Stok</label>
                    <input type="number" class="form-control" id="edit_stok" name="stok" required min="0">
                </div>
                <div class="form-group">
                    <label for="edit_foto">Foto Produk (Ubah jika perlu)</label>
                    <input type="file" class="form-control" id="edit_foto" name="foto" accept="image/*">
                    <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>
                    <div class="file-preview" id="editFotoPreview">
                        <!-- Preview foto saat ini -->
                        <?php if ($produk_edit && !empty($produk_edit['foto'])): ?>
                            <img src="../../Src/uploads/produk/<?php echo $produk_edit['foto']; ?>" 
                                 class="preview-image" 
                                 id="currentFotoPreview"
                                 onerror="this.style.display='none'">
                            <p>Foto saat ini</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-success" name="edit_produk">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail Produk -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal modal-wide">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i>Detail Produk</h3>
                <button class="close-modal" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <?php if ($produk_detail): ?>
            <div class="detail-container">
                <div class="detail-photo">
                    <?php 
                    $foto_src = !empty($produk_detail['foto']) ? 
                        "../../Src/uploads/produk/" . $produk_detail['foto'] : 
                        "https://via.placeholder.com/300x400/cccccc/666666?text=No+Image";
                    ?>
                    <img src="<?php echo $foto_src; ?>" 
                         alt="<?php echo htmlspecialchars($produk_detail['judul_buku']); ?>"
                         onerror="this.src='https://via.placeholder.com/300x400/cccccc/666666?text=No+Image'">
                </div>
                <div class="detail-info">
                    <div class="detail-item">
                        <span class="detail-label">Judul Buku</span>
                        <div class="detail-value"><?php echo htmlspecialchars($produk_detail['judul_buku']); ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Kategori</span>
                        <div class="detail-value">
                            <span class="product-category"><?php echo htmlspecialchars($produk_detail['kategori_buku']); ?></span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Harga Jual</span>
                        <div class="detail-value price">Rp <?php echo number_format($produk_detail['harga_buku'], 0, ',', '.'); ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Modal</span>
                        <div class="detail-value">Rp <?php echo number_format($produk_detail['modal'], 0, ',', '.'); ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Stok Tersedia</span>
                        <div class="detail-value stock"><?php echo $produk_detail['stok']; ?> pcs</div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Nama Penjual</span>
                        <div class="detail-value"><?php echo htmlspecialchars($produk_detail['nama_penjual']); ?></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email Penjual</span>
                        <div class="detail-value"><?php echo htmlspecialchars($produk_detail['email_penjual']); ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModal('detailModal')">Tutup</button>
                <button type="button" class="btn btn-warning" onclick="editProduk(<?php echo $produk_detail['id_buku']; ?>)">
                    <i class="fas fa-edit"></i>Edit Produk
                </button>
            </div>
            <?php endif; ?>
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('produkTable');
            
            if (table) {
                const rows = table.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }
                    
                    rows[i].style.display = found ? '' : 'none';
                }
            }
        });

        // Preview image for tambah produk
        document.getElementById('foto').addEventListener('change', function(e) {
            const preview = document.getElementById('fotoPreview');
            const file = e.target.files[0];
            
            preview.innerHTML = '';
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        });

        // Preview image for edit produk
        document.getElementById('edit_foto').addEventListener('change', function(e) {
            const preview = document.getElementById('editFotoPreview');
            const file = e.target.files[0];
            
            // Hapus preview sebelumnya kecuali foto saat ini
            const currentFoto = document.getElementById('currentFotoPreview');
            if (currentFoto) {
                preview.innerHTML = '';
                preview.appendChild(currentFoto);
                const label = document.createElement('p');
                label.textContent = 'Foto saat ini';
                preview.appendChild(label);
            } else {
                preview.innerHTML = '';
            }
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    
                    if (currentFoto) {
                        const newFotoLabel = document.createElement('p');
                        newFotoLabel.textContent = 'Foto baru';
                        newFotoLabel.style.marginTop = '10px';
                        newFotoLabel.style.fontWeight = '600';
                        preview.appendChild(newFotoLabel);
                    }
                    
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        });

        // Edit produk
        function editProduk(id) {
            window.location.href = 'produk.php?edit=' + id;
        }

        // Detail produk
        function detailProduk(id) {
            window.location.href = 'produk.php?detail=' + id;
        }

        // Delete confirmation
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                window.location.href = 'produk.php?hapus=' + id;
            }
        }

        // Jika ada data edit, tampilkan modal edit
        <?php if ($produk_edit): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('edit_id').value = '<?php echo $produk_edit['id_buku']; ?>';
            document.getElementById('edit_judul').value = '<?php echo htmlspecialchars($produk_edit['judul_buku']); ?>';
            document.getElementById('edit_harga').value = '<?php echo $produk_edit['harga_buku']; ?>';
            document.getElementById('edit_modal').value = '<?php echo $produk_edit['modal']; ?>';
            document.getElementById('edit_stok').value = '<?php echo $produk_edit['stok']; ?>';
            openModal('editModal');
        });
        <?php endif; ?>

        // Jika ada data detail, tampilkan modal detail
        <?php if ($produk_detail): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('detailModal');
        });
        <?php endif; ?>
    </script>
</body>
</html>