<?php
$pageTitle = 'Mutasi Ruangan Barang';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();
$canManage = can_manage_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    if (!$canManage) {
        set_flash('danger', 'Anda tidak memiliki hak untuk memproses mutasi.');
        redirect('mutasi.php');
    }
    try {
        $barangId = (int) ($_POST['barang_id'] ?? 0);
        $tujuanId = (int) ($_POST['ruangan_tujuan_id'] ?? 0);
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $userId = (int) current_user()['id'];

        MutasiService::prosesMutasi($barangId, $tujuanId, $tanggal, $keterangan, $userId);
        set_flash('success', 'Mutasi ruangan berhasil disimpan.');
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses mutasi: ' . $e->getMessage());
    }
    redirect('mutasi.php');
}

$barang = db()->query("SELECT b.id, b.kode_barang, b.nama_barang, r.nama AS ruangan FROM barang b LEFT JOIN ruangan r ON r.id=b.ruangan_id WHERE b.status='aktif' ORDER BY b.nama_barang")->fetchAll();
$ruangan = get_options('ruangan');
$rows = MutasiService::ambilRiwayat(100);
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4 <?= $canManage ? '' : 'd-none' ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Input Mutasi</h2></div>
            <div class="card-body px-4 pb-4">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="required">Tanggal</label><input type="date" name="tanggal" class="form-control" required value="<?= e(date('Y-m-d')) ?>"></div>
                    <div class="mb-3"><label class="required">Barang</label><select name="barang_id" class="form-control" required><option value="">- Pilih Barang -</option><?php foreach ($barang as $opt): ?><option value="<?= e($opt['id']) ?>"><?= e($opt['kode_barang'] . ' - ' . $opt['nama_barang'] . ' (' . ($opt['ruangan'] ?? '-') . ')') ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="required">Ruangan Tujuan</label><select name="ruangan_tujuan_id" class="form-control" required><option value="">- Pilih Ruangan -</option><?php foreach ($ruangan as $opt): ?><option value="<?= e($opt['id']) ?>"><?= e($opt['nama']) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="3"></textarea></div>
                    <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan Mutasi</button>
                </form>
            </div>
        </div>
    </div>
    <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Riwayat Mutasi</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Tanggal</th><th>Barang</th><th>Dari</th><th>Ke</th><th>Keterangan</th><th>User</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e(tanggal($row['tanggal'])) ?></td><td><?= e($row['kode_barang'] . ' - ' . $row['nama_barang']) ?></td><td><?= e($row['ruangan_asal'] ?? '-') ?></td><td><?= e($row['ruangan_tujuan'] ?? '-') ?></td><td><?= e($row['keterangan']) ?></td><td><?= e($row['user_nama'] ?? '-') ?></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">Belum ada mutasi.</td></tr><?php endif; ?></tbody></table></div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
