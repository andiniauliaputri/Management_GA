<?php
$page_title = 'Pengaturan Akun & Pengarsipan System';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager', 'secom']);
$logged_user = get_logged_user();

// Handle Form Posts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
            set_flash_message('danger', 'Semua bidang password wajib diisi.');
        } elseif ($new_pass !== $confirm_pass) {
            set_flash_message('danger', 'Konfirmasi password baru tidak cocok.');
        } elseif (strlen($new_pass) < 6) {
            set_flash_message('danger', 'Password baru minimal 6 karakter.');
        } else {
            // Check old password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$logged_user['id']]);
            $current_hash = $stmt->fetchColumn();

            if ($current_hash && password_verify($old_pass, $current_hash)) {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $logged_user['id']]);
                set_flash_message('success', 'Password akun Anda berhasil diperbarui!');
            } else {
                set_flash_message('danger', 'Password lama Anda salah.');
            }
        }
        header("Location: settings.php");
        exit();
    } elseif ($action === 'run_archive' && $logged_user['role'] === 'manager') {
        // Run archiving for data older than 3 months
        if (!is_dir(__DIR__ . '/archives')) {
            mkdir(__DIR__ . '/archives', 0777, true);
        }

        $cutoff_date = date('Y-m-d H:i:s', strtotime('-3 months'));

        // Fetch old completed guests
        $stmt = $pdo->prepare("SELECT * FROM guests WHERE time_out IS NOT NULL AND time_out < ?");
        $stmt->execute([$cutoff_date]);
        $old_guests = $stmt->fetchAll();

        // Fetch old completed borrowings
        $stmt = $pdo->prepare("SELECT * FROM item_borrowings WHERE status = 'returned' AND return_time < ?");
        $stmt->execute([$cutoff_date]);
        $old_borrowings = $stmt->fetchAll();

        $total_records = count($old_guests) + count($old_borrowings);

        if ($total_records === 0) {
            set_flash_message('info', 'Tidak ditemukan data selesai yang lebih dari 3 bulan untuk diarsip.');
        } else {
            $filename = "Arsip_GA_" . date('Ymd_His') . ".csv";
            $filepath = __DIR__ . '/archives/' . $filename;
            $fp = fopen($filepath, 'w');

            // Write CSV Header
            fputcsv($fp, ['Tipe Data', 'ID', 'Nama Peminjam/Tamu', 'Instansi/Dept', 'Detail/Tujuan', 'Waktu Masuk/Pinjam', 'Waktu Keluar/Kembali', 'Catatan']);

            foreach ($old_guests as $g) {
                fputcsv($fp, ['Buku Tamu', $g['id'], $g['name'], $g['institution'], $g['purpose'], $g['time_in'], $g['time_out'], $g['guest_category']]);
            }
            foreach ($old_borrowings as $b) {
                fputcsv($fp, ['Peminjaman', $b['id'], $b['borrower_name'], $b['department'], $b['item_name'], $b['borrow_time'], $b['return_time'], $b['return_condition']]);
            }
            fclose($fp);

            // Record Archive log
            $stmt = $pdo->prepare("INSERT INTO archives (filename, archive_type, records_count) VALUES (?, 'Otomatis > 3 Bulan', ?)");
            $stmt->execute([$filename, $total_records]);

            // Clean up database tables
            $stmt = $pdo->prepare("DELETE FROM guests WHERE time_out IS NOT NULL AND time_out < ?");
            $stmt->execute([$cutoff_date]);
            $stmt = $pdo->prepare("DELETE FROM item_borrowings WHERE status = 'returned' AND return_time < ?");
            $stmt->execute([$cutoff_date]);

            set_flash_message('success', "Berhasil mengarsipkan $total_records data lama ke file $filename.");
        }
        header("Location: settings.php");
        exit();
    }
}

// Fetch Archive list
$archives = [];
try {
    $stmt = $pdo->query("SELECT * FROM archives ORDER BY created_at DESC");
    $archives = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<div class="grid-2">
    <!-- Change Password Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Profil & Ubah Password</h2>
        </div>
        <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            <div><strong>Pengguna Saat Ini:</strong> <?= htmlspecialchars($logged_user['name']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($logged_user['email']) ?></div>
            <div><strong>Peran (Role):</strong> <span class="badge badge-info"><?= htmlspecialchars($logged_user['role']) ?></span></div>
        </div>

        <form action="settings.php" method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label class="form-label">Password Lama *</label>
                <input type="password" name="old_password" required class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Password Baru *</label>
                <input type="password" name="new_password" required class="form-control" placeholder="Minimal 6 karakter">
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru *</label>
                <input type="password" name="confirm_password" required class="form-control">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan Password</button>
        </form>
    </div>

    <!-- Archiving Card (Manager Only) -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Pengarsipan & Pembersihan Data</h2>
        </div>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Fitur pengarsipan secara otomatis mengekspor dan membersihkan record data tamu & peminjaman yang sudah selesai dan berumur lebih dari 3 bulan untuk mengoptimalkan performa database.
        </p>

        <?php if ($logged_user['role'] === 'manager'): ?>
            <form action="settings.php" method="POST" onsubmit="return confirm('Jalankan proses pengarsipan data lama (> 3 bulan)? Data akan dipindahkan ke file CSV arsip.');">
                <input type="hidden" name="action" value="run_archive">
                <button type="submit" class="btn btn-warning btn-block" style="margin-bottom: 1.5rem;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8zm14 0l-2 10H7L5 8"></path></svg>
                    Jalankan Pengarsipan Manual Data > 3 Bulan
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                Pengarsipan data hanya dapat dijalankan oleh peranan <strong>Manager</strong>.
            </div>
        <?php endif; ?>

        <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 0.75rem;">Daftar File Arsip Terimpan</h4>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>File Arsip</th>
                        <th>Jumlah Record</th>
                        <th>Waktu Arsip</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($archives) === 0): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Belum ada berkas arsip.</td></tr>
                    <?php else: ?>
                        <?php foreach ($archives as $arc): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($arc['filename']) ?></code></td>
                                <td><?= number_format($arc['records_count']) ?> data</td>
                                <td><?= date('d/m/Y H:i', strtotime($arc['created_at'])) ?></td>
                                <td>
                                    <?php if (file_exists(__DIR__ . '/archives/' . $arc['filename'])): ?>
                                        <a href="archives/<?= urlencode($arc['filename']) ?>" download class="btn btn-sm btn-outline">Download CSV</a>
                                    <?php else: ?>
                                        <span style="color: var(--danger); font-size: 0.8rem;">File Hilang</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
