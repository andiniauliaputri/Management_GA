<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['guest_category'] ?? 'kedinasan');
    $institution = trim($_POST['institution'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $person_to_meet = trim($_POST['person_to_meet'] ?? '');
    $id_type = trim($_POST['id_type'] ?? '');
    $visitor_card = trim($_POST['visitor_card_number'] ?? '');
    $signature = $_POST['signature'] ?? '';

    if (empty($name) || empty($purpose) || empty($person_to_meet)) {
        $error_msg = "Nama Tamu, Tujuan Kunjungan, dan Orang yang Ditemui wajib diisi!";
    } else {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO guests (name, guest_category, institution, purpose, person_to_meet, id_type, visitor_card_number, time_in, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $institution, $purpose, $person_to_meet, $id_type, $visitor_card, $now, $signature]);
            
            set_flash_message('success', 'Check-in Tamu berhasil dicatat. Selamat datang!');
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $error_msg = "Gagal menyimpan data tamu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Registrasi Tamu - GA Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="portal-body">

    <header class="portal-navbar">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div class="sidebar-logo-icon">GA</div>
            <div>
                <strong style="font-size: 1.1rem; color: #fff;">Form Buku Tamu Digital</strong>
                <div style="font-size: 0.75rem; color: #94a3b8;">General Affairs Visitor Registration</div>
            </div>
        </div>
        <div>
            <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="btn btn-sm btn-primary">Masuk ke Dashboard</a>
            <?php else: ?>
                <a href="index.php" class="btn btn-sm btn-outline-light">← Kembali ke Beranda Portal</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="portal-container" style="max-width: 800px; margin-top: 2rem; margin-bottom: 3rem;">
        
        <div style="margin-bottom: 1.5rem;">
            <a href="index.php" class="btn-back">
                ← Kembali ke Portal Utama
            </a>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Form Registrasi Tamu Baru</h2>
                    <small style="color: var(--text-muted);">Lengkapi formulir di bawah ini untuk mencatat kunjungan Anda</small>
                </div>
            </div>

            <form action="guest_form.php" method="POST">
                <input type="hidden" name="signature" id="guest_signature_input">

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Tamu *</label>
                        <input type="text" name="name" required class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kategori Tamu</label>
                        <select name="guest_category" class="form-select">
                            <option value="kedinasan">Kedinasan / Instansi</option>
                            <option value="vendor">Vendor / Kontraktor</option>
                            <option value="pelamar">Pelamar / Interview</option>
                            <option value="pribadi">Kunjungan Pribadi</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Instansi / Perusahaan Asal</label>
                        <input type="text" name="institution" class="form-control" placeholder="PT / Instansi Asal" value="<?= htmlspecialchars($_POST['institution'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Orang yang Ditemui (Karyawan) *</label>
                        <input type="text" name="person_to_meet" required class="form-control" placeholder="Nama Karyawan / Departemen" value="<?= htmlspecialchars($_POST['person_to_meet'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Jenis Kartu Identitas</label>
                        <select name="id_type" class="form-select">
                            <option value="KTP">KTP</option>
                            <option value="SIM">SIM</option>
                            <option value="PASPOR">Paspor / ID Card</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor Kartu Akses Tamu (Visitor Card)</label>
                        <input type="text" name="visitor_card_number" class="form-control" placeholder="Contoh: V-012" value="<?= htmlspecialchars($_POST['visitor_card_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Tujuan Kunjungan *</label>
                    <textarea name="purpose" required class="form-control" placeholder="Jelaskan keperluan/tujuan kunjungan Anda..."><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanda Tangan Digital Tamu</label>
                    <div class="signature-container">
                        <canvas id="guest_signature_canvas" class="signature-canvas"></canvas>
                        <div class="signature-controls">
                            <small style="color: #64748b;">Gunakan mouse atau layar sentuh untuk menggambar tanda tangan</small>
                            <button type="button" id="clear_guest_signature" class="btn btn-sm btn-secondary">Hapus Canvas</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 1rem;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan & Proses Check-in Tamu
                </button>
            </form>
        </div>

    </div>

    <script src="js/signature_pad.js"></script>
</body>
</html>
