<?php
$pageTitle = 'Laporan Inventaris';
require_once __DIR__ . '/includes/auth.php';
require_login();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$ringkasan = db()->query("SELECT COUNT(*) AS jenis_barang, COALESCE(SUM(jumlah),0) AS total_stok, COALESCE(SUM(jumlah * harga),0) AS nilai_aset FROM barang WHERE status='aktif'")->fetch();
$perKondisi = db()->query("SELECT kondisi, COUNT(*) AS total, COALESCE(SUM(jumlah),0) AS stok FROM barang WHERE status='aktif' GROUP BY kondisi")->fetchAll();
$perRuangan = db()->query("SELECT COALESCE(r.nama,'Tanpa Ruangan') AS ruangan, COUNT(b.id) AS jenis_barang, COALESCE(SUM(b.jumlah),0) AS stok, COALESCE(SUM(b.jumlah*b.harga),0) AS nilai FROM barang b LEFT JOIN ruangan r ON r.id=b.ruangan_id WHERE b.status='aktif' GROUP BY r.nama ORDER BY r.nama")->fetchAll();

$stmt = db()->prepare("SELECT l.*, b.kode_barang, b.nama_barang, u.nama AS user_nama FROM stok_log l JOIN barang b ON b.id=l.barang_id LEFT JOIN users u ON u.id=l.user_id WHERE l.tanggal BETWEEN ? AND ? ORDER BY l.tanggal DESC, l.id DESC");
$stmt->execute([$from, $to]);
$stokLogs = $stmt->fetchAll();

$stmt = db()->prepare("SELECT * FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ? ORDER BY tanggal_pinjam DESC");
$stmt->execute([$from, $to]);
$peminjaman = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="print-only text-center mb-4">
    <h2 class="mb-0"><?= e(APP_NAME) ?></h2>
    <p>Laporan periode <?= e(tanggal($from)) ?> s.d. <?= e(tanggal($to)) ?></p>
</div>
<form method="get" class="card border-0 shadow-sm rounded-4 mb-4 no-print">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-3"><label>Dari Tanggal</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
        <div class="col-md-3"><label>Sampai Tanggal</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
        <div class="col-md-6"><button class="btn btn-primary"><i class="bi bi-funnel mr-1"></i>Tampilkan</button> <button type="button" onclick="printPage()" class="btn btn-outline-secondary"><i class="bi bi-printer mr-1"></i>Cetak</button> <a href="<?= e(url('export.php?type=barang')) ?>" class="btn btn-outline-success"><i class="bi bi-filetype-csv mr-1"></i>Export Barang</a></div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-stat"><div class="card-body"><div class="text-muted small">Jenis Barang Aktif</div><div class="display-6 fw-bold"><?= e($ringkasan['jenis_barang']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card card-stat"><div class="card-body"><div class="text-muted small">Total Stok Tersedia</div><div class="display-6 fw-bold"><?= e($ringkasan['total_stok']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card card-stat"><div class="card-body"><div class="text-muted small">Estimasi Nilai Aset</div><div class="h2 fw-bold"><?= e(rupiah($ringkasan['nilai_aset'])) ?></div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Rekap Kondisi Barang</h2></div>
            <div class="card-body px-4"><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Kondisi</th><th>Jenis</th><th>Stok</th></tr></thead><tbody><?php foreach ($perKondisi as $row): ?><tr><td><?= status_badge($row['kondisi']) ?></td><td><?= e($row['total']) ?></td><td><?= e($row['stok']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Rekap Per Ruangan</h2></div>
            <div class="card-body px-4"><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Ruangan</th><th>Jenis Barang</th><th>Stok</th><th>Nilai</th></tr></thead><tbody><?php foreach ($perRuangan as $row): ?><tr><td><?= e($row['ruangan']) ?></td><td><?= e($row['jenis_barang']) ?></td><td><?= e($row['stok']) ?></td><td><?= e(rupiah($row['nilai'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between"><h2 class="h5 mb-0">Laporan Transaksi Stok</h2><a href="<?= e(url('export.php?type=stok&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>" class="btn btn-outline-success btn-sm no-print">Export CSV</a></div>
            <div class="card-body px-4"><div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Tanggal</th><th>Barang</th><th>Jenis</th><th>Masuk</th><th>Keluar</th><th>Uraian</th><th>User</th></tr></thead><tbody><?php foreach ($stokLogs as $row): ?><tr><td><?= e(tanggal($row['tanggal'])) ?></td><td><?= e($row['kode_barang'] . ' - ' . $row['nama_barang']) ?></td><td><?= status_badge($row['jenis']) ?></td><td><?= e($row['jumlah_masuk']) ?></td><td><?= e($row['jumlah_keluar']) ?></td><td><?= e($row['uraian']) ?></td><td><?= e($row['user_nama'] ?? '-') ?></td></tr><?php endforeach; ?><?php if (!$stokLogs): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?></tbody></table></div></div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between"><h2 class="h5 mb-0">Laporan Peminjaman</h2><a href="<?= e(url('export.php?type=peminjaman&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>" class="btn btn-outline-success btn-sm no-print">Export CSV</a></div>
            <div class="card-body px-4"><div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Kode</th><th>Peminjam</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Tgl Kembali</th><th>Status</th><th>Keperluan</th></tr></thead><tbody><?php foreach ($peminjaman as $row): ?><tr><td><?= e($row['kode_pinjam']) ?></td><td><?= e($row['nama_peminjam']) ?></td><td><?= e(tanggal($row['tanggal_pinjam'])) ?></td><td><?= e(tanggal($row['tanggal_rencana_kembali'])) ?></td><td><?= e(tanggal($row['tanggal_kembali'])) ?></td><td><?= status_badge($row['status']) ?></td><td><?= e($row['keperluan']) ?></td></tr><?php endforeach; ?><?php if (!$peminjaman): ?><tr><td colspan="7" class="text-center text-muted">Tidak ada peminjaman pada periode ini.</td></tr><?php endif; ?></tbody></table></div></div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
