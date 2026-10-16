<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Sinkronisasi keterlambatan dan ambil data via Service Layer
PeminjamanService::sinkronisasiStatusTerlambat();

$stats = BarangService::ambilStatistik();
$totalBarang = $stats['total_barang'];
$totalStok = $stats['total_stok'];
$totalRusak = $stats['total_rusak'];
$totalDipinjam = $stats['total_dipinjam'];

$lowStock = BarangService::ambilStokMenipis(8);
$recentLoans = PeminjamanService::ambilPeminjamanTerbaru(8);
$byCategory = BarangService::ambilRekapKategori(8);

require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card card-stat"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="text-muted small">Jenis Barang</div><div class="display-6 fw-bold"><?= e($totalBarang) ?></div></div>
            <div class="icon text-primary"><i class="bi bi-box-seam"></i></div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card card-stat"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="text-muted small">Total Stok Tersedia</div><div class="display-6 fw-bold"><?= e($totalStok) ?></div></div>
            <div class="icon text-success"><i class="bi bi-stack"></i></div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card card-stat"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="text-muted small">Barang Rusak</div><div class="display-6 fw-bold"><?= e($totalRusak) ?></div></div>
            <div class="icon text-danger"><i class="bi bi-exclamation-triangle"></i></div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card card-stat"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="text-muted small">Sedang Dipinjam</div><div class="display-6 fw-bold"><?= e($totalDipinjam) ?></div></div>
            <div class="icon text-warning"><i class="bi bi-journal-arrow-up"></i></div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Stok Menipis</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Kode</th><th>Barang</th><th>Kategori</th><th>Ruangan</th><th>Stok</th></tr></thead>
                        <tbody>
                        <?php if (!$lowStock): ?>
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada stok menipis.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($lowStock as $row): ?>
                            <tr>
                                <td><?= e($row['kode_barang']) ?></td>
                                <td><?= e($row['nama_barang']) ?></td>
                                <td><?= e($row['kategori'] ?? '-') ?></td>
                                <td><?= e($row['ruangan'] ?? '-') ?></td>
                                <td><span class="badge badge-danger"><?= e($row['jumlah']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Rekap Kategori</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Kategori</th><th>Jenis</th><th>Stok</th></tr></thead>
                        <tbody>
                        <?php foreach ($byCategory as $row): ?>
                            <tr><td><?= e($row['kategori']) ?></td><td><?= e($row['total']) ?></td><td><?= e($row['stok']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$byCategory): ?><tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Peminjaman Terbaru</h2>
                <a href="<?= e(url('peminjaman.php?action=create')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Buat Peminjaman</a>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Kode</th><th>Peminjam</th><th>Tanggal</th><th>Rencana Kembali</th><th>Status</th><th>Petugas</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentLoans as $row): ?>
                            <tr>
                                <td><?= e($row['kode_pinjam']) ?></td>
                                <td><?= e($row['nama_peminjam']) ?></td>
                                <td><?= e(tanggal($row['tanggal_pinjam'])) ?></td>
                                <td><?= e(tanggal($row['tanggal_rencana_kembali'])) ?></td>
                                <td><?= status_badge($row['status']) ?></td>
                                <td><?= e($row['petugas'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recentLoans): ?><tr><td colspan="6" class="text-center text-muted">Belum ada peminjaman.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
