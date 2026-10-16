<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$type = $_GET['type'] ?? 'barang';
$from = $_GET['from'] ?? '1900-01-01';
$to = $_GET['to'] ?? date('Y-m-d');

function csv_output($filename, $headers, $rows){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

if ($type === 'barang') {
    $rows = db()->query("SELECT b.kode_barang, b.nama_barang, COALESCE(k.nama,'') AS kategori, COALESCE(r.nama,'') AS ruangan, COALESCE(s.nama,'') AS supplier, b.merk, b.spesifikasi, b.tahun_pengadaan, b.sumber_dana, b.kondisi, b.jumlah, b.satuan, b.harga, b.status, b.keterangan FROM barang b LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN ruangan r ON r.id=b.ruangan_id LEFT JOIN supplier s ON s.id=b.supplier_id ORDER BY b.nama_barang")->fetchAll(PDO::FETCH_NUM);
    csv_output('barang_inventaris_' . date('Ymd') . '.csv', ['Kode', 'Nama Barang', 'Kategori', 'Ruangan', 'Supplier', 'Merk', 'Spesifikasi', 'Tahun', 'Sumber Dana', 'Kondisi', 'Jumlah', 'Satuan', 'Harga', 'Status', 'Keterangan'], $rows);
}

if ($type === 'stok') {
    $stmt = db()->prepare("SELECT l.tanggal, b.kode_barang, b.nama_barang, l.jenis, l.jumlah_masuk, l.jumlah_keluar, l.uraian, COALESCE(u.nama,'') AS user_nama FROM stok_log l JOIN barang b ON b.id=l.barang_id LEFT JOIN users u ON u.id=l.user_id WHERE l.tanggal BETWEEN ? AND ? ORDER BY l.tanggal DESC, l.id DESC");
    $stmt->execute([$from, $to]);
    csv_output('stok_' . date('Ymd') . '.csv', ['Tanggal', 'Kode Barang', 'Nama Barang', 'Jenis', 'Masuk', 'Keluar', 'Uraian', 'User'], $stmt->fetchAll(PDO::FETCH_NUM));
}

if ($type === 'peminjaman') {
    $stmt = db()->prepare("SELECT kode_pinjam, tanggal_pinjam, tanggal_rencana_kembali, tanggal_kembali, nama_peminjam, kelas_jurusan, no_hp, keperluan, status, catatan FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ? ORDER BY tanggal_pinjam DESC");
    $stmt->execute([$from, $to]);
    csv_output('peminjaman_' . date('Ymd') . '.csv', ['Kode', 'Tanggal Pinjam', 'Rencana Kembali', 'Tanggal Kembali', 'Peminjam', 'Kelas/Jurusan', 'No HP', 'Keperluan', 'Status', 'Catatan'], $stmt->fetchAll(PDO::FETCH_NUM));
}

set_flash('danger', 'Jenis export tidak valid.');
redirect('laporan.php');
