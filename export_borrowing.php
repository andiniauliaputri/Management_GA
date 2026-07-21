<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager', 'secom']);

$stmt = $pdo->query("SELECT * FROM item_borrowings ORDER BY borrow_time DESC");
$borrowings = $stmt->fetchAll();

$filename = "Peminjaman_Barang_Report_" . date('Y-m-d_H-i') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
echo ' <Worksheet ss:Name="Data Peminjaman">' . "\n";
echo '  <Table>' . "\n";

// Header Row
echo '   <Row>' . "\n";
echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Nama Peminjam</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Departemen</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Nama Barang</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Kode Barang</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Jumlah (Qty)</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Waktu Pinjam</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Waktu Kembali</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Kondisi Awal</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Kondisi Kembali</Data></Cell>' . "\n";
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
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($b['initial_condition']) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($b['return_condition'] ?: '-') . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . $status . '</Data></Cell>' . "\n";
    echo '   </Row>' . "\n";
}

echo '  </Table>' . "\n";
echo ' </Worksheet>' . "\n";
echo '</Workbook>' . "\n";
