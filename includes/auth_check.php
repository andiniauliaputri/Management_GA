<?php
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memastikan pengguna sudah login.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=" . urlencode("Silakan login terlebih dahulu."));
        exit();
    }
}

/**
 * Memastikan pengguna memiliki peran yang diizinkan.
 */
function check_role($allowed_roles = []) {
    check_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: index.php?error=" . urlencode("Akses ditolak. Anda tidak memiliki izin ke halaman tersebut."));
        exit();
    }
}

/**
 * Cek apakah pengguna saat ini sedang login.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Mengambil data session user aktif.
 */
function get_logged_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['name'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'role'  => $_SESSION['role'] ?? 'secom'
    ];
}

/**
 * Set Flash Message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'text' => $message
    ];
}

/**
 * Ambil & Hapus Flash Message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Render Pagination Helper (5 Data Per Halaman)
 * Halaman 1 dan Halaman Terakhir Tetap di Ujung, Halaman Tengah Bergeser Dinamis.
 */
function render_pagination($current_page, $total_pages, $query_params = [], $page_param_name = 'page') {
    $total_pages = max(1, (int)$total_pages);
    $current_page = max(1, min($current_page, $total_pages));

    $build_url = function($page) use ($query_params, $page_param_name) {
        $params = array_merge($_GET, $query_params, [$page_param_name => $page]);
        return '?' . http_build_query($params);
    };

    $html = '<div class="pagination-container">';
    
    // Tombol Prev
    if ($current_page > 1) {
        $html .= '<a href="' . $build_url($current_page - 1) . '" class="page-link page-prev">&laquo; Prev</a>';
    } else {
        $html .= '<span class="page-link disabled">&laquo; Prev</span>';
    }

    $pages_to_show = [];
    $pages_to_show[] = 1; // Halaman 1 selalu tetap di awal

    if ($total_pages <= 7) {
        for ($i = 2; $i <= $total_pages; $i++) {
            $pages_to_show[] = $i;
        }
    } else {
        $middle_start = max(2, $current_page - 1);
        $middle_end   = min($total_pages - 1, $current_page + 1);

        if ($current_page <= 3) {
            $middle_start = 2;
            $middle_end   = 4;
        }
        if ($current_page >= $total_pages - 2) {
            $middle_start = $total_pages - 3;
            $middle_end   = $total_pages - 1;
        }

        if ($middle_start > 2) {
            $pages_to_show[] = '...';
        }

        for ($i = $middle_start; $i <= $middle_end; $i++) {
            $pages_to_show[] = $i;
        }

        if ($middle_end < $total_pages - 1) {
            $pages_to_show[] = '...';
        }

        $pages_to_show[] = $total_pages; // Halaman terakhir selalu tetap di akhir
    }

    foreach ($pages_to_show as $p) {
        if ($p === '...') {
            $html .= '<span class="page-link ellipsis">&hellip;</span>';
        } elseif ($p == $current_page) {
            $html .= '<span class="page-link active">' . $p . '</span>';
        } else {
            $html .= '<a href="' . $build_url($p) . '" class="page-link">' . $p . '</a>';
        }
    }

    // Tombol Next
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $build_url($current_page + 1) . '" class="page-link page-next">Next &raquo;</a>';
    } else {
        $html .= '<span class="page-link disabled">Next &raquo;</span>';
    }

    $html .= '</div>';
    return $html;
}
