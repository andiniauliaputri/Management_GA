<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager', 'secom']);

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');
$type       = $_GET['type'] ?? 'all';

$filename = "Rekap_Laporan_GA_" . $start_date . "_sd_" . $end_date . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

// SHEET 1: TAMU (IF APPLICABLE)
if ($type === 'all' || $type === 'guest') {
    $stmt = $pdo->prepare("SELECT * FROM guests WHERE DATE(time_in) BETWEEN ? AND ? ORDER BY time_in DESC");
    $stmt->execute([$start_date, $end_date]);
    $guests = $stmt->fetchAll();

    echo ' <Worksheet ss:Name="Rekap Buku Tamu">' . "\n";
    echo '  <Table>' . "\n";
    echo '   <Row>' . "\n";
    echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Nama Tamu</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Kategori</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Instansi</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Orang Ditemui</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Tujuan</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Waktu Masuk</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Waktu Keluar</Data></Cell>' . "\n";
    echo '   </Row>' . "\n";

    $no = 1;
    foreach ($guests as $g) {
        echo '   <Row>' . "\n";
        echo '    <Cell><Data ss:Type="Number">' . $no++ . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['name']) . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars(ucfirst($g['guest_category'])) . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['institution'] ?: '-') . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['person_to_meet']) . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['purpose']) . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . $g['time_in'] . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . ($g['time_out'] ?: '-') . '</Data></Cell>' . "\n";
        echo '   </Row>' . "\n";
    }
    echo '  </Table>' . "\n";
    echo ' </Worksheet>' . "\n";
}

// SHEET 2: PEMINJAMAN (IF APPLICABLE)
if ($type === 'all' || $type === 'borrowing') {
    $stmt = $pdo->prepare("SELECT * FROM item_borrowings WHERE DATE(borrow_time) BETWEEN ? AND ? ORDER BY borrow_time DESC");
    $stmt->execute([$start_date, $end_date]);
    $borrowings = $stmt->fetchAll();

    echo ' <Worksheet ss:Name="Rekap Peminjaman">' . "\n";
    echo '  <Table>' . "\n";
    echo '   <Row>' . "\n";
    echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Nama Peminjam</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Departemen</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Nama Barang</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Kode Barang</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Jumlah</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Waktu Pinjam</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Waktu Kembali</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">Status</Data></Cell>' . "\n";
    echo '   </Row>' . "\n";

    $no = 1;
    foreach ($borrowings as $b) {
        $status = $b['status'] === 'borrowed' ? 'Sedang Dipinjam' : 'Dikembalikan';
        echo '   <Row>' . "\n";
        echo '    <Cell><Data ss:Type="Number">' . $no++ . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($b['borrower_name']) . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($b['department']) . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($b['item_name']) . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($b['item_code'] ?: '-') . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="Number">' . $b['quantity'] . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . $b['borrow_time'] . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . ($b['return_time'] ?: '-') . '</Data></Cell>' . "\n";
        echo '    <Cell><Data ss:Type="String">' . $status . '</Data></Cell>' . "\n";
        echo '   </Row>' . "\n";
    }
    echo '  </Table>' . "\n";
    echo ' </Worksheet>' . "\n";
}

echo '</Workbook>' . "\n";
