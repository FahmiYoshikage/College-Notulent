<?php
require_once 'config.php';
requireLogin();

// Download Dompdf dari: https://github.com/dompdf/dompdf
// Ekstrak ke folder 'dompdf' di root project
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$notula_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = getConnection();

// Ambil detail notula
$stmt = $conn->prepare("
    SELECT n.*, k.nama_kelompok, k.kode_kelompok, u.nama as creator_name,
           uk.user_id as has_access
    FROM notula n
    JOIN kelompok k ON n.kelompok_id = k.id
    JOIN users u ON n.created_by = u.id
    LEFT JOIN user_kelompok uk ON k.id = uk.kelompok_id AND uk.user_id = ?
    WHERE n.id = ?
");
$stmt->bind_param("ii", $user_id, $notula_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Notula tidak ditemukan atau Anda tidak memiliki akses");
}

$notula = $result->fetch_assoc();
$conn->close();

// Template HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #667eea;
            font-size: 24pt;
            margin: 0 0 5px 0;
        }
        .header .subtitle {
            color: #666;
            font-size: 10pt;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 8px;
            border-bottom: 1px solid #e1e8ed;
        }
        .meta-table td:first-child {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .section-title {
            background: #667eea;
            color: white;
            padding: 10px 15px;
            margin: 25px 0 15px 0;
            font-size: 14pt;
            border-radius: 3px;
        }
        .content {
            text-align: justify;
            margin: 15px 0;
        }
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .content table th,
        .content table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .content table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e1e8ed;
            text-align: center;
            font-size: 9pt;
            color: #999;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }
        .status-final {
            background: #28a745;
            color: white;
        }
        .status-draft {
            background: #ffc107;
            color: #333;
        }
        .status-perlu_revisi {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📝 NOTULA</h1>
        <div class="subtitle">' . htmlspecialchars($notula['nama_kelompok']) . '</div>
    </div>

    <table class="meta-table">
        <tr>
            <td>Judul</td>
            <td><strong>' . htmlspecialchars($notula['judul']) . '</strong></td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>' . date('d F Y', strtotime($notula['tanggal'])) . '</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>' . date('H:i', strtotime($notula['waktu'])) . ' WIB</td>
        </tr>
        <tr>
            <td>Lokasi</td>
            <td>' . htmlspecialchars($notula['lokasi'] ?: '-') . '</td>
        </tr>
        <tr>
            <td>Pembuat</td>
            <td>' . htmlspecialchars($notula['creator_name']) . '</td>
        </tr>
        <tr>
            <td>Status</td>
            <td><span class="status-badge status-' . $notula['status'] . '">' . strtoupper($notula['status']) . '</span></td>
        </tr>
    </table>';

if (!empty($notula['daftar_peserta'])) {
    $html .= '
    <div class="section-title">Daftar Peserta</div>
    <div class="content">' . nl2br(htmlspecialchars($notula['daftar_peserta'])) . '</div>';
}

$html .= '
    <div class="section-title">Isi Notula</div>
    <div class="content">' . $notula['isi_notula'] . '</div>

    <div class="footer">
        <p>Dokumen ini digenerate otomatis oleh Platform Notula</p>
        <p>Tanggal Export: ' . date('d F Y H:i:s') . '</p>
    </div>
</body>
</html>';

// Konfigurasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

// Buat instance Dompdf
$dompdf = new Dompdf($options);

// Load HTML
$dompdf->loadHtml($html);

// Setup ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Nama file
$filename = 'Notula_' . preg_replace('/[^a-zA-Z0-9]/', '_', $notula['judul']) . '_' . date('Ymd') . '.pdf';

// Output PDF ke browser
$dompdf->stream($filename, array("Attachment" => true));
?>