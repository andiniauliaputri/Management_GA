<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrower_name = trim($_POST['borrower_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $item_name = trim($_POST['item_name'] ?? '');
    $item_code = trim($_POST['item_code'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    $initial_condition = trim($_POST['initial_condition'] ?? 'Baik');
    $signature = $_POST['signature'] ?? '';

    if (empty($borrower_name) || empty($department) || empty($item_name)) {
        $error_msg = "Nama Peminjam, Departemen, dan Nama Barang wajib diisi!";
    } else {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO item_borrowings (borrower_name, department, item_name, item_code, quantity, borrow_time, initial_condition, signature, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'borrowed')");
            $stmt->execute([$borrower_name, $department, $item_name, $item_code, $quantity, $now, $initial_condition, $signature]);
            
            set_flash_message('success', 'Peminjaman barang/kunci berhasil dicatat!');
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $error_msg = "Gagal menyimpan data peminjaman: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman Barang & Kunci - GA Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="portal-body">

    <header class="portal-navbar">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div class="sidebar-logo-icon">GA</div>
            <div>
                <strong style="font-size: 1.1rem; color: #fff;">Form Peminjaman Barang & Kunci</strong>
                <div style="font-size: 0.75rem; color: #94a3b8;">General Affairs Inventory Request</div>
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
                    <h2 class="card-title">Form Peminjaman Barang / Kunci</h2>
                    <small style="color: var(--text-muted);">Lengkapi data peminjaman peralatan inventaris GA</small>
                </div>
            </div>

            <form action="borrowing_form.php" method="POST">
                <input type="hidden" name="signature" id="borrow_signature_input">

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nama Peminjam (Karyawan / Staf) *</label>
                        <input type="text" name="borrower_name" required class="form-control" placeholder="Nama Lengkap" value="<?= htmlspecialchars($_POST['borrower_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Departemen / Bagian *</label>
                        <input type="text" name="department" required class="form-control" placeholder="Contoh: IT / Production / HR" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Nama Barang / Kunci *</label>
                        <input type="text" name="item_name" required class="form-control" placeholder="Kunci Ruang Meeting A / Proyektor" value="<?= htmlspecialchars($_POST['item_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kode / Nomor Barang (Opsional)</label>
                        <input type="text" name="item_code" class="form-control" placeholder="Contoh: K-05 / PRJ-01" value="<?= htmlspecialchars($_POST['item_code'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jumlah (Qty)</label>
                        <input type="number" name="quantity" value="<?= intval($_POST['quantity'] ?? 1) ?>" min="1" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi Awal Barang</label>
                    <select name="initial_condition" class="form-select">
                        <option value="Baik">Baik & Berfungsi Normal</option>
                        <option value="Cacat Fisik Ringan">Cacat Fisik Ringan (Goresan)</option>
                        <option value="Kondisi Khusus">Kondisi Khusus / Catatan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanda Tangan Digital Peminjam</label>
                    <div class="signature-container">
                        <canvas id="borrow_signature_canvas" class="signature-canvas"></canvas>
                        <div class="signature-controls">
                            <small style="color: #64748b;">Gunakan mouse atau layar sentuh untuk menggambar tanda tangan</small>
                            <button type="button" id="clear_borrow_signature" class="btn btn-sm btn-secondary">Hapus Canvas</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg btn-block" style="margin-top: 1rem;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Data Peminjaman
                </button>
            </form>
        </div>

    </div>

    <script src="js/signature_pad.js"></script>
</body>
</html>
