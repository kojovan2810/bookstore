<!-- composer require dompdf/dompdf -->

<?php
session_start();
include "../../Src/config.php";
require_once '../../vendor/autoload.php'; // Pastikan DomPDF sudah diinstall

use Dompdf\Dompdf;
use Dompdf\Options;

// Cek login
if (!isset($_SESSION['email_pembeli'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

$email_pembeli = $_SESSION['email_pembeli'];
$data_pembeli = $conn->query("SELECT * FROM pembeli WHERE email_pembeli = '$email_pembeli'")->fetch_assoc();

// Ambil parameter filter
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query riwayat
$query = "SELECT * FROM pesanan WHERE email_pembeli = '$email_pembeli' AND status = 'Diterima'";

if (!empty($search)) {
    $query .= " AND (kode_pesanan LIKE '%$search%' OR judul_buku LIKE '%$search%' OR nama_penjual LIKE '%$search%')";
}

if (!empty($filter_month) && $filter_month != 'semua') {
    $query .= " AND MONTH(tanggal_pesanan) = '$filter_month'";
}

if (!empty($filter_year) && $filter_year != 'semua') {
    $query .= " AND YEAR(tanggal_pesanan) = '$filter_year'";
}

$query .= " ORDER BY tanggal_pesanan DESC";

$riwayat_query = $conn->query($query);
$total_riwayat = $riwayat_query->num_rows;

// Hitung total pengeluaran
$total_semua = 0;
$riwayat_data = [];
while($row = $riwayat_query->fetch_assoc()) {
    $riwayat_data[] = $row;
    $total_semua += $row['total_harga'];
}

// Konfigurasi DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'helvetica');

$dompdf = new Dompdf($options);

// HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Riwayat Belanja - BukuBook</title>
    <style>
        body {
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4361ee;
            padding-bottom: 20px;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #4361ee;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .info-section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #333;
        }
        
        .info-value {
            flex: 1;
            color: #555;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10px;
        }
        
        th {
            background-color: #4361ee;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-left {
            text-align: left;
        }
        
        .total-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #e8f5e9;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .total-label {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        
        .total-value {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .status-diterima {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">RIWAYAT BELANJA - BUKUBOOK</div>
        <div class="subtitle">Laporan Transaksi Pelanggan</div>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Nama Pembeli:</div>
            <div class="info-value">' . htmlspecialchars($data_pembeli['nama_pembeli']) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">' . htmlspecialchars($data_pembeli['email_pembeli']) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Transaksi:</div>
            <div class="info-value">' . $total_riwayat . ' transaksi</div>
        </div>';
        
        if (!empty($search)) {
            $html .= '<div class="info-row">
                <div class="info-label">Pencarian:</div>
                <div class="info-value">"' . htmlspecialchars($search) . '"</div>
            </div>';
        }
        
        if (!empty($filter_month) && $filter_month != 'semua') {
            $bulan = date('F', mktime(0, 0, 0, $filter_month, 1));
            $html .= '<div class="info-row">
                <div class="info-label">Bulan:</div>
                <div class="info-value">' . $bulan . '</div>
            </div>';
        }
        
        if (!empty($filter_year) && $filter_year != 'semua') {
            $html .= '<div class="info-row">
                <div class="info-label">Tahun:</div>
                <div class="info-value">' . $filter_year . '</div>
            </div>';
        }
        
$html .= '
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="8%">No</th>
                <th width="12%">Kode Pesanan</th>
                <th width="12%">Tanggal</th>
                <th width="25%">Judul Buku</th>
                <th width="15%">Penjual</th>
                <th width="6%">Qty</th>
                <th width="12%">Harga</th>
                <th width="10%">Total</th>
            </tr>
        </thead>
        <tbody>';
        
        if ($total_riwayat > 0) {
            $no = 1;
            foreach ($riwayat_data as $row) {
                $tanggal = date('d/m/Y', strtotime($row['tanggal_pesanan']));
                $html .= '
                <tr>
                    <td class="text-center">' . $no++ . '</td>
                    <td>' . htmlspecialchars($row['kode_pesanan']) . '</td>
                    <td>' . $tanggal . '</td>
                    <td>' . htmlspecialchars($row['judul_buku']) . '</td>
                    <td>' . htmlspecialchars($row['nama_penjual']) . '</td>
                    <td class="text-center">' . $row['qty'] . '</td>
                    <td class="text-right">Rp ' . number_format($row['harga_satuan'], 0, ',', '.') . '</td>
                    <td class="text-right">Rp ' . number_format($row['total_harga'], 0, ',', '.') . '</td>
                </tr>';
            }
        } else {
            $html .= '
            <tr>
                <td colspan="8" class="text-center" style="padding: 30px;">
                    Tidak ada data transaksi
                </td>
            </tr>';
        }
        
$html .= '
        </tbody>
    </table>
    
    <div class="total-section">
        <div class="info-row">
            <div class="info-label">Total Transaksi:</div>
            <div class="info-value">' . $total_riwayat . ' transaksi</div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Pengeluaran:</div>
            <div class="total-value">Rp ' . number_format($total_semua, 0, ',', '.') . '</div>
        </div>
    </div>
    
    <div class="footer">
        <p>Dokumen ini dicetak otomatis dari sistem BukuBook.</p>
        <p>© ' . date('Y') . ' BukuBook. Hak cipta dilindungi.</p>
    </div>
</body>
</html>';

// Load HTML ke DomPDF
$dompdf->loadHtml($html);

// Ukuran kertas dan orientasi
$dompdf->setPaper('A4', 'landscape');

// Render PDF
$dompdf->render();

// Output PDF
$filename = 'riwayat_belanja_' . str_replace(' ', '_', $data_pembeli['nama_pembeli']) . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, array("Attachment" => true));
?>