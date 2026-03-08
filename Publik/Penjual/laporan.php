<!-- composer require dompdf/dompdf -->

<?php
session_start();
require '../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
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

// Filter tanggal jika ada
$filter_tanggal = "";
$filter_tahun = "";
$filter_bulan = "";
$filter_params = []; // Untuk menyimpan parameter filter

if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
    $tahun = mysqli_real_escape_string($conn, $_GET['tahun']);
    $filter_tahun = "AND YEAR(p.tanggal_pesanan) = '$tahun'";
    $filter_tanggal .= $filter_tahun;
    $filter_params['tahun'] = $tahun;
}

if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
    $bulan = mysqli_real_escape_string($conn, $_GET['bulan']);
    $filter_bulan = "AND MONTH(p.tanggal_pesanan) = '$bulan'";
    $filter_tanggal .= $filter_bulan;
    $filter_params['bulan'] = $bulan;
}

if (isset($_GET['tanggal_mulai']) && isset($_GET['tanggal_selesai'])) {
    $tanggal_mulai = mysqli_real_escape_string($conn, $_GET['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($conn, $_GET['tanggal_selesai']);
    
    if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
        $filter_tanggal = "AND p.tanggal_pesanan BETWEEN '$tanggal_mulai' AND '$tanggal_selesai 23:59:59'";
        $filter_params['tanggal_mulai'] = $tanggal_mulai;
        $filter_params['tanggal_selesai'] = $tanggal_selesai;
    }
}

// Query untuk mendapatkan data laporan dengan perhitungan keuntungan
$query = "
    SELECT 
        p.judul_buku,
        p.qty,
        p.bukti_pembayaran,
        p.metode_bayar,
        p.total_harga,
        pb.modal,
        pb.harga_buku,
        (p.qty * pb.modal) as total_modal,
        (p.qty * pb.harga_buku) as total_penjualan,
        ((p.qty * pb.harga_buku) - (p.qty * pb.modal)) as total_keuntungan,
        p.tanggal_pesanan,
        p.status,
        p.approve
    FROM pesanan p
    LEFT JOIN produk_buku pb ON p.judul_buku = pb.judul_buku AND p.email_penjual = pb.email_penjual
    WHERE p.email_penjual = '$email_penjual' 
    AND p.approve = 'Disetujui'
    AND p.status = 'Diterima'
    $filter_tanggal
    ORDER BY p.tanggal_pesanan DESC
";

$result = $conn->query($query);

// Query untuk statistik
$stat_query = "
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(p.qty) as total_buku_terjual,
        SUM(p.qty * pb.harga_buku) as total_penjualan,
        SUM(p.qty * pb.modal) as total_modal,
        SUM((p.qty * pb.harga_buku) - (p.qty * pb.modal)) as total_keuntungan,
        AVG((p.qty * pb.harga_buku) - (p.qty * pb.modal)) as rata_keuntungan
    FROM pesanan p
    LEFT JOIN produk_buku pb ON p.judul_buku = pb.judul_buku AND p.email_penjual = pb.email_penjual
    WHERE p.email_penjual = '$email_penjual' 
    AND p.approve = 'Disetujui'
    AND p.status = 'Diterima'
    $filter_tanggal
";

$stat_result = $conn->query($stat_query);
$stats = $stat_result->fetch_assoc();

// Query untuk grafik bulanan dengan filter yang SAMA
$grafik_query = "
    SELECT 
        MONTH(p.tanggal_pesanan) as bulan,
        YEAR(p.tanggal_pesanan) as tahun,
        COUNT(DISTINCT p.kode_pesanan) as jumlah_transaksi,
        SUM(p.qty) as total_buku,
        SUM(p.qty * pb.harga_buku) as penjualan,
        SUM(p.qty * pb.modal) as modal,
        SUM((p.qty * pb.harga_buku) - (p.qty * pb.modal)) as keuntungan,
        AVG((p.qty * pb.harga_buku) - (p.qty * pb.modal)) as rata_keuntungan_per_transaksi
    FROM pesanan p
    LEFT JOIN produk_buku pb ON p.judul_buku = pb.judul_buku AND p.email_penjual = pb.email_penjual
    WHERE p.email_penjual = '$email_penjual' 
    AND p.approve = 'Disetujui'
    AND p.status = 'Diterima'
    $filter_tanggal
    GROUP BY YEAR(p.tanggal_pesanan), MONTH(p.tanggal_pesanan)
    ORDER BY tahun, bulan
";

$grafik_result = $conn->query($grafik_query);

// Data untuk grafik
$labels = [];
$transaksi_data = [];
$buku_data = [];
$penjualan_data = [];
$modal_data = [];
$keuntungan_data = [];

while ($row = $grafik_result->fetch_assoc()) {
    $bulan = $row['bulan'];
    $tahun = $row['tahun'];
    $nama_bulan = date("M", mktime(0, 0, 0, $bulan, 1));
    
    $labels[] = "$nama_bulan $tahun";
    $transaksi_data[] = (int)($row['jumlah_transaksi'] ?? 0);
    $buku_data[] = (int)($row['total_buku'] ?? 0);
    $penjualan_data[] = (float)($row['penjualan'] ?? 0);
    $modal_data[] = (float)($row['modal'] ?? 0);
    $keuntungan_data[] = (float)($row['keuntungan'] ?? 0);
}

// Query untuk mendapatkan tahun-tahun yang tersedia (untuk filter dropdown)
$tahun_query = "
    SELECT DISTINCT YEAR(tanggal_pesanan) as tahun 
    FROM pesanan 
    WHERE email_penjual = '$email_penjual' 
    AND approve = 'Disetujui'
    AND status = 'Diterima'
    ORDER BY tahun DESC
";
$tahun_result = $conn->query($tahun_query);

// Fungsi untuk download PDF
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    // Bersihkan semua output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Tentukan tipe download
    $download_type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    // Fungsi untuk format Rupiah
    function formatRupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    // Ambil semua data untuk PDF (gunakan query yang sama dengan filter)
    $pdf_result = $conn->query($query);
    
    // Hitung total
    $total_qty = 0;
    $total_modal_all = 0;
    $total_penjualan_all = 0;
    $total_keuntungan_all = 0;
    
    $items = [];
    while ($row = $pdf_result->fetch_assoc()) {
        $items[] = $row;
        $total_qty += $row['qty'];
        $total_modal_all += $row['total_modal'];
        $total_penjualan_all += $row['total_penjualan'];
        $total_keuntungan_all += $row['total_keuntungan'];
    }

    // Buat judul filter untuk PDF
    $filter_text = "";
    if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
        $filter_text .= "Tahun: " . $_GET['tahun'] . " ";
    }
    if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
        $bulan_list = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $filter_text .= "Bulan: " . $bulan_list[$_GET['bulan']] . " ";
    }
    if (isset($_GET['tanggal_mulai']) && isset($_GET['tanggal_selesai']) && 
        !empty($_GET['tanggal_mulai']) && !empty($_GET['tanggal_selesai'])) {
        $filter_text .= "Periode: " . $_GET['tanggal_mulai'] . " s/d " . $_GET['tanggal_selesai'];
    }
    
    if (empty($filter_text)) {
        $filter_text = "Semua Data";
    }

    // Query ulang untuk data grafik di PDF dengan filter yang sama
    $grafik_result_pdf = $conn->query($grafik_query);
    
    // Data grafik untuk PDF
    $labels_pdf = [];
    $transaksi_data_pdf = [];
    $buku_data_pdf = [];
    $penjualan_data_pdf = [];
    $modal_data_pdf = [];
    $keuntungan_data_pdf = [];
    $total_transaksi_grafik = 0;
    $total_buku_grafik = 0;
    $total_penjualan_grafik = 0;
    $total_modal_grafik = 0;
    $total_keuntungan_grafik = 0;
    
    while ($grafik = $grafik_result_pdf->fetch_assoc()) {
        $labels_pdf[] = date("F Y", mktime(0, 0, 0, $grafik['bulan'], 1, $grafik['tahun']));
        $transaksi_data_pdf[] = (int)($grafik['jumlah_transaksi'] ?? 0);
        $buku_data_pdf[] = (int)($grafik['total_buku'] ?? 0);
        $penjualan_data_pdf[] = (float)($grafik['penjualan'] ?? 0);
        $modal_data_pdf[] = (float)($grafik['modal'] ?? 0);
        $keuntungan_data_pdf[] = (float)($grafik['keuntungan'] ?? 0);
        
        $total_transaksi_grafik += $grafik['jumlah_transaksi'] ?? 0;
        $total_buku_grafik += $grafik['total_buku'] ?? 0;
        $total_penjualan_grafik += $grafik['penjualan'] ?? 0;
        $total_modal_grafik += $grafik['modal'] ?? 0;
        $total_keuntungan_grafik += $grafik['keuntungan'] ?? 0;
    }

    // Generate HTML untuk PDF berdasarkan tipe
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Laporan Penjualan - ' . htmlspecialchars($data_penjual['nama_penjual']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                color: #333;
                line-height: 1.2;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #4361ee;
                padding-bottom: 20px;
            }
            .company-name {
                color: #4361ee;
                font-size: 28px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .report-title {
                font-size: 22px;
                font-weight: bold;
                margin: 20px 0;
                text-transform: uppercase;
            }
            .info-section {
                margin: 20px 0;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 5px;
            }
            .info-row {
                margin-bottom: 8px;
            }
            .info-label {
                font-weight: bold;
                width: 200px;
                display: inline-block;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin: 20px 0;
            }
            .stat-card {
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 15px;
                text-align: center;
            }
            .stat-value {
                font-size: 20px;
                font-weight: bold;
                color: #4361ee;
                margin: 10px 0;
            }
            .stat-label {
                font-size: 14px;
                color: #666;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                font-size: 11px;
            }
            .table th {
                background: #4361ee;
                color: white;
                padding: 10px;
                text-align: left;
                border: 1px solid #ddd;
            }
            .table td {
                padding: 8px 10px;
                border: 1px solid #ddd;
            }
            .table tr:nth-child(even) {
                background: #f8f9fa;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .total-row {
                font-weight: bold;
                background: #e9ecef !important;
            }
            .profit-positive {
                color: #28a745;
            }
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
            .page-break {
                page-break-before: always;
            }
            .chart-container {
                margin: 20px 0;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background: white;
            }
            .chart-title {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 15px;
                color: #4361ee;
                border-bottom: 1px solid #eee;
                padding-bottom: 8px;
            }
            .chart-summary {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-top: 15px;
                font-size: 12px;
            }
            .chart-summary-item {
                padding: 8px;
                background: #f8f9fa;
                border-radius: 3px;
            }
            .diagram-box {
                border: 2px dashed #4361ee;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .diagram-title {
                font-size: 18px;
                font-weight: bold;
                color: #4361ee;
                margin-bottom: 15px;
            }
            .diagram-note {
                font-size: 12px;
                color: #666;
                margin-top: 10px;
                font-style: italic;
            }
            .bar-chart {
                margin: 20px 0;
                padding: 15px;
                background: white;
            }
            .bar-container {
                display: flex;
                align-items: flex-end;
                justify-content: space-around;
                height: 250px;
                margin: 20px 0;
                border-bottom: 2px solid #333;
                border-left: 2px solid #333;
                padding-left: 20px;
            }
            .bar-group {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 80px;
            }
            .bar-wrapper {
                display: flex;
                justify-content: center;
                gap: 5px;
                height: 200px;
                align-items: flex-end;
            }
            .bar {
                width: 30px;
                background-color: #4361ee;
                transition: height 0.3s;
                position: relative;
            }
            .bar-label {
                margin-top: 10px;
                font-size: 10px;
                transform: rotate(-45deg);
                white-space: nowrap;
            }
            .bar-value {
                position: absolute;
                top: -20px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 9px;
                white-space: nowrap;
            }
            @page {
                margin: 15mm;
            }
        </style>
    </head>
    <body>';
    
    // Header untuk semua tipe
    $html .= '
        <div class="header">
            <div class="company-name">BUKUBOOK</div>
            <div>Platform Jual Beli Buku Online</div>
            <div style="margin-top: 10px; color: #666;">www.bukubook.com | support@bukubook.com</div>
            <div class="report-title">LAPORAN PENJUALAN</div>
        </div>
        
        <!-- Info Penjual dan Filter -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Nama Penjual:</span>
                <span>' . htmlspecialchars($data_penjual['nama_penjual']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span>' . htmlspecialchars($data_penjual['email_penjual']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Filter:</span>
                <span>' . htmlspecialchars($filter_text) . '</span>
            </div>
        </div>';
    
    // Statistik
    $html .= '
        <!-- Statistik -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">' . number_format($stats['total_transaksi'] ?? 0, 0, ',', '.') . '</div>
                <div class="stat-label">Total Transaksi</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">' . number_format($stats['total_buku_terjual'] ?? 0, 0, ',', '.') . '</div>
                <div class="stat-label">Total Buku Terjual</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Rp ' . number_format($stats['total_penjualan'] ?? 0, 0, ',', '.') . '</div>
                <div class="stat-label">Total Penjualan</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Rp ' . number_format($stats['total_modal'] ?? 0, 0, ',', '.') . '</div>
                <div class="stat-label">Total Modal</div>
            </div>
            <div class="stat-card">
                <div class="stat-value profit-positive">Rp ' . number_format($stats['total_keuntungan'] ?? 0, 0, ',', '.') . '</div>
                <div class="stat-label">Total Keuntungan</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Rp ' . number_format($stats['rata_keuntungan'] ?? 0, 0, ',', '.') . '</div>
                <div class="stat-label">Rata-rata Keuntungan</div>
            </div>
        </div>';
    
    // Tabel detail (jika table atau all)
    if ($download_type == 'table' || $download_type == 'all') {
        if (count($items) > 0) {
            $html .= '
            <!-- Tabel Detail Transaksi -->
            <div class="page-break">
                <h3 style="margin: 30px 0 10px 0; color: #4361ee;">DETAIL TRANSAKSI</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Buku</th>
                            <th>Qty</th>
                            <th>Metode Bayar</th>
                            <th class="text-right">Harga Satuan</th>
                            <th class="text-right">Modal Satuan</th>
                            <th class="text-right">Total Modal</th>
                            <th class="text-right">Total Penjualan</th>
                            <th class="text-right">Keuntungan</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            $counter = 1;
            foreach ($items as $item) {
                $keuntungan_class = $item['total_keuntungan'] >= 0 ? 'profit-positive' : '';
                $html .= '
                <tr>
                    <td class="text-center">' . $counter++ . '</td>
                    <td>' . htmlspecialchars(substr($item['judul_buku'], 0, 30)) . 
                        (strlen($item['judul_buku']) > 30 ? '...' : '') . '</td>
                    <td class="text-center">' . number_format($item['qty'], 0, ',', '.') . '</td>
                    <td>' . htmlspecialchars($item['metode_bayar']) . '</td>
                    <td class="text-right">' . formatRupiah($item['harga_buku']) . '</td>
                    <td class="text-right">' . formatRupiah($item['modal']) . '</td>
                    <td class="text-right">' . formatRupiah($item['total_modal']) . '</td>
                    <td class="text-right">' . formatRupiah($item['total_penjualan']) . '</td>
                    <td class="text-right ' . $keuntungan_class . '">' . formatRupiah($item['total_keuntungan']) . '</td>
                    <td>' . date('d-m-Y H:i', strtotime($item['tanggal_pesanan'])) . '</td>
                </tr>';
            }
            
            $html .= '
                    <!-- Total Row -->
                    <tr class="total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <td class="text-center"><strong>' . number_format($total_qty, 0, ',', '.') . '</strong></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right"><strong>' . formatRupiah($total_modal_all) . '</strong></td>
                        <td class="text-right"><strong>' . formatRupiah($total_penjualan_all) . '</strong></td>
                        <td class="text-right profit-positive"><strong>' . formatRupiah($total_keuntungan_all) . '</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            </div>';
        } else {
            $html .= '
            <div style="text-align: center; padding: 40px; color: #666;">
                <h3>Tidak Ada Data Transaksi</h3>
                <p>Belum ada transaksi dalam periode yang dipilih.</p>
            </div>';
        }
    }
    
    // Diagram/chart (jika diagram atau all)
    // if ($download_type == 'diagram' || $download_type == 'all') {
    //     $html .= '
    //     <div class="page-break">
    //         <h3 style="margin: 30px 0 20px 0; color: #4361ee;">ANALISIS DATA & DIAGRAM</h3>';
            
    //     if (!empty($labels_pdf)) {
    //         // Diagram 1: Performa Bulanan (Bar Chart Manual)
    //         $html .= '
    //         <!-- Diagram 1: Performa Bulanan -->
    //         <div class="diagram-box">
    //             <div class="diagram-title">📈 DIAGRAM 1: PERFORMA BULANAN</div>
    //             <div style="margin: 15px 0;">
    //                 <p><strong>Keterangan Diagram:</strong> Grafik batang menunjukkan Penjualan, Modal, dan Keuntungan per bulan</p>
    //                 <p><strong>Warna:</strong> Biru = Penjualan | Kuning = Modal | Hijau = Keuntungan</p>
    //             </div>
                
    //             <!-- Bar Chart Manual -->
    //             <div class="bar-chart">
    //                 <div class="bar-container">';
            
    //         // Cari nilai maksimum untuk skala
    //         $max_value = max(
    //             max($penjualan_data_pdf),
    //             max($modal_data_pdf),
    //             max($keuntungan_data_pdf)
    //         );
            
    //         foreach ($labels_pdf as $index => $label) {
    //             $height_penjualan = $max_value > 0 ? ($penjualan_data_pdf[$index] / $max_value) * 180 : 0;
    //             $height_modal = $max_value > 0 ? ($modal_data_pdf[$index] / $max_value) * 180 : 0;
    //             $height_keuntungan = $max_value > 0 ? ($keuntungan_data_pdf[$index] / $max_value) * 180 : 0;
                
    //             $html .= '
    //                     <div class="bar-group">
    //                         <div class="bar-wrapper">
    //                             <div class="bar" style="height: ' . $height_penjualan . 'px; background-color: #4361ee;">
    //                                 <span class="bar-value">' . number_format($penjualan_data_pdf[$index] / 1000000, 1) . 'jt</span>
    //                             </div>
    //                             <div class="bar" style="height: ' . $height_modal . 'px; background-color: #ffc107;">
    //                                 <span class="bar-value">' . number_format($modal_data_pdf[$index] / 1000000, 1) . 'jt</span>
    //                             </div>
    //                             <div class="bar" style="height: ' . $height_keuntungan . 'px; background-color: #28a745;">
    //                                 <span class="bar-value">' . number_format($keuntungan_data_pdf[$index] / 1000000, 1) . 'jt</span>
    //                             </div>
    //                         </div>
    //                         <div class="bar-label">' . date('M Y', strtotime($label)) . '</div>
    //                     </div>';
    //         }
            
    //         $html .= '
    //                 </div>
    //             </div>
                
    //             <div class="chart-summary">
    //                 <div class="chart-summary-item">
    //                     <span>Periode Analisis:</span>
    //                     <strong>' . htmlspecialchars($filter_text) . '</strong>
    //                 </div>
    //                 <div class="chart-summary-item">
    //                     <span>Total Bulan:</span>
    //                     <strong>' . count($labels_pdf) . ' bulan</strong>
    //                 </div>
    //             </div>
    //             <div class="diagram-note">* Diagram menunjukkan trend performa penjualan dari waktu ke waktu</div>
    //         </div>
            
    //         <!-- Data Bulanan untuk Diagram 1 -->
    //         <div class="chart-container">
    //             <div class="chart-title">DATA PERFORMA BULANAN</div>
    //             <table class="table">
    //                 <thead>
    //                     <tr>
    //                         <th>Bulan/Tahun</th>
    //                         <th class="text-center">Transaksi</th>
    //                         <th class="text-center">Buku Terjual</th>
    //                         <th class="text-right">Total Penjualan</th>
    //                         <th class="text-right">Total Modal</th>
    //                         <th class="text-right">Keuntungan</th>
    //                     </tr>
    //                 </thead>
    //                 <tbody>';
            
    //         foreach ($labels_pdf as $index => $label) {
    //             $keuntungan_class = $keuntungan_data_pdf[$index] >= 0 ? 'profit-positive' : '';
                
    //             $html .= '
    //             <tr>
    //                 <td>' . $label . '</td>
    //                 <td class="text-center">' . number_format($transaksi_data_pdf[$index] ?? 0, 0, ',', '.') . '</td>
    //                 <td class="text-center">' . number_format($buku_data_pdf[$index] ?? 0, 0, ',', '.') . '</td>
    //                 <td class="text-right">' . formatRupiah($penjualan_data_pdf[$index] ?? 0) . '</td>
    //                 <td class="text-right">' . formatRupiah($modal_data_pdf[$index] ?? 0) . '</td>
    //                 <td class="text-right ' . $keuntungan_class . '">' . formatRupiah($keuntungan_data_pdf[$index] ?? 0) . '</td>
    //             </tr>';
    //         }
            
    //         $keuntungan_total_class = $total_keuntungan_grafik >= 0 ? 'profit-positive' : '';
            
    //         $html .= '
    //             <tr class="total-row">
    //                 <td><strong>GRAND TOTAL</strong></td>
    //                 <td class="text-center"><strong>' . number_format($total_transaksi_grafik, 0, ',', '.') . '</strong></td>
    //                 <td class="text-center"><strong>' . number_format($total_buku_grafik, 0, ',', '.') . '</strong></td>
    //                 <td class="text-right"><strong>' . formatRupiah($total_penjualan_grafik) . '</strong></td>
    //                 <td class="text-right"><strong>' . formatRupiah($total_modal_grafik) . '</strong></td>
    //                 <td class="text-right ' . $keuntungan_total_class . '"><strong>' . formatRupiah($total_keuntungan_grafik) . '</strong></td>
    //             </tr>
    //         </tbody>
    //     </table>
    //     </div>
        
    //     <!-- Diagram 2: Ringkasan Performa -->
    //     <div class="diagram-box">
    //         <div class="diagram-title">📊 DIAGRAM 2: RINGKASAN PERFORMA</div>
    //         <div style="margin: 15px 0;">
    //             <p><strong>Keterangan Diagram:</strong> Perbandingan jumlah Transaksi vs Buku Terjual per bulan</p>
    //             <p><strong>Warna:</strong> Biru = Jumlah Transaksi | Hijau = Buku Terjual</p>
    //         </div>
            
    //         <!-- Bar Chart Ringkasan -->
    //         <div class="bar-chart">
    //             <div class="bar-container">';
            
    //         // Cari nilai maksimum untuk skala ringkasan
    //         $max_summary = max(
    //             max($transaksi_data_pdf),
    //             max($buku_data_pdf)
    //         );
            
    //         foreach ($labels_pdf as $index => $label) {
    //             $height_transaksi = $max_summary > 0 ? ($transaksi_data_pdf[$index] / $max_summary) * 180 : 0;
    //             $height_buku = $max_summary > 0 ? ($buku_data_pdf[$index] / $max_summary) * 180 : 0;
                
    //             $html .= '
    //                     <div class="bar-group">
    //                         <div class="bar-wrapper">
    //                             <div class="bar" style="height: ' . $height_transaksi . 'px; background-color: #4361ee;">
    //                                 <span class="bar-value">' . number_format($transaksi_data_pdf[$index]) . '</span>
    //                             </div>
    //                             <div class="bar" style="height: ' . $height_buku . 'px; background-color: #28a745;">
    //                                 <span class="bar-value">' . number_format($buku_data_pdf[$index]) . '</span>
    //                             </div>
    //                         </div>
    //                         <div class="bar-label">' . date('M Y', strtotime($label)) . '</div>
    //                     </div>';
    //         }
            
    //         $html .= '
    //                 </div>
    //             </div>
            
    //         <div class="chart-summary">
    //             <div class="chart-summary-item">
    //                 <span>Rasio Buku/Transaksi:</span>
    //                 <strong>' . ($total_transaksi_grafik > 0 ? number_format(($total_buku_grafik / $total_transaksi_grafik), 1) : '0') . ' buku/transaksi</strong>
    //             </div>
    //             <div class="chart-summary-item">
    //                 <span>Keuntungan/Bulan (Rata-rata):</span>
    //                 <strong class="profit-positive">' . formatRupiah($total_keuntungan_grafik / max(1, count($labels_pdf))) . '</strong>
    //             </div>
    //         </div>
    //         <div class="diagram-note">* Diagram menunjukkan hubungan antara jumlah transaksi dengan volume penjualan</div>
    //     </div>';
        
    //     } else {
    //         $html .= '
    //         <div style="text-align: center; padding: 40px; color: #666;">
    //             <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: 20px;"></i>
    //             <h3>Tidak Ada Data Grafik</h3>
    //             <p>Tidak ada data untuk ditampilkan dalam grafik pada periode yang dipilih.</p>
    //         </div>';
    //     }
        
    //     // Statistik Lanjutan untuk Analisis
    //     if (!empty($labels_pdf)) {
    //         $html .= '
    //         <!-- Statistik Lanjutan -->
    //         <div class="chart-container">
    //             <div class="chart-title">STATISTIK ANALISIS LANJUTAN</div>
    //             <div class="chart-summary" style="grid-template-columns: repeat(3, 1fr);">
    //                 <div class="chart-summary-item">
    //                     <span>Bulan dengan Penjualan Tertinggi:</span>
    //                     <strong>';
            
    //         if (!empty($penjualan_data_pdf)) {
    //             $max_penjualan = max($penjualan_data_pdf);
    //             $max_index = array_search($max_penjualan, $penjualan_data_pdf);
    //             $html .= formatRupiah($max_penjualan) . '<br><small>(' . ($labels_pdf[$max_index] ?? '-') . ')</small>';
    //         } else {
    //             $html .= '-';
    //         }
            
    //         $html .= '</strong>
    //                 </div>
    //                 <div class="chart-summary-item">
    //                     <span>Bulan dengan Keuntungan Tertinggi:</span>
    //                     <strong class="profit-positive">';
            
    //         if (!empty($keuntungan_data_pdf)) {
    //             $max_keuntungan = max($keuntungan_data_pdf);
    //             $max_index = array_search($max_keuntungan, $keuntungan_data_pdf);
    //             $html .= formatRupiah($max_keuntungan) . '<br><small>(' . ($labels_pdf[$max_index] ?? '-') . ')</small>';
    //         } else {
    //             $html .= '-';
    //         }
            
    //         $html .= '</strong>
    //                 </div>
    //                 <div class="chart-summary-item">
    //                     <span>Tren Keuntungan:</span>
    //                     <strong>';
            
    //         if (count($keuntungan_data_pdf) >= 2) {
    //             $last_month = end($keuntungan_data_pdf);
    //             $second_last = prev($keuntungan_data_pdf);
    //             if ($second_last > 0) {
    //                 $trend = (($last_month - $second_last) / $second_last) * 100;
    //                 if ($trend > 0) {
    //                     $html .= '<span class="profit-positive">↑ ' . number_format(abs($trend), 1) . '%</span>';
    //                 } else {
    //                     $html .= '<span class="profit-negative">↓ ' . number_format(abs($trend), 1) . '%</span>';
    //                 }
    //             } else {
    //                 $html .= 'Stabil';
    //             }
    //         } else {
    //             $html .= 'Data Tidak Cukup';
    //         }
            
    //         $html .= '</strong>
    //                 </div>
    //             </div>
    //         </div>
            
    //         <!-- Kesimpulan Analisis -->
    //         <div style="margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 8px; border-left: 4px solid #4361ee;">
    //             <h4 style="color: #4361ee; margin-bottom: 10px;">KESIMPULAN ANALISIS:</h4>
    //             <ul style="margin: 0; padding-left: 20px; color: #333;">
    //                 <li>Total periode analisis: <strong>' . count($labels_pdf) . ' bulan</strong></li>
    //                 <li>Rata-rata keuntungan per bulan: <strong class="profit-positive">' . formatRupiah($total_keuntungan_grafik / max(1, count($labels_pdf))) . '</strong></li>
    //                 <li>Efisiensi modal: <strong>' . ($total_modal_grafik > 0 ? number_format(($total_keuntungan_grafik / $total_modal_grafik) * 100, 1) : '0') . '%</strong></li>
    //                 <li>Rasio penjualan/transaksi: <strong>' . ($total_transaksi_grafik > 0 ? number_format($total_buku_grafik / $total_transaksi_grafik, 1) : '0') . ' buku per transaksi</strong></li>
    //             </ul>
    //         </div>
    //         </div>'; // End of page-break
    //     }
    // }
    
    // Footer
    $html .= '
        <div class="footer">
            <div style="margin-bottom: 10px;">
                <strong>CATATAN:</strong> Laporan ini dihasilkan otomatis oleh sistem BukuBook berdasarkan filter yang dipilih.
            </div>
            <div style="border-top: 1px solid #ddd; padding-top: 10px;">
                Tipe Download: ' . strtoupper($download_type) . ' | Halaman 1/1 | Dicetak pada: ' . date('d/m/Y H:i:s') . '
            </div>
        </div>
    </body>
    </html>';
    
    // Setup Dompdf
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Arial');
    $options->set('isPhpEnabled', false);
    
    $dompdf = new Dompdf($options);
    
    // Load HTML
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'landscape');
    
    // Render PDF
    $dompdf->render();
    
    // Generate filename dengan menyertakan filter
    $filter_filename = "";
    if (!empty($filter_params)) {
        $filter_filename = "_" . implode("_", $filter_params);
    }
    $type_text = $download_type == 'all' ? 'Lengkap' : ($download_type == 'table' ? 'Tabel' : 'Diagram');
    $filename = 'Laporan_Penjualan_' . $type_text . $filter_filename . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data_penjual['nama_penjual']) . '_' . date('Ymd_His') . '.pdf';
    
    // Output PDF untuk download
    $dompdf->stream($filename, [
        'Attachment' => true
    ]);
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BukuBook - Laporan Penjualan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* [Semua CSS tetap sama seperti sebelumnya] */
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

