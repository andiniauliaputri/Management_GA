<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user = get_logged_user();
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div class="sidebar-logo-icon">GA</div>
            <div class="sidebar-brand">
                GA Management
                <span>System Operational</span>
            </div>
        </div>
        <button type="button" class="sidebar-mobile-close" onclick="toggleSidebarToggle()" title="Tutup Menu">&times;</button>
    </div>

    <nav class="sidebar-nav">
        <?php if ($user && $user['role'] === 'manager'): ?>
            <div class="nav-section-title">Monitoring</div>

            <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" title="Dashboard Monitoring">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v12a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM9 14a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                <span class="nav-text">Dashboard Monitoring</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title">Layanan Operasional</div>

        <a href="guest.php" class="nav-item <?= $current_page === 'guest.php' ? 'active' : '' ?>" title="Buku Tamu Digital">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span class="nav-text">Buku Tamu Digital</span>
        </a>

        <a href="borrowing.php" class="nav-item <?= $current_page === 'borrowing.php' ? 'active' : '' ?>" title="Peminjaman Barang & Kunci">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
            <span class="nav-text">Peminjaman Barang & Kunci</span>
        </a>

        <div class="nav-section-title">Laporan & Pengaturan</div>

        <a href="report.php" class="nav-item <?= $current_page === 'report.php' ? 'active' : '' ?>" title="Rekap Laporan GA">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span class="nav-text">Rekap Laporan GA</span>
        </a>

        <a href="settings.php" class="nav-item <?= $current_page === 'settings.php' ? 'active' : '' ?>" title="Pengaturan">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="nav-text">Pengaturan</span>
        </a>
    </nav>

    <?php if ($user): ?>
    <div class="sidebar-footer">
        <div class="user-profile-badge">
            <div class="user-info">
                <div class="user-name" title="<?= htmlspecialchars($user['name']) ?>"><?= htmlspecialchars($user['name']) ?></div>
                <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
            </div>
            <a href="logout.php" class="logout-btn" title="Logout Session">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </a>
        </div>
    </div>
    <?php endif; ?>
</aside>
