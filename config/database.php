<?php
require_once __DIR__ . '/config.php';

// Ubah sesuai konfigurasi database lokal/server.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'inventaris_lab_smkn3');
define('DB_USER', 'root');
define('DB_PASS', '');

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
