<?php
$page_title = 'Rekap Laporan GA';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager', 'secom']);

// Filter Defaults
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');
$type       = $_GET['type'] ?? 'all';
$per_page   = 5;

// Current Pages for pagination
$guest_page   = max(1, intval($_GET['g_page'] ?? 1));
$borrow_page  = max(1, intval($_GET['b_page'] ?? 1));

// Totals for Summary Cards
$stmt = $pdo->prepare("SELECT COUNT(*) FROM guests WHERE DATE(time_in) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_guest_records = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM item_borrowings WHERE DATE(borrow_time) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_borrow_records = $stmt->fetchColumn();

// Fetch Guests (Paginated 7 items per page)
$guests = [];
$guest_total_pages = 0;
$guest_offset = 0;
if ($type === 'all' || $type === 'guest') {
    $guest_total_pages = ceil($total_guest_records / $per_page);
    $guest_offset = ($guest_page - 1) * $per_page;

    $stmt = $pdo->prepare("SELECT * FROM guests WHERE DATE(time_in) BETWEEN ? AND ? ORDER BY time_in DESC LIMIT $per_page OFFSET $guest_offset");
    $stmt->execute([$start_date, $end_date]);
    $guests = $stmt->fetchAll();
}

// Fetch Item Borrowings (Paginated 7 items per page)
$borrowings = [];
$borrow_total_pages = 0;
$borrow_offset = 0;
if ($type === 'all' || $type === 'borrowing') {
    $borrow_total_pages = ceil($total_borrow_records / $per_page);
    $borrow_offset = ($borrow_page - 1) * $per_page;

    $stmt = $pdo->prepare("SELECT * FROM item_borrowings WHERE DATE(borrow_time) BETWEEN ? AND ? ORDER BY borrow_time DESC LIMIT $per_page OFFSET $borrow_offset");
    $stmt->execute([$start_date, $end_date]);
    $borrowings = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Filter Rekapitulasi Laporan GA</h2>
            <small style="color: var(--text-muted);">Pilih rentang tanggal dan modul laporan yang ingin diekspor</small>
        </div>
        <div>
            <a href="export_report.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&type=<?= urlencode($type) ?>" class="btn btn-success">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Unduh Excel Rekap (.xls)
            </a>
        </div>
    </div>

    <form method="GET" action="report.php" class="grid-3" style="align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 0.5rem;">
            <div style="flex: 1;">
                <label class="form-label">Kategori Laporan</label>
                <select name="type" class="form-select">
                    <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Semua Modul (Tamu & Pinjam)</option>
                    <option value="guest" <?= $type === 'guest' ? 'selected' : '' ?>>Buku Tamu Digital</option>
                    <option value="borrowing" <?= $type === 'borrowing' ? 'selected' : '' ?>>Peminjaman Barang & Kunci</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
        </div>
    </form>
</div>

<!-- Summary Cards for Period -->
<div class="grid-4" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($total_guest_records) ?></div>
            <div class="stat-label">Total Tamu Periode Ini</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon info">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($total_borrow_records) ?></div>
            <div class="stat-label">Total Peminjaman Periode Ini</div>
        </div>
    </div>
</div>

<?php if ($type === 'all' || $type === 'guest'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Rekap Buku Tamu (<?= number_format($total_guest_records) ?> Records)</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Tamu</th>
                    <th>Instansi</th>
                    <th>Kategori</th>
                    <th>Orang Ditemui</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Keluar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($guests) === 0): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data tamu di rentang tanggal ini.</td></tr>
                <?php else: ?>
                    <?php $no = $guest_offset + 1; foreach ($guests as $g): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                            <td><?= htmlspecialchars($g['institution'] ?: '-') ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars(ucfirst($g['guest_category'])) ?></span></td>
                            <td><?= htmlspecialchars($g['person_to_meet']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($g['time_in'])) ?></td>
                            <td><?= $g['time_out'] ? date('d/m/Y H:i', strtotime($g['time_out'])) : 'Masih Masuk' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= render_pagination($guest_page, $guest_total_pages, ['g_page' => $guest_page]) ?>
</div>
<?php endif; ?>

<?php if ($type === 'all' || $type === 'borrowing'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Rekap Peminjaman Barang & Kunci (<?= number_format($total_borrow_records) ?> Records)</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Peminjam</th>
                    <th>Dept</th>
                    <th>Nama & Kode Barang</th>
                    <th>Qty</th>
                    <th>Waktu Pinjam</th>
                    <th>Waktu Kembali</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($borrowings) === 0): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada data peminjaman di rentang tanggal ini.</td></tr>
                <?php else: ?>
                    <?php $no = $borrow_offset + 1; foreach ($borrowings as $b): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($b['borrower_name']) ?></strong></td>
                            <td><?= htmlspecialchars($b['department']) ?></td>
                            <td><?= htmlspecialchars($b['item_name']) ?> <?= $b['item_code'] ? "({$b['item_code']})" : '' ?></td>
                            <td><?= $b['quantity'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($b['borrow_time'])) ?></td>
                            <td><?= $b['return_time'] ? date('d/m/Y H:i', strtotime($b['return_time'])) : '-' ?></td>
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
    <?= render_pagination($borrow_page, $borrow_total_pages, ['b_page' => $borrow_page]) ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
