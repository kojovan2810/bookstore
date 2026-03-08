<?php
session_start();
include "config.php";

// Cek apakah user sudah login
if (!isset($_SESSION['email_pembeli'])) {
    echo json_encode(['success' => false, 'message' => 'Silahkan login terlebih dahulu']);
    exit();
}

// Cek method request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
    exit();
}

// Ambil data dari POST
$action = $_POST['action'] ?? '';
$kode_pesanan = $_POST['kode_pesanan'] ?? '';

// Validasi
if ($action != 'complete_refund' || empty($kode_pesanan)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

$email_pembeli = $_SESSION['email_pembeli'];

// Mulai transaksi
$conn->begin_transaction();

try {
    // 1. Cek apakah pesanan valid dan milik user
    $check_query = "SELECT * FROM pesanan 
                   WHERE kode_pesanan = ? 
                   AND email_pembeli = ? 
                   AND status = 'Refund'
                   LIMIT 1";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $kode_pesanan, $email_pembeli);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception('Pesanan tidak ditemukan atau tidak dalam status Refund');
    }
    
    $pesanan = $result->fetch_assoc();
    
    // 2. Hapus pesanan dari tabel pesanan
    $delete_query = "DELETE FROM pesanan WHERE kode_pesanan = ? AND email_pembeli = ?";
    $stmt_delete = $conn->prepare($delete_query);
    $stmt_delete->bind_param("ss", $kode_pesanan, $email_pembeli);
    
    if (!$stmt_delete->execute()) {
        throw new Exception('Gagal menghapus pesanan');
    }
    
    // 3. Simpan ke tabel riwayat_hapus untuk tracking (opsional)
    // Buat tabel terlebih dahulu jika belum ada
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS riwayat_hapus (
            id INT PRIMARY KEY AUTO_INCREMENT,
            kode_pesanan VARCHAR(50) NOT NULL,
            email_pembeli VARCHAR(100) NOT NULL,
            judul_buku VARCHAR(255) NOT NULL,
            total_harga DECIMAL(10,2) NOT NULL,
            alasan VARCHAR(50) DEFAULT 'refund_selesai',
            tanggal_hapus DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_kode (kode_pesanan),
            INDEX idx_email (email_pembeli),
            INDEX idx_tanggal (tanggal_hapus)
        )
    ";
    
    $conn->query($create_table_query);
    
    // Insert ke riwayat_hapus
    $insert_history = "INSERT INTO riwayat_hapus 
                      (kode_pesanan, email_pembeli, judul_buku, total_harga, alasan) 
                      VALUES (?, ?, ?, ?, 'refund_selesai')";
    
    $stmt_history = $conn->prepare($insert_history);
    $stmt_history->bind_param("sssd", 
        $kode_pesanan, 
        $email_pembeli, 
        $pesanan['judul_buku'], 
        $pesanan['total_harga']
    );
    
    $stmt_history->execute();
    
    // Commit transaksi
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Refund berhasil diselesaikan. Pesanan telah dihapus dari sistem.'
    ]);
    
} catch (Exception $e) {
    // Rollback jika ada error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_delete)) $stmt_delete->close();
    if (isset($stmt_history)) $stmt_history->close();
}
?>