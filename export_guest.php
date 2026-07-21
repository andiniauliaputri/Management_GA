<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager', 'secom']);

$stmt = $pdo->query("SELECT * FROM guests ORDER BY time_in DESC");
$guests = $stmt->fetchAll();

$filename = "Buku_Tamu_Report_" . date('Y-m-d_H-i') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
echo ' <Worksheet ss:Name="Data Buku Tamu">' . "\n";
echo '  <Table>' . "\n";

// Header Row
echo '   <Row>' . "\n";
echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Nama Tamu</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Kategori</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Instansi</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Orang Ditemui</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">No Kartu Visitor</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Tujuan</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Waktu Masuk</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Waktu Keluar</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Status</Data></Cell>' . "\n";
echo '   </Row>' . "\n";

$no = 1;
foreach ($guests as $g) {
    $status = $g['time_out'] ? 'Sudah Keluar' : 'Masih di Lokasi';
    echo '   <Row>' . "\n";
    echo '    <Cell><Data ss:Type="Number">' . $no++ . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['name']) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars(ucfirst($g['guest_category'])) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['institution'] ?: '-') . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['person_to_meet']) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['visitor_card_number'] ?: '-') . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['purpose']) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . $g['time_in'] . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . ($g['time_out'] ?: '-') . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . $status . '</Data></Cell>' . "\n";
    echo '   </Row>' . "\n";
}

echo '  </Table>' . "\n";
echo ' </Worksheet>' . "\n";
echo '</Workbook>' . "\n";
