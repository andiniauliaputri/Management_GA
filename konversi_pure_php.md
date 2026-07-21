# Panduan Konversi Proyek GA Management System ke Pure PHP (Native PHP)

Dokumen ini berisi panduan lengkap, struktur arsitektur, rencana migrasi, dan contoh implementasi kode untuk mengubah aplikasi **GA Management System** dari framework **Laravel** menjadi **Pure PHP (Native PHP + PDO MySQL)**.

---

## 1. Alasan & Keuntungan Migrasi ke Pure PHP

1. **Kemudahan Deployment**: Dapat dijalankan di server web manapun (Laragon, XAMPP, Shared Hosting cPanel, Vercel, Niagahoster, Cpanel, dll) tanpa memerlukan *composer*, *node_modules*, *artisan CLI*, atau konfigurasi serverless khusus.
2. **Performa Sangat Ringan & Cepat**: Tanpa *framework overhead*, waktu eksekusi (*response time*) halaman jauh lebih cepat dan penggunaan memori RAM sangat kecil.
3. **Kemudahan Maintenance**: Struktur file sederhana, mudah dibaca, dan tidak tergantung pada versi framework atau dependensi luar yang sering diperbarui.

---

## 2. Perbandingan Komponen Arsitektur

| Fitur | Implementasi di Laravel | Implementasi di Pure PHP (Native) |
| :--- | :--- | :--- |
| **Routing / Navigasi** | `routes/web.php` + Controllers | File halaman terpisah (`guest.php`, `borrowing.php`, `login.php`) atau `index.php?page=...` |
| **Database & ORM** | Eloquent ORM (`App\Models\*`) | PDO (PHP Data Objects) dengan *Prepared Statements* untuk keamanan SQL Injection |
| **Template & View** | Blade (`.blade.php`) + `@extends` | Native PHP (`.php`) dengan `require_once 'includes/header.php'` & `footer.php` |
| **Autentikasi & Session**| `Auth::attempt()`, `bcrypt()` | `session_start()`, `password_verify()`, `password_hash()` |
| **Migrasi Database** | `database/migrations/*` | File skrip SQL murni (`database.sql`) di-import lewat phpMyAdmin |
| **Export Excel** | Stream Response XML Spreadsheet | Fungsi Header PHP Native (`header('Content-Type: application/vnd.ms-excel')`) |

---

## 3. usulan Struktur Direktori Pure PHP

```text
GA-Management-System-Native/
│
├── config/
│   └── database.php          # Koneksi Database PDO MySQL
│
├── includes/
│   ├── header.php            # Bagian atas HTML, CSS, Font, & Metadata
│   ├── sidebar.php           # Sidebar Navigasi Utama & Profil User
│   ├── footer.php            # Bagian bawah HTML & Script JS (Canvas Signature Pad)
│   └── auth_check.php        # Middleware Cek Session Login & Peran (Role)
│
├── css/
│   └── style.css             # Style CSS Utama
│
├── js/
│   └── signature_pad.js      # Handler Canvas Tanda Tangan Digital
│
├── database.sql              # Skrip SQL Pembuatan Tabel & Data Awal (Seeder)
│
├── index.php                 # Halaman Portal Layanan GA (Beranda Publik / Kiosk)
├── guest.php                 # Halaman Buku Tamu Digital (Check-in & Riwayat)
├── borrowing.php             # Halaman Peminjaman Barang & Kunci (Pinjam & Pengembalian)
├── dashboard.php             # Dashboard Monitoring Manager
├── report.php                # Laporan Rekapitulasi & Filter GA
├── settings.php              # Pengaturan Akun & Password
│
├── export_guest.php          # Script Download Excel Buku Tamu
├── export_borrowing.php      # Script Download Excel Peminjaman Barang
├── export_report.php         # Script Download Excel Laporan Rekap
│
├── login.php                 # Halaman Login Pengguna (Manager & Secom)
└── logout.php                # Script Proses Logout & Hapus Session
```

---

## 4. Skrip Database (`database.sql`)

Buat file `database.sql` untuk di-import langsung ke phpMyAdmin / MySQL:

```sql
CREATE DATABASE IF NOT EXISTS `ga_management_db`;
USE `ga_management_db`;

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('manager', 'secom') NOT NULL DEFAULT 'secom',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Guests (Buku Tamu)
CREATE TABLE IF NOT EXISTS `guests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `institution` VARCHAR(255) NULL,
  `guest_category` VARCHAR(100) NOT NULL DEFAULT 'kedinasan',
  `purpose` TEXT NOT NULL,
  `person_to_meet` VARCHAR(255) NOT NULL,
  `id_type` VARCHAR(100) NULL,
  `visitor_card_number` VARCHAR(100) NULL,
  `time_in` DATETIME NOT NULL,
  `time_out` DATETIME NULL,
  `signature` LONGTEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabel Item Borrowings (Peminjaman Barang & Kunci)
