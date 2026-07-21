<?php
// Set Timezone Real-Time Local (WIB / Asia/Jakarta UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database GA Management System
$host = '127.0.0.1';
$db   = 'ga_management_db';
$user = 'root';
$pass = ''; // Sesuaikan dengan password MySQL Anda (misal: 'root' atau '')
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+07:00';");
} catch (\PDOException $e) {
    try {
        $pass = 'root';
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET time_zone = '+07:00';");
    } catch (\PDOException $ex) {
        die("Koneksi Database Gagal: " . $ex->getMessage() . "<br><br><i>Pastikan MySQL di Laragon/XAMPP sudah berjalan dan file <b>database.sql</b> sudah diimport.</i>");
    }
}
