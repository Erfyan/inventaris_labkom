<?php
$pageTitle = 'Peminjaman Barang';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();

$canManage = can_manage_data();
PeminjamanService::sinkronisasiStatusTerlambat();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    if (!$canManage) {
        set_flash('danger', 'Anda tidak memiliki hak untuk memproses peminjaman.');
        redirect('peminjaman.php');
    }

    $action = $_POST['action'] ?? '';
    try {
        $userId = (int) current_user()['id'];
        if ($action === 'create') {
            $kode = PeminjamanService::buatPeminjaman($_POST, $userId);
            set_flash('success', 'Peminjaman berhasil dibuat dengan kode ' . $kode . '.');
        } elseif ($action === 'return') {
            $id = (int) ($_POST['id'] ?? 0);
            $tanggalKembali = $_POST['tanggal_kembali'] ?? date('Y-m-d');
            $kondisiKembali = $_POST['kondisi_kembali'] ?? 'baik';
            $catatan = trim($_POST['catatan'] ?? '');

            PeminjamanService::kembalikanPeminjaman($id, $tanggalKembali, $kondisiKembali, $catatan, $userId);
            set_flash('success', 'Barang berhasil dikembalikan.');
        } elseif ($action === 'cancel') {
            require_role('admin');
            $id = (int) ($_POST['id'] ?? 0);
            PeminjamanService::batalkanPeminjaman($id, $userId);
            set_flash('success', 'Peminjaman dibatalkan dan stok dikembalikan.');
        }
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses peminjaman: ' . $e->getMessage());
    }
    redirect('peminjaman.php');
}

$action = $_GET['action'] ?? 'list';

if ($action === 'create' && $canManage) {
    $barang = db()->query("SELECT id, kode_barang, nama_barang, jumlah, satuan FROM barang WHERE status='aktif' AND kondisi <> 'hilang' ORDER BY nama_barang")->fetchAll();
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Buat Peminjaman Baru</h2></div>
        <div class="card-body px-4 pb-4">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="row g-3">
                    <div class="col-md-4"><label class="required">Nama Peminjam</label><input type="text" name="nama_peminjam" class="form-control" required></div>
                    <div class="col-md-4"><label>Kelas/Jurusan/Unit</label><input type="text" name="kelas_jurusan" class="form-control" placeholder="XI TKJ / Guru Produktif"></div>
                    <div class="col-md-4"><label>No. HP</label><input type="text" name="no_hp" class="form-control"></div>
                    <div class="col-md-3"><label class="required">Tanggal Pinjam</label><input type="date" name="tanggal_pinjam" class="form-control" required value="<?= e(date('Y-m-d')) ?>"></div>
                    <div class="col-md-3"><label class="required">Rencana Kembali</label><input type="date" name="tanggal_rencana_kembali" class="form-control" required value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"></div>
                    <div class="col-md-6"><label>Keperluan</label><input type="text" name="keperluan" class="form-control" placeholder="Praktikum, lomba, perbaikan, dll"></div>
                    <div class="col-12"><label>Catatan</label><textarea name="catatan" class="form-control" rows="2"></textarea></div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">Barang yang Dipinjam</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addLoanRow()"><i class="bi bi-plus-lg mr-1"></i>Tambah Baris</button>
                </div>
                <div id="loan-items">
                    <div class="row g-2 align-items-end loan-row mb-2">
                        <div class="col-md-8"><label>Barang</label><select name="barang_id[]" class="form-control"><option value="">- Pilih Barang -</option><?php foreach ($barang as $opt): ?><option value="<?= e($opt['id']) ?>"><?= e($opt['kode_barang'] . ' - ' . $opt['nama_barang'] . ' (stok: ' . $opt['jumlah'] . ' ' . $opt['satuan'] . ')') ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label>Jumlah</label><input type="number" min="1" name="jumlah[]" class="form-control" value="1"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeLoanRow(this)"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                </div>
                <template id="loan-row-template">
                    <div class="row g-2 align-items-end loan-row mb-2">
                        <div class="col-md-8"><select name="barang_id[]" class="form-control"><option value="">- Pilih Barang -</option><?php foreach ($barang as $opt): ?><option value="<?= e($opt['id']) ?>"><?= e($opt['kode_barang'] . ' - ' . $opt['nama_barang'] . ' (stok: ' . $opt['jumlah'] . ' ' . $opt['satuan'] . ')') ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><input type="number" min="1" name="jumlah[]" class="form-control" value="1"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="removeLoanRow(this)"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                </template>
                <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan Peminjaman</button><a href="peminjaman.php" class="btn btn-light border">Batal</a></div>
            </form>
        </div>
    </div>
    <?php require_once __DIR__ . '/includes/footer.php'; exit;
}