/* ===== SIDEBAR STYLES ===== */
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

/* ===== MAIN CONTENT AREA ===== */
.main-content {
    flex: 1;
    margin-left: var(--sidebar-width);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ===== TOPBAR STYLES ===== */
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

.menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 22px;
    color: var(--primary);
    cursor: pointer;
    margin-right: 15px;
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

/* ===== CONTENT AREA ===== */
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

/* ===== STATS GRID ===== */
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
    background-color: rgba(40, 167, 69, 0.1);
    color: var(--success);
}

.stat-3 .stat-icon {
    background-color: rgba(255, 193, 7, 0.1);
    color: var(--warning);
}

.stat-4 .stat-icon {
    background-color: rgba(23, 162, 184, 0.1);
    color: var(--info);
}

.stat-5 .stat-icon {
    background-color: rgba(108, 117, 125, 0.1);
    color: var(--gray);
}

.stat-6 .stat-icon {
    background-color: rgba(220, 53, 69, 0.1);
    color: var(--danger);
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

/* ===== FILTER SECTION ===== */
.filter-section {
    background-color: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.filter-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark);
    font-size: 14px;
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

/* ===== BUTTON STYLES ===== */
.btn {
    padding: 12px 25px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
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

.btn-secondary {
    background-color: var(--light-gray);
    color: var(--dark);
}

.btn-secondary:hover {
    background-color: #dee2e6;
    transform: translateY(-2px);
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

/* ===== DOWNLOAD SELECTOR ===== */
.download-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.download-selector label {
    font-weight: 600;
    color: var(--dark);
    white-space: nowrap;
}

.select-wrapper {
    position: relative;
    flex: 1;
}

.select-wrapper select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid var(--light-gray);
    border-radius: 8px;
    font-size: 15px;
    background: white;
    cursor: pointer;
    appearance: none;
    padding-right: 40px;
}

.select-wrapper::after {
    content: '\f078';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    pointer-events: none;
}

.select-wrapper select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* ===== EXPORT ACTIONS ===== */
.export-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

/* ===== CHARTS CONTAINER ===== */
.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.chart-card {
    background-color: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-subtitle {
    font-size: 14px;
    color: var(--gray);
    margin-top: 5px;
}

.chart-period {
    font-size: 12px;
    color: var(--gray);
    background: var(--light-gray);
    padding: 4px 10px;
    border-radius: 20px;
}

.chart-wrapper {
    position: relative;
    height: 350px;
    width: 100%;
}

/* ===== CHART LEGEND ===== */
.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid var(--light-gray);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

/* ===== CHART STATS ===== */
.chart-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-top: 20px;
    font-size: 12px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 8px;
    background: var(--light-gray);
    border-radius: 6px;
}

.stat-value {
    font-weight: 600;
    color: var(--dark);
}

/* ===== TABLE CONTAINER ===== */
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
    min-width: 1000px;
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

/* ===== NUMBER FORMAT ===== */
.number {
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

.profit-positive {
    color: var(--success);
}

.profit-negative {
    color: var(--danger);
}

.profit-neutral {
    color: var(--gray);
}

/* ===== PAYMENT METHOD BADGE ===== */
.payment-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.badge-qris {
    background-color: rgba(67, 97, 238, 0.1);
    color: var(--primary);
}

.badge-transfer {
    background-color: rgba(40, 167, 69, 0.1);
    color: var(--success);
}

.badge-cash {
    background-color: rgba(255, 193, 7, 0.1);
    color: var(--warning);
}

/* ===== BUKTI BUTTON ===== */
.btn-bukti {
    background-color: var(--info);
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    border: none;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-bukti:hover {
    background-color: #138496;
    transform: translateY(-2px);
}

/* ===== NO DATA ===== */
.no-data {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
}

.no-data i {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-data h3 {
    font-size: 20px;
    margin-bottom: 10px;
    color: var(--dark);
}

.no-data p {
    font-size: 14px;
    color: var(--gray);
}

/* ===== BOTTOMBAR STYLES ===== */
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

/* ===== MODAL STYLES ===== */
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
    max-width: 700px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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

.bukti-image {
    width: 100%;
    max-height: 400px;
    object-fit: contain;
    border-radius: 8px;
    margin-top: 10px;
    border: 1px solid var(--light-gray);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--light-gray);
}

/* ===== TEXT UTILITIES ===== */
.text-muted {
    color: var(--gray);
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.text-left {
    text-align: left;
}

/* ===== PRINT STYLES ===== */
@media print {
    .sidebar,
    .topbar,
    .bottombar,
    .export-actions,
    .filter-section,
    .charts-container,
    .btn-bukti,
    .download-selector,
    .menu-toggle {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
    }
    
    .content {
        padding: 0 !important;
    }
    
    .table-container {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
    
    .table {
        min-width: auto !important;
    }
    
    .table th {
        background: #f0f0f0 !important;
    }
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 1200px) {
    .charts-container {
        grid-template-columns: 1fr;
    }
}

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
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .topbar {
        padding: 0 15px;
    }
    
    .content {
        padding: 20px 15px;
    }
    
    .chart-card {
        padding: 15px;
    }
    
    .chart-wrapper {
        height: 300px;
    }
    
    .table-container {
        padding: 15px;
    }
    
    .bottombar {
        flex-direction: column;
        padding: 15px;
        text-align: center;
        gap: 10px;
        height: auto;
    }
    
    .filter-form {
        grid-template-columns: 1fr;
    }
    
    .export-actions {
        flex-direction: column;
    }
    
    .download-selector {
        flex-direction: column;
        align-items: stretch;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .chart-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .chart-period {
        align-self: flex-start;
    }
    
    .chart-stats {
        grid-template-columns: 1fr;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 24px;
    }
    
    .welcome-message {
        font-size: 14px;
    }
    
    .stat-card {
        padding: 15px;
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-content h3 {
        font-size: 24px;
    }
    
    .chart-title {
        font-size: 16px;
    }
    
    .chart-subtitle {
        font-size: 12px;
    }
}

/* ===== ANIMATIONS ===== */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.5s ease-out;
}

/* ===== LOADING STATES ===== */
.loading {
    position: relative;
    opacity: 0.7;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 24px;
    height: 24px;
    margin: -12px 0 0 -12px;
    border: 3px solid var(--light-gray);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* ===== SCROLLBAR STYLES ===== */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--light-gray);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* ===== TOOLTIP STYLES ===== */
.tooltip {
    position: relative;
    display: inline-block;
}

.tooltip .tooltip-text {
    visibility: hidden;
    width: 200px;
    background-color: var(--dark);
    color: white;
    text-align: center;
    border-radius: 6px;
    padding: 8px 12px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 12px;
    font-weight: normal;
}

.tooltip .tooltip-text::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: var(--dark) transparent transparent transparent;
}

.tooltip:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

/* ===== ALERT MESSAGES ===== */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.3);
    color: var(--success);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: var(--danger);
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: var(--warning);
}

.alert-info {
    background-color: rgba(23, 162, 184, 0.1);
    border: 1px solid rgba(23, 162, 184, 0.3);
    color: var(--info);
}

/* ===== CARD STYLES ===== */
.card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 25px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--light-gray);
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
}

.card-body {
    padding: 10px 0;
}

/* ===== BADGE STYLES ===== */
.badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 4px;
    letter-spacing: 0.5px;
}

