<?php
$pageTitle = 'Perawatan / Perbaikan';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();
$canManage = can_manage_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    if (!$canManage) {
        set_flash('danger', 'Anda tidak memiliki hak untuk memproses perawatan.');
        redirect('perawatan.php');
    }
    $action = $_POST['action'] ?? 'save';
    try {
        $userId = (int) current_user()['id'];
        if ($action === 'save') {
            PerawatanService::tambah($_POST, $userId);
            set_flash('success', 'Data perawatan berhasil disimpan.');
        } elseif ($action === 'status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? 'selesai';
            PerawatanService::updateStatus($id, $status, $userId);
            set_flash('success', 'Status perawatan berhasil diperbarui.');
        }
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses perawatan: ' . $e->getMessage());
    }
    redirect('perawatan.php');
}

$barang = db()->query("SELECT id, kode_barang, nama_barang FROM barang WHERE status='aktif' ORDER BY nama_barang")->fetchAll();
$rows = PerawatanService::ambilRiwayat(100);
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4 <?= $canManage ? '' : 'd-none' ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Input Perawatan</h2></div>
            <div class="card-body px-4 pb-4">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3"><label class="required">Barang</label><select name="barang_id" class="form-control" required><option value="">- Pilih Barang -</option><?php foreach ($barang as $opt): ?><option value="<?= e($opt['id']) ?>"><?= e($opt['kode_barang'] . ' - ' . $opt['nama_barang']) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="required">Tanggal Mulai</label><input type="date" name="tanggal_mulai" class="form-control" required value="<?= e(date('Y-m-d')) ?>"></div>
                    <div class="mb-3"><label>Tanggal Selesai</label><input type="date" name="tanggal_selesai" class="form-control"></div>
                    <div class="mb-3"><label class="required">Jenis</label><input type="text" name="jenis" class="form-control" required value="Perawatan rutin"></div>
                    <div class="mb-3"><label>Teknisi/Pihak Ketiga</label><input type="text" name="teknisi" class="form-control"></div>
                    <div class="mb-3"><label>Biaya</label><input type="number" min="0" step="0.01" name="biaya" class="form-control" value="0"></div>
                    <div class="mb-3"><label>Status</label><select name="status" class="form-control"><option value="proses">Proses</option><option value="selesai">Selesai</option><option value="batal">Batal</option></select></div>
                    <div class="mb-3"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
                    <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan Perawatan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Riwayat Perawatan</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Barang</th><th>Mulai</th><th>Selesai</th><th>Jenis</th><th>Biaya</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['kode_barang'] . ' - ' . $row['nama_barang']) ?><br><small class="text-muted"><?= e($row['deskripsi']) ?></small></td><td><?= e(tanggal($row['tanggal_mulai'])) ?></td><td><?= e(tanggal($row['tanggal_selesai'])) ?></td><td><?= e($row['jenis']) ?><br><small class="text-muted"><?= e($row['teknisi']) ?></small></td><td><?= e(rupiah($row['biaya'])) ?></td><td><?= status_badge($row['status']) ?></td><td class="text-end"><?php if ($canManage && $row['status'] === 'proses'): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><input type="hidden" name="status" value="selesai"><button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i></button></form><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><input type="hidden" name="status" value="batal"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></button></form><?php else: ?><span class="text-muted">-</span><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted">Belum ada data perawatan.</td></tr><?php endif; ?></tbody></table></div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
