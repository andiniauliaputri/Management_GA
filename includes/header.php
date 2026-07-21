<?php
require_once __DIR__ . '/auth_check.php';
$logged_user = get_logged_user();
$page_title = $page_title ?? 'GA Management System';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - GA Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<script>
    // Restore sidebar collapse state immediately to prevent layout flicker
    if (localStorage.getItem('sidebar_collapsed') === 'true') {
        document.body.classList.add('preload-collapsed');
    }
</script>
<div class="app-container" id="app-container">
    <script>
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.getElementById('app-container').classList.add('collapsed');
        }
    </script>
    <div class="sidebar-backdrop" onclick="toggleSidebarToggle()"></div>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-wrapper">
        <header class="top-navbar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button type="button" class="btn-sidebar-toggle" onclick="toggleSidebarToggle()" title="Tutup / Buka Menu Left Sidebar">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="page-title-heading"><?= htmlspecialchars($page_title) ?></div>
            </div>
            <div class="top-nav-actions">
                <!-- Portal Link removed for internal staff/manager header -->
            </div>
        </header>

        <main class="content-body">
            <?php
            $flash = get_flash_message();
            if ($flash):
            ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?= htmlspecialchars($flash['text']) ?>
                </div>
            <?php endif; ?>
