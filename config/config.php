<?php
// Konfigurasi umum aplikasi

date_default_timezone_set('Asia/Makassar');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Inventaris Lab SMKN 3 Soppeng');
define('APP_SHORT_NAME', 'Inventaris Lab');
define('APP_VERSION', '1.0.0');

// Kosongkan untuk link relatif. Jika perlu URL absolut, isi contoh: /lab_inventaris_smkn3_soppeng
define('BASE_URL', '');

function url($path = ''){
    $base = trim(BASE_URL, '/');
    $path = ltrim($path, '/');

    if ($base === '') {
        return $path === '' ? './' : $path;
    }

    return '/' . $base . ($path === '' ? '' : '/' . $path);
}
