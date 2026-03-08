<!-- composer require dompdf/dompdf -->

<?php
session_start();
include "../../Src/config.php";
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Cek login
if (!isset($_SESSION['email_pembeli'])) {
    echo "<script>alert('Silahkan login terlebih dahulu');location.href='../../login.php'</script>";
    exit();
}

$kode_pesanan = $_GET['kode'];
$email_pembeli = $_SESSION['email_pembeli'];

// Ambil data pesanan
$query = "SELECT p.*, pl.nama_pembeli, pl.email_pembeli FROM pesanan p
          LEFT JOIN pembeli pl ON p.email_pembeli = pl.email_pembeli
          WHERE p.kode_pesanan = '$kode_pesanan' AND p.email_pembeli = '$email_pembeli'";

$pesanan = $conn->query($query)->fetch_assoc();

if (!$pesanan) {
    die("Pesanan tidak ditemukan");
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
    <title>Invoice ' . htmlspecialchars($kode_pesanan) . ' - BukuBook</title>
    <style>
        body {
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4361ee;
        }
        
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #4361ee;
            margin-bottom: 5px;
        }
        
        .invoice-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .invoice-code {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #4361ee;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-box {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        
        .info-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #495057;
            font-size: 11px;
        }
        
        .info-value {
            font-size: 13px;
            color: #212529;
        }
        
        .info-value strong {
            color: #28a745;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 11px;
        }
        
        .detail-table th {
            background-color: #4361ee;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        .detail-table td {
            padding: 12px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .detail-table .text-right {
            text-align: right;
        }
        
        .detail-table .text-center {
            text-align: center;
        }
        
        .total-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #e8f5e9;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
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
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-diterima {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-disetujui {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .qr-section {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            border: 1px dashed #dee2e6;
            border-radius: 8px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="invoice-title">INVOICE</div>
        <div class="invoice-subtitle">BUKUBOOK - PLATFORM BELANJA BUKU ONLINE</div>
        <div class="invoice-code">' . htmlspecialchars($kode_pesanan) . '</div>
    </div>
    
    <div class="info-grid">
        <div class="info-box">
            <div class="section-title">Informasi Pembeli</div>
            <div style="margin-bottom: 10px;">
                <div class="info-label">Nama</div>
                <div class="info-value">' . htmlspecialchars($pesanan['nama_pembeli']) . '</div>
            </div>
            <div style="margin-bottom: 10px;">
                <div class="info-label">Email</div>
                <div class="info-value">' . htmlspecialchars($pesanan['email_pembeli']) . '</div>
            </div>
            <div>
                <div class="info-label">Alamat Pengiriman</div>
                <div class="info-value">' . htmlspecialchars($pesanan['alamat_pembeli']) . '</div>
            </div>
        </div>
        
        <div class="info-box">
            <div class="section-title">Informasi Penjual</div>
            <div style="margin-bottom: 10px;">
                <div class="info-label">Nama Penjual</div>
                <div class="info-value">' . htmlspecialchars($pesanan['nama_penjual']) . '</div>
            </div>
            <div>
                <div class="info-label">Email Penjual</div>
                <div class="info-value">' . htmlspecialchars($pesanan['email_penjual']) . '</div>
            </div>
        </div>
    </div>
    
    <div class="section-title">Detail Pesanan</div>
    
    <table class="detail-table">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th width="15%">Harga Satuan</th>
                <th width="10%">Quantity</th>
                <th width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>' . htmlspecialchars($pesanan['judul_buku']) . '</strong><br>
                    <small>Kode: ' . htmlspecialchars($kode_pesanan) . '</small>
                </td>
                <td class="text-right">Rp ' . number_format($pesanan['harga_satuan'], 0, ',', '.') . '</td>
                <td class="text-center">' . $pesanan['qty'] . '</td>
                <td class="text-right"><strong>Rp ' . number_format($pesanan['total_harga'], 0, ',', '.') . '</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="info-grid">
        <div class="info-box">
            <div class="section-title">Informasi Transaksi</div>
            <div style="margin-bottom: 8px;">
                <div class="info-label">Tanggal Pesanan</div>
                <div class="info-value">' . date('d F Y, H:i', strtotime($pesanan['tanggal_pesanan'])) . '</div>
            </div>
            <div style="margin-bottom: 8px;">
                <div class="info-label">Metode Pembayaran</div>
                <div class="info-value">' . htmlspecialchars($pesanan['metode_bayar']) . '</div>
            </div>
            <div style="margin-bottom: 8px;">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge status-diterima">' . htmlspecialchars($pesanan['status']) . '</span>
                </div>
            </div>
            <div>
                <div class="info-label">Persetujuan</div>
                <div class="info-value">
                    <span class="status-badge status-disetujui">' . htmlspecialchars($pesanan['approve']) . '</span>
                </div>
            </div>
        </div>
        
        <div class="info-box">';
        
        if ($pesanan['ekspedisi']) {
            $html .= '
            <div class="section-title">Informasi Pengiriman</div>
            <div style="margin-bottom: 8px;">
                <div class="info-label">Ekspedisi</div>
                <div class="info-value"><strong>' . htmlspecialchars($pesanan['ekspedisi']) . '</strong></div>
            </div>';
            
            if ($pesanan['no_resi']) {
                $html .= '
                <div>
                    <div class="info-label">Nomor Resi</div>
                    <div class="info-value"><strong>' . htmlspecialchars($pesanan['no_resi']) . '</strong></div>
                </div>';
            }
        } else {
            $html .= '
            <div class="section-title">Informasi Pengiriman</div>
            <div style="text-align: center; padding: 20px; color: #666;">
                <i>Informasi pengiriman tidak tersedia</i>
            </div>';
        }
        
$html .= '
        </div>
    </div>
    
    <div class="total-section">
        <div class="total-row">
            <div class="total-label">Subtotal</div>
            <div class="info-value">Rp ' . number_format($pesanan['total_harga'], 0, ',', '.') . '</div>
        </div>
        <div class="total-row">
            <div class="total-label">Total Pembayaran</div>
            <div class="total-value">Rp ' . number_format($pesanan['total_harga'], 0, ',', '.') . '</div>
        </div>
    </div>
    
    <div class="qr-section">
        <p><strong>Kode Verifikasi: </strong>' . strtoupper(substr(md5($kode_pesanan), 0, 8)) . '</p>
        <p><small>Gunakan kode ini untuk verifikasi keaslian invoice</small></p>
    </div>
    
    <div class="footer">
        <p>Invoice ini sah dan dapat digunakan sebagai bukti transaksi.</p>
        <p>Terima kasih telah berbelanja di BukuBook!</p>
        <p>© ' . date('Y') . ' BukuBook. Hak cipta dilindungi.</p>
    </div>
</body>
</html>';

// Load HTML ke DomPDF
$dompdf->loadHtml($html);

// Ukuran kertas dan orientasi
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Output PDF
$filename = 'invoice_' . $kode_pesanan . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, array("Attachment" => true));
?>