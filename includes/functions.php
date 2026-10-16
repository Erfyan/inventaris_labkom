<?php
require_once __DIR__ . '/../config/database.php';

// Autoloader untuk Service Layer
spl_autoload_register(function ($class) {
    $serviceFile = __DIR__ . '/services/' . $class . '.php';
    if (file_exists($serviceFile)) {
        require_once $serviceFile;
    }
});

function e($value){
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path){
    header('Location: ' . url($path));
    exit;
}

function set_flash($type, $message){
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash(){
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = e($flash['type']);
    $message = e($flash['message']);

    return "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
        {$message}
            <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>
    </div>";
}

function rupiah($number){
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}

function tanggal($date){
    if (!$date) {
        return '-';
    }
    return date('d/m/Y', strtotime($date));
}

function tanggal_jam($datetime){
    if (!$datetime) {
        return '-';
    }
    return date('d/m/Y H:i', strtotime($datetime));
}

function selected($a, $b){
    return (string) $a === (string) $b ? 'selected' : '';
}

function checked($condition){
    return $condition ? 'checked' : '';
}

function status_badge($status){
    $map = [
        'aktif' => 'success',
        'nonaktif' => 'secondary',
        'baik' => 'success',
        'rusak_ringan' => 'warning',
        'rusak_berat' => 'danger',
        'hilang' => 'dark',
        'masuk' => 'success',
        'keluar' => 'danger',
        'penyesuaian' => 'info',
        'mutasi' => 'primary',
        'dipinjam' => 'warning',
        'dikembalikan' => 'success',
        'terlambat' => 'danger',
        'dibatalkan' => 'secondary',
        'proses' => 'warning',
        'selesai' => 'success',
        'batal' => 'secondary',
        'admin' => 'danger',
        'petugas' => 'primary',
        'kepala_lab' => 'dark',
    ];
    $class = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge badge-' . $class . '">' . e($label) . '</span>';
}

function generate_code($prefix, $table, $column){
    $allowed = [
        'barang' => 'kode_barang',
        'peminjaman' => 'kode_pinjam',
    ];

    if (!isset($allowed[$table]) || $allowed[$table] !== $column) {
        throw new InvalidArgumentException('Generator kode tidak valid.');
    }

    $date = date('Ymd');
    $like = $prefix . $date . '%';
    $stmt = db()->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY {$column} DESC LIMIT 1");
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();

    $next = 1;
    if ($last) {
        $next = ((int) substr($last, -4)) + 1;
    }

    return $prefix . $date . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function count_table($table, $where = '1=1'){
    $allowed = ['users', 'kategori', 'ruangan', 'supplier', 'barang', 'stok_log', 'peminjaman', 'mutasi', 'perawatan'];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }
    return (int) db()->query("SELECT COUNT(*) FROM {$table} WHERE {$where}")->fetchColumn();
}

function log_audit($aksi, $tabel, $recordId = null, $keterangan = ''){
    if (empty($_SESSION['user']['id'])) {
        return;
    }
    $stmt = db()->prepare('INSERT INTO audit_logs (user_id, aksi, tabel, record_id, keterangan, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $_SESSION['user']['id'],
        $aksi,
        $tabel,
        $recordId,
        $keterangan,
        $_SERVER['REMOTE_ADDR'] ?? '',
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
}

function get_options($table, $orderBy = 'nama'){
    $allowed = ['kategori', 'ruangan', 'supplier', 'barang'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }
    $allowedOrder = ['nama', 'nama_barang', 'kode_barang'];
    if (!in_array($orderBy, $allowedOrder, true)) {
        $orderBy = 'nama';
    }
    return db()->query("SELECT * FROM {$table} ORDER BY {$orderBy}")->fetchAll();
}
