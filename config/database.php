<?php
// Set Timezone Real-Time Local (WIB / Asia/Jakarta UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database GA Management System
// Mendukung Environment Variables (untuk Deployment Cloud Vercel/Railway) & Fallback Local Laragon
$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'ga_management_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+07:00';");
} catch (\PDOException $e) {
    if (!getenv('DB_HOST')) {
        try {
            $pass = 'root';
            $pdo = new PDO($dsn, $user, $pass, $options);
            $pdo->exec("SET time_zone = '+07:00';");
        } catch (\PDOException $ex) {
            die("Koneksi Database Gagal: " . $ex->getMessage() . "<br><br><i>Pastikan MySQL di Laragon/XAMPP sudah berjalan dan file <b>database.sql</b> sudah diimport.</i>");
        }
    } else {
        die("Koneksi Cloud Database Gagal: " . $e->getMessage() . "<br><br><i>Periksa kembali Environment Variables DB_HOST, DB_NAME, DB_USER, DB_PASS di Dashboard Vercel.</i>");
    }
}
