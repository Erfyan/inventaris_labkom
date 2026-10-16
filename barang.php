<?php
$pageTitle = 'Data Barang Inventaris';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();

$canManage = can_manage_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    if (!$canManage) {
        set_flash('danger', 'Anda tidak memiliki hak untuk mengubah data barang.');
        redirect('barang.php');
    }

    $action = $_POST['action'] ?? '';
    try {
        $userId = (int) current_user()['id'];
        if ($action === 'save') {
            $isEdit = !empty($_POST['id']);
            BarangService::simpan($_POST, $userId);
            set_flash('success', $isEdit ? 'Data barang berhasil diperbarui.' : 'Data barang berhasil ditambahkan.');
        } elseif ($action === 'delete') {
            require_role('admin');
            $id = (int) ($_POST['id'] ?? 0);
            BarangService::hapus($id);
            set_flash('success', 'Data barang berhasil dihapus.');
        }
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses data: ' . $e->getMessage());
    }
    redirect('barang.php');
}

$edit = null;
if (isset($_GET['edit']) && $canManage) {
    $stmt = db()->prepare('SELECT * FROM barang WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$kategori = get_options('kategori');
$ruangan = get_options('ruangan');
$supplier = get_options('supplier');

$where = ['1=1'];
$params = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $where[] = '(b.kode_barang LIKE ? OR b.nama_barang LIKE ? OR b.merk LIKE ? OR b.spesifikasi LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if (!empty($_GET['kategori_id'])) {
    $where[] = 'b.kategori_id = ?';
    $params[] = (int) $_GET['kategori_id'];
}
if (!empty($_GET['ruangan_id'])) {
    $where[] = 'b.ruangan_id = ?';
    $params[] = (int) $_GET['ruangan_id'];
}
if (!empty($_GET['kondisi'])) {
    $where[] = 'b.kondisi = ?';
    $params[] = $_GET['kondisi'];
}

$sql = 'SELECT b.*, k.nama AS kategori, r.nama AS ruangan, s.nama AS supplier
        FROM barang b
        LEFT JOIN kategori k ON k.id=b.kategori_id
        LEFT JOIN ruangan r ON r.id=b.ruangan_id
        LEFT JOIN supplier s ON s.id=b.supplier_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY b.nama_barang ASC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="card border-0 shadow-sm rounded-4 mb-4 <?= $canManage ? '' : 'd-none' ?>">
    <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0"><?= $edit ? 'Edit Barang' : 'Tambah Barang' ?></h2></div>
    <div class="card-body px-4 pb-4">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
            <div class="row g-3">
                <div class="col-md-3"><label>Kode Barang</label><input type="text" name="kode_barang" class="form-control" value="<?= e($edit['kode_barang'] ?? '') ?>" placeholder="Auto jika kosong"></div>
                <div class="col-md-5"><label class="required">Nama Barang</label><input type="text" name="nama_barang" class="form-control" required value="<?= e($edit['nama_barang'] ?? '') ?>"></div>
                <div class="col-md-2"><label>Merk</label><input type="text" name="merk" class="form-control" value="<?= e($edit['merk'] ?? '') ?>"></div>
                <div class="col-md-2"><label>Tahun</label><input type="number" name="tahun_pengadaan" class="form-control" value="<?= e($edit['tahun_pengadaan'] ?? date('Y')) ?>"></div>
                <div class="col-md-3"><label>Kategori</label><select name="kategori_id" class="form-control"><option value="">- Pilih -</option><?php foreach ($kategori as $opt): ?><option value="<?= e($opt['id']) ?>" <?= selected($edit['kategori_id'] ?? '', $opt['id']) ?>><?= e($opt['nama']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label>Ruangan/Lab</label><select name="ruangan_id" class="form-control"><option value="">- Pilih -</option><?php foreach ($ruangan as $opt): ?><option value="<?= e($opt['id']) ?>" <?= selected($edit['ruangan_id'] ?? '', $opt['id']) ?>><?= e($opt['nama']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label>Supplier</label><select name="supplier_id" class="form-control"><option value="">- Pilih -</option><?php foreach ($supplier as $opt): ?><option value="<?= e($opt['id']) ?>" <?= selected($edit['supplier_id'] ?? '', $opt['id']) ?>><?= e($opt['nama']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label>Sumber Dana</label><input type="text" name="sumber_dana" class="form-control" value="<?= e($edit['sumber_dana'] ?? '') ?>" placeholder="BOS/DAK/Komite"></div>
                <div class="col-md-2"><label class="required">Jumlah</label><input type="number" min="0" name="jumlah" class="form-control" required value="<?= e($edit['jumlah'] ?? 0) ?>"></div>
                <div class="col-md-2"><label>Satuan</label><input type="text" name="satuan" class="form-control" value="<?= e($edit['satuan'] ?? 'unit') ?>"></div>
                <div class="col-md-2"><label>Stok Minimum</label><input type="number" min="0" name="stok_minimum" class="form-control" value="<?= e($edit['stok_minimum'] ?? 1) ?>"></div>
                <div class="col-md-3"><label>Harga Satuan</label><input type="number" min="0" step="0.01" name="harga" class="form-control" value="<?= e($edit['harga'] ?? 0) ?>"></div>
                <div class="col-md-3"><label>Kondisi</label><select name="kondisi" class="form-control"><option value="baik" <?= selected($edit['kondisi'] ?? 'baik', 'baik') ?>>Baik</option><option value="rusak_ringan" <?= selected($edit['kondisi'] ?? '', 'rusak_ringan') ?>>Rusak Ringan</option><option value="rusak_berat" <?= selected($edit['kondisi'] ?? '', 'rusak_berat') ?>>Rusak Berat</option><option value="hilang" <?= selected($edit['kondisi'] ?? '', 'hilang') ?>>Hilang</option></select></div>
                <div class="col-md-2"><label>Status</label><select name="status" class="form-control"><option value="aktif" <?= selected($edit['status'] ?? 'aktif', 'aktif') ?>>Aktif</option><option value="nonaktif" <?= selected($edit['status'] ?? '', 'nonaktif') ?>>Nonaktif</option></select></div>
                <div class="col-md-5"><label>Spesifikasi</label><textarea name="spesifikasi" class="form-control" rows="2"><?= e($edit['spesifikasi'] ?? '') ?></textarea></div>
                <div class="col-md-5"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="2"><?= e($edit['keterangan'] ?? '') ?></textarea></div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan Barang</button>
                <?php if ($edit): ?><a href="barang.php" class="btn btn-light border">Batal</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h2 class="h5 mb-0">Daftar Barang</h2>
            <a href="<?= e(url('export.php?type=barang')) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv mr-1"></i>Export CSV</a>
        </div>
    </div>
    <div class="card-body px-4">
        <form method="get" class="row g-2 mb-3 no-print">
            <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Cari kode/nama/merk/spesifikasi" value="<?= e($q) ?>"></div>
            <div class="col-md-2"><select name="kategori_id" class="form-control"><option value="">Semua Kategori</option><?php foreach ($kategori as $opt): ?><option value="<?= e($opt['id']) ?>" <?= selected($_GET['kategori_id'] ?? '', $opt['id']) ?>><?= e($opt['nama']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><select name="ruangan_id" class="form-control"><option value="">Semua Ruangan</option><?php foreach ($ruangan as $opt): ?><option value="<?= e($opt['id']) ?>" <?= selected($_GET['ruangan_id'] ?? '', $opt['id']) ?>><?= e($opt['nama']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><select name="kondisi" class="form-control"><option value="">Semua Kondisi</option><option value="baik" <?= selected($_GET['kondisi'] ?? '', 'baik') ?>>Baik</option><option value="rusak_ringan" <?= selected($_GET['kondisi'] ?? '', 'rusak_ringan') ?>>Rusak Ringan</option><option value="rusak_berat" <?= selected($_GET['kondisi'] ?? '', 'rusak_berat') ?>>Rusak Berat</option><option value="hilang" <?= selected($_GET['kondisi'] ?? '', 'hilang') ?>>Hilang</option></select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search mr-1"></i>Cari</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Ruangan</th><th>Merk</th><th>Stok</th><th>Kondisi</th><th>Nilai</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['kode_barang']) ?></td>
                        <td><strong><?= e($row['nama_barang']) ?></strong><br><small class="text-muted"><?= e($row['spesifikasi']) ?></small></td>
                        <td><?= e($row['kategori'] ?? '-') ?></td>
                        <td><?= e($row['ruangan'] ?? '-') ?></td>
                        <td><?= e($row['merk']) ?></td>
                        <td><span class="badge <?= ((int)$row['jumlah'] <= (int)$row['stok_minimum']) ? 'badge-danger' : 'badge-primary' ?>"><?= e($row['jumlah'] . ' ' . $row['satuan']) ?></span></td>
                        <td><?= status_badge($row['kondisi']) ?></td>
                        <td><?= e(rupiah(((float) $row['harga']) * ((int) $row['jumlah']))) ?></td>
                        <td class="text-end">
                            <?php if ($canManage): ?>
                                <a href="barang.php?edit=<?= e($row['id']) ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                <?php if (has_role('admin')): ?>
                                <form method="post" class="d-inline" onsubmit="return confirmDelete('Hapus barang ini?')">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted">Data tidak ditemukan.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
