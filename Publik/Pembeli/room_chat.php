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

// Ambil semua penjual yang pernah diajak chat atau ada produknya
$penjual_query = $conn->query("
    SELECT DISTINCT p.email_penjual, p.nama_penjual, p.foto, p.status,
           (SELECT COUNT(*) FROM chat_messages cm 
            WHERE cm.sender_id = p.email_penjual 
            AND cm.receiver_id = '$email_pembeli' 
            AND cm.is_read = 0) as unread_count,
           (SELECT cm.message FROM chat_messages cm 
            WHERE (cm.sender_id = p.email_penjual AND cm.receiver_id = '$email_pembeli') 
            OR (cm.sender_id = '$email_pembeli' AND cm.receiver_id = p.email_penjual)
            ORDER BY cm.timestamp DESC LIMIT 1) as last_message,
           (SELECT cm.timestamp FROM chat_messages cm 
            WHERE (cm.sender_id = p.email_penjual AND cm.receiver_id = '$email_pembeli') 
            OR (cm.sender_id = '$email_pembeli' AND cm.receiver_id = p.email_penjual)
            ORDER BY cm.timestamp DESC LIMIT 1) as last_message_time
    FROM penjual p
    WHERE EXISTS (
        SELECT 1 FROM produk_buku pb 
        WHERE pb.email_penjual = p.email_penjual 
        AND pb.status = 'Aktif'
    )
    OR EXISTS (
        SELECT 1 FROM chat_messages cm 
        WHERE (cm.sender_id = p.email_penjual AND cm.receiver_id = '$email_pembeli')
        OR (cm.sender_id = '$email_pembeli' AND cm.receiver_id = p.email_penjual)
    )
    ORDER BY last_message_time DESC, p.nama_penjual ASC
");

// Ambil parameter untuk chat dengan penjual tertentu
$chat_with = isset($_GET['chat_with']) ? $_GET['chat_with'] : '';

// Jika ada parameter chat_with, ambil data penjual tersebut
$current_penjual = null;
if ($chat_with) {
    $penjual_data = $conn->query("SELECT * FROM penjual WHERE email_penjual = '$chat_with'");
    if ($penjual_data->num_rows > 0) {
        $current_penjual = $penjual_data->fetch_assoc();
        
        // Tandai pesan sebagai telah dibaca
        $conn->query("UPDATE chat_messages SET is_read = 1 
                      WHERE sender_id = '$chat_with' 
                      AND receiver_id = '$email_pembeli' 
                      AND is_read = 0");
    }
}

// PROSES KIRIM PESAN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message) && !empty($receiver_id)) {
        $message = $conn->real_escape_string($message);
        $timestamp = date('Y-m-d H:i:s');
        
        $insert_message = "INSERT INTO chat_messages (sender_id, receiver_id, message, timestamp, is_read) 
                          VALUES ('$email_pembeli', '$receiver_id', '$message', '$timestamp', 0)";
        
        if ($conn->query($insert_message)) {
            // Redirect untuk refresh chat
            header("Location: room_chat.php?chat_with=$receiver_id");
            exit();
        } else {
            $error = "Gagal mengirim pesan: " . $conn->error;
        }
    }
}

