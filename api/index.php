<?php
// Entrypoint Router Vercel untuk PHP Native
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($path, '/');

if (empty($path) || $path === 'index.php') {
    require __DIR__ . '/../index.php';
    exit();
}

$file = __DIR__ . '/../' . $path;

if (file_exists($file) && is_file($file)) {
    require $file;
} else {
    require __DIR__ . '/../index.php';
}
