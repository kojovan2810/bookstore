<?php
session_start();
require '../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include "../../Src/config.php";

// Cek apakah pembeli sudah login
if (!isset($_SESSION['email_pembeli'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

// Ambil data pembeli dari session
$email_pembeli = $_SESSION['email_pembeli'];
$data_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email_pembeli'")->fetch_assoc();

// Ambil parameter kode pesanan
$kode_pesanan = isset($_GET['kode_pesanan']) ? $_GET['kode_pesanan'] : '';
$download = isset($_GET['download']) ? $_GET['download'] : false;
$is_multi = isset($_GET['multi']) ? $_GET['multi'] : 'false';

// Cek apakah ada kode pesanan di session (untuk multi-penjual)
if (empty($kode_pesanan) && isset($_SESSION['generated_kode_pesanan']) && !empty($_SESSION['generated_kode_pesanan'])) {
    $kode_pesanan = $_SESSION['generated_kode_pesanan'][0];
    $is_multi = count($_SESSION['generated_kode_pesanan']) > 1 ? 'true' : 'false';
}

// Validasi kode pesanan
if (empty($kode_pesanan)) {
    echo "<script>alert('Kode pesanan tidak valid!');location.href='pesanan.php'</script>";
    exit();
}

// Ambil semua kode pesanan jika multi penjual
$all_kode_pesanan = [];
if ($is_multi == 'true' && isset($_SESSION['generated_kode_pesanan'])) {
    $all_kode_pesanan = $_SESSION['generated_kode_pesanan'];
} else {
    $all_kode_pesanan = [$kode_pesanan];
}

// Ambil data pesanan untuk semua kode pesanan
$all_items = [];
$total_semua_pesanan = 0;
$metode_bayar = '';
$tanggal_pesanan = '';
$nama_penjual_terakhir = '';
$bukti_pembayaran = '';

foreach ($all_kode_pesanan as $kp) {
    $pesanan_query = $conn->query("
        SELECT p.*, pb.foto as foto_buku, pb.kategori_buku
        FROM pesanan p
        LEFT JOIN produk_buku pb ON p.id_buku = pb.id_buku
        WHERE p.kode_pesanan = '$kp' 
        AND p.email_pembeli = '$email_pembeli'
    ");
    
    if ($pesanan_query->num_rows > 0) {
        while ($item = $pesanan_query->fetch_assoc()) {
            $all_items[] = $item;
            $total_semua_pesanan += $item['total_harga'];
            $metode_bayar = $item['metode_bayar'];
            $tanggal_pesanan = $item['tanggal_pesanan'];
            $nama_penjual_terakhir = $item['nama_penjual'];
            $bukti_pembayaran = $item['bukti_pembayaran'];
        }
    }
}

// Kelompokkan item berdasarkan penjual (untuk multi-penjual)
$items_by_seller = [];
$sellers_info = [];
$seller_totals = [];

foreach ($all_items as $item) {
    $email_penjual = $item['email_penjual'];
    if (!isset($items_by_seller[$email_penjual])) {
        $items_by_seller[$email_penjual] = [];
        $sellers_info[$email_penjual] = [
            'nama_penjual' => $item['nama_penjual'],
            'kode_pesanan' => $item['kode_pesanan']
        ];
        $seller_totals[$email_penjual] = 0;
    }
    $items_by_seller[$email_penjual][] = $item;
    $seller_totals[$email_penjual] += $item['total_harga'];
}

// Format tanggal
$tanggal_format = date('d F Y', strtotime($tanggal_pesanan));
$waktu_format = date('H:i:s', strtotime($tanggal_pesanan));
$tanggal_cetak = date('d/m/Y H:i:s');

// Fungsi untuk format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Jika request download PDF
if ($download) {
    // Generate HTML content untuk PDF invoice (format struk)
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Struk BukuBook - ' . $kode_pesanan . '</title>
        <style>
            body {
                font-family: "Courier New", monospace;
                margin: 0;
                padding: 0;
                color: #000;
                background: #fff;
                line-height: 1.2;
                font-size: 11px;
            }
            
            .struk-container {
                max-width: 300px;
                margin: 0 auto;
                background: white;
                padding: 10px;
                position: relative;
            }
            
            /* Header */
            .header {
                text-align: center;
                border-bottom: 1px dashed #000;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            
            .company-name {
                font-size: 18px;
                font-weight: bold;
                margin: 0;
                letter-spacing: 1px;
            }
            
            .company-tagline {
                font-size: 10px;
                margin: 3px 0;
            }
            
            .invoice-title {
                font-size: 14px;
                font-weight: bold;
                margin: 8px 0;
                text-transform: uppercase;
            }
            
            .invoice-number {
                font-size: 12px;
                font-weight: bold;
                margin: 5px 0;
            }
            
            /* Multi Seller Info */
            .multi-seller-info {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 8px;
                margin-bottom: 10px;
                font-size: 10px;
                color: #856404;
                text-align: center;
            }
            
            /* Info Section */
            .info-section {
                margin-bottom: 10px;
                border-bottom: 1px dashed #000;
                padding-bottom: 10px;
            }
            
            .info-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 3px;
            }
            
            .info-label {
                font-weight: bold;
            }
            
            /* Seller Sections */
            .seller-section {
                margin: 15px 0;
                border: 1px dashed #ccc;
                padding: 8px;
            }
            
            .seller-header {
                font-weight: bold;
                margin-bottom: 5px;
                color: #000;
                font-size: 12px;
            }
            
            .seller-kode {
                font-size: 10px;
                color: #666;
                margin-bottom: 5px;
            }
            
            /* Items Table */
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
                font-size: 10px;
            }
            
            .items-table th {
                text-align: left;
                padding: 3px 0;
                border-bottom: 1px dashed #000;
                font-weight: bold;
            }
            
            .items-table td {
                padding: 4px 0;
                border-bottom: 1px dotted #ddd;
                vertical-align: top;
            }
            
            .text-right {
                text-align: right;
            }
            
            .text-center {
                text-align: center;
            }
            
            /* Summary */
            .summary-section {
                margin-top: 15px;
                border-top: 1px dashed #000;
                padding-top: 10px;
            }
            
            .summary-row {
                display: flex;
                justify-content: space-between;
                padding: 3px 0;
            }
            
            .summary-row.total {
                font-weight: bold;
                font-size: 12px;
                border-top: 2px solid #000;
                margin-top: 5px;
                padding-top: 8px;
            }
            
            /* Seller Summary */
            .seller-summary {
                background: #f8f9fa;
                padding: 5px;
                margin: 8px 0;
                font-size: 10px;
            }
            
            /* Footer */
            .footer {
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px dashed #000;
                text-align: center;
                font-size: 9px;
            }
            
            .separator {
                text-align: center;
                margin: 10px 0;
                letter-spacing: 5px;
            }
            
            .thank-you {
                text-align: center;
                margin: 15px 0;
                font-weight: bold;
            }
            
            /* Print Settings */
            @page {
                margin: 5mm;
                size: auto;
            }
            
            @media print {
                body {
                    padding: 0;
                    margin: 0;
                }
                
                .struk-container {
                    border: none;
                    padding: 0;
                    max-width: 100%;
                }
                
                .no-print {
                    display: none;
                }
            }
            
            /* Status Badge */
            .status {
                text-align: center;
                margin: 10px 0;
                font-weight: bold;
                background: #e8f5e8;
                padding: 5px;
                border: 1px solid #c3e6cb;
            }
            
            /* Payment Info */
            .payment-info {
                background: #f8f9fa;
                padding: 8px;
                margin: 10px 0;
                border: 1px dashed #ccc;
            }
        </style>
    </head>
    <body>
        <div class="struk-container">
            <!-- Header -->
            <div class="header">
                <div class="company-name">BUKUBOOK</div>
                <div class="company-tagline">Platform Jual Beli Buku Online</div>
                <div class="company-tagline">support@bukubook.com | www.bukubook.com</div>
                <div class="separator">-----------------------------</div>
                <div class="invoice-title">STRUK PEMBAYARAN</div>
                <div class="invoice-number">' . $kode_pesanan . '</div>
                <div>Tanggal: ' . $tanggal_format . '</div>
                <div>Waktu: ' . $waktu_format . '</div>';
                
                if ($is_multi == 'true') {
                    $html .= '<div class="multi-seller-info">
                        ⚠ PESANAN DARI ' . count($items_by_seller) . ' PENJUAL BERBEDA
                    </div>';
                }
                
                $html .= '<div class="separator">-----------------------------</div>
            </div>
            
            <!-- Status -->
            <div class="status">
                ✅ PEMBAYARAN LUNAS
            </div>
            
            <!-- Customer Info -->
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">PELANGGAN:</span>
                    <span>' . htmlspecialchars($data_pembeli['nama_pembeli']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">EMAIL:</span>
                    <span>' . htmlspecialchars($data_pembeli['email_pembeli']) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">TOTAL PENJUAL:</span>
                    <span>' . count($items_by_seller) . ' penjual</span>
                </div>
                <div class="info-row">
                    <span class="info-label">TOTAL ITEMS:</span>
                    <span>' . count($all_items) . ' item</span>
                </div>
                <div class="separator">-----------------------------</div>
            </div>';
            
            // Tampilkan per penjual
            $seller_counter = 1;
            foreach ($items_by_seller as $email_penjual => $items) {
                $seller_info = $sellers_info[$email_penjual];
                $seller_total = $seller_totals[$email_penjual];
                
                $html .= '
                <div class="seller-section">
                    <div class="seller-header">
                        ' . $seller_counter . '. ' . htmlspecialchars($seller_info['nama_penjual']) . '
                    </div>
                    <div class="seller-kode">
                        Kode: ' . $seller_info['kode_pesanan'] . ' | Items: ' . count($items) . '
                    </div>
                    
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>PRODUK</th>
                                <th class="text-right">QTY</th>
                                <th class="text-right">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody>';
                        
                        $item_counter = 1;
                        foreach ($items as $item) {
                            $html .= '
                            <tr>
                                <td>' . $item_counter++ . '</td>
                                <td>' . htmlspecialchars(substr($item['judul_buku'], 0, 20)) . 
                                (strlen($item['judul_buku']) > 20 ? '...' : '') . '</td>
                                <td class="text-right">' . $item['qty'] . '</td>
                                <td class="text-right">' . number_format($item['total_harga'], 0, ',', '.') . '</td>
                            </tr>';
                        }
                        
                        $html .= '
                        </tbody>
                    </table>
                    
                    <div class="seller-summary">
                        <div class="info-row">
                            <span>Subtotal:</span>
                            <span>Rp ' . number_format($seller_total, 0, ',', '.') . '</span>
                        </div>
                    </div>
                </div>';
                
                $seller_counter++;
            }
            
            $html .= '
            <!-- Summary -->
            <div class="summary-section">
                <div class="separator">-----------------------------</div>
                <div class="summary-row">
                    <span>Total Penjual:</span>
                    <span>' . count($items_by_seller) . ' penjual</span>
                </div>
                <div class="summary-row">
                    <span>Total Items:</span>
                    <span>' . count($all_items) . ' item</span>
                </div>
                <div class="summary-row">
                    <span>Metode Bayar:</span>
                    <span>' . htmlspecialchars($metode_bayar) . '</span>
                </div>
                <div class="summary-row total">
                    <span>TOTAL SEMUA:</span>
                    <span>Rp ' . number_format($total_semua_pesanan, 0, ',', '.') . '</span>
                </div>
            </div>';
            
            if ($metode_bayar == 'Transfer') {
                $html .= '
                <div class="payment-info">
                    <div style="text-align: center; font-weight: bold;">INFORMASI TRANSFER</div>
                    <div>Bukti: ' . htmlspecialchars($bukti_pembayaran) . '</div>
                    <div><small>Transfer telah dikonfirmasi sistem</small></div>
                </div>';
            }
            
            $html .= '
            <!-- Verification Code -->
            <div style="text-align: center; margin: 15px 0;">
                <div style="font-family: monospace; letter-spacing: 2px; font-size: 10px;">
                    BUKUBOOK-' . $kode_pesanan . '
                </div>
                <div style="font-size: 8px;">Kode Verifikasi Transaksi</div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <div class="thank-you">TERIMA KASIH TELAH BERBELANJA</div>
                <div class="separator">*****************************</div>
                <div><strong>Catatan:</strong></div>
                <div>1. Struk ini adalah bukti pembayaran sah</div>
                <div>2. Simpan untuk keperluan pengembalian</div>
                <div>3. Hubungi support@bukubook.com untuk bantuan</div>
                <div class="separator">-----------------------------</div>
                <div>Dicetak: ' . $tanggal_cetak . '</div>
                <div>Halaman 1/1</div>
                <div class="separator">=============================</div>
            </div>
        </div>
    </body>
    </html>';
    
    // Setup Dompdf
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'Courier');
    $options->set('chroot', realpath('.'));
    $options->set('defaultPaperSize', array(0,0,300,1000)); // Lebar 80mm (receipt paper)
    $options->set('defaultPaperOrientation', 'portrait');
    
    $dompdf = new Dompdf($options);
    
    // Load HTML
    $dompdf->loadHtml($html);
    
    // Render PDF
    $dompdf->render();
    
    // Buat nama file
    $filename = 'Struk_BukuBook_' . $kode_pesanan . '.pdf';
    
    // Output PDF langsung ke browser untuk download
    $dompdf->stream($filename, [
        'Attachment' => true
    ]);
    
    exit;
}

// Jika bukan download, tampilkan halaman HTML normal
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Struk <?php echo $kode_pesanan; ?></title>
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
            justify-content: space-between;
        }

        .welcome-message {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 30px;
        }

        /* Multi Seller Info */
        .multi-seller-alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
        }

        .multi-seller-alert i {
            font-size: 24px;
            color: #ffc107;
        }

        /* Struk Container */
        .struk-container {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid var(--light-gray);
            max-width: 800px;
            margin: 0 auto;
        }

        /* Struk Header */
        .struk-header {
            text-align: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
        }

        .company-name {
            color: var(--primary);
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-size: 14px;
            color: var(--gray);
            line-height: 1.4;
        }

        .struk-title {
            color: var(--dark);
            font-size: 22px;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
        }

        .struk-number {
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            margin: 10px 0;
            letter-spacing: 1px;
        }

        /* Info Section */
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--light-gray);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #dee2e6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark);
        }

        .info-value {
            color: var(--gray);
            text-align: right;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
            margin: 15px 0;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        /* Seller Section */
        .seller-section {
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            margin: 25px 0;
            overflow: hidden;
            background: white;
        }

        .seller-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seller-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .seller-kode {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-family: 'Courier New', monospace;
        }

        .items-table th {
            background: #f8f9fa;
            color: var(--dark);
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--light-gray);
            font-size: 13px;
        }

        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--light-gray);
            font-size: 13px;
        }

        .items-table tr:hover {
            background: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Product Image */
        .product-image-small {
            width: 40px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 10px;
        }

        /* Seller Summary */
        .seller-summary {
            background: #f8f9fa;
            padding: 15px;
            border-top: 1px solid var(--light-gray);
            border-bottom: 1px solid var(--light-gray);
        }

        .seller-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .seller-total {
            background: var(--success);
            color: white;
            font-weight: bold;
            border-radius: 4px;
            margin-top: 10px;
        }

        /* Summary Section */
        .summary-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            margin-top: 20px;
            font-family: 'Courier New', monospace;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--light-gray);
            font-size: 14px;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: var(--success);
            padding-top: 15px;
            margin-top: 5px;
        }

        /* Bukti Pembayaran */
        .bukti-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px dashed var(--light-gray);
        }

        .bukti-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bukti-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .bukti-image:hover {
            transform: scale(1.05);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
            border: 2px solid var(--success);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
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
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        /* Verification Code */
        .verification-code {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 1px dashed var(--light-gray);
            border-radius: 8px;
            background: white;
            font-family: 'Courier New', monospace;
        }

        .code-text {
            font-size: 16px;
            letter-spacing: 3px;
            margin: 10px 0;
            color: var(--dark);
            font-weight: bold;
        }

        /* Footer */
        .struk-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--light-gray);
            text-align: center;
            font-size: 12px;
            color: var(--gray);
        }

        .thank-you {
            font-weight: bold;
            font-size: 16px;
            margin: 15px 0;
            color: var(--success);
        }

        /* Notes */
        .notes {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
        }

        .notes ul {
            list-style: none;
            padding-left: 0;
        }

        .notes li {
            padding: 5px 0;
            color: var(--gray);
            position: relative;
            padding-left: 20px;
        }

        .notes li:before {
            content: "•";
            color: var(--primary);
            font-size: 16px;
            position: absolute;
            left: 0;
            top: 2px;
        }

        /* Separator */
        .separator {
            text-align: center;
            margin: 15px 0;
            color: #ccc;
            letter-spacing: 3px;
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
            
            .struk-container {
                padding: 15px;
            }
            
            .items-table {
                display: block;
                overflow-x: auto;
            }
            
            .bottombar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
                gap: 10px;
                height: auto;
            }
            
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
        
        /* Print Styles */
        @media print {
            .sidebar, .topbar, .bottombar, .action-buttons {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .content {
                padding: 0 !important;
            }
            
            .struk-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                padding: 10px !important;
            }
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
            <div class="page-title">
                <div>Struk Pembelian</div>
                <div class="struk-number"><?php echo $kode_pesanan; ?></div>
            </div>
            
            <?php if ($is_multi == 'true'): ?>
            <div class="multi-seller-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>PESANAN DARI <?php echo count($items_by_seller); ?> PENJUAL BERBEDA</strong><br>
                    Anda membeli dari <?php echo count($items_by_seller); ?> penjual yang berbeda. Setiap penjual memiliki kode pesanan sendiri.
                </div>
            </div>
            <?php endif; ?>
            
            <p class="welcome-message">
                Struk pembelian Anda. Struk ini dapat diunduh sebagai bukti pembayaran.
            </p>

            <div class="struk-container">
                <!-- Struk Header -->
                <div class="struk-header">
                    <div class="company-name">BUKUBOOK</div>
                    <div class="company-tagline">Platform Jual Beli Buku Online</div>
                    <div class="company-tagline">support@bukubook.com | www.bukubook.com</div>
                    <div class="separator">═══════════════════════════</div>
                    <div class="struk-title">STRUK PEMBAYARAN</div>
                    <div class="struk-number"><?php echo $kode_pesanan; ?></div>
                    <div>Tanggal: <?php echo $tanggal_format; ?></div>
                    <div>Waktu: <?php echo $waktu_format; ?></div>
                    <div class="separator">═══════════════════════════</div>
                </div>

                <!-- Status -->
                <div class="status-badge status-success">
                    <i class="fas fa-check-circle"></i> PEMBAYARAN LUNAS
                </div>

                <!-- Customer Info -->
                <div class="info-section">
                    <div class="info-row">
                        <span class="info-label">PELANGGAN:</span>
                        <span class="info-value"><?php echo htmlspecialchars($data_pembeli['nama_pembeli']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">EMAIL:</span>
                        <span class="info-value"><?php echo htmlspecialchars($data_pembeli['email_pembeli']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">TOTAL PENJUAL:</span>
                        <span class="info-value"><?php echo count($items_by_seller); ?> penjual</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">TOTAL ITEMS:</span>
                        <span class="info-value"><?php echo count($all_items); ?> item</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">METODE BAYAR:</span>
                        <span class="info-value"><?php echo htmlspecialchars($metode_bayar); ?></span>
                    </div>
                    <div class="separator">─────────────────────────────</div>
                </div>

                <!-- Tampilkan per Penjual -->
                <?php $seller_counter = 1; ?>
                <?php foreach ($items_by_seller as $email_penjual => $items): 
                    $seller_info = $sellers_info[$email_penjual];
                    $seller_total = $seller_totals[$email_penjual];
                ?>
                    <div class="seller-section">
                        <div class="seller-header">
                            <div class="seller-title">
                                <i class="fas fa-store"></i>
                                <?php echo $seller_counter; ?>. <?php echo htmlspecialchars($seller_info['nama_penjual']); ?>
                            </div>
                            <div class="seller-kode">
                                Kode Pesanan: <?php echo $seller_info['kode_pesanan']; ?>
                            </div>
                        </div>
                        
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th width="5%">NO</th>
                                    <th width="45%">PRODUK</th>
                                    <th width="15%" class="text-right">HARGA</th>
                                    <th width="10%" class="text-center">QTY</th>
                                    <th width="15%" class="text-right">SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $item_counter = 1; ?>
                                <?php foreach ($items as $item): 
                                    $foto_buku = !empty($item['foto_buku']) ? "../../Src/uploads/produk/" . $item['foto_buku'] : "https://via.placeholder.com/40x60/cccccc/666666?text=Buku";
                                ?>
                                    <tr>
                                        <td><?php echo $item_counter++; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div class="product-image-small">
                                                    <img src="<?php echo $foto_buku; ?>" 
                                                         alt="<?php echo htmlspecialchars($item['judul_buku']); ?>"
                                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 3px;"
                                                         onerror="this.src='https://via.placeholder.com/40x60/cccccc/666666?text=Buku'">
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars(substr($item['judul_buku'], 0, 25)); ?></strong><br>
                                                    <small style="color: var(--gray); font-size: 11px;">
                                                        Kategori: <?php echo htmlspecialchars($item['kategori_buku']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right"><?php echo formatRupiah($item['harga_satuan']); ?></td>
                                        <td class="text-center"><?php echo $item['qty']; ?></td>
                                        <td class="text-right"><?php echo formatRupiah($item['total_harga']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="seller-summary">
                            <div class="seller-summary-row">
                                <span>Total Items dari <?php echo htmlspecialchars($seller_info['nama_penjual']); ?>:</span>
                                <span><?php echo count($items); ?> item</span>
                            </div>
                            <div class="seller-summary-row seller-total" style="padding: 10px; background: #d4edda; color: #155724;">
                                <span>Subtotal untuk <?php echo htmlspecialchars($seller_info['nama_penjual']); ?>:</span>
                                <span><?php echo formatRupiah($seller_total); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php $seller_counter++; ?>
                <?php endforeach; ?>

                <!-- Summary Section -->
                <div class="summary-section">
                    <div class="summary-row">
                        <span>Total Penjual:</span>
                        <span><?php echo count($items_by_seller); ?> penjual</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Items:</span>
                        <span><?php echo count($all_items); ?> item</span>
                    </div>
                    <div class="summary-row">
                        <span>Metode Bayar:</span>
                        <span><?php echo htmlspecialchars($metode_bayar); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>TOTAL SEMUA PEMBAYARAN:</span>
                        <span style="font-weight: bold; color: var(--success);"><?php echo formatRupiah($total_semua_pesanan); ?></span>
                    </div>
                    <div class="separator">═══════════════════════════</div>
                </div>

                <!-- Bukti Pembayaran -->
                <?php if ($metode_bayar == 'Transfer'): ?>
                <div class="bukti-section">
                    <div class="bukti-title">
                        <i class="fas fa-receipt"></i> Bukti Pembayaran
                    </div>
                    <?php 
                    $bukti_path = "../../Src/uploads/bukti_pembayaran/" . $bukti_pembayaran;
                    if (file_exists($bukti_path) && is_file($bukti_path)): 
                        $file_ext = strtolower(pathinfo($bukti_pembayaran, PATHINFO_EXTENSION));
                        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                    ?>
                        <div style="text-align: center;">
                            <img src="<?php echo $bukti_path; ?>" 
                                 alt="Bukti Pembayaran" 
                                 class="bukti-image"
                                 onclick="window.open('<?php echo $bukti_path; ?>', '_blank')">
                        </div>
                    <?php else: ?>
                        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center;">
                            <i class="fas fa-file-pdf" style="font-size: 48px; color: var(--danger); margin-bottom: 10px;"></i><br>
                            <strong><?php echo htmlspecialchars($bukti_pembayaran); ?></strong><br>
                            <small style="color: var(--gray);">File PDF - Silahkan download untuk melihat</small>
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Verification Code -->
                <div class="verification-code">
                    <div style="font-weight: bold; margin-bottom: 10px;">KODE VERIFIKASI TRANSAKSI</div>
                    <div class="code-text">BUKUBOOK-<?php echo $kode_pesanan; ?></div>
                    <div style="height: 15px; background: repeating-linear-gradient(90deg, #333, #333 2px, transparent 2px, transparent 4px); margin: 10px 0;"></div>
                    <small style="color: var(--gray);">Gunakan kode ini untuk verifikasi transaksi</small>
                </div>

                <!-- Thank You Message -->
                <div class="struk-footer">
                    <div class="thank-you">
                        <i class="fas fa-heart" style="color: #e74c3c;"></i> TERIMA KASIH TELAH BERBELANJA <i class="fas fa-heart" style="color: #e74c3c;"></i>
                    </div>
                    <div class="separator">═══════════════════════════</div>
                    
                    <!-- Notes -->
                    <div class="notes">
                        <strong>CATATAN PENTING:</strong>
                        <ul>
                            <li>Struk ini adalah bukti pembayaran yang sah</li>
                            <li>Simpan struk ini untuk keperluan pengembalian barang</li>
                            <li>Untuk pertanyaan hubungi: support@bukubook.com</li>
                            <li>Struk dicetak otomatis oleh sistem BukuBook</li>
                        </ul>
                    </div>
                    
                    <div class="separator">═══════════════════════════</div>
                    <div>Dicetak pada: <?php echo $tanggal_cetak; ?></div>
                    <div>Halaman 1/1</div>
                    <div class="separator">═══════════════════════════</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="invoice.php?kode_pesanan=<?php echo $kode_pesanan; ?>&multi=<?php echo $is_multi; ?>&download=true" 
                   class="btn btn-primary">
                    <i class="fas fa-download"></i> Download Struk (PDF)
                </a>
                <a href="pesanan.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
                </a>
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Struk
                </button>
            </div>
            
            <!-- Info Kode Pesanan Lain -->
            <?php if ($is_multi == 'true' && count($all_kode_pesanan) > 1): ?>
            <div class="multi-seller-alert" style="margin-top: 30px;">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>KODE PESANAN LAIN DARI CHECKOUT INI:</strong><br>
                    <?php foreach ($all_kode_pesanan as $index => $kp): ?>
                        <?php if ($kp != $kode_pesanan): ?>
                            <a href="invoice.php?kode_pesanan=<?php echo $kp; ?>&multi=true" 
                               style="color: var(--primary); text-decoration: none; margin-right: 10px;">
                                <i class="fas fa-receipt"></i> <?php echo $kp; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <a href="pesanan.php" class="breadcrumb-item">Pesanan Saya</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Struk <?php echo $kode_pesanan; ?></span>
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

        // Print struk
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a print parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'true') {
                window.print();
            }
        });

        // Auto print after download if needed
        const printAfterDownload = <?php echo isset($_GET['print']) && $_GET['print'] == 'true' ? 'true' : 'false'; ?>;
        if (printAfterDownload) {
            window.addEventListener('afterprint', function() {
                // Remove print parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('print');
                window.history.replaceState({}, '', url);
            });
        }
    </script>
</body>
</html>