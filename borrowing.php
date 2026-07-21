<?php
$page_title = 'Peminjaman Barang & Kunci';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

check_role(['manager', 'secom']);

// Handle POST actions (Proses Pengembalian Barang oleh Petugas / Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'return_item') {
        $borrow_id = intval($_POST['borrow_id'] ?? 0);
        $return_condition = trim($_POST['return_condition'] ?? 'Baik');

        if ($borrow_id > 0) {
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE item_borrowings SET return_time = ?, return_condition = ?, status = 'returned' WHERE id = ? AND status = 'borrowed'");
            $stmt->execute([$now, $return_condition, $borrow_id]);
            set_flash_message('success', 'Pengembalian barang berhasil diproses.');
        }
    }
    header("Location: borrowing.php");
    exit();
}

$per_page = 5;

// 1. QUERY TABEL ATAS: Barang Sedang Dipinjam (Aktif) dengan Pagination 5 Data
$active_page = max(1, intval($_GET['active_page'] ?? 1));
$stmt = $pdo->query("SELECT COUNT(*) FROM item_borrowings WHERE status = 'borrowed'");
$total_active_records = $stmt->fetchColumn();
$total_active_pages = ceil($total_active_records / $per_page);
$active_offset = ($active_page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT * FROM item_borrowings WHERE status = 'borrowed' ORDER BY borrow_time DESC LIMIT $per_page OFFSET $active_offset");
$stmt->execute();
$active_borrowings = $stmt->fetchAll();

// 2. QUERY TABEL BAWAH: Riwayat Peminjaman (Sudah Dikembalikan) dengan Pagination 5 Data
$search = trim($_GET['search'] ?? '');
$history_page = max(1, intval($_GET['history_page'] ?? 1));

$count_query = "SELECT COUNT(*) FROM item_borrowings WHERE status = 'returned'";
$params = [];

if (!empty($search)) {
    $count_query .= " AND (borrower_name LIKE ? OR department LIKE ? OR item_name LIKE ? OR item_code LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_history_records = $stmt->fetchColumn();
$total_history_pages = ceil($total_history_records / $per_page);
$history_offset = ($history_page - 1) * $per_page;

$data_query = str_replace("SELECT COUNT(*)", "SELECT *", $count_query) . " ORDER BY return_time DESC LIMIT $per_page OFFSET $history_offset";
$stmt = $pdo->prepare($data_query);
$stmt->execute($params);
$history_borrowings = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- TABEL 1: BARANG SEDANG DIPINJAM (AKTIF) -->
<div class="card" style="border-top: 4px solid var(--warning);">
    <div class="card-header">
        <div>
            <h2 class="card-title" style="color: #92400e; display: flex; align-items: center; gap: 0.5rem;">
                <span class="badge badge-warning" style="font-size: 0.85rem;"><?= number_format($total_active_records) ?> Dipinjam</span>
                Daftar Barang & Kunci Sedang Dipinjam
            </h2>
            <small style="color: var(--text-muted);">Aset GA yang saat ini sedang dibawa/dipinjam oleh karyawan</small>
        </div>
        <div>
            <a href="export_borrowing.php" class="btn btn-success btn-sm">
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
                    <th>Nama Peminjam</th>
                    <th>Dept</th>
                    <th>Barang & Kode</th>
                    <th>Qty</th>
                    <th>Waktu Pinjam</th>
                    <th>Kondisi Awal</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($active_borrowings) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 1.75rem;">Saat ini tidak ada barang yang sedang dipinjam.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = $active_offset + 1; foreach ($active_borrowings as $item): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['borrower_name']) ?></strong>
                                <?php if ($item['signature']): ?>
                                    <div style="font-size: 0.725rem; color: var(--primary); font-weight: 500;">✓ Ada Ttd Digital</div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($item['department']) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                <?php if ($item['item_code']): ?>
                                    <div><code><?= htmlspecialchars($item['item_code']) ?></code></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $item['quantity'] ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($item['borrow_time'])) ?></td>
                            <td><?= htmlspecialchars($item['initial_condition']) ?></td>
                            <td><span class="badge badge-warning">Sedang Dipinjam</span></td>
                            <td style="text-align: center;">
                                <button type="button" class="btn btn-sm btn-success" onclick="openReturnModal(<?= $item['id'] ?>, '<?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['borrower_name'], ENT_QUOTES) ?>')">
                                    Kembalikan
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Tabel Barang Dipinjam (5 Data Per Halaman) -->
    <?= render_pagination($active_page, $total_active_pages, [], 'active_page') ?>
</div>

<!-- TABEL 2: RIWAYAT PEMINJAMAN (SUDAH DIKEMBALIKAN) -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <div>
            <h2 class="card-title">Riwayat Peminjaman (Sudah Dikembalikan)</h2>
            <small style="color: var(--text-muted);">Arsip barang/kunci yang telah selesai dikembalikan (Total: <?= number_format($total_history_records) ?> data)</small>
        </div>
    </div>

    <!-- Search Bar khusus Riwayat -->
    <form method="GET" action="borrowing.php" style="margin-bottom: 1.25rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama, barang, atau dept di riwayat..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Cari Riwayat</button>
        <?php if (!empty($search)): ?>
            <a href="borrowing.php" class="btn btn-outline btn-sm">Reset Pencarian</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Peminjam</th>
                    <th>Dept</th>
                    <th>Barang & Kode</th>
                    <th>Qty</th>
                    <th>Waktu Pinjam</th>
                    <th>Waktu Kembali</th>
                    <th>Kondisi (Awal / Akhir)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($history_borrowings) === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 1.75rem;">Belum ada data riwayat pengembalian barang.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = $history_offset + 1; foreach ($history_borrowings as $b): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($b['borrower_name']) ?></strong></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($b['department']) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($b['item_name']) ?></strong>
                                <?php if ($b['item_code']): ?>
                                    <div><code><?= htmlspecialchars($b['item_code']) ?></code></div>
                                <?php endif; ?>
                            </td>
                            <td><?= $b['quantity'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($b['borrow_time'])) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($b['return_time'])) ?></td>
                            <td>
                                <div style="font-size: 0.8rem;"><strong>Awal:</strong> <?= htmlspecialchars($b['initial_condition']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><strong>Kembali:</strong> <?= htmlspecialchars($b['return_condition'] ?: '-') ?></div>
                            </td>
                            <td><span class="badge badge-success">Dikembalikan</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Tabel Riwayat Peminjaman (5 Data Per Halaman) -->
    <?= render_pagination($history_page, $total_history_pages, ['search' => $search], 'history_page') ?>
</div>

<!-- MODAL PROSES PENGEMBALIAN BARANG -->
<div id="modal-return-item" class="modal-backdrop">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Proses Pengembalian Barang</h3>
            <button type="button" class="modal-close" onclick="toggleModal('modal-return-item', false)">&times;</button>
        </div>
        <form action="borrowing.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="return_item">
                <input type="hidden" name="borrow_id" id="return_borrow_id">

                <p style="margin-bottom: 1rem;" id="return_info_text"></p>

                <div class="form-group">
                    <label class="form-label">Kondisi Barang Saat Dikembalikan *</label>
                    <select name="return_condition" class="form-select">
                        <option value="Baik / Sesuai Semula">Baik / Sesuai Semula</option>
                        <option value="Ada Kerusakan">Ada Kerusakan / Cacat Baru</option>
                        <option value="Hilang / Tidak Lengkap">Hilang / Tidak Lengkap</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('modal-return-item', false)">Batal</button>
                <button type="submit" class="btn btn-success">Konfirmasi Pengembalian</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(modalId, show) {
    const el = document.getElementById(modalId);
    if (show) {
        el.classList.add('show');
    } else {
        el.classList.remove('show');
    }
}

function openReturnModal(id, itemName, borrowerName) {
    document.getElementById('return_borrow_id').value = id;
    document.getElementById('return_info_text').innerHTML = 'Pengembalian barang <strong>' + itemName + '</strong> dipinjam oleh <strong>' + borrowerName + '</strong>.';
    toggleModal('modal-return-item', true);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
