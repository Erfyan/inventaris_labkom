<?php
require_once __DIR__ . '/config.php';

// Memuat file override lokal jika tersedia
if (file_exists(__DIR__ . '/database.local.php')) {
    require_once __DIR__ . '/database.local.php';
}

// Deteksi environment otomatis: Hosting InfinityFree vs Lokal Laragon
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = empty($httpHost)
    || in_array($httpHost, ['localhost', '127.0.0.1'], true)
    || str_starts_with($httpHost, 'localhost:')
    || str_starts_with($httpHost, '127.0.0.1:')
    || str_ends_with($httpHost, '.test');

if ($isLocal) {
    // Konfigurasi Lokal (Laragon / Localhost)
    if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
    if (!defined('DB_NAME')) define('DB_NAME', 'inventaris_lab_smkn3');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
} else {
    // Konfigurasi Hosting InfinityFree
    if (!defined('DB_HOST')) define('DB_HOST', 'sql313.infinityfree.com');
    if (!defined('DB_NAME')) define('DB_NAME', 'if0_43189308_inventaris_labkomsmkn3');
    if (!defined('DB_USER')) define('DB_USER', 'if0_43189308');
    if (!defined('DB_PASS')) define('DB_PASS', 'oI0Y8j87GO8W7a');
}

function db(){
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
