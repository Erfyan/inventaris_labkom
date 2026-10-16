<?php
$pageTitle = 'Transaksi Stok';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_role(['admin', 'petugas']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    try {
        $barangId = (int) ($_POST['barang_id'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $jenis = $_POST['jenis'] ?? 'masuk';
        $jumlahInput = max(0, (int) ($_POST['jumlah'] ?? 0));
        $uraian = trim($_POST['uraian'] ?? '');
        $userId = (int) current_user()['id'];

        StokService::prosesTransaksi($barangId, $jenis, $jumlahInput, $tanggal, $uraian, $userId);
        set_flash('success', 'Transaksi stok berhasil disimpan.');
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses stok: ' . $e->getMessage());
    }
    redirect('stok.php');
}

$barang = db()->query("SELECT id, kode_barang, nama_barang, jumlah, satuan FROM barang WHERE status='aktif' ORDER BY nama_barang")->fetchAll();
$logs = db()->query("SELECT l.*, b.kode_barang, b.nama_barang, u.nama AS user_nama FROM stok_log l JOIN barang b ON b.id=l.barang_id LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC, l.id DESC LIMIT 100")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Input Transaksi Stok</h2></div>
            <div class="card-body px-4 pb-4">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="required">Tanggal</label><input type="date" name="tanggal" class="form-control" required value="<?= e(date('Y-m-d')) ?>"></div>
                    <div class="mb-3"><label class="required">Barang</label><select name="barang_id" class="form-control" required><option value="">- Pilih Barang -</option><?php foreach ($barang as $opt): ?><option value="<?= e($opt['id']) ?>"><?= e($opt['kode_barang'] . ' - ' . $opt['nama_barang'] . ' (stok: ' . $opt['jumlah'] . ' ' . $opt['satuan'] . ')') ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="required">Jenis</label><select name="jenis" class="form-control" required><option value="masuk">Stok Masuk</option><option value="keluar">Stok Keluar</option><option value="penyesuaian">Penyesuaian Stok Baru</option></select></div>
                    <div class="mb-3"><label class="required">Jumlah</label><input type="number" min="0" name="jumlah" class="form-control" required><div class="form-text">Untuk penyesuaian, isi dengan stok akhir yang benar.</div></div>
                    <div class="mb-3"><label>Uraian</label><textarea name="uraian" class="form-control" rows="3" placeholder="Contoh: pembelian, barang rusak, koreksi stok"></textarea></div>
                    <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan Transaksi</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Riwayat Stok</h2><a class="btn btn-outline-success btn-sm" href="<?= e(url('export.php?type=stok')) ?>"><i class="bi bi-filetype-csv mr-1"></i>Export CSV</a></div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Tanggal</th><th>Barang</th><th>Jenis</th><th>Masuk</th><th>Keluar</th><th>Uraian</th><th>User</th></tr></thead>
                        <tbody>
                        <?php foreach ($logs as $row): ?>
                            <tr><td><?= e(tanggal($row['tanggal'])) ?></td><td><?= e($row['kode_barang'] . ' - ' . $row['nama_barang']) ?></td><td><?= status_badge($row['jenis']) ?></td><td><?= e($row['jumlah_masuk']) ?></td><td><?= e($row['jumlah_keluar']) ?></td><td><?= e($row['uraian']) ?></td><td><?= e($row['user_nama'] ?? '-') ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?><tr><td colspan="7" class="text-center text-muted">Belum ada transaksi stok.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