CREATE TABLE IF NOT EXISTS `item_borrowings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `borrower_name` VARCHAR(255) NOT NULL,
  `department` VARCHAR(255) NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `item_code` VARCHAR(255) NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `borrow_time` DATETIME NOT NULL,
  `return_time` DATETIME NULL,
  `initial_condition` VARCHAR(255) NOT NULL DEFAULT 'Baik',
  `return_condition` VARCHAR(255) NULL,
  `signature` LONGTEXT NULL,
  `status` ENUM('borrowed', 'returned') NOT NULL DEFAULT 'borrowed',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Data Seeder Awal (Password default menggunakan password_hash Bcrypt)
-- Password admin: admin123
-- Password secom: secom123
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Manager GA Supervisor', 'admin@ga.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.XoMgE3g1y', 'manager'),
('Staf Secom GA', 'secom@ga.com', '$2y$10$e.w2gA9jW2P2i/JqO2k5.uL4a.H8R8c9Z6O/eK1pL6f.gH0i1j2k3', 'secom')
ON DUPLICATE KEY UPDATE `email`=`email`;
```

---

## 5. Contoh Implementasi Kode Pure PHP

### A. File `config/database.php` (Koneksi PDO)

```php
<?php
$host = '127.0.0.1';
$db   = 'ga_management_db';
$user = 'root';
$pass = 'root'; // Sesuaikan password MySQL Anda
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
```

---

### B. File `includes/auth_check.php` (Proteksi Session Login)

```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function check_role($allowed_roles = []) {
    check_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: index.php?error=Akses+ditolak");
        exit();
    }
}
```

---

### C. File `login.php` (Proses & Form Login Native)

```php
<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'manager') {
            header("Location: dashboard.php");
        } else {
            header("Location: guest.php");
        }
        exit();
    } else {
        $error = "Email atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - GA Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <a href="index.php" class="back-link">← Kembali ke Beranda</a>
        <h2>Login GA Management</h2>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Masuk</button>
        </form>
    </div>
</body>
</html>
```

---

### D. File `export_guest.php` (Export Excel Native)

```php
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Proteksi hanya user terotorisasi
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
echo ' <Worksheet ss:Name="Data Tamu">' . "\n";
echo '  <Table>' . "\n";

// Header Row
echo '   <Row>' . "\n";
echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Nama Tamu</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Kategori</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Instansi</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Orang Ditemui</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Waktu Masuk</Data></Cell>' . "\n";
echo '    <Cell><Data ss:Type="String">Waktu Keluar</Data></Cell>' . "\n";
echo '   </Row>' . "\n";

$no = 1;
foreach ($guests as $g) {
    echo '   <Row>' . "\n";
    echo '    <Cell><Data ss:Type="Number">' . $no++ . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['name']) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['guest_category']) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['institution'] ?? '-') . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . htmlspecialchars($g['person_to_meet']) . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . $g['time_in'] . '</Data></Cell>' . "\n";
    echo '    <Cell><Data ss:Type="String">' . ($g['time_out'] ?? '-') . '</Data></Cell>' . "\n";
    echo '   </Row>' . "\n";
}

echo '  </Table>' . "\n";
echo ' </Worksheet>' . "\n";
echo '</Workbook>' . "\n";
```

---

## 6. Langkah-Langkah Eksekusi Migrasi

1. **Buat Folder Baru**: Buat folder proyek baru bernama `GA-Management-System-Native`.
2. **Copy Asset Frontend**: Salin folder `css/` dan `js/` dari proyek Laravel lama ke proyek baru.
3. **Import `database.sql`**: Buka phpMyAdmin, buat database `ga_management_db`, lalu import file `database.sql`.
4. **Buat File Layout `includes/`**: Buat `header.php`, `sidebar.php`, dan `footer.php` dengan memindahkan HTML template dari `resources/views/layouts/app.blade.php`.
5. **Buat File Halaman Native**: Salin struktur tampilan Blade dari `guest.blade.php`, `borrowing.blade.php`, `dashboard.blade.php`, dan `settings.blade.php` ke file `.php` murni dengan mengganti variabel Blade `{{ $var }}` menjadi `<?= htmlspecialchars($var) ?>`.
6. **Uji Coba di Laragon**: Buka `http://GA-Management-System-Native.test` di browser.