if ($action === 'detail' && isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT p.*, u.nama AS petugas FROM peminjaman p LEFT JOIN users u ON u.id=p.created_by WHERE p.id=?');
    $stmt->execute([(int) $_GET['id']]);
    $peminjaman = $stmt->fetch();
    if (!$peminjaman) {
        set_flash('danger', 'Peminjaman tidak ditemukan.');
        redirect('peminjaman.php');
    }
    $stmt = db()->prepare('SELECT d.*, b.kode_barang, b.nama_barang, b.satuan FROM peminjaman_detail d JOIN barang b ON b.id=d.barang_id WHERE d.peminjaman_id=?');
    $stmt->execute([$peminjaman['id']]);
    $details = $stmt->fetchAll();
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <h2 class="h5 mb-1">Detail Peminjaman <?= e($peminjaman['kode_pinjam']) ?></h2>
                    <div class="text-muted">Status: <?= status_badge($peminjaman['status']) ?></div>
                </div>
                <div class="no-print"><button class="btn btn-outline-secondary" onclick="printPage()"><i class="bi bi-printer mr-1"></i>Cetak</button><a href="peminjaman.php" class="btn btn-light border">Kembali</a></div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-4"><strong>Peminjam</strong><br><?= e($peminjaman['nama_peminjam']) ?></div>
                <div class="col-md-4"><strong>Kelas/Jurusan/Unit</strong><br><?= e($peminjaman['kelas_jurusan']) ?></div>
                <div class="col-md-4"><strong>No. HP</strong><br><?= e($peminjaman['no_hp']) ?></div>
                <div class="col-md-4"><strong>Tanggal Pinjam</strong><br><?= e(tanggal($peminjaman['tanggal_pinjam'])) ?></div>
                <div class="col-md-4"><strong>Rencana Kembali</strong><br><?= e(tanggal($peminjaman['tanggal_rencana_kembali'])) ?></div>
                <div class="col-md-4"><strong>Tanggal Kembali</strong><br><?= e(tanggal($peminjaman['tanggal_kembali'])) ?></div>
                <div class="col-md-8"><strong>Keperluan</strong><br><?= e($peminjaman['keperluan']) ?></div>
                <div class="col-md-4"><strong>Petugas</strong><br><?= e($peminjaman['petugas'] ?? '-') ?></div>
                <div class="col-12"><strong>Catatan</strong><br><pre class="mb-0" style="white-space:pre-wrap;font-family:inherit;"><?= e($peminjaman['catatan']) ?></pre></div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4"><h3 class="h5 mb-0">Daftar Barang</h3></div>
        <div class="card-body px-4">
            <div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Kode</th><th>Barang</th><th>Jumlah</th><th>Kondisi Pinjam</th><th>Kondisi Kembali</th></tr></thead><tbody><?php foreach ($details as $d): ?><tr><td><?= e($d['kode_barang']) ?></td><td><?= e($d['nama_barang']) ?></td><td><?= e($d['jumlah'] . ' ' . $d['satuan']) ?></td><td><?= status_badge($d['kondisi_pinjam']) ?></td><td><?= $d['kondisi_kembali'] ? status_badge($d['kondisi_kembali']) : '-' ?></td></tr><?php endforeach; ?></tbody></table></div>
        </div>
    </div>
    <?php if ($canManage && in_array($peminjaman['status'], ['dipinjam', 'terlambat'], true)): ?>
    <div class="card border-0 shadow-sm rounded-4 no-print">
        <div class="card-header bg-white border-0 pt-4 px-4"><h3 class="h5 mb-0">Proses Pengembalian</h3></div>
        <div class="card-body px-4 pb-4">
            <form method="post" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="return"><input type="hidden" name="id" value="<?= e($peminjaman['id']) ?>">
                <div class="col-md-3"><label class="required">Tanggal Kembali</label><input type="date" name="tanggal_kembali" class="form-control" required value="<?= e(date('Y-m-d')) ?>"></div>
                <div class="col-md-3"><label class="required">Kondisi Kembali</label><select name="kondisi_kembali" class="form-control"><option value="baik">Baik</option><option value="rusak_ringan">Rusak Ringan</option><option value="rusak_berat">Rusak Berat</option><option value="hilang">Hilang</option></select></div>
                <div class="col-md-6"><label>Catatan</label><input type="text" name="catatan" class="form-control"></div>
                <div class="col-12"><button class="btn btn-success"><i class="bi bi-check2-circle mr-1"></i>Selesaikan Pengembalian</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php require_once __DIR__ . '/includes/footer.php'; exit;
}

