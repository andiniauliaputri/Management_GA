<?php
$page_title = 'Buku Tamu Digital';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager', 'secom']);

// Handle POST actions (Check-out Tamu oleh Petugas / Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'checkout') {
        $guest_id = intval($_POST['guest_id'] ?? 0);
        if ($guest_id > 0) {
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE guests SET time_out = ? WHERE id = ? AND time_out IS NULL");
            $stmt->execute([$now, $guest_id]);
            set_flash_message('success', 'Tamu berhasil di-checkout.');
        }
    }
    header("Location: guest.php");
    exit();
}

$per_page = 5;

// 1. QUERY TABEL ATAS: Tamu Aktif (Masih di Lokasi) dengan Pagination 5 Data
$active_page = max(1, intval($_GET['active_page'] ?? 1));
$stmt = $pdo->query("SELECT COUNT(*) FROM guests WHERE time_out IS NULL");
$total_active_records = $stmt->fetchColumn();
$total_active_pages = ceil($total_active_records / $per_page);
$active_offset = ($active_page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT * FROM guests WHERE time_out IS NULL ORDER BY time_in DESC LIMIT $per_page OFFSET $active_offset");
$stmt->execute();
$active_guests = $stmt->fetchAll();

// 2. QUERY TABEL BAWAH: Riwayat Tamu (Sudah Keluar) dengan Pagination 5 Data
$search = trim($_GET['search'] ?? '');
$history_page = max(1, intval($_GET['history_page'] ?? 1));

$count_query = "SELECT COUNT(*) FROM guests WHERE time_out IS NOT NULL";
$params = [];

if (!empty($search)) {
    $count_query .= " AND (name LIKE ? OR institution LIKE ? OR person_to_meet LIKE ? OR visitor_card_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_history_records = $stmt->fetchColumn();
$total_history_pages = ceil($total_history_records / $per_page);
$history_offset = ($history_page - 1) * $per_page;

$data_query = str_replace("SELECT COUNT(*)", "SELECT *", $count_query) . " ORDER BY time_out DESC LIMIT $per_page OFFSET $history_offset";
$stmt = $pdo->prepare($data_query);
$stmt->execute($params);
$history_guests = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- TABEL 1: TAMU AKTIF (MASIH DI LOKASI) -->
<div class="card" style="border-top: 4px solid var(--success);">
    <div class="card-header">
        <div>
            <h2 class="card-title" style="color: #065f46; display: flex; align-items: center; gap: 0.5rem;">
                <span class="badge badge-success" style="font-size: 0.85rem;"><?= number_format($total_active_records) ?> Aktif</span>
                Daftar Tamu Masih di Lokasi
            </h2>
            <small style="color: var(--text-muted);">Tamu yang saat ini berada di dalam fasilitas pabrik / kantor</small>
        </div>
        <div>
            <a href="export_guest.php" class="btn btn-success btn-sm">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export Excel (.xls)
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Tamu</th>
                    <th>Instansi</th>
                    <th>Kategori</th>
                    <th>Tujuan & Orang Ditemui</th>
                    <th>No. Kartu</th>
                    <th>Waktu Masuk</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($active_guests) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 1.75rem;">Saat ini tidak ada tamu aktif di dalam lokasi.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = $active_offset + 1; foreach ($active_guests as $g): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($g['name']) ?></strong>
                                <?php if ($g['signature']): ?>
                                    <div style="font-size: 0.725rem; color: var(--primary); font-weight: 500;">✓ Ada Ttd Digital</div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($g['institution'] ?: '-') ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars(ucfirst($g['guest_category'])) ?></span></td>
                            <td>
                                <div><strong>Tujuan:</strong> <?= htmlspecialchars($g['purpose']) ?></div>
                                <div style="font-size: 0.775rem; color: var(--text-muted);">Bertemu: <?= htmlspecialchars($g['person_to_meet']) ?></div>
                            </td>
                            <td><code><?= htmlspecialchars($g['visitor_card_number'] ?: '-') ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($g['time_in'])) ?></td>
                            <td><span class="badge badge-success">Masih di Lokasi</span></td>
                            <td style="text-align: center;">
                                <form action="guest.php" method="POST" style="display: inline;" onsubmit="return confirm('Proses check-out untuk tamu <?= htmlspecialchars($g['name']) ?>?');">
                                    <input type="hidden" name="action" value="checkout">
                                    <input type="hidden" name="guest_id" value="<?= $g['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Check-out</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Tabel Tamu Aktif (5 Data Per Halaman) -->
    <?= render_pagination($active_page, $total_active_pages, [], 'active_page') ?>
</div>

<!-- TABEL 2: RIWAYAT BUKU TAMU (SUDAH KELUAR) -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <div>
            <h2 class="card-title">Riwayat Buku Tamu (Sudah Keluar)</h2>
            <small style="color: var(--text-muted);">Arsip kunjungan tamu yang telah selesai (Total: <?= number_format($total_history_records) ?> data)</small>
        </div>
    </div>

    <!-- Search Bar khusus Riwayat -->
    <form method="GET" action="guest.php" style="margin-bottom: 1.25rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama, instansi, atau kartu di riwayat..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Cari Riwayat</button>
        <?php if (!empty($search)): ?>
            <a href="guest.php" class="btn btn-outline btn-sm">Reset Pencarian</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Tamu</th>
                    <th>Instansi</th>
                    <th>Kategori</th>
                    <th>Tujuan & Orang Ditemui</th>
                    <th>No. Kartu</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Keluar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($history_guests) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 1.75rem;">Belum ada data riwayat kunjungan tamu.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = $history_offset + 1; foreach ($history_guests as $g): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                            <td><?= htmlspecialchars($g['institution'] ?: '-') ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars(ucfirst($g['guest_category'])) ?></span></td>
                            <td>
                                <div><strong>Tujuan:</strong> <?= htmlspecialchars($g['purpose']) ?></div>
                                <div style="font-size: 0.775rem; color: var(--text-muted);">Bertemu: <?= htmlspecialchars($g['person_to_meet']) ?></div>
                            </td>
                            <td><code><?= htmlspecialchars($g['visitor_card_number'] ?: '-') ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($g['time_in'])) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($g['time_out'])) ?></td>
                            <td><span class="badge badge-secondary">Sudah Keluar</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Tabel Riwayat Tamu (5 Data Per Halaman) -->
    <?= render_pagination($history_page, $total_history_pages, ['search' => $search], 'history_page') ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
