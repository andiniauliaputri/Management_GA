<?php
$page_title = 'Dashboard Monitoring GA';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager']);

// Statistics Query Today
$today = date('Y-m-d');

// 1. Total Tamu Hari Ini
$stmt = $pdo->prepare("SELECT COUNT(*) FROM guests WHERE DATE(time_in) = ?");
$stmt->execute([$today]);
$count_guests_today = $stmt->fetchColumn();

// 2. Tamu Aktif Masih di Lokasi
$stmt = $pdo->query("SELECT COUNT(*) FROM guests WHERE time_out IS NULL");
$count_guests_active = $stmt->fetchColumn();

// 3. Total Peminjaman Hari Ini
$stmt = $pdo->prepare("SELECT COUNT(*) FROM item_borrowings WHERE DATE(borrow_time) = ?");
$stmt->execute([$today]);
$count_borrow_today = $stmt->fetchColumn();

// 4. Barang Belum Dikembalikan
$stmt = $pdo->query("SELECT COUNT(*) FROM item_borrowings WHERE status = 'borrowed'");
$count_borrow_pending = $stmt->fetchColumn();

// Recent Guests Today (Limit 5)
$stmt = $pdo->prepare("SELECT * FROM guests ORDER BY time_in DESC LIMIT 5");
$stmt->execute();
$recent_guests = $stmt->fetchAll();

// Recent Item Borrowings (Limit 5)
$stmt = $pdo->prepare("SELECT * FROM item_borrowings ORDER BY borrow_time DESC LIMIT 5");
$stmt->execute();
$recent_borrowings = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Stat Cards Row -->
<div class="grid-4" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($count_guests_today) ?></div>
            <div class="stat-label">Tamu Hari Ini</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($count_guests_active) ?></div>
            <div class="stat-label">Tamu Masih di Lokasi</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($count_borrow_today) ?></div>
            <div class="stat-label">Peminjaman Hari Ini</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($count_borrow_pending) ?></div>
            <div class="stat-label">Barang Belum Kembali</div>
        </div>
    </div>
</div>

<!-- Grid 2 Tables -->
<div class="grid-2">
    <!-- Recent Guests -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Riwayat Tamu Terbaru</h3>
            <a href="guest.php" class="btn btn-sm btn-outline">Lihat Riwayat Lengkap →</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Instansi</th>
                        <th>Waktu Masuk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_guests) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada riwayat kunjungan tamu.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_guests as $g): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                                <td><?= htmlspecialchars($g['institution'] ?: '-') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($g['time_in'])) ?></td>
                                <td>
                                    <?php if ($g['time_out']): ?>
                                        <span class="badge badge-secondary">Selesai</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Borrowings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Riwayat Peminjaman Terbaru</h3>
            <a href="borrowing.php" class="btn btn-sm btn-outline">Lihat Riwayat Lengkap →</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Peminjam</th>
                        <th>Barang</th>
                        <th>Waktu Pinjam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_borrowings) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted);">Belum ada peminjaman barang hari ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_borrowings as $b): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($b['borrower_name']) ?></strong> (<?= htmlspecialchars($b['department']) ?>)</td>
                                <td><?= htmlspecialchars($b['item_name']) ?></td>
                                <td><?= date('H:i', strtotime($b['borrow_time'])) ?></td>
                                <td>
                                    <?php if ($b['status'] === 'borrowed'): ?>
                                        <span class="badge badge-warning">Dipinjam</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Dikembalikan</span>
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