// Buat tabel chat_messages jika belum ada
$check_table = $conn->query("SHOW TABLES LIKE 'chat_messages'");
if ($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE chat_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id VARCHAR(100) NOT NULL,
        receiver_id VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        timestamp DATETIME NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        INDEX idx_sender (sender_id),
        INDEX idx_receiver (receiver_id),
        INDEX idx_timestamp (timestamp)
    )";
    $conn->query($create_table);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Room Chat</title>
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
            --message-sent: #e3f2fd;
            --message-received: #f1f3f4;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Reuse sidebar, topbar, main-content styles */
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
            display: flex;
            flex-direction: column;
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

        /* Chat Container */
        .chat-container {
            display: flex;
            flex: 1;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* Sidebar Kontak */
        .chat-sidebar {
            width: 320px;
            border-right: 1px solid var(--light-gray);
            background-color: white;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid var(--light-gray);
            background-color: var(--primary);
            color: white;
        }

        .chat-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .chat-search {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .chat-search-box {
            position: relative;
        }

        .chat-search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 14px;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }

        .chat-search-box input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .chat-search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
        }

        .chat-contacts {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .contact-item {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .contact-item:hover {
            background-color: #f8f9fa;
        }

        .contact-item.active {
            background-color: rgba(67, 97, 238, 0.1);
            border-left: 4px solid var(--primary);
        }

        .contact-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #f8f9fa;
        }

        .contact-info {
            flex: 1;
            min-width: 0;
        }

        .contact-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
            font-size: 15px;
        }

        .contact-last-message {
            font-size: 13px;
            color: var(--gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .contact-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .contact-time {
            font-size: 11px;
            color: var(--gray);
        }

        .unread-badge {
            background-color: var(--primary);
            color: white;
            font-size: 11px;
            font-weight: 600;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
        }

        .contact-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
        }

        .status-online {
            color: var(--success);
        }

        .status-offline {
            color: var(--gray);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-online .status-dot {
            background-color: var(--success);
        }

        .status-offline .status-dot {
            background-color: var(--gray);
        }

        .no-contacts {
            padding: 40px 20px;
            text-align: center;
            color: var(--gray);
        }

        .no-contacts i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-contacts h4 {
            font-size: 16px;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .no-contacts p {
            font-size: 14px;
            margin-bottom: 15px;
        }

        /* Area Chat Utama */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }

        .chat-topbar {
            padding: 15px 20px;
            background-color: white;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .current-chat-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .current-chat-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }

        .current-chat-details {
            display: flex;
            flex-direction: column;
        }

        .current-chat-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }

        .current-chat-status {
            font-size: 12px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chat-actions {
            display: flex;
            gap: 10px;
        }

        .chat-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background-color: #f8f9fa;
            color: var(--gray);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-action-btn:hover {
            background-color: var(--primary);
            color: white;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message-date {
            text-align: center;
            margin: 10px 0;
        }

        .date-label {
            background-color: var(--light-gray);
            color: var(--gray);
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 12px;
            display: inline-block;
        }

        .message-item {
            display: flex;
            margin-bottom: 5px;
            max-width: 70%;
        }

        .message-item.sent {
            align-self: flex-end;
        }

        .message-item.received {
            align-self: flex-start;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            align-self: flex-end;
            margin: 0 8px;
        }

        .message-bubble {
            padding: 12px 15px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }

        .message-item.sent .message-bubble {
            background-color: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-item.received .message-bubble {
            background-color: var(--message-received);
            color: var(--dark);
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 11px;
            color: var(--gray);
            margin-top: 4px;
            text-align: right;
        }

        .message-item.sent .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        .no-chat-selected {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            text-align: center;
            padding: 40px;
        }

        .no-chat-selected i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .no-chat-selected h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .no-chat-selected p {
            font-size: 15px;
            margin-bottom: 20px;
            max-width: 400px;
        }

        /* Chat Input */
        .chat-input-container {
            padding: 15px 20px;
            background-color: white;
            border-top: 1px solid var(--light-gray);
        }

        .chat-input-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 24px;
            font-size: 15px;
            background-color: #f8f9fa;
            resize: none;
            max-height: 120px;
            min-height: 46px;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .send-button {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: none;
            background-color: var(--primary);
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-button:hover {
            background-color: var(--primary-dark);
            transform: scale(1.05);
        }

        .send-button:disabled {
            background-color: var(--gray);
            cursor: not-allowed;
        }

        .input-actions {
            display: flex;
            gap: 8px;
        }

        .input-action-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background-color: #f8f9fa;
            color: var(--gray);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-action-btn:hover {
            background-color: var(--light-gray);
            color: var(--primary);
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

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-item {
            animation: fadeIn 0.3s ease;
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
            
            .chat-container {
                flex-direction: column;
            }
            
            .chat-sidebar {
                width: 100%;
                height: 300px;
                border-right: none;
                border-bottom: 1px solid var(--light-gray);
            }
            
            .chat-main {
                flex: 1;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 0 15px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .chat-messages {
                padding: 15px;
            }
            
            .message-item {
                max-width: 85%;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 12px;
                text-align: center;
                gap: 8px;
                height: auto;
            }
            
            .input-actions {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .chat-header h3 {
                font-size: 16px;
            }
            
            .current-chat-name {
                font-size: 14px;
            }
            
            .message-bubble {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .chat-input {
                font-size: 14px;
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
                <a href="room_chat.php" class="nav-item active">
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

            <?php if (!empty($error)): ?>
                <div style="background-color: rgba(220, 53, 69, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="chat-container">
                <!-- Sidebar Kontak -->
                <div class="chat-sidebar">
                    <div class="chat-header">
                        <h3><i class="fas fa-comments"></i> Daftar Penjual</h3>
                    </div>
                    
                    <div class="chat-search">
                        <div class="chat-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="contactSearch" placeholder="Cari penjual...">
                        </div>
                    </div>
                    
                    <div class="chat-contacts" id="chatContacts">
                        <?php if ($penjual_query->num_rows > 0): ?>
                            <?php while ($penjual = $penjual_query->fetch_assoc()): 
                                $foto_penjual = !empty($penjual['foto']) ? "../../Src/uploads/" . $penjual['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($penjual['nama_penjual']) . "&background=4361ee&color=fff&size=120";
                                $last_message = $penjual['last_message'] ? (strlen($penjual['last_message']) > 30 ? substr($penjual['last_message'], 0, 30) . '...' : $penjual['last_message']) : 'Belum ada pesan';
                                $last_time = $penjual['last_message_time'] ? date('H:i', strtotime($penjual['last_message_time'])) : '';
                                $unread_count = $penjual['unread_count'] ?: 0;
                                // $is_active = $penjual['chat_with'] === $chat_with;
                            ?>
                                <div class="contact-item <?php echo $is_active ? 'active' : ''; ?>" 
                                     onclick="window.location.href='room_chat.php?chat_with=<?php echo urlencode($penjual['email_penjual']); ?>'">
                                    <img src="<?php echo $foto_penjual; ?>" 
                                         alt="<?php echo htmlspecialchars($penjual['nama_penjual']); ?>"
                                         class="contact-avatar"
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($penjual['nama_penjual']); ?>&background=4361ee&color=fff&size=120'">
                                    
                                    <div class="contact-info">
                                        <div class="contact-name"><?php echo htmlspecialchars($penjual['nama_penjual']); ?></div>
                                        <div class="contact-last-message"><?php echo htmlspecialchars($last_message); ?></div>
                                        <div class="contact-meta">
                                            <div class="contact-status <?php echo $penjual['status'] === 'Online' ? 'status-online' : 'status-offline'; ?>">
                                                <div class="status-dot"></div>
                                                <span><?php echo $penjual['status'] === 'Online' ? 'Online' : 'Offline'; ?></span>
                                            </div>
                                            <?php if ($last_time): ?>
                                                <div class="contact-time"><?php echo $last_time; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($unread_count > 0): ?>
                                        <div class="unread-badge"><?php echo $unread_count; ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-contacts">
                                <i class="fas fa-comment-slash"></i>
                                <h4>Belum ada kontak</h4>
                                <p>Mulailah belanja untuk bisa chat dengan penjual</p>
                                <a href="produk.php" class="btn btn-primary" style="padding: 10px 20px; display: inline-block; text-decoration: none;">
                                    <i class="fas fa-shopping-bag"></i> Mulai Belanja
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Area Chat Utama -->
                <div class="chat-main">
                    <?php if ($current_penjual): 
                        $foto_current = !empty($current_penjual['foto']) ? "../../Src/uploads/" . $current_penjual['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($current_penjual['nama_penjual']) . "&background=4361ee&color=fff&size=120";
                        $foto_pembeli = isset($data_pembeli['foto']) ? "../../Src/uploads/" . $data_pembeli['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($data_pembeli['nama_pembeli']) . "&background=4361ee&color=fff&size=120";
                    ?>
                        <div class="chat-topbar">
                            <div class="current-chat-info">
                                <img src="<?php echo $foto_current; ?>" 
                                     alt="<?php echo htmlspecialchars($current_penjual['nama_penjual']); ?>"
                                     class="current-chat-avatar"
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($current_penjual['nama_penjual']); ?>&background=4361ee&color=fff&size=120'">
                                
                                <div class="current-chat-details">
                                    <div class="current-chat-name"><?php echo htmlspecialchars($current_penjual['nama_penjual']); ?></div>
                                    <div class="current-chat-status <?php echo $current_penjual['status'] === 'Online' ? 'status-online' : 'status-offline'; ?>">
                                        <div class="status-dot"></div>
                                        <span><?php echo $current_penjual['status'] === 'Online' ? 'Sedang Online' : 'Sedang Offline'; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="chat-actions">
                                <button class="chat-action-btn" title="Info Penjual" onclick="window.location.href='penjual_lain.php?email=<?php echo urlencode($current_penjual['email_penjual']); ?>'">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <button class="chat-action-btn" title="Produk Penjual" onclick="window.location.href='produk.php?penjual=<?php echo urlencode($current_penjual['email_penjual']); ?>'">
                                    <i class="fas fa-store"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Area Pesan -->
                        <div class="chat-messages" id="chatMessages">
                            <?php 
                            // Ambil pesan antara pembeli dan penjual ini
                            $messages_query = $conn->query("
                                SELECT * FROM chat_messages 
                                WHERE (sender_id = '$email_pembeli' AND receiver_id = '$chat_with')
                                OR (sender_id = '$chat_with' AND receiver_id = '$email_pembeli')
                                ORDER BY timestamp ASC
                            ");
                            
                            $current_date = null;
                            
                            while ($message = $messages_query->fetch_assoc()):
                                $message_date = date('Y-m-d', strtotime($message['timestamp']));
                                $message_time = date('H:i', strtotime($message['timestamp']));
                                $is_sent = $message['sender_id'] === $email_pembeli;
                                
                                // Tampilkan tanggal jika berbeda dengan sebelumnya
                                if ($message_date !== $current_date) {
                                    $current_date = $message_date;
                                    $display_date = date('d F Y', strtotime($message_date));
                                    echo '<div class="message-date"><span class="date-label">' . $display_date . '</span></div>';
                                }
                            ?>
                                <div class="message-item <?php echo $is_sent ? 'sent' : 'received'; ?>">
                                    <?php if (!$is_sent): ?>
                                        <img src="<?php echo $foto_current; ?>" 
                                             alt="Avatar" 
                                             class="message-avatar"
                                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($current_penjual['nama_penjual']); ?>&background=4361ee&color=fff&size=120'">
                                    <?php endif; ?>
                                    
                                    <div>
                                        <div class="message-bubble">
                                            <?php echo htmlspecialchars($message['message']); ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo $message_time; ?>
                                            <?php if ($is_sent && $message['is_read']): ?>
                                                <i class="fas fa-check-double" style="margin-left: 5px; color: #4CAF50;"></i>
                                            <?php elseif ($is_sent): ?>
                                                <i class="fas fa-check" style="margin-left: 5px;"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($is_sent): ?>
                                        <img src="<?php echo $foto_pembeli; ?>" 
                                             alt="Avatar" 
                                             class="message-avatar"
                                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($data_pembeli['nama_pembeli']); ?>&background=4361ee&color=fff&size=120'">
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                            
                            <?php if ($messages_query->num_rows == 0): ?>
                                <div style="text-align: center; color: var(--gray); padding: 40px 0;">
                                    <i class="fas fa-comments" style="font-size: 48px; opacity: 0.3;"></i>
                                    <p style="margin-top: 15px;">Mulailah percakapan dengan <?php echo htmlspecialchars($current_penjual['nama_penjual']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Input Pesan -->
                        <div class="chat-input-container">
                            <form method="POST" action="" class="chat-input-form" id="chatForm">
                                <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($chat_with); ?>">
                                
                                <div class="input-actions">
                                    <button type="button" class="input-action-btn" title="Lampirkan Gambar">
                                        <i class="fas fa-image"></i>
                                    </button>
                                    <button type="button" class="input-action-btn" title="Kirim Emoji">
                                        <i class="fas fa-smile"></i>
                                    </button>
                                </div>
                                
                                <textarea name="message" 
                                          class="chat-input" 
                                          id="messageInput" 
                                          placeholder="Ketik pesan..." 
                                          rows="1"
                                          oninput="autoResize(this)"
                                          required></textarea>
                                
                                <button type="submit" name="send_message" class="send-button" id="sendButton">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Tampilan ketika belum ada chat yang dipilih -->
                        <div class="no-chat-selected">
                            <div>
                                <i class="fas fa-comments"></i>
                                <h3>Pilih Percakapan</h3>
                                <p>Pilih salah satu penjual dari daftar untuk memulai percakapan.</p>
                                <p>Anda dapat bertanya tentang produk, harga, atau status pesanan.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Room Chat</span>
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

        // Search functionality di topbar
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = 'produk.php?search=' + encodeURIComponent(searchTerm);
                }
            }
        });

        // Search kontak
        document.getElementById('contactSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const contacts = document.querySelectorAll('.contact-item');
            
            contacts.forEach(contact => {
                const name = contact.querySelector('.contact-name').textContent.toLowerCase();
                const message = contact.querySelector('.contact-last-message').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || message.includes(searchTerm)) {
                    contact.style.display = 'flex';
                } else {
                    contact.style.display = 'none';
                }
            });
        });

        // Auto resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        // Validasi form sebelum submit
        document.getElementById('chatForm')?.addEventListener('submit', function(e) {
            const messageInput = document.getElementById('messageInput');
            if (messageInput.value.trim() === '') {
                e.preventDefault();
                messageInput.focus();
                return false;
            }
            return true;
        });

        // Scroll ke bawah di chat messages
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Auto refresh chat setiap 5 detik
        <?php if ($current_penjual): ?>
        let lastMessageCount = <?php echo $messages_query->num_rows; ?>;
        
        function checkNewMessages() {
            fetch(`check_messages.php?chat_with=<?php echo urlencode($chat_with); ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.count > lastMessageCount) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Check new messages every 5 seconds
        setInterval(checkNewMessages, 5000);
        <?php endif; ?>

        // Enter untuk submit pesan (Shift+Enter untuk new line)
        document.getElementById('messageInput')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('sendButton').click();
            }
        });

        // Fokus ke input pesan saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.focus();
            }
        });
    </script>
</body>
</html>