$where = ['1=1'];
$params = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $where[] = '(kode_pinjam LIKE ? OR nama_peminjam LIKE ? OR kelas_jurusan LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
}
$sql = 'SELECT * FROM peminjaman WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h2 class="h5 mb-0">Daftar Peminjaman</h2>
            <div><?php if ($canManage): ?><a href="peminjaman.php?action=create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg mr-1"></i>Buat Peminjaman</a><?php endif; ?> <a href="<?= e(url('export.php?type=peminjaman')) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv mr-1"></i>Export CSV</a></div>
        </div>
    </div>
    <div class="card-body px-4">
        <form method="get" class="row g-2 mb-3 no-print">
            <div class="col-md-6"><input type="text" name="q" class="form-control" placeholder="Cari kode/peminjam/kelas" value="<?= e($q) ?>"></div>
            <div class="col-md-3"><select name="status" class="form-control"><option value="">Semua Status</option><option value="dipinjam" <?= selected($_GET['status'] ?? '', 'dipinjam') ?>>Dipinjam</option><option value="terlambat" <?= selected($_GET['status'] ?? '', 'terlambat') ?>>Terlambat</option><option value="dikembalikan" <?= selected($_GET['status'] ?? '', 'dikembalikan') ?>>Dikembalikan</option><option value="dibatalkan" <?= selected($_GET['status'] ?? '', 'dibatalkan') ?>>Dibatalkan</option></select></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search mr-1"></i>Cari</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Kode</th><th>Peminjam</th><th>Tanggal</th><th>Rencana Kembali</th><th>Tanggal Kembali</th><th>Status</th><th>Keperluan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['kode_pinjam']) ?></td><td><?= e($row['nama_peminjam']) ?><br><small class="text-muted"><?= e($row['kelas_jurusan']) ?></small></td><td><?= e(tanggal($row['tanggal_pinjam'])) ?></td><td><?= e(tanggal($row['tanggal_rencana_kembali'])) ?></td><td><?= e(tanggal($row['tanggal_kembali'])) ?></td><td><?= status_badge($row['status']) ?></td><td><?= e($row['keperluan']) ?></td>
                        <td class="text-end"><a href="peminjaman.php?action=detail&id=<?= e($row['id']) ?>" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a><?php if (has_role('admin') && in_array($row['status'], ['dipinjam', 'terlambat'], true)): ?><form method="post" class="d-inline" onsubmit="return confirmDelete('Batalkan peminjaman ini dan kembalikan stok?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button></form><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted">Belum ada data peminjaman.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