.badge-primary {
    background-color: rgba(67, 97, 238, 0.1);
    color: var(--primary);
}

.badge-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: var(--success);
}

.badge-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: var(--warning);
}

.badge-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: var(--danger);
}

/* ===== FORM ELEMENTS ===== */
.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.form-check-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-check-label {
    font-size: 14px;
    color: var(--dark);
    cursor: pointer;
}

.form-text {
    font-size: 12px;
    color: var(--gray);
    margin-top: 5px;
}

/* ===== PAGINATION ===== */
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 30px;
    list-style: none;
}

.page-item {
    display: inline-block;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: white;
    color: var(--dark);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    border: 1px solid var(--light-gray);
}

.page-link:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.page-item.active .page-link {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* ===== SEPARATOR ===== */
.separator {
    display: flex;
    align-items: center;
    text-align: center;
    color: var(--gray);
    margin: 20px 0;
}

.separator::before,
.separator::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid var(--light-gray);
}

.separator:not(:empty)::before {
    margin-right: 15px;
}

.separator:not(:empty)::after {
    margin-left: 15px;
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
                    <a href="laporan.php" class="nav-item active">
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

        <!-- Menu NETWORK -->
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
                <i class="fas fa-chart-bar"></i>Laporan Penjualan
            </h1>
            <p class="welcome-message">
                Analisis performa penjualan Anda. Lihat statistik, grafik, dan detail transaksi.
            </p>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card stat-1">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_transaksi'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Transaksi</p>
                    </div>
                </div>
                
                <div class="stat-card stat-2">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_buku_terjual'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Buku Terjual</p>
                    </div>
                </div>
                
                <div class="stat-card stat-3">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="number">Rp <?php echo number_format($stats['total_penjualan'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Penjualan</p>
                    </div>
                </div>
                
                <div class="stat-card stat-4">
                    <div class="stat-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="number">Rp <?php echo number_format($stats['total_modal'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Modal</p>
                    </div>
                </div>
                
                <div class="stat-card stat-5">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="number profit-positive">Rp <?php echo number_format($stats['total_keuntungan'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Total Keuntungan</p>
                    </div>
                </div>
                
                <div class="stat-card stat-6">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="number">Rp <?php echo number_format($stats['rata_keuntungan'] ?? 0, 0, ',', '.'); ?></h3>
                        <p>Rata-rata Keuntungan</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h3 class="filter-title"><i class="fas fa-filter"></i> Filter Laporan</h3>
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label for="tahun">Tahun</label>
                        <select id="tahun" name="tahun" class="form-control">
                            <option value="">Semua Tahun</option>
                            <?php 
                            // Reset pointer tahun_result
                            $tahun_result->data_seek(0);
                            while($tahun_row = $tahun_result->fetch_assoc()): ?>
                                <option value="<?php echo $tahun_row['tahun']; ?>" <?php echo isset($_GET['tahun']) && $_GET['tahun'] == $tahun_row['tahun'] ? 'selected' : ''; ?>>
                                    <?php echo $tahun_row['tahun']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulan">Bulan</label>
                        <select id="bulan" name="bulan" class="form-control">
                            <option value="">Semua Bulan</option>
                            <?php 
                            $bulan_list = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            foreach($bulan_list as $key => $bulan): ?>
                                <option value="<?php echo $key; ?>" <?php echo isset($_GET['bulan']) && $_GET['bulan'] == $key ? 'selected' : ''; ?>>
                                    <?php echo $bulan; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" 
                               value="<?php echo isset($_GET['tanggal_mulai']) ? htmlspecialchars($_GET['tanggal_mulai']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_selesai">Tanggal Selesai</label>
                        <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control" 
                               value="<?php echo isset($_GET['tanggal_selesai']) ? htmlspecialchars($_GET['tanggal_selesai']) : ''; ?>">
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        <a href="laporan.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Download Selector -->
            <div class="download-selector">
                <label for="downloadType"><i class="fas fa-file-download"></i> Download:</label>
                <div class="select-wrapper">
                    <select id="downloadType" name="downloadType">
                        <option value="all">Laporan Lengkap (Table + Diagram)</option>
                        <option value="table">Hanya Table Data</option>
                        <option value="diagram">Hanya Diagram & Analisis</option>
                    </select>
                </div>
            </div>

            <!-- Export Actions -->
            <div class="export-actions">
                <button class="btn btn-danger" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
                <button class="btn btn-warning" onclick="printReport()">
                    <i class="fas fa-print"></i> Cetak Laporan
                </button>
            </div>

            <!-- Charts Container - Hanya 2 Diagram -->
            <div class="charts-container">
                <!-- Chart 1: Performa Bulanan -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title"><i class="fas fa-chart-line"></i> Performa Bulanan</h3>
                            <div class="chart-subtitle">Trend Penjualan, Modal & Keuntungan</div>
                        </div>
                        <div class="chart-period">
                            <?php 
                            $periode_text = "Semua Periode";
                            if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
                                $periode_text = "Tahun " . $_GET['tahun'];
                            }
                            if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
                                $periode_text = $bulan_list[$_GET['bulan']] . " " . ($_GET['tahun'] ?? date('Y'));
                            }
                            if (isset($_GET['tanggal_mulai']) && isset($_GET['tanggal_selesai']) && 
                                !empty($_GET['tanggal_mulai']) && !empty($_GET['tanggal_selesai'])) {
                                $periode_text = date('d/m/Y', strtotime($_GET['tanggal_mulai'])) . " - " . date('d/m/Y', strtotime($_GET['tanggal_selesai']));
                            }
                            echo $periode_text;
                            ?>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="monthlyPerformanceChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #4361ee;"></div>
                            <span>Penjualan</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #ffc107;"></div>
                            <span>Modal</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #28a745;"></div>
                            <span>Keuntungan</span>
                        </div>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <span>Rata-rata Penjualan/Bulan:</span>
                            <span class="stat-value"><?php echo !empty($penjualan_data) ? 'Rp ' . number_format(array_sum($penjualan_data) / count($penjualan_data), 0, ',', '.') : '-'; ?></span>
                        </div>
                        <div class="stat-item">
                            <span>Rata-rata Keuntungan/Bulan:</span>
                            <span class="stat-value profit-positive"><?php echo !empty($keuntungan_data) ? 'Rp ' . number_format(array_sum($keuntungan_data) / count($keuntungan_data), 0, ',', '.') : '-'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Chart 2: Ringkasan Performa -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Ringkasan Performa</h3>
                            <div class="chart-subtitle">Transaksi vs Buku Terjual per Bulan</div>
                        </div>
                        <div class="chart-period">
                            Per Bulan
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="summaryChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #4361ee;"></div>
                            <span>Transaksi</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #28a745;"></div>
                            <span>Buku Terjual</span>
                        </div>
                    </div>
                    <div class="chart-stats">
                        <div class="stat-item">
                            <span>Total Transaksi:</span>
                            <span class="stat-value"><?php echo number_format($stats['total_transaksi'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <span>Total Buku Terjual:</span>
                            <span class="stat-value"><?php echo number_format($stats['total_buku_terjual'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <span>Rasio Buku/Transaksi:</span>
                            <span class="stat-value"><?php echo $stats['total_transaksi'] > 0 ? number_format(($stats['total_buku_terjual'] / $stats['total_transaksi']), 1) : '0'; ?></span>
                        </div>
                        <div class="stat-item">
                            <span>Keuntungan/Transaksi:</span>
                            <span class="stat-value profit-positive">Rp <?php echo number_format($stats['rata_keuntungan'] ?? 0, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: var(--dark);"><i class="fas fa-table"></i> Detail Transaksi</h3>
                        <div class="chart-period">
                            <?php echo $result->num_rows; ?> Data
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul Buku</th>
                                <th>Qty</th>
                                <th>Bukti</th>
                                <th>Metode Bayar</th>
                                <th>Harga Satuan</th>
                                <th>Modal Satuan</th>
                                <th>Total Modal</th>
                                <th>Total Penjualan</th>
                                <th>Total Keuntungan</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $total_qty = 0;
                            $total_modal_all = 0;
                            $total_penjualan_all = 0;
                            $total_keuntungan_all = 0;
                            
                            // Reset pointer result
                            $result->data_seek(0);
                            
                            while ($row = $result->fetch_assoc()): 
                                $total_qty += $row['qty'];
                                $total_modal_all += $row['total_modal'];
                                $total_penjualan_all += $row['total_penjualan'];
                                $total_keuntungan_all += $row['total_keuntungan'];
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                    <td class="number"><?php echo number_format($row['qty'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($row['bukti_pembayaran']): ?>
                                            <button class="btn-bukti" onclick="lihatBukti('<?php echo htmlspecialchars($row['bukti_pembayaran']); ?>')">
                                                <i class="fas fa-receipt"></i> Lihat
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['metode_bayar'] == 'QRIS'): ?>
                                            <span class="payment-badge badge-qris">
                                                <i class="fas fa-qrcode"></i> QRIS
                                            </span>
                                        <?php elseif ($row['metode_bayar'] == 'Transfer'): ?>
                                            <span class="payment-badge badge-transfer">
                                                <i class="fas fa-university"></i> Transfer
                                            </span>
                                        <?php elseif ($row['metode_bayar'] == 'Cash'): ?>
                                            <span class="payment-badge badge-cash">
                                                <i class="fas fa-money-bill-wave"></i> Cash
                                            </span>
                                        <?php else: ?>
                                            <span class="payment-badge">
                                                <?php echo htmlspecialchars($row['metode_bayar']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="number">Rp <?php echo number_format($row['harga_buku'], 0, ',', '.'); ?></td>
                                    <td class="number">Rp <?php echo number_format($row['modal'], 0, ',', '.'); ?></td>
                                    <td class="number">Rp <?php echo number_format($row['total_modal'], 0, ',', '.'); ?></td>
                                    <td class="number">Rp <?php echo number_format($row['total_penjualan'], 0, ',', '.'); ?></td>
                                    <td class="number <?php echo $row['total_keuntungan'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                        Rp <?php echo number_format($row['total_keuntungan'], 0, ',', '.'); ?>
                                    </td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($row['tanggal_pesanan'] ?? '')); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <!-- Total Row -->
                            <tr style="background-color: #f8f9fa; font-weight: 600;">
                                <td colspan="2"><strong>TOTAL</strong></td>
                                <td class="number"><strong><?php echo number_format($total_qty, 0, ',', '.'); ?></strong></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="number"><strong>Rp <?php echo number_format($total_modal_all, 0, ',', '.'); ?></strong></td>
                                <td class="number"><strong>Rp <?php echo number_format($total_penjualan_all, 0, ',', '.'); ?></strong></td>
                                <td class="number profit-positive"><strong>Rp <?php echo number_format($total_keuntungan_all, 0, ',', '.'); ?></strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Tidak Ada Data Laporan</h3>
                        <p>Belum ada transaksi yang disetujui dan dikirim dalam periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Bottom Bar -->
        <footer class="bottombar">
            <div class="breadcrumb">
                <a href="beranda.php" class="breadcrumb-item">Home</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">Laporan</span>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> BukuBook. Hak cipta dilindungi.
            </div>
        </footer>
    </div>

    <!-- Modal Bukti Pembayaran -->
    <div class="modal-overlay" id="buktiModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Bukti Pembayaran</h3>
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

        // Lihat bukti pembayaran
        function lihatBukti(buktiFile) {
            const buktiContent = document.getElementById('buktiContent');
            const imagePath = `../../Src/uploads/bukti_pembayaran/${buktiFile}`;
            
            buktiContent.innerHTML = `
                <div style="padding: 20px;">
                    <p style="margin-bottom: 15px;"><strong>File:</strong> ${buktiFile}</p>
                    <img src="${imagePath}" 
                         alt="Bukti Pembayaran" 
                         class="bukti-image"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Gambar+Tidak+Ditemukan'">
                </div>
            `;
            openModal('buktiModal');
        }

        // Download PDF dengan tipe tertentu
        function downloadPDF(type = null) {
            // Ambil parameter filter dari URL saat ini
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('download', 'pdf');
            
            // Jika type diberikan secara manual, gunakan itu
            // Jika tidak, ambil dari dropdown
            if (type) {
                urlParams.set('type', type);
            } else {
                const downloadType = document.getElementById('downloadType').value;
                urlParams.set('type', downloadType);
            }
            
            // Redirect ke halaman dengan parameter download
            window.location.href = 'laporan.php?' + urlParams.toString();
        }

        // Print Report
        function printReport() {
            window.print();
        }

        // Format angka untuk tooltip
        function formatCurrency(value) {
            return 'Rp ' + value.toLocaleString('id-ID');
        }

        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (count($labels) > 0): ?>
                // Chart 1: Performa Bulanan (Line Chart)
                const monthlyCtx = document.getElementById('monthlyPerformanceChart').getContext('2d');
                const monthlyChart = new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [
                            {
                                label: 'Penjualan',
                                data: <?php echo json_encode($penjualan_data); ?>,
                                borderColor: '#4361ee',
                                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#4361ee',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            },
                            {
                                label: 'Modal',
                                data: <?php echo json_encode($modal_data); ?>,
                                borderColor: '#ffc107',
                                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#ffc107',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            },
                            {
                                label: 'Keuntungan',
                                data: <?php echo json_encode($keuntungan_data); ?>,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#28a745',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#4361ee',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += formatCurrency(context.parsed.y);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                                        }
                                        if (value >= 1000) {
                                            return 'Rp ' + (value / 1000).toFixed(0) + ' rb';
                                        }
                                        return 'Rp ' + value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });

                // Chart 2: Ringkasan Performa (Bar Chart)
                const summaryCtx = document.getElementById('summaryChart').getContext('2d');
                const summaryChart = new Chart(summaryCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [
                            {
                                label: 'Transaksi',
                                data: <?php echo json_encode($transaksi_data); ?>,
                                backgroundColor: 'rgba(67, 97, 238, 0.6)',
                                borderColor: '#4361ee',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Buku Terjual',
                                data: <?php echo json_encode($buku_data); ?>,
                                backgroundColor: 'rgba(40, 167, 69, 0.6)',
                                borderColor: '#28a745',
                                borderWidth: 1,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        label += ': ' + context.parsed.y.toLocaleString('id-ID');
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            y: {
                                type: 'linear',
                                position: 'left',
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('id-ID');
                                    }
                                }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });

            <?php else: ?>
                // Jika tidak ada data, tampilkan pesan
                document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
                    wrapper.innerHTML = `
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--gray);">
                            <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>Belum ada data untuk ditampilkan dalam grafik</p>
                            <small>Terapkan filter atau tunggu hingga ada transaksi</small>
                        </div>
                    `;
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